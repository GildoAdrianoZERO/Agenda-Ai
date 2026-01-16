<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$loja_id = $_SESSION['loja_id'];

// --- FILTROS ---
$data_inicio = filter_input(INPUT_GET, 'inicio') ?? date('Y-m-01'); // Início do mês atual
$data_fim = filter_input(INPUT_GET, 'fim') ?? date('Y-m-t'); // Fim do mês atual
$prof_id = filter_input(INPUT_GET, 'profissional'); // ID ou vazio para todos

// --- QUERY BASE ---
// Buscamos apenas agendamentos Confirmados ou Concluídos (para somar dinheiro real)
$sql_base = "FROM agendamentos a 
             JOIN servicos s ON a.servico_id = s.id 
             LEFT JOIN profissionais p ON a.profissional_id = p.id
             WHERE a.estabelecimento_id = ? 
             AND a.status IN ('confirmado', 'concluido')
             AND DATE(a.data_hora_inicio) BETWEEN ? AND ?";

$params = [$loja_id, $data_inicio, $data_fim];

if ($prof_id) {
    $sql_base .= " AND a.profissional_id = ?";
    $params[] = $prof_id;
}

// 1. DADOS TOTAIS (CARDS)
$stmt = $pdo->prepare("SELECT COUNT(*) as qtd, SUM(s.preco) as total $sql_base");
$stmt->execute($params);
$resumo = $stmt->fetch();
$total_faturamento = $resumo['total'] ?? 0;
$total_atendimentos = $resumo['qtd'] ?? 0;
$ticket_medio = $total_atendimentos > 0 ? $total_faturamento / $total_atendimentos : 0;

// 2. DADOS PARA O GRÁFICO (POR DIA)
$stmt = $pdo->prepare("SELECT DATE(a.data_hora_inicio) as dia, SUM(s.preco) as valor $sql_base GROUP BY DATE(a.data_hora_inicio) ORDER BY dia ASC");
$stmt->execute($params);
$grafico_dias = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Retorna array [dia => valor]

// 3. DADOS PARA O GRÁFICO (POR PROFISSIONAL)
// Nota: Se selecionou um profissional específico, esse gráfico mostrará 100% ele, mas serve para comparar quando seleciona "Todos"
$stmt = $pdo->prepare("SELECT COALESCE(p.nome, 'Sem Profissional') as nome, SUM(s.preco) as valor $sql_base GROUP BY a.profissional_id");
$stmt->execute($params);
$grafico_prof = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. LISTA DETALHADA
$stmt = $pdo->prepare("SELECT a.data_hora_inicio, a.cliente_nome, s.nome as servico, s.preco, COALESCE(p.nome, '-') as profissional $sql_base ORDER BY a.data_hora_inicio DESC");
$stmt->execute($params);
$lista = $stmt->fetchAll();

// --- LISTA DE PROFISSIONAIS (PARA O SELECT DO FILTRO) ---
$stmt = $pdo->prepare("SELECT id, nome FROM profissionais WHERE estabelecimento_id = ?");
$stmt->execute([$loja_id]);
$profissionais_lista = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Financeiro - AgendaAí</title>
    
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { gold: '#F59E0B', dark: { bg: '#0f0f0f', surface: '#18181b', border: '#27272a' }, light: { bg: '#f4f4f5', surface: '#ffffff', border: '#e4e4e7' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light-bg text-gray-900 dark:bg-dark-bg dark:text-white font-sans min-h-screen transition-colors duration-300">

    <nav class="bg-white dark:bg-dark-surface border-b border-light-border dark:border-dark-border px-4 md:px-8 py-4 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto flex justify-between items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="painel.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-dark-border hover:bg-gold hover:text-black dark:hover:bg-gold dark:hover:text-black transition text-gray-500">←</a>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-500/10 text-green-500 rounded-xl flex items-center justify-center text-xl font-bold">$</div>
                    <div><h1 class="font-bold text-lg leading-tight">Relatório Financeiro</h1><p class="text-xs text-gray-500">Acompanhe seus ganhos</p></div>
                </div>
            </div>
            <button id="theme-toggle" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-dark-border hover:text-gold transition text-gray-500"><span id="theme-icon">☀️</span></button>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto p-4 md:p-8 pb-24">
        
        <form class="bg-white dark:bg-dark-surface p-4 rounded-2xl border border-light-border dark:border-dark-border shadow-sm mb-8 flex flex-col md:flex-row gap-4 items-end">
            <div class="w-full md:w-auto">
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Início</label>
                <input type="date" name="inicio" value="<?= $data_inicio ?>" class="w-full p-2.5 rounded-xl border border-gray-200 dark:border-dark-border bg-gray-50 dark:bg-dark-bg focus:border-gold outline-none">
            </div>
            <div class="w-full md:w-auto">
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Fim</label>
                <input type="date" name="fim" value="<?= $data_fim ?>" class="w-full p-2.5 rounded-xl border border-gray-200 dark:border-dark-border bg-gray-50 dark:bg-dark-bg focus:border-gold outline-none">
            </div>
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Profissional</label>
                <select name="profissional" class="w-full p-2.5 rounded-xl border border-gray-200 dark:border-dark-border bg-gray-50 dark:bg-dark-bg focus:border-gold outline-none">
                    <option value="">Todos os Profissionais</option>
                    <?php foreach($profissionais_lista as $prof): ?>
                        <option value="<?= $prof['id'] ?>" <?= $prof_id == $prof['id'] ? 'selected' : '' ?>><?= htmlspecialchars($prof['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="w-full md:w-auto bg-gold hover:bg-yellow-500 text-black font-bold py-2.5 px-6 rounded-xl shadow-lg transition">Filtrar</button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-green-500 text-white p-6 rounded-2xl shadow-lg shadow-green-500/20">
                <p class="text-xs font-bold uppercase opacity-80">Faturamento Total</p>
                <h2 class="text-3xl font-bold mt-1">R$ <?= number_format($total_faturamento, 2, ',', '.') ?></h2>
            </div>
            <div class="bg-white dark:bg-dark-surface p-6 rounded-2xl border border-light-border dark:border-dark-border shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase">Atendimentos</p>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mt-1"><?= $total_atendimentos ?></h2>
            </div>
            <div class="bg-white dark:bg-dark-surface p-6 rounded-2xl border border-light-border dark:border-dark-border shadow-sm">
                <p class="text-xs font-bold text-gray-400 uppercase">Ticket Médio</p>
                <h2 class="text-3xl font-bold text-gold mt-1">R$ <?= number_format($ticket_medio, 2, ',', '.') ?></h2>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-2 bg-white dark:bg-dark-surface p-6 rounded-2xl border border-light-border dark:border-dark-border shadow-sm">
                <h3 class="font-bold text-lg mb-4 text-gray-900 dark:text-white">Faturamento por Dia</h3>
                <div class="h-64">
                    <canvas id="chartDias"></canvas>
                </div>
            </div>
            <div class="lg:col-span-1 bg-white dark:bg-dark-surface p-6 rounded-2xl border border-light-border dark:border-dark-border shadow-sm">
                <h3 class="font-bold text-lg mb-4 text-gray-900 dark:text-white">Por Profissional</h3>
                <div class="h-64 flex items-center justify-center">
                    <canvas id="chartProf"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-surface rounded-2xl border border-light-border dark:border-dark-border shadow-sm overflow-hidden">
            <div class="p-6 border-b border-light-border dark:border-dark-border">
                <h3 class="font-bold text-lg text-gray-900 dark:text-white">Extrato de Agendamentos</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                    <thead class="bg-gray-50 dark:bg-black/20 uppercase text-xs font-bold text-gray-500">
                        <tr>
                            <th class="px-6 py-4">Data</th>
                            <th class="px-6 py-4">Cliente</th>
                            <th class="px-6 py-4">Serviço</th>
                            <th class="px-6 py-4">Profissional</th>
                            <th class="px-6 py-4 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-dark-border">
                        <?php if(count($lista) == 0): ?>
                            <tr><td colspan="5" class="px-6 py-8 text-center">Nenhum registro encontrado neste período.</td></tr>
                        <?php else: ?>
                            <?php foreach($lista as $item): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white"><?= date('d/m/Y H:i', strtotime($item['data_hora_inicio'])) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($item['cliente_nome']) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($item['servico']) ?></td>
                                <td class="px-6 py-4"><span class="bg-gray-100 dark:bg-dark-bg px-2 py-1 rounded text-xs font-bold"><?= htmlspecialchars($item['profissional']) ?></span></td>
                                <td class="px-6 py-4 text-right font-bold text-green-600 dark:text-green-400">R$ <?= number_format($item['preco'], 2, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        // Dados PHP para JS
        const dadosDias = <?= json_encode(array_values($grafico_dias)) ?>;
        const labelsDias = <?= json_encode(array_map(function($d){ return date('d/m', strtotime($d)); }, array_keys($grafico_dias))) ?>;
        
        const dadosProfValores = <?= json_encode(array_column($grafico_prof, 'valor')) ?>;
        const dadosProfNomes = <?= json_encode(array_column($grafico_prof, 'nome')) ?>;

        // Configuração de Cores
        const isDark = document.documentElement.classList.contains('dark');
        const colorText = isDark ? '#e5e7eb' : '#374151';
        const colorGrid = isDark ? '#27272a' : '#e5e7eb';

        // Gráfico Dias (Linha)
        new Chart(document.getElementById('chartDias'), {
            type: 'line',
            data: {
                labels: labelsDias,
                datasets: [{
                    label: 'Faturamento (R$)',
                    data: dadosDias,
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: colorText }, grid: { display: false } },
                    y: { ticks: { color: colorText }, grid: { color: colorGrid, borderDash: [5, 5] } }
                }
            }
        });

        // Gráfico Profissionais (Doughnut)
        new Chart(document.getElementById('chartProf'), {
            type: 'doughnut',
            data: {
                labels: dadosProfNomes,
                datasets: [{
                    data: dadosProfValores,
                    backgroundColor: ['#F59E0B', '#10B981', '#3B82F6', '#EF4444', '#8B5CF6'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { color: colorText } } }
            }
        });

        // Tema
        const toggleBtn = document.getElementById('theme-toggle');
        const icon = document.getElementById('theme-icon');
        const updateIcon = () => icon.innerText = document.documentElement.classList.contains('dark') ? '☀️' : '🌙';
        updateIcon();
        toggleBtn.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
            updateIcon();
            location.reload(); // Recarrega para atualizar cores do gráfico
        });
    </script>
</body>
</html>