<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// 1. RECEBE OS DADOS DO FORMULÁRIO (POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$loja_id = filter_input(INPUT_POST, 'loja_id', FILTER_VALIDATE_INT);
$servico_id = filter_input(INPUT_POST, 'servico_id', FILTER_VALIDATE_INT);
$data = filter_input(INPUT_POST, 'data'); // YYYY-MM-DD
$hora = filter_input(INPUT_POST, 'hora'); // HH:mm
$cliente_nome = filter_input(INPUT_POST, 'cliente_nome', FILTER_SANITIZE_SPECIAL_CHARS);
$cliente_zap = filter_input(INPUT_POST, 'cliente_zap', FILTER_SANITIZE_SPECIAL_CHARS);

// Validação básica
if (!$loja_id || !$servico_id || !$data || !$hora || !$cliente_nome) {
    die("Dados inválidos. Tente novamente.");
}

try {
    // 2. BUSCA A DURAÇÃO DO SERVIÇO (Para calcular a hora que acaba)
    $stmtServico = $pdo->prepare("SELECT duracao_minutos, nome, preco FROM servicos WHERE id = ?");
    $stmtServico->execute([$servico_id]);
    $servico = $stmtServico->fetch();

    // 3. CÁLCULO DE DATAS
    $inicio = DateTime::createFromFormat('Y-m-d H:i', "$data $hora");
    $fim = clone $inicio;
    $fim->modify("+{$servico['duracao_minutos']} minutes");

    // 4. SALVA NO BANCO (Aqui é onde o estabelecimento recebe o dado!)
    $sql = "INSERT INTO agendamentos 
            (estabelecimento_id, servico_id, data_hora_inicio, data_hora_fim, cliente_nome, cliente_telefone, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'agendado')";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $loja_id, 
        $servico_id, 
        $inicio->format('Y-m-d H:i:s'), 
        $fim->format('Y-m-d H:i:s'),
        $cliente_nome,
        $cliente_zap
    ]);

} catch (PDOException $e) {
    die("Erro ao salvar: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendamento Confirmado!</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gold: '#F59E0B',
                        dark: { bg: '#0a0a0a', surface: '#121212', border: '#2A2A2A' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 dark:bg-dark-bg text-gray-900 dark:text-white font-sans h-screen flex items-center justify-center p-4">

    <div class="bg-white dark:bg-dark-surface w-full max-w-md p-8 rounded-3xl shadow-2xl border border-gray-200 dark:border-dark-border text-center relative overflow-hidden">
        
        <div class="absolute top-0 left-0 w-full h-2 bg-green-500"></div>

        <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <h1 class="text-2xl font-bold mb-2">Agendamento Confirmado!</h1>
        <p class="text-gray-500 dark:text-gray-400 mb-8">Seu horário foi reservado com sucesso.</p>

        <div class="bg-gray-50 dark:bg-black/20 rounded-xl p-5 mb-8 text-left space-y-3 border border-gray-100 dark:border-dark-border">
            <div class="flex justify-between">
                <span class="text-gray-500 text-sm">Serviço</span>
                <span class="font-bold"><?= e($servico['nome']) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 text-sm">Data & Hora</span>
                <span class="font-bold text-gold">
                    <?= date('d/m', strtotime($data)) ?> às <?= $hora ?>
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 text-sm">Valor</span>
                <span class="font-bold"><?= formatarPreco($servico['preco']) ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 text-sm">Cliente</span>
                <span class="font-bold"><?= e($cliente_nome) ?></span>
            </div>
        </div>

        <div class="space-y-3">
            <button onclick="window.print()" class="w-full bg-gray-100 dark:bg-dark-border hover:bg-gray-200 text-gray-700 dark:text-gray-300 font-bold py-3 rounded-xl transition">
                🖨 Imprimir Comprovante
            </button>
            
            <a href="index.php" class="block w-full bg-gold hover:bg-yellow-400 text-black font-bold py-3 rounded-xl transition shadow-lg shadow-gold/20">
                Voltar para o Início
            </a>
        </div>

    </div>

    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>