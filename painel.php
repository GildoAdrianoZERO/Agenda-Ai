<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$loja_id = $_SESSION['loja_id'];

// --- 1. LÓGICA DE DATA ÚNICA ---
$filtro_data = filter_input(INPUT_GET, 'data');
if (empty($filtro_data) || !strtotime($filtro_data)) {
    $filtro_data = date('Y-m-d');
}
$display_data = date('d/m/Y', strtotime($filtro_data));

// Texto dia da semana
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
$texto_semana = ucfirst(strftime('%A', strtotime($filtro_data)));
if($filtro_data == date('Y-m-d')) $texto_semana = "Hoje";

// --- NOVO: BUSCA PROFISSIONAIS (Para o Modal) ---
$sqlProf = "SELECT id, nome, foto FROM profissionais WHERE estabelecimento_id = ?"; 
$stmtProf = $pdo->prepare($sqlProf);
$stmtProf->execute([$loja_id]);
$profissionais = $stmtProf->fetchAll();

// --- 2. BUSCA NO BANCO (ATUALIZADA COM JOIN PROFISSIONAIS) ---
$sql = "SELECT a.*, s.nome as servico_nome, s.preco,
               p.nome as prof_nome, p.foto as prof_foto
        FROM agendamentos a
        JOIN servicos s ON a.servico_id = s.id
        LEFT JOIN profissionais p ON a.profissional_id = p.id
        WHERE a.estabelecimento_id = ? 
        AND DATE(a.data_hora_inicio) = ?
        ORDER BY a.data_hora_inicio ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$loja_id, $filtro_data]);
$agenda = $stmt->fetchAll();

// Estatísticas
$faturamento = 0;
$confirmados = 0;
$cancelados = 0;
foreach($agenda as $item) {
    if(strpos($item['status'], 'cancelado') !== false) $cancelados++;
    else {
        $faturamento += $item['preco'];
        if($item['status'] == 'confirmado') $confirmados++;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - AgendaAí</title>
    
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gold: '#F59E0B',
                        dark: { bg: '#0f0f0f', surface: '#18181b', border: '#27272a' },
                        light: { bg: '#f4f4f5', surface: '#ffffff', border: '#e4e4e7' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/pt.js"></script>

    <style>
        .flatpickr-calendar { position: fixed !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; z-index: 99999 !important; border-radius: 16px !important; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important; }
        .dark .flatpickr-calendar { background: #18181b !important; border: 1px solid #27272a !important; }
        .flatpickr-day.selected { background: #F59E0B !important; border-color: #F59E0B !important; color: #000 !important; font-weight: bold; }
        #calendar-backdrop { transition: opacity 0.3s ease; }
        
        /* Modal Animation */
        .modal-enter { opacity: 0; transform: scale(0.95); }
        .modal-enter-active { opacity: 1; transform: scale(1); transition: all 0.2s ease-out; }
        
        /* Hide scrollbar for horiz scrolling */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-light-bg text-gray-900 dark:bg-dark-bg dark:text-white font-sans min-h-screen transition-colors duration-300">

    <div id="calendar-backdrop" class="fixed inset-0 bg-black/60 z-[9000] hidden backdrop-blur-sm"></div>

    <nav class="bg-white dark:bg-dark-surface border-b border-light-border dark:border-dark-border px-4 md:px-8 py-4 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-center gap-4">
            
            <div class="flex items-center justify-between w-full md:w-auto">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gold/10 text-gold rounded-xl flex items-center justify-center text-xl">✂️</div>
                    <div>
                        <h1 class="font-bold text-lg leading-tight">AgendaAí</h1>
                        <p class="text-xs text-gray-500">Painel Profissional</p>
                    </div>
                </div>
                </div>
            
            <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
                
                <div class="bg-gray-100 dark:bg-dark-bg p-1.5 rounded-xl border border-gray-200 dark:border-dark-border flex items-center gap-2 w-full md:w-auto justify-center" id="date-trigger">
                    <div class="flex items-center gap-3 bg-white dark:bg-dark-surface px-4 py-2 rounded-lg shadow-sm cursor-pointer hover:border-gold border border-transparent transition group w-full md:w-auto justify-center">
                        <div class="text-left">
                            <span class="text-[10px] text-gray-400 font-bold uppercase block leading-none mb-1">Data Selecionada</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-gold transition"><?= $display_data ?></span>
                        </div>
                        <div class="w-px h-8 bg-gray-200 dark:bg-dark-border mx-1"></div>
                        <span class="text-sm font-medium text-gray-500 uppercase"><?= $texto_semana ?></span>
                    </div>
                    <input type="text" id="calendar-input" value="<?= $filtro_data ?>" class="hidden">
                </div>

                <div class="flex items-center justify-center gap-2 w-full md:w-auto mt-2 md:mt-0">
                    <a href="relatorios.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 hover:bg-green-100 transition" title="Relatórios Financeiros">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </a>

                    <a href="configuracoes.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-dark-border hover:text-gold transition text-gray-500" title="Configurações">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </a>

                    <button id="theme-toggle" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-dark-border hover:text-gold transition text-gray-500">
                        <span id="theme-icon">☀️</span>
                    </button>
                    
                    <a href="logout.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-100 transition" title="Sair">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-4 md:p-8 pb-24">
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="col-span-2 md:col-span-1 bg-white dark:bg-dark-surface p-5 rounded-2xl border border-light-border dark:border-dark-border shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase">Faturamento</p>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?= formatarPreco($faturamento) ?></h2>
            </div>
            <div class="col-span-2 md:col-span-1 bg-white dark:bg-dark-surface p-5 rounded-2xl border border-light-border dark:border-dark-border shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase">Agendamentos</p>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-1"><?= count($agenda) ?></h2>
            </div>
            <div class="bg-green-50 dark:bg-green-900/10 p-5 rounded-2xl border border-green-100 dark:border-green-900/20">
                <p class="text-xs font-bold text-green-600 dark:text-green-400 uppercase">Confirmados</p>
                <h2 class="text-2xl font-bold text-green-700 dark:text-green-300 mt-1"><?= $confirmados ?></h2>
            </div>
            <div class="bg-red-50 dark:bg-red-900/10 p-5 rounded-2xl border border-red-100 dark:border-red-900/20">
                <p class="text-xs font-bold text-red-600 dark:text-red-400 uppercase">Cancelados</p>
                <h2 class="text-2xl font-bold text-red-700 dark:text-red-300 mt-1"><?= $cancelados ?></h2>
            </div>
        </div>

        <?php if(count($agenda) == 0): ?>
            <div class="flex flex-col items-center justify-center py-20 bg-white dark:bg-dark-surface rounded-2xl border border-dashed border-gray-300 dark:border-dark-border">
                <div class="text-4xl grayscale opacity-30 mb-4">📅</div>
                <h3 class="font-bold text-gray-900 dark:text-white">Nenhum agendamento</h3>
                <p class="text-sm text-gray-500">Sua agenda está livre para esta data.</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach($agenda as $item): 
                    $hora = date('H:i', strtotime($item['data_hora_inicio']));
                    $data_br = date('d/m/Y', strtotime($item['data_hora_inicio']));
                    $isCancelado = strpos($item['status'], 'cancelado') !== false;
                    $isConfirmado = $item['status'] == 'confirmado';

                    $cardClass = "bg-white dark:bg-dark-surface p-4 rounded-xl border border-light-border dark:border-dark-border shadow-sm flex flex-col md:flex-row items-center gap-4 hover:border-gold/50 transition-all";
                    if($isConfirmado) $cardClass .= " border-l-4 border-l-green-500";
                    if($isCancelado) $cardClass .= " border-l-4 border-l-red-500 opacity-60 bg-gray-50 dark:bg-[#121212]";
                ?>
                <div class="<?= $cardClass ?>">
                    <div class="w-full md:w-auto flex justify-between md:block">
                        <div class="bg-gray-100 dark:bg-dark-bg px-4 py-2 rounded-lg font-bold text-xl text-gray-900 dark:text-white border border-gray-200 dark:border-dark-border min-w-[80px] text-center">
                            <?= $hora ?>
                        </div>
                        <span class="md:hidden text-xs font-bold uppercase py-1 px-2 rounded bg-gray-100 dark:bg-dark-bg"><?= $item['status'] ?></span>
                    </div>

                    <div class="flex-1 w-full text-center md:text-left">
                        
                        <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white"><?= e($item['cliente_nome']) ?></h3>
                            
                            <?php if(!empty($item['profissional_id'])): ?>
                                <button onclick="abrirModalProfissional(<?= $item['id'] ?>)" class="flex items-center gap-2 pl-1 pr-3 py-0.5 rounded-full bg-gold/10 hover:bg-gold/20 text-gold border border-gold/20 transition group">
                                    <?php if($item['prof_foto']): ?>
                                        <img src="<?= $item['prof_foto'] ?>" class="w-6 h-6 rounded-full object-cover">
                                    <?php else: ?>
                                        <div class="w-6 h-6 rounded-full bg-gold text-white text-[10px] flex items-center justify-center font-bold">
                                            <?= strtoupper(substr($item['prof_nome'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="text-xs font-bold truncate max-w-[100px]"><?= e($item['prof_nome']) ?></span>
                                </button>
                            <?php else: ?>
                                <button onclick="abrirModalProfissional(<?= $item['id'] ?>)" class="flex items-center gap-1 px-2 py-1 rounded-full border border-dashed border-gray-300 dark:border-gray-600 text-gray-400 hover:text-gold hover:border-gold transition text-xs font-bold">
                                    👤 <span class="hidden sm:inline">Escolher</span>
                                </button>
                            <?php endif; ?>
                            </div>

                        <div class="flex flex-wrap justify-center md:justify-start gap-4 text-sm text-gray-500 mt-1">
                            <span>✂️ <?= e($item['servico_nome']) ?></span>
                            <span>📱 <?= e($item['cliente_telefone']) ?></span>
                            <span class="text-gold font-semibold">R$ <?= number_format($item['preco'], 2, ',', '.') ?></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full md:w-auto justify-center">
                        <?php if(!$isCancelado): ?>
                            <?php if(!$isConfirmado): ?>
                                <button onclick="gerenciarAgendamento(<?= $item['id'] ?>, 'confirmar', '<?= $item['cliente_telefone'] ?>', '<?= $item['cliente_nome'] ?>', '<?= $data_br ?>', '<?= $hora ?>')" 
                                        class="p-2 rounded-lg bg-green-50 hover:bg-green-100 text-green-600 dark:bg-green-900/20 dark:hover:bg-green-900/30 dark:text-green-400 transition" title="Confirmar">
                                    ✅
                                </button>
                            <?php endif; ?>

                            <button onclick="abrirModalReagendamento(<?= $item['id'] ?>, '<?= $item['cliente_nome'] ?>')" 
                                    class="p-2 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 dark:text-blue-400 transition flex items-center gap-1 font-bold text-xs" title="Reagendar">
                                📅 Reagendar
                            </button>

                            <button onclick="gerenciarAgendamento(<?= $item['id'] ?>, 'cancelar', '<?= $item['cliente_telefone'] ?>', '<?= $item['cliente_nome'] ?>', '<?= $data_br ?>', '<?= $hora ?>')" 
                                    class="p-2 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-900/20 dark:hover:bg-red-900/30 dark:text-red-400 transition" title="Cancelar">
                                ❌
                            </button>
                        <?php else: ?>
                            <span class="text-red-500 text-xs font-bold uppercase">Cancelado</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="modal-reagendar" class="fixed inset-0 z-[10000] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="fecharModal()"></div>
        <div class="bg-white dark:bg-[#151515] w-full max-w-lg rounded-2xl shadow-2xl relative z-10 overflow-hidden flex flex-col max-h-[90vh]">
            <div class="p-6 border-b border-light-border dark:border-dark-border flex justify-between items-center bg-gray-50 dark:bg-[#1A1A1A]">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Reagendar Horário</h3>
                    <p class="text-sm text-gray-500">Cliente: <span id="modal-cliente-nome" class="font-bold text-gold">...</span></p>
                </div>
                <button onclick="fecharModal()" class="text-gray-400 hover:text-red-500 text-2xl">×</button>
            </div>
            <div class="p-6 overflow-y-auto">
                <input type="hidden" id="reagendar-id">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">1. Escolha a Nova Data</label>
                <div class="flex gap-2 overflow-x-auto pb-4 no-scrollbar" id="lista-dias"></div>
                <label class="block text-xs font-bold text-gray-500 uppercase mt-4 mb-2">2. Escolha o Novo Horário</label>
                <div id="grid-horarios" class="grid grid-cols-4 gap-2">
                    <div class="col-span-4 text-center py-6 text-gray-500 text-sm border border-dashed border-gray-300 dark:border-dark-border rounded-lg">Selecione um dia acima 👆</div>
                </div>
            </div>
            <div class="p-4 border-t border-light-border dark:border-dark-border bg-gray-50 dark:bg-[#1A1A1A]">
                <button id="btn-confirmar-reagendamento" onclick="confirmarReagendamento()" disabled class="w-full bg-gray-300 dark:bg-dark-border text-gray-500 font-bold py-3 rounded-xl cursor-not-allowed transition">
                    Selecione um horário
                </button>
            </div>
        </div>
    </div>

    <div id="modal-profissional" class="fixed inset-0 z-[10000] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="fecharModalProfissional()"></div>
        <div class="bg-white dark:bg-[#151515] w-full max-w-md rounded-2xl shadow-2xl relative z-10 overflow-hidden flex flex-col">
            <div class="p-5 border-b border-light-border dark:border-dark-border flex justify-between items-center bg-gray-50 dark:bg-[#1A1A1A]">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Escolher Profissional</h3>
                <button onclick="fecharModalProfissional()" class="text-gray-400 hover:text-red-500 text-2xl">×</button>
            </div>
            
            <div class="p-5 overflow-y-auto max-h-[60vh]">
                <input type="hidden" id="atribuir-agendamento-id">
                <div class="grid grid-cols-1 gap-3">
                    <?php foreach($profissionais as $prof): ?>
                        <button onclick="salvarAtribuicao(<?= $prof['id'] ?>)" class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-dark-border hover:border-gold bg-gray-50 dark:bg-black/20 hover:bg-white dark:hover:bg-dark-surface transition group text-left">
                            <?php if($prof['foto']): ?>
                                <img src="<?= $prof['foto'] ?>" class="w-10 h-10 rounded-full object-cover border border-gray-300 dark:border-dark-border group-hover:border-gold">
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-dark-border text-gray-500 flex items-center justify-center font-bold text-lg group-hover:bg-gold group-hover:text-white transition">
                                    <?= strtoupper(substr($prof['nome'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white"><?= e($prof['nome']) ?></h4>
                                <span class="text-xs text-gray-500 group-hover:text-gold">Clique para atribuir</span>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const LOJA_ID = <?= $loja_id ?>;
        
        // --- 1. FUNÇÕES DO MODAL REAGENDAMENTO (MANTIDAS) ---
        let dataSelecionada = '';
        let horaSelecionada = '';

        function abrirModalReagendamento(id, nomeCliente) {
            document.getElementById('reagendar-id').value = id;
            document.getElementById('modal-cliente-nome').innerText = nomeCliente;
            document.getElementById('modal-reagendar').classList.remove('hidden');
            gerarDias(); 
        }

        function fecharModal() {
            document.getElementById('modal-reagendar').classList.add('hidden');
            dataSelecionada = ''; horaSelecionada = '';
            document.getElementById('grid-horarios').innerHTML = '<div class="col-span-4 text-center py-6 text-gray-500 text-sm border border-dashed border-gray-300 dark:border-dark-border rounded-lg">Selecione um dia acima 👆</div>';
            atualizarBotaoModal();
        }

        function gerarDias() {
            const container = document.getElementById('lista-dias');
            container.innerHTML = '';
            const diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            const hoje = new Date();

            for(let i=0; i<14; i++) {
                const d = new Date(hoje);
                d.setDate(hoje.getDate() + i);
                if(d.getDay() === 0) continue; 

                const diaF = d.toISOString().split('T')[0];
                const btn = document.createElement('button');
                btn.className = "flex-shrink-0 w-14 h-16 rounded-xl border border-gray-200 dark:border-dark-border bg-white dark:bg-black/20 flex flex-col items-center justify-center hover:border-gold transition";
                btn.innerHTML = `<span class="text-[10px] text-gray-500 uppercase">${diasSemana[d.getDay()]}</span><span class="text-lg font-bold text-gray-900 dark:text-white">${d.getDate()}</span>`;
                
                btn.onclick = () => {
                    Array.from(container.children).forEach(c => {
                        c.classList.remove('bg-gold', 'border-gold');
                        c.querySelectorAll('span')[1].classList.remove('text-black');
                        c.querySelectorAll('span')[1].classList.add('text-gray-900', 'dark:text-white');
                    });
                    btn.classList.add('bg-gold', 'border-gold');
                    btn.querySelectorAll('span')[1].classList.remove('text-gray-900', 'dark:text-white');
                    btn.querySelectorAll('span')[1].classList.add('text-black');
                    
                    dataSelecionada = diaF;
                    carregarHorarios(diaF);
                };
                container.appendChild(btn);
            }
        }

        async function carregarHorarios(data) {
            const grid = document.getElementById('grid-horarios');
            grid.innerHTML = '<div class="col-span-4 text-center text-gray-400 py-4">Carregando...</div>';
            let ocupados = [];
            try {
                const res = await fetch(`api_horarios.php?loja_id=${LOJA_ID}&data=${data}`);
                ocupados = await res.json();
            } catch(e) {}

            const possiveis = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
            grid.innerHTML = '';

            possiveis.forEach(h => {
                const btn = document.createElement('button');
                if(ocupados.includes(h)) {
                    btn.className = "py-2 rounded-lg bg-gray-100 dark:bg-black/40 text-gray-300 dark:text-gray-600 line-through cursor-not-allowed border border-transparent";
                    btn.disabled = true;
                } else {
                    btn.className = "py-2 rounded-lg bg-white dark:bg-black/20 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-dark-border hover:border-gold hover:text-gold transition";
                    btn.onclick = () => {
                        Array.from(grid.children).forEach(c => {
                            if(!c.disabled) {
                                c.classList.remove('bg-gold', 'text-black', 'border-gold');
                                c.classList.add('bg-white', 'dark:bg-black/20', 'text-gray-700', 'dark:text-gray-300');
                            }
                        });
                        btn.classList.remove('bg-white', 'dark:bg-black/20', 'text-gray-700', 'dark:text-gray-300');
                        btn.classList.add('bg-gold', 'text-black', 'border-gold');
                        horaSelecionada = h;
                        atualizarBotaoModal();
                    }
                }
                btn.innerText = h;
                grid.appendChild(btn);
            });
        }

        function atualizarBotaoModal() {
            const btn = document.getElementById('btn-confirmar-reagendamento');
            if(dataSelecionada && horaSelecionada) {
                btn.disabled = false;
                btn.classList.remove('bg-gray-300', 'dark:bg-dark-border', 'text-gray-500', 'cursor-not-allowed');
                btn.classList.add('bg-green-600', 'hover:bg-green-500', 'text-white', 'cursor-pointer');
                btn.innerText = `Confirmar para ${dataSelecionada.split('-').reverse().join('/')} às ${horaSelecionada}`;
            } else {
                btn.disabled = true;
                btn.classList.add('bg-gray-300', 'dark:bg-dark-border', 'text-gray-500', 'cursor-not-allowed');
                btn.classList.remove('bg-green-600', 'hover:bg-green-500', 'text-white', 'cursor-pointer');
                btn.innerText = 'Selecione um horário';
            }
        }

        async function confirmarReagendamento() {
            const id = document.getElementById('reagendar-id').value;
            const nome = document.getElementById('modal-cliente-nome').innerText;
            try {
                const res = await fetch('api_reagendar.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id, data: dataSelecionada, hora: horaSelecionada })
                });
                const json = await res.json();
                if(json.success) {
                    const msg = `Olá ${nome}. Reagendamos seu horário para dia ${dataSelecionada.split('-').reverse().join('/')} às ${horaSelecionada}. Te aguardamos! 📅`;
                    window.open(`https://wa.me/?text=${encodeURIComponent(msg)}`, '_blank');
                    location.reload();
                } else {
                    alert('Erro ao reagendar');
                }
            } catch(e) { console.error(e); }
        }

        // --- 2. NOVO: FUNÇÕES DE ATRIBUIÇÃO DE PROFISSIONAL ---
        function abrirModalProfissional(agendamentoId) {
            document.getElementById('atribuir-agendamento-id').value = agendamentoId;
            document.getElementById('modal-profissional').classList.remove('hidden');
        }

        function fecharModalProfissional() {
            document.getElementById('modal-profissional').classList.add('hidden');
        }

        async function salvarAtribuicao(profissionalId) {
            const agendamentoId = document.getElementById('atribuir-agendamento-id').value;
            if(!agendamentoId || !profissionalId) return;

            document.body.style.cursor = 'wait';

            try {
                const res = await fetch('api_atribuir.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ agendamento_id: agendamentoId, profissional_id: profissionalId })
                });
                const json = await res.json();
                
                if(json.success) {
                    location.reload();
                } else {
                    alert('Erro ao atribuir: ' + (json.message || 'Erro desconhecido'));
                }
            } catch(e) {
                console.error(e);
                alert('Erro na comunicação com o servidor');
            } finally {
                document.body.style.cursor = 'default';
            }
        }


        // --- 3. AÇÕES DE CONFIRMAR/CANCELAR (MANTIDAS) ---
        async function gerenciarAgendamento(id, acao, telefone, nome, data, hora) {
            let zapLimpo = telefone.replace(/\D/g, '');
            if(zapLimpo.length <= 11) zapLimpo = "55" + zapLimpo;
            let msg = "", status = "";

            if(acao === 'confirmar') {
                status = 'confirmado';
                msg = `Olá ${nome}, tudo bem? Passando para *CONFIRMAR* seu agendamento dia ${data} às ${hora}. ✂️`;
            } else if(acao === 'cancelar') {
                if(!confirm("Tem certeza?")) return;
                status = 'cancelado_loja';
                msg = `Olá ${nome}. Infelizmente precisamos *CANCELAR* seu horário de ${data} às ${hora}. 🙏`;
            }

            if(status) {
                await fetch('api_status.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id, status})
                });
                window.open(`https://wa.me/${zapLimpo}?text=${encodeURIComponent(msg)}`, '_blank');
                location.reload();
            }
        }

        // --- 4. CALENDÁRIO TOPO (MANTIDO) ---
        document.addEventListener('DOMContentLoaded', function() {
            const backdrop = document.getElementById('calendar-backdrop');
            const trigger = document.getElementById('date-trigger');
            const calendar = flatpickr("#calendar-input", {
                locale: "pt", dateFormat: "Y-m-d", defaultDate: "<?= $filtro_data ?>", disableMobile: true,
                onOpen: () => backdrop.classList.remove('hidden'),
                onClose: () => backdrop.classList.add('hidden'),
                onChange: (d, s) => window.location.href = "?data=" + s
            });
            trigger.onclick = () => calendar.open();
            backdrop.onclick = () => calendar.close();

            const toggle = document.getElementById('theme-toggle');
            const icon = document.getElementById('theme-icon');
            const updateIcon = () => icon.innerText = document.documentElement.classList.contains('dark') ? '☀️' : '🌙';
            updateIcon();
            toggle.onclick = () => {
                document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
                updateIcon();
            }
        });
    </script>
</body>
</html>