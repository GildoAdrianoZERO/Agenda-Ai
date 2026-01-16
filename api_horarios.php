<?php
// api_horarios.php
header('Content-Type: application/json');
require_once 'config/database.php';

// Recebe os dados do front-end
$loja_id = filter_input(INPUT_GET, 'loja_id', FILTER_VALIDATE_INT);
$data = filter_input(INPUT_GET, 'data');
$prof_selecionado = filter_input(INPUT_GET, 'profissional_id', FILTER_VALIDATE_INT); // Pode vir vazio (Tanto faz)

if (!$loja_id || !$data) { 
    echo json_encode([]); 
    exit; 
}

try {
    // 1. Configurações da Loja (Horário Global)
    $stmt = $pdo->prepare("SELECT horario_abertura, horario_fechamento FROM estabelecimentos WHERE id = ?");
    $stmt->execute([$loja_id]);
    $loja = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$loja) { echo json_encode([]); exit; }

    // 2. Busca Todos os Profissionais Ativos (Para calcular a capacidade da loja)
    // Se um profissional foi demitido (ativo=0), ele não conta na vaga.
    $stmtP = $pdo->prepare("SELECT id, inicio_intervalo, fim_intervalo FROM profissionais WHERE estabelecimento_id = ? AND ativo = 1");
    $stmtP->execute([$loja_id]);
    $profissionais = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    if(empty($profissionais)) { 
        echo json_encode([]); // Se não tem equipe, não tem horário
        exit; 
    }

    // 3. Busca TODOS os Agendamentos do dia (Confirmados/Agendados/Concluídos)
    // Ignoramos os cancelados para liberar a vaga
    $stmtA = $pdo->prepare("SELECT profissional_id, DATE_FORMAT(data_hora_inicio, '%H:%i') as hora 
                            FROM agendamentos 
                            WHERE estabelecimento_id = ? 
                            AND DATE(data_hora_inicio) = ? 
                            AND status NOT IN ('cancelado_cliente', 'cancelado_loja')");
    $stmtA->execute([$loja_id, $data]);
    $agendamentos = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    // Organiza agendamentos na memória para ficar rápido: 
    // Ex: [ ID_JOAO => ['09:00', '10:00'], ID_MARIA => ['14:00'] ]
    $agenda_map = [];
    foreach($agendamentos as $ag) {
        // Se o agendamento não tiver profissional (sistema antigo), assumimos que ocupa uma vaga qualquer
        // Mas com o novo sistema, todos terão ID.
        $pid = $ag['profissional_id'] ?? 'sem_prof';
        $agenda_map[$pid][] = $ag['hora'];
    }

    // 4. A Mágica: Loop hora a hora para ver quem está livre
    $horarios_bloqueados = [];

    // Cria os slots de horários baseado na abertura/fechamento da loja
    $inicio = new DateTime($data . ' ' . $loja['horario_abertura']);
    $fim = new DateTime($data . ' ' . $loja['horario_fechamento']);
    $intervalo = new DateInterval('PT1H'); // Intervalo de 1 hora (Padrão)
    
    // Se quiser intervalos de 30min, mude para 'PT30M' acima.
    
    $periodo = new DatePeriod($inicio, $intervalo, $fim);

    foreach ($periodo as $hora_obj) {
        $hora_str = $hora_obj->format('H:i');
        
        $total_profissionais = count($profissionais);
        $contador_ocupados = 0;
        $meu_prof_esta_ocupado = false; // Flag para quando escolhe um específico

        foreach($profissionais as $prof) {
            $p_id = $prof['id'];
            $esta_ocupado = false;

            // A. Verifica se tem Agendamento
            if (isset($agenda_map[$p_id]) && in_array($hora_str, $agenda_map[$p_id])) {
                $esta_ocupado = true;
            }

            // B. Verifica se é horário de Almoço DELE
            if ($prof['inicio_intervalo'] && $prof['fim_intervalo']) {
                $almoco_ini = substr($prof['inicio_intervalo'], 0, 5);
                $almoco_fim = substr($prof['fim_intervalo'], 0, 5);
                
                // Se a hora atual está dentro do almoço dele
                if ($hora_str >= $almoco_ini && $hora_str < $almoco_fim) {
                    $esta_ocupado = true;
                }
            }

            if ($esta_ocupado) {
                $contador_ocupados++;
                if ($prof_selecionado == $p_id) {
                    $meu_prof_esta_ocupado = true;
                }
            }
        }

        // DECISÃO FINAL: Bloquear ou Liberar o horário?
        
        if ($prof_selecionado) {
            // CENÁRIO 1: Cliente escolheu um Barbeiro específico
            // Só bloqueia se ESSE barbeiro estiver ocupado (agendamento ou almoço)
            if ($meu_prof_esta_ocupado) {
                $horarios_bloqueados[] = $hora_str;
            }
        } else {
            // CENÁRIO 2: "Tanto faz" (Qualquer profissional)
            // Só bloqueia se TODOS os profissionais estiverem ocupados
            if ($contador_ocupados >= $total_profissionais) {
                $horarios_bloqueados[] = $hora_str;
            }
        }
    }

    echo json_encode($horarios_bloqueados);

} catch (PDOException $e) {
    echo json_encode([]);
}
?>