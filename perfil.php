<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Validação do ID da loja
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

// Busca Loja
$stmt = $pdo->prepare("SELECT * FROM estabelecimentos WHERE id = ? AND status_conta = 'ativo'");
$stmt->execute([$id]);
$loja = $stmt->fetch();
if (!$loja) { header('Location: index.php'); exit; }

// Busca Serviços
$stmtServicos = $pdo->prepare("SELECT * FROM servicos WHERE estabelecimento_id = ? AND ativo = 1 ORDER BY preco ASC");
$stmtServicos->execute([$id]);
$servicos = $stmtServicos->fetchAll();

// Busca Profissionais (NOVO)
$stmtProf = $pdo->prepare("SELECT * FROM profissionais WHERE estabelecimento_id = ? AND ativo = 1");
$stmtProf->execute([$id]);
$profissionais = $stmtProf->fetchAll();

// Horários Loja
$horaAbertura = substr($loja['horario_abertura'] ?? '09:00', 0, 5);
$horaFechamento = substr($loja['horario_fechamento'] ?? '19:00', 0, 5);
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
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { gold: '#F59E0B', dark: { bg: '#0a0a0a', surface: '#121212', border: '#2A2A2A' }, light: { bg: '#f3f4f6', surface: '#ffffff', border: '#e5e7eb' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
    </script>
    <style>.no-scrollbar::-webkit-scrollbar { display: none; } .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; } .modal-animate-slide { animation: slideUp 0.3s ease-out; } @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }</style>
</head>
<body class="bg-light-bg text-gray-900 dark:bg-dark-bg dark:text-white font-sans transition-colors duration-300">

    <nav class="w-full py-6 px-4 border-b border-light-border dark:border-dark-border/50 bg-light-surface dark:bg-dark-bg sticky top-0 z-40 backdrop-blur-md bg-opacity-80">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-2"><div class="bg-gold text-black w-8 h-8 flex items-center justify-center rounded-full font-bold text-lg">✂️</div><span class="text-gold text-xl font-bold tracking-wide">AgendaAí</span></a>
            <button id="theme-toggle" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-800 transition"><span id="theme-icon">☀️</span></button>
        </div>
    </nav>

    <div class="relative w-full h-72 md:h-80 bg-gray-800">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('<?= empty($loja['foto_capa']) ? 'assets/img/default.jpg' : $loja['foto_capa'] ?>');"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-dark-bg via-dark-bg/60 to-transparent"></div>
        <div class="absolute bottom-0 w-full p-6 max-w-7xl mx-auto left-0 right-0">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-2"><?= e($loja['nome_fantasia']) ?></h1>
            <p class="text-gray-300 flex items-center gap-1"><svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg> <?= e($loja['endereco']) ?></p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-10 grid grid-cols-1 lg:grid-cols-3 gap-10">
        <div class="lg:col-span-2">
            <h2 class="text-2xl font-bold mb-6 flex items-center gap-2"><span class="text-gold">✂️</span> Escolha o Serviço</h2>
            <div class="space-y-4">
                <?php foreach($servicos as $servico): ?>
                <div class="bg-white dark:bg-dark-surface p-5 rounded-xl border border-gray-200 dark:border-dark-border flex justify-between items-center group hover:border-gold transition-all shadow-sm cursor-pointer" onclick="abrirModal('<?= $servico['id'] ?>', '<?= e($servico['nome']) ?>', '<?= formatarPreco($servico['preco']) ?>', '<?= $servico['duracao_minutos'] ?>')">
                    <div class="flex-1 pr-4">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white group-hover:text-gold transition"><?= e($servico['nome']) ?></h3>
                        <p class="text-gray-500 text-sm mb-1"><?= e($servico['descricao']) ?></p>
                        <span class="text-xs text-gray-400 bg-gray-100 dark:bg-dark-bg px-2 py-1 rounded">⏱ <?= $servico['duracao_minutos'] ?> min</span>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-gray-900 dark:text-white mb-2"><?= formatarPreco($servico['preco']) ?></div>
                        <button class="bg-dark-bg dark:bg-white text-white dark:text-black px-4 py-2 rounded-lg text-sm font-bold">Agendar</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-dark-surface p-6 rounded-xl border border-gray-200 dark:border-dark-border sticky top-28 shadow-lg">
                <h3 class="font-bold text-gold mb-4 uppercase text-xs tracking-wider">Informações</h3>
                <p class="text-gray-600 dark:text-gray-400 text-sm mb-6 leading-relaxed"><?= e($loja['descricao_curta']) ?></p>
                <div class="border-t border-gray-200 dark:border-dark-border pt-4">
                    <p class="text-sm font-bold mb-2">Horário de Funcionamento</p>
                    <p class="text-gray-500 text-sm">Seg a Sáb: <?= $horaAbertura ?> às <?= $horaFechamento ?></p>
                </div>
            </div>
        </div>
    </div>

    <div id="modal-agendamento" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity" onclick="fecharModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
            <div class="bg-white dark:bg-[#151515] w-full max-w-5xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl border border-gray-200 dark:border-dark-border pointer-events-auto modal-animate-slide flex flex-col md:flex-row relative">
                
                <button onclick="fecharModal()" class="absolute top-4 right-4 z-10 p-2 bg-gray-100 dark:bg-black/50 rounded-full hover:bg-red-500 hover:text-white transition">✕</button>

                <div class="w-full md:w-1/3 bg-gray-50 dark:bg-[#0f0f0f] p-6 border-b md:border-b-0 md:border-r border-gray-200 dark:border-dark-border flex flex-col justify-between">
                    <div>
                        <h2 class="text-xl font-bold mb-1 text-gray-900 dark:text-white">Finalizar Agendamento</h2>
                        <p class="text-sm text-gray-500 mb-6">Confira os detalhes abaixo</p>
                        
                        <div class="bg-white dark:bg-[#1A1A1A] p-4 rounded-xl border border-gray-200 dark:border-dark-border mb-4 shadow-sm">
                            <span class="text-[10px] text-gold font-bold uppercase tracking-wider">Serviço Selecionado</span>
                            <h3 id="modal-nome-servico" class="text-lg font-bold text-gray-900 dark:text-white mt-1">...</h3>
                            <p id="modal-duracao" class="text-xs text-gray-500 mb-3">... min</p>
                            
                            <div class="flex justify-between items-center border-t border-gray-100 dark:border-dark-border pt-3">
                                <span class="text-sm text-gray-400">Valor Total</span>
                                <p id="modal-preco" class="text-xl font-bold text-green-500">...</p>
                            </div>
                        </div>
                    </div>

                    <form id="form-agendamento" action="confirmar_agendamento.php" method="POST" class="mt-4">
                        <input type="hidden" name="loja_id" value="<?= $loja['id'] ?>">
                        <input type="hidden" name="servico_id" id="input-servico-id">
                        <input type="hidden" name="profissional_id" id="input-prof-id"> <input type="hidden" name="data" id="input-data">
                        <input type="hidden" name="hora" id="input-hora">
                        
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase ml-1">Seu Nome</label>
                                <input type="text" name="cliente_nome" placeholder="Digite seu nome" required class="w-full bg-white dark:bg-black border border-gray-200 dark:border-dark-border rounded-lg p-3 text-sm focus:border-gold outline-none transition">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-500 uppercase ml-1">Seu WhatsApp</label>
                                <input type="tel" name="cliente_zap" placeholder="(00) 90000-0000" required class="w-full bg-white dark:bg-black border border-gray-200 dark:border-dark-border rounded-lg p-3 text-sm focus:border-gold outline-none transition">
                            </div>
                        </div>
                        
                        <button type="submit" id="btn-confirmar" disabled class="w-full mt-6 bg-gray-300 dark:bg-dark-border text-gray-500 font-bold py-3.5 rounded-xl cursor-not-allowed transition-all shadow-lg hover:shadow-xl">
                            Selecione Profissional e Data
                        </button>
                    </form>
                </div>

                <div class="w-full md:w-2/3 p-6 bg-white dark:bg-[#151515] overflow-y-auto">
                    
                    <div class="mb-8">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">1. Escolha o Profissional</h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div onclick="selecionarProfissional(null, this)" class="prof-card cursor-pointer p-3 rounded-xl border border-gray-200 dark:border-dark-border hover:border-gold dark:hover:border-gold transition flex flex-col items-center gap-2 bg-gray-50 dark:bg-dark-bg">
                                <div class="w-12 h-12 rounded-full bg-gray-200 dark:bg-dark-border flex items-center justify-center text-xl">🎲</div>
                                <span class="text-sm font-bold text-center">Tanto faz</span>
                            </div>

                            <?php foreach($profissionais as $prof): 
                                $foto = !empty($prof['foto']) ? $prof['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($prof['nome']).'&background=F59E0B&color=000';
                            ?>
                            <div onclick="selecionarProfissional(<?= $prof['id'] ?>, this)" class="prof-card cursor-pointer p-3 rounded-xl border border-gray-200 dark:border-dark-border hover:border-gold dark:hover:border-gold transition flex flex-col items-center gap-2 bg-gray-50 dark:bg-dark-bg">
                                <img src="<?= $foto ?>" class="w-12 h-12 rounded-full object-cover">
                                <div class="text-center leading-tight">
                                    <span class="block text-sm font-bold"><?= htmlspecialchars($prof['nome']) ?></span>
                                    <span class="text-[10px] text-gray-500"><?= htmlspecialchars($prof['funcao']) ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-8 opacity-50 pointer-events-none transition-all" id="step-data">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">2. Escolha o Dia</h3>
                        <div class="flex gap-3 overflow-x-auto no-scrollbar pb-2" id="lista-dias"></div>
                    </div>

                    <div class="opacity-50 pointer-events-none transition-all" id="step-hora">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">3. Escolha o Horário</h3>
                        <div id="grid-horarios" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                            <div class="col-span-full text-center py-8 text-gray-500 border border-dashed border-gray-300 dark:border-dark-border rounded-xl">Selecione um dia acima 👆</div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        const LOJA_ID = <?= $loja['id'] ?>;
        const HORA_ABERTURA = "<?= $horaAbertura ?>";
        const HORA_FECHAMENTO = "<?= $horaFechamento ?>";

        // Elementos
        const modal = document.getElementById('modal-agendamento');
        const inputServicoId = document.getElementById('input-servico-id');
        const inputProfId = document.getElementById('input-prof-id');
        const inputData = document.getElementById('input-data');
        const inputHora = document.getElementById('input-hora');
        const btnConfirmar = document.getElementById('btn-confirmar');
        const stepData = document.getElementById('step-data');
        const stepHora = document.getElementById('step-hora');

        function abrirModal(id, nome, preco, duracao) {
            inputServicoId.value = id;
            document.getElementById('modal-nome-servico').innerText = nome;
            document.getElementById('modal-preco').innerText = preco;
            document.getElementById('modal-duracao').innerText = duracao + " min";
            
            // Resetar
            inputProfId.value = '';
            inputData.value = '';
            inputHora.value = '';
            document.querySelectorAll('.prof-card').forEach(c => c.classList.remove('border-gold', 'bg-gold/10', 'ring-2', 'ring-gold'));
            stepData.classList.add('opacity-50', 'pointer-events-none');
            stepHora.classList.add('opacity-50', 'pointer-events-none');
            atualizarBtn();
            
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function fecharModal() {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // --- 1. SELEÇÃO DE PROFISSIONAL ---
        function selecionarProfissional(id, el) {
            // Visual
            document.querySelectorAll('.prof-card').forEach(c => c.classList.remove('border-gold', 'bg-gold/10', 'ring-2', 'ring-gold'));
            el.classList.add('border-gold', 'bg-gold/10', 'ring-2', 'ring-gold');
            
            // Lógica
            inputProfId.value = id ? id : ''; // Se null, manda vazio (qualquer um)
            
            // Libera próximo passo
            stepData.classList.remove('opacity-50', 'pointer-events-none');
            gerarDias(); // Regenera dias (pode filtrar folgas no futuro)
            
            // Scroll suave
            stepData.scrollIntoView({behavior: "smooth", block: "start"});
        }

        // --- 2. GERAÇÃO DE DIAS ---
        function gerarDias() {
            const container = document.getElementById('lista-dias');
            container.innerHTML = '';
            const diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            const hoje = new Date();

            for (let i = 0; i < 14; i++) {
                const data = new Date(hoje);
                data.setDate(hoje.getDate() + i);
                
                // Pular Domingo (Exemplo simples, ideal é ler config da loja)
                if (data.getDay() === 0) continue; 

                const diaF = data.toISOString().split('T')[0];
                const btn = document.createElement('button');
                btn.className = "flex-shrink-0 w-16 h-20 rounded-xl border border-gray-200 dark:border-dark-border bg-white dark:bg-black/20 flex flex-col items-center justify-center transition hover:border-gold hover:text-gold group";
                btn.innerHTML = `<span class="text-[10px] text-gray-500 uppercase font-bold group-hover:text-gold">${diasSemana[data.getDay()]}</span><span class="text-xl font-bold text-gray-900 dark:text-white group-hover:text-gold">${data.getDate()}</span>`;
                
                btn.onclick = (e) => {
                    // Remove active
                    Array.from(container.children).forEach(c => {
                        c.classList.remove('bg-gold', 'border-gold', 'text-black');
                        c.classList.add('bg-white', 'dark:bg-black/20');
                        c.querySelector('span:last-child').classList.remove('text-black');
                        c.querySelector('span:last-child').classList.add('text-gray-900', 'dark:text-white');
                    });
                    
                    // Add active
                    btn.classList.remove('bg-white', 'dark:bg-black/20', 'hover:text-gold');
                    btn.classList.add('bg-gold', 'border-gold', 'text-black');
                    btn.querySelector('span:last-child').classList.remove('text-gray-900', 'dark:text-white');
                    btn.querySelector('span:last-child').classList.add('text-black');

                    inputData.value = diaF;
                    stepHora.classList.remove('opacity-50', 'pointer-events-none');
                    carregarHorarios(diaF);
                };
                container.appendChild(btn);
            }
        }

        // --- 3. HORÁRIOS ---
        async function carregarHorarios(dataSelecionada) {
            const container = document.getElementById('grid-horarios');
            container.innerHTML = '<div class="col-span-full text-center py-4 text-gray-400 animate-pulse">Buscando disponibilidade...</div>';
            
            // Gera slots (Simplificado: Abertura até Fechamento)
            let horarios = [];
            let [hI, mI] = HORA_ABERTURA.split(':').map(Number);
            let [hF, mF] = HORA_FECHAMENTO.split(':').map(Number);
            let atual = new Date(); atual.setHours(hI, mI, 0);
            let fim = new Date(); fim.setHours(hF, mF, 0);

            while(atual < fim) {
                horarios.push(atual.toTimeString().substr(0, 5));
                atual.setHours(atual.getHours() + 1); // Intervalo de 1h
            }

            // Busca Ocupados
            let ocupados = [];
            try {
                // Passa o ID do profissional para filtrar a agenda DELE (se selecionado)
                const profId = inputProfId.value;
                const url = `api_horarios.php?loja_id=${LOJA_ID}&data=${dataSelecionada}` + (profId ? `&profissional_id=${profId}` : '');
                const res = await fetch(url);
                ocupados = await res.json();
            } catch(e) {}

            container.innerHTML = '';
            horarios.forEach(h => {
                const btn = document.createElement('button');
                btn.type = 'button'; // Importante pra não submeter form
                
                if (ocupados.includes(h)) {
                    btn.className = "py-3 rounded-lg bg-gray-100 dark:bg-black/40 text-gray-300 dark:text-gray-700 line-through cursor-not-allowed border border-transparent";
                    btn.disabled = true;
                } else {
                    btn.className = "py-3 rounded-lg bg-white dark:bg-black/20 border border-gray-200 dark:border-dark-border text-gray-900 dark:text-white hover:border-gold hover:text-gold transition font-bold";
                    btn.onclick = () => {
                        Array.from(container.children).forEach(c => {
                            if(!c.disabled) {
                                c.classList.remove('bg-gold', 'text-black', 'border-gold');
                                c.classList.add('bg-white', 'dark:bg-black/20', 'text-gray-900', 'dark:text-white');
                            }
                        });
                        btn.classList.remove('bg-white', 'dark:bg-black/20', 'text-gray-900', 'dark:text-white');
                        btn.classList.add('bg-gold', 'text-black', 'border-gold');
                        inputHora.value = h;
                        atualizarBtn();
                    };
                }
                btn.innerText = h;
                container.appendChild(btn);
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
                btnConfirmar.classList.add('bg-gray-300', 'dark:bg-dark-border', 'text-gray-500', 'cursor-not-allowed');
                btnConfirmar.classList.remove('bg-green-600', 'hover:bg-green-500', 'text-white', 'cursor-pointer');
                btnConfirmar.innerText = 'Selecione Data e Horário';
            }
        }

        // Dark Mode Logic
        const toggleBtn = document.getElementById('theme-toggle'); 
        const icon = document.getElementById('theme-icon'); 
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) { document.documentElement.classList.add('dark'); icon.textContent = '☀️'; } else { document.documentElement.classList.remove('dark'); icon.textContent = '🌙'; } 
        toggleBtn.addEventListener('click', () => { document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light'); icon.textContent = document.documentElement.classList.contains('dark') ? '☀️' : '🌙'; });
    </script>
</body>
</html>