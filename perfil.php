<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// 1. Validação
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

// 2. Busca Estabelecimento
$stmt = $pdo->prepare("SELECT * FROM estabelecimentos WHERE id = ? AND status_conta = 'ativo'");
$stmt->execute([$id]);
$loja = $stmt->fetch();
if (!$loja) { header('Location: index.php'); exit; }

// 3. Busca Serviços
$stmtServicos = $pdo->prepare("SELECT * FROM servicos WHERE estabelecimento_id = ? AND ativo = 1 ORDER BY preco ASC");
$stmtServicos->execute([$id]);
$servicos = $stmtServicos->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($loja['nome_fantasia']) ?> - AgendaAí</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        gold: '#F59E0B',
                        dark: { bg: '#0a0a0a', surface: '#121212', border: '#2A2A2A' },
                        light: { bg: '#f3f4f6', surface: '#ffffff', border: '#e5e7eb' }
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .modal-animate-fade { animation: fadeIn 0.2s ease-out; }
        .modal-animate-slide { animation: slideUp 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
</head>
<body class="bg-light-bg text-gray-900 dark:bg-dark-bg dark:text-white font-sans transition-colors duration-300">

    <nav class="w-full py-6 px-4 border-b border-light-border dark:border-dark-border/50 bg-light-surface dark:bg-dark-bg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-2">
                <div class="bg-gold text-black w-8 h-8 flex items-center justify-center rounded-full font-bold text-lg">✂️</div>
                <span class="text-gold text-xl font-bold tracking-wide">AgendaAí</span>
            </a>
            <div class="flex items-center gap-4">
                <button id="theme-toggle" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-800 transition">
                    <span id="theme-icon">☀️</span>
                </button>
            </div>
        </div>
    </nav>

    <div class="relative w-full h-72 md:h-80 bg-gray-800">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?= empty($loja['foto_capa']) ? 'assets/img/default.jpg' : $loja['foto_capa'] ?>');"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
        <div class="absolute bottom-0 w-full p-6 max-w-7xl mx-auto left-0 right-0">
            <a href="index.php" class="text-gray-300 hover:text-white mb-4 inline-flex items-center gap-1 text-sm bg-black/30 px-3 py-1 rounded-full backdrop-blur-sm border border-white/10">← Voltar</a>
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-2"><?= e($loja['nome_fantasia']) ?></h1>
            <p class="text-gray-300">📍 <?= e($loja['endereco']) ?></p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-1 lg:grid-cols-3 gap-10">
        <div class="lg:col-span-2">
            <h2 class="text-2xl font-bold mb-6">Serviços Disponíveis</h2>
            
            <div class="space-y-4">
                <?php foreach($servicos as $servico): ?>
                <div class="bg-white dark:bg-dark-surface p-5 rounded-xl border border-gray-200 dark:border-dark-border flex justify-between items-center group hover:border-gold transition-all shadow-sm">
                    <div class="flex-1 pr-4">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white"><?= e($servico['nome']) ?></h3>
                        <p class="text-gray-500 text-sm mb-1"><?= e($servico['descricao']) ?></p>
                        <span class="text-xs text-gray-400">⏱ <?= $servico['duracao_minutos'] ?> min</span>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-gray-900 dark:text-white mb-2"><?= formatarPreco($servico['preco']) ?></div>
                        <button onclick="abrirModal('<?= $servico['id'] ?>', '<?= e($servico['nome']) ?>', '<?= formatarPreco($servico['preco']) ?>', '<?= $servico['duracao_minutos'] ?>')" 
                                class="bg-gray-900 dark:bg-gray-800 hover:bg-gold hover:text-black dark:hover:bg-gold dark:hover:text-black text-white px-6 py-2 rounded-lg text-sm font-bold transition shadow-lg transform hover:scale-105">
                            Agendar
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-dark-surface p-6 rounded-xl border border-gray-200 dark:border-dark-border sticky top-28">
                <h3 class="font-bold text-gold mb-4">Sobre</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-4"><?= e($loja['descricao_curta']) ?></p>
                <div class="text-sm space-y-2">
                    <p>🕒 Seg - Sáb: 09:00 - 19:00</p>
                    <p class="text-green-500">✅ Aberto Agora</p>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-agendamento" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm modal-animate-fade" onclick="fecharModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white dark:bg-[#151515] w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl border border-gray-200 dark:border-dark-border pointer-events-auto modal-animate-slide flex flex-col md:flex-row relative">
                
                <button onclick="fecharModal()" class="absolute top-4 right-4 z-10 p-2 bg-gray-100 dark:bg-black/50 rounded-full hover:bg-red-500 hover:text-white transition">✕</button>

                <div class="w-full md:w-1/3 bg-gray-50 dark:bg-[#0f0f0f] p-6 border-b md:border-b-0 md:border-r border-gray-200 dark:border-dark-border flex flex-col justify-between">
                    <div>
                        <h2 class="text-xl font-bold mb-1 text-gray-900 dark:text-white">Resumo</h2>
                        <p class="text-sm text-gray-500 mb-6">Confira os detalhes</p>
                        
                        <div class="bg-white dark:bg-[#1A1A1A] p-4 rounded-xl border border-gray-200 dark:border-dark-border mb-4">
                            <span class="text-xs text-gold font-bold uppercase tracking-wider">Serviço</span>
                            <h3 id="modal-nome-servico" class="text-lg font-bold text-gray-900 dark:text-white mt-1">...</h3>
                            <p id="modal-duracao" class="text-sm text-gray-500 mb-2">... min</p>
                            <div class="border-t border-gray-200 dark:border-dark-border pt-2 mt-2">
                                <span class="text-xs text-gray-400">Total a pagar</span>
                                <p id="modal-preco" class="text-xl font-bold text-green-500">...</p>
                            </div>
                        </div>
                    </div>

                    <form id="form-agendamento" action="confirmar_agendamento.php" method="POST" class="mt-4">
                        <input type="hidden" name="loja_id" value="<?= $loja['id'] ?>">
                        <input type="hidden" name="servico_id" id="input-servico-id">
                        <input type="hidden" name="data" id="input-data">
                        <input type="hidden" name="hora" id="input-hora">
                        
                        <div class="space-y-3">
                            <input type="text" name="cliente_nome" placeholder="Seu Nome" required class="w-full bg-white dark:bg-black border border-gray-300 dark:border-dark-border rounded-lg p-3 text-sm focus:border-gold outline-none">
                            <input type="tel" name="cliente_zap" placeholder="Seu WhatsApp" required class="w-full bg-white dark:bg-black border border-gray-300 dark:border-dark-border rounded-lg p-3 text-sm focus:border-gold outline-none">
                        </div>

                        <button type="submit" id="btn-confirmar" disabled class="w-full mt-4 bg-gray-300 dark:bg-dark-border text-gray-500 font-bold py-3 rounded-xl cursor-not-allowed transition-all">
                            Selecione um horário →
                        </button>
                    </form>
                </div>

                <div class="w-full md:w-2/3 p-6 bg-white dark:bg-[#151515]">
                    <div class="mb-6">
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">1. Escolha o dia</h3>
                        <div class="flex gap-3 overflow-x-auto no-scrollbar pb-2" id="lista-dias"></div>
                    </div>

                    <div>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">2. Escolha o horário</h3>
                        <div id="grid-horarios" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 max-h-60 overflow-y-auto pr-2">
                            <div class="col-span-full text-center py-10 text-gray-500 border border-dashed border-gray-300 dark:border-dark-border rounded-xl">
                                Selecione um dia acima 👆
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const LOJA_ID = <?= $loja['id'] ?>; // ID da loja para a API saber onde buscar

        // --- 1. MODAL ---
        const modal = document.getElementById('modal-agendamento');
        const inputServicoId = document.getElementById('input-servico-id');
        const modalNome = document.getElementById('modal-nome-servico');
        const modalPreco = document.getElementById('modal-preco');
        const modalDuracao = document.getElementById('modal-duracao');

        function abrirModal(id, nome, preco, duracao) {
            inputServicoId.value = id;
            modalNome.innerText = nome;
            modalPreco.innerText = preco;
            modalDuracao.innerText = duracao + " min";
            resetarSelecao();
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Simula clique no primeiro dia disponível para carregar algo
            const primeiroDia = document.querySelector('#lista-dias button');
            if(primeiroDia) primeiroDia.click();
        }

        function fecharModal() {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // --- 2. GERAÇÃO DE DIAS ---
        const containerDias = document.getElementById('lista-dias');
        const containerHorarios = document.getElementById('grid-horarios');
        const inputData = document.getElementById('input-data');
        const inputHora = document.getElementById('input-hora');
        const btnConfirmar = document.getElementById('btn-confirmar');
        const diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        
        const hoje = new Date();
        for (let i = 0; i < 14; i++) {
            const data = new Date(hoje);
            data.setDate(hoje.getDate() + i);
            if (data.getDay() === 0) continue; // Pula Domingo

            const diaFormatado = data.toISOString().split('T')[0];
            const btn = document.createElement('button');
            btn.className = `flex-shrink-0 w-14 h-16 rounded-xl border border-gray-200 dark:border-dark-border bg-white dark:bg-black/20 flex flex-col items-center justify-center transition hover:border-gold`;
            btn.innerHTML = `<span class="text-[10px] text-gray-500 uppercase">${diasSemana[data.getDay()]}</span><span class="text-lg font-bold text-gray-900 dark:text-white">${data.getDate()}</span>`;
            
            btn.onclick = (e) => {
                e.preventDefault();
                // Visual Selecionado
                Array.from(containerDias.children).forEach(c => {
                    c.classList.remove('bg-gold', 'border-gold'); 
                    c.querySelector('.font-bold').classList.remove('text-black');
                    c.querySelector('.font-bold').classList.add('text-gray-900', 'dark:text-white');
                });
                btn.classList.add('bg-gold', 'border-gold');
                btn.querySelector('.font-bold').classList.remove('text-gray-900', 'dark:text-white');
                btn.querySelector('.font-bold').classList.add('text-black');

                inputData.value = diaFormatado;
                inputHora.value = '';
                atualizarBtn();
                
                // CARREGA HORÁRIOS REAIS DO BANCO
                carregarHorarios(diaFormatado);
            };
            containerDias.appendChild(btn);
        }

        // --- 3. CARREGAR HORÁRIOS (COM BLOQUEIO) ---
        async function carregarHorarios(dataSelecionada) {
            containerHorarios.innerHTML = '<div class="col-span-full text-center py-4 text-gray-400">Carregando disponibilidade...</div>';
            
            // 1. Busca os ocupados na API
            let horariosOcupados = [];
            try {
                const response = await fetch(`api_horarios.php?loja_id=${LOJA_ID}&data=${dataSelecionada}`);
                horariosOcupados = await response.json();
            } catch (e) {
                console.error("Erro ao buscar horários", e);
            }

            // 2. Lista padrão de horários (Pode vir do banco depois)
            const horariosPossiveis = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];
            
            containerHorarios.innerHTML = ''; // Limpa loading

            horariosPossiveis.forEach(h => {
                const isOcupado = horariosOcupados.includes(h);

                const btn = document.createElement('button');
                btn.type = 'button'; // Importante para não submeter form
                
                if (isOcupado) {
                    // ESTILO DE BLOQUEADO
                    btn.className = 'py-2 px-1 rounded-lg border border-gray-100 dark:border-dark-border bg-gray-100 dark:bg-black/40 text-sm text-gray-300 dark:text-gray-600 cursor-not-allowed line-through';
                    btn.disabled = true;
                    btn.innerText = h;
                } else {
                    // ESTILO DE DISPONÍVEL
                    btn.className = 'py-2 px-1 rounded-lg border border-gray-200 dark:border-dark-border bg-white dark:bg-black/20 text-sm text-gray-700 dark:text-gray-300 hover:border-gold hover:text-gold transition';
                    btn.innerText = h;
                    
                    btn.onclick = () => {
                        Array.from(containerHorarios.children).forEach(c => {
                            if(!c.disabled) {
                                c.classList.remove('bg-gold', 'text-black', 'border-gold');
                                c.classList.add('bg-white', 'dark:bg-black/20', 'text-gray-700', 'dark:text-gray-300');
                            }
                        });
                        btn.classList.remove('bg-white', 'dark:bg-black/20', 'text-gray-700', 'dark:text-gray-300');
                        btn.classList.add('bg-gold', 'text-black', 'border-gold');
                        inputHora.value = h;
                        atualizarBtn();
                    };
                }
                containerHorarios.appendChild(btn);
            });
        }

        function atualizarBtn() {
            if(inputData.value && inputHora.value) {
                btnConfirmar.disabled = false;
                btnConfirmar.classList.remove('bg-gray-300', 'dark:bg-dark-border', 'text-gray-500', 'cursor-not-allowed');
                btnConfirmar.classList.add('bg-green-600', 'hover:bg-green-500', 'text-white', 'cursor-pointer');
                btnConfirmar.innerText = 'Confirmar Agendamento ✅';
            } else {
                btnConfirmar.disabled = true;
                btnConfirmar.innerText = 'Selecione um horário →';
            }
        }

        function resetarSelecao() {
            inputData.value = ''; inputHora.value = '';
            containerHorarios.innerHTML = '<div class="col-span-full text-center py-10 text-gray-500 border border-dashed border-gray-300 dark:border-dark-border rounded-xl">Selecione um dia acima 👆</div>';
            atualizarBtn();
        }

        // TEMA
        const toggleBtn = document.getElementById('theme-toggle');
        const icon = document.getElementById('theme-icon');
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark'); icon.textContent = '☀️';
        } else {
            document.documentElement.classList.remove('dark'); icon.textContent = '🌙';
        }
        toggleBtn.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            icon.textContent = isDark ? '☀️' : '🌙';
        });
    </script>
</body>
</html>