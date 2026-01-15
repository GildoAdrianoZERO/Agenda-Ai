<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$loja_id = $_SESSION['loja_id'];
$mensagem = '';
$tipo_msg = '';

// --- PROCESSAMENTO ---

// 1. Salvar Horários da Loja
if (isset($_POST['acao']) && $_POST['acao'] == 'salvar_horarios') {
    $abertura = $_POST['abertura'];
    $fechamento = $_POST['fechamento'];
    $dias = isset($_POST['dias']) ? json_encode($_POST['dias']) : '[]';
    
    // Almoço da Loja (Geral - opcional se usar individual)
    $almoco_inicio = !empty($_POST['almoco_inicio']) ? $_POST['almoco_inicio'] : null;
    $almoco_fim = !empty($_POST['almoco_fim']) ? $_POST['almoco_fim'] : null;

    $stmt = $pdo->prepare("UPDATE estabelecimentos SET horario_abertura=?, horario_fechamento=?, horario_almoco_inicio=?, horario_almoco_fim=?, dias_funcionamento=? WHERE id=?");
    $stmt->execute([$abertura, $fechamento, $almoco_inicio, $almoco_fim, $dias, $loja_id]);
    $mensagem = "Horários da loja atualizados!"; $tipo_msg = 'success';
}

// 2. Salvar/Editar Profissional (COM FOTO E INTERVALO)
if (isset($_POST['acao']) && $_POST['acao'] == 'salvar_profissional') {
    $nome = $_POST['nome'];
    $funcao = $_POST['funcao'];
    $inicio_intervalo = !empty($_POST['inicio_intervalo']) ? $_POST['inicio_intervalo'] : null;
    $fim_intervalo = !empty($_POST['fim_intervalo']) ? $_POST['fim_intervalo'] : null;
    $id_prof = $_POST['id_profissional']; // Se tiver ID, é edição. Se não, é novo.

    // Upload de Foto
    $caminho_foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $novo_nome = uniqid() . "." . $ext;
        $destino = 'assets/uploads/' . $novo_nome;
        if (!is_dir('assets/uploads')) mkdir('assets/uploads', 0777, true);
        
        if(move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            $caminho_foto = $destino;
        }
    }

    if ($id_prof) {
        // ATUALIZAR EXISTENTE
        $sql = "UPDATE profissionais SET nome=?, funcao=?, inicio_intervalo=?, fim_intervalo=?";
        $params = [$nome, $funcao, $inicio_intervalo, $fim_intervalo];
        
        if($caminho_foto) { // Só atualiza foto se enviou uma nova
            $sql .= ", foto=?";
            $params[] = $caminho_foto;
        }
        $sql .= " WHERE id=? AND estabelecimento_id=?";
        $params[] = $id_prof;
        $params[] = $loja_id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $mensagem = "Profissional atualizado!";
    } else {
        // CRIAR NOVO
        $stmt = $pdo->prepare("INSERT INTO profissionais (estabelecimento_id, nome, funcao, foto, inicio_intervalo, fim_intervalo) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$loja_id, $nome, $funcao, $caminho_foto, $inicio_intervalo, $fim_intervalo]);
        $mensagem = "Profissional cadastrado!";
    }
    $tipo_msg = 'success';
}

// 3. Excluir Profissional
if (isset($_GET['del_prof'])) {
    $stmt = $pdo->prepare("DELETE FROM profissionais WHERE id=? AND estabelecimento_id=?");
    $stmt->execute([$_GET['del_prof'], $loja_id]);
    header("Location: configuracoes.php"); exit;
}

// 4. Salvar/Excluir Serviços (Mantido igual)
if (isset($_POST['acao']) && $_POST['acao'] == 'add_servico') {
    $stmt = $pdo->prepare("INSERT INTO servicos (estabelecimento_id, nome, preco, duracao_minutos) VALUES (?, ?, ?, ?)");
    $stmt->execute([$loja_id, $_POST['nome'], str_replace(',', '.', $_POST['preco']), $_POST['duracao']]);
    $mensagem = "Serviço criado!"; $tipo_msg = 'success';
}
if (isset($_GET['del_servico'])) {
    $stmt = $pdo->prepare("DELETE FROM servicos WHERE id=? AND estabelecimento_id=?");
    $stmt->execute([$_GET['del_servico'], $loja_id]);
    header("Location: configuracoes.php"); exit;
}

// --- BUSCAR DADOS ---
$stmt = $pdo->prepare("SELECT * FROM estabelecimentos WHERE id=?");
$stmt->execute([$loja_id]); $loja = $stmt->fetch();
$dias_ativos = json_decode($loja['dias_funcionamento'] ?? '[]', true);

$stmt = $pdo->prepare("SELECT * FROM servicos WHERE estabelecimento_id=?");
$stmt->execute([$loja_id]); $servicos = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM profissionais WHERE estabelecimento_id=?");
$stmt->execute([$loja_id]); $profissionais = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - AgendaAí</title>
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { gold: '#F59E0B', dark: { bg: '#0f0f0f', surface: '#18181b', border: '#27272a' }, light: { bg: '#f4f4f5', surface: '#ffffff', border: '#e4e4e7' } }, fontFamily: { sans: ['Inter', 'sans-serif'] } } } }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>.modal-enter { opacity: 0; transform: scale(0.95); } .modal-enter-active { opacity: 1; transform: scale(1); transition: all 0.2s; }</style>
</head>
<body class="bg-light-bg text-gray-900 dark:bg-dark-bg dark:text-white font-sans min-h-screen transition-colors duration-300">

    <nav class="bg-white dark:bg-dark-surface border-b border-light-border dark:border-dark-border px-4 md:px-8 py-4 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto flex justify-between items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="painel.php" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-dark-border hover:bg-gold hover:text-black dark:hover:bg-gold dark:hover:text-black transition text-gray-500">←</a>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gold/10 text-gold rounded-xl flex items-center justify-center text-xl">⚙️</div>
                    <div><h1 class="font-bold text-lg leading-tight">Configurações</h1><p class="text-xs text-gray-500">Gerencie sua barbearia</p></div>
                </div>
            </div>
            <button id="theme-toggle" class="w-10 h-10 flex items-center justify-center rounded-xl bg-gray-100 dark:bg-dark-border hover:text-gold transition text-gray-500"><span id="theme-icon">☀️</span></button>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto p-4 md:p-8 pb-24">
        <?php if($mensagem): ?>
            <div class="mb-6 p-4 rounded-xl border <?= $tipo_msg == 'success' ? 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-900/30 dark:text-green-400' : 'bg-red-50 border-red-200 text-red-700' ?> flex items-center gap-3">
                <span><?= $tipo_msg == 'success' ? '✅' : '⚠️' ?></span><span class="font-bold"><?= $mensagem ?></span>
            </div>
        <?php endif; ?>
        
        <div class="flex space-x-2 mb-8 bg-gray-200 dark:bg-dark-surface p-1.5 rounded-xl max-w-md mx-auto md:mx-0">
            <button onclick="mudarAba('horarios')" id="tab-horarios" class="flex-1 py-2.5 rounded-lg text-sm font-bold transition shadow-sm bg-white dark:bg-dark-border text-gold">🕒 Horários</button>
            <button onclick="mudarAba('servicos')" id="tab-servicos" class="flex-1 py-2.5 rounded-lg text-sm font-bold transition text-gray-500 hover:text-gray-900 dark:hover:text-white">✂️ Serviços</button>
            <button onclick="mudarAba('profissionais')" id="tab-profissionais" class="flex-1 py-2.5 rounded-lg text-sm font-bold transition text-gray-500 hover:text-gray-900 dark:hover:text-white">👨‍💼 Equipe</button>
        </div>

        <div id="content-horarios" class="tab-content">
            <div class="bg-white dark:bg-dark-surface p-6 md:p-8 rounded-2xl border border-light-border dark:border-dark-border shadow-sm">
                <form method="POST">
                    <input type="hidden" name="acao" value="salvar_horarios">
                    <h3 class="font-bold text-lg mb-4 text-gold">Expediente Geral da Loja</h3>
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <div><label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Abertura</label><input type="time" name="abertura" value="<?= $loja['horario_abertura'] ?>" class="w-full bg-gray-50 dark:bg-dark-bg border border-gray-200 dark:border-dark-border rounded-xl p-4 text-xl font-bold text-center focus:border-gold outline-none transition"></div>
                        <div><label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Fechamento</label><input type="time" name="fechamento" value="<?= $loja['horario_fechamento'] ?>" class="w-full bg-gray-50 dark:bg-dark-bg border border-gray-200 dark:border-dark-border rounded-xl p-4 text-xl font-bold text-center focus:border-gold outline-none transition"></div>
                    </div>
                    
                    <div class="border-t border-gray-100 dark:border-dark-border my-6"></div>
                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Dias de Atendimento</label>
                    <div class="grid grid-cols-4 md:grid-cols-7 gap-3 mb-8">
                        <?php $dias_semana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']; foreach($dias_semana as $k => $dia): $checked = in_array((string)$k, $dias_ativos) ? 'checked' : ''; ?>
                        <label class="cursor-pointer relative"><input type="checkbox" name="dias[]" value="<?= $k ?>" <?= $checked ?> class="peer sr-only"><div class="text-center py-3 rounded-xl border-2 border-transparent bg-gray-100 dark:bg-dark-bg text-gray-500 font-bold peer-checked:bg-gold peer-checked:text-black peer-checked:shadow-lg transition-all hover:bg-gray-200 dark:hover:bg-dark-border"><?= $dia ?></div></label>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex justify-end"><button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg shadow-green-900/20">Salvar Alterações</button></div>
                </form>
            </div>
        </div>

        <div id="content-servicos" class="tab-content hidden">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-1">
                    <div class="bg-white dark:bg-dark-surface p-6 rounded-2xl border border-light-border dark:border-dark-border shadow-sm sticky top-24">
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white mb-4">Novo Serviço</h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="acao" value="add_servico">
                            <div><label class="text-xs font-bold text-gray-400 uppercase">Nome</label><input type="text" name="nome" placeholder="Ex: Corte Navalhado" required class="w-full mt-1 p-3 bg-gray-50 dark:bg-dark-bg border border-gray-200 dark:border-dark-border rounded-xl focus:border-gold outline-none"></div>
                            <div class="grid grid-cols-2 gap-3"><div><label class="text-xs font-bold text-gray-400 uppercase">Preço</label><input type="text" name="preco" placeholder="30,00" required class="w-full mt-1 p-3 bg-gray-50 dark:bg-dark-bg border border-gray-200 dark:border-dark-border rounded-xl focus:border-gold outline-none"></div><div><label class="text-xs font-bold text-gray-400 uppercase">Min</label><input type="number" name="duracao" placeholder="40" required class="w-full mt-1 p-3 bg-gray-50 dark:bg-dark-bg border border-gray-200 dark:border-dark-border rounded-xl focus:border-gold outline-none"></div></div>
                            <button type="submit" class="w-full bg-gold hover:bg-yellow-500 text-black font-bold py-3 rounded-xl transition">Adicionar</button>
                        </form>
                    </div>
                </div>
                <div class="md:col-span-2 space-y-3">
                    <?php if(count($servicos) == 0): ?><div class="text-center py-10 text-gray-500 bg-white dark:bg-dark-surface rounded-2xl border border-dashed border-gray-300 dark:border-dark-border">Nenhum serviço.</div><?php else: ?>
                        <?php foreach($servicos as $s): ?>
                        <div class="bg-white dark:bg-dark-surface p-4 rounded-xl border border-light-border dark:border-dark-border shadow-sm flex justify-between items-center group hover:border-gold/50 transition">
                            <div class="flex items-center gap-4"><div class="w-10 h-10 bg-gray-100 dark:bg-dark-bg rounded-lg flex items-center justify-center text-xl">✂️</div><div><h4 class="font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($s['nome']) ?></h4><p class="text-sm text-gray-500">⏱ <?= $s['duracao_minutos'] ?> min • <span class="text-green-600 dark:text-green-400 font-bold">R$ <?= number_format($s['preco'], 2, ',', '.') ?></span></p></div></div>
                            <a href="?del_servico=<?= $s['id'] ?>" onclick="return confirm('Excluir?')" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">🗑</a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="content-profissionais" class="tab-content hidden">
            
            <div class="flex justify-between items-center mb-6">
                <div><h2 class="text-xl font-bold text-gray-900 dark:text-white">Gerenciar Equipe</h2><p class="text-sm text-gray-500">Adicione seus colaboradores e fotos.</p></div>
                <button onclick="abrirModalProfissional()" class="bg-gold hover:bg-yellow-500 text-black font-bold py-2 px-4 rounded-xl shadow-lg transition">+ Novo Profissional</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php if(count($profissionais) == 0): ?>
                    <div class="col-span-full text-center py-12 text-gray-500 bg-white dark:bg-dark-surface rounded-2xl border border-dashed border-gray-300 dark:border-dark-border">Nenhum profissional cadastrado.</div>
                <?php else: ?>
                    <?php foreach($profissionais as $p): 
                        $foto = !empty($p['foto']) ? $p['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($p['nome']).'&background=F59E0B&color=000';
                        // Prepara dados para o JS (JSON dentro do HTML)
                        $dadosJson = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="bg-white dark:bg-dark-surface p-5 rounded-2xl border border-light-border dark:border-dark-border shadow-sm group hover:border-gold/50 transition relative">
                        <div class="flex items-center gap-4">
                            <img src="<?= $foto ?>" class="w-14 h-14 rounded-full object-cover border-2 border-gray-200 dark:border-dark-border group-hover:border-gold transition">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-lg"><?= htmlspecialchars($p['nome']) ?></h4>
                                <p class="text-xs text-gold font-bold uppercase tracking-wider"><?= htmlspecialchars($p['funcao']) ?></p>
                                <?php if($p['inicio_intervalo']): ?>
                                    <p class="text-xs text-gray-400 mt-1">🍽 <?= substr($p['inicio_intervalo'],0,5) ?> - <?= substr($p['fim_intervalo'],0,5) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="absolute top-4 right-4 flex gap-1">
                            <button onclick='editarProfissional(<?= $dadosJson ?>)' class="p-1.5 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition">✏️</button>
                            <a href="?del_prof=<?= $p['id'] ?>" onclick="return confirm('Excluir?')" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">🗑</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="modal-profissional" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="fecharModalProfissional()"></div>
        <div class="bg-white dark:bg-[#151515] w-full max-w-md rounded-2xl shadow-2xl relative z-10 p-6 border border-light-border dark:border-dark-border">
            <h3 class="text-xl font-bold mb-4 text-gray-900 dark:text-white" id="modal-titulo">Novo Profissional</h3>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="acao" value="salvar_profissional">
                <input type="hidden" name="id_profissional" id="prof-id">

                <div class="flex gap-4">
                    <div class="w-20 h-20 bg-gray-100 dark:bg-dark-bg rounded-full flex items-center justify-center border border-dashed border-gray-300 dark:border-dark-border relative overflow-hidden group">
                        <img id="preview-foto" class="absolute inset-0 w-full h-full object-cover hidden">
                        <span class="text-2xl text-gray-400">📷</span>
                        <input type="file" name="foto" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(this)">
                    </div>
                    <div class="flex-1 space-y-3">
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Nome</label>
                            <input type="text" name="nome" id="prof-nome" required class="w-full mt-1 p-2 bg-gray-50 dark:bg-dark-bg border border-gray-200 dark:border-dark-border rounded-lg focus:border-gold outline-none">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Função</label>
                            <input type="text" name="funcao" id="prof-funcao" placeholder="Ex: Barbeiro" required class="w-full mt-1 p-2 bg-gray-50 dark:bg-dark-bg border border-gray-200 dark:border-dark-border rounded-lg focus:border-gold outline-none">
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-100 dark:border-dark-border my-2"></div>
                
                <p class="text-xs font-bold text-gold uppercase mb-2">Intervalo de Almoço (Individual)</p>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="text-xs text-gray-500">Início</label><input type="time" name="inicio_intervalo" id="prof-inicio" class="w-full mt-1 p-2 bg-gray-50 dark:bg-dark-bg border border-gray-200 dark:border-dark-border rounded-lg"></div>
                    <div><label class="text-xs text-gray-500">Fim</label><input type="time" name="fim_intervalo" id="prof-fim" class="w-full mt-1 p-2 bg-gray-50 dark:bg-dark-bg border border-gray-200 dark:border-dark-border rounded-lg"></div>
                </div>

                <button type="submit" class="w-full bg-gold hover:bg-yellow-500 text-black font-bold py-3 rounded-xl mt-4 transition">Salvar</button>
            </form>
            <button onclick="fecharModalProfissional()" class="absolute top-4 right-4 text-gray-400 hover:text-red-500">✕</button>
        </div>
    </div>

    <script>
        function mudarAba(aba) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.getElementById('content-' + aba).classList.remove('hidden');
            const btns = ['horarios', 'servicos', 'profissionais'];
            btns.forEach(b => {
                const btn = document.getElementById('tab-' + b);
                if (b === aba) { btn.classList.add('bg-white', 'dark:bg-dark-border', 'shadow-sm', 'text-gold'); btn.classList.remove('text-gray-500'); } 
                else { btn.classList.remove('bg-white', 'dark:bg-dark-border', 'shadow-sm', 'text-gold'); btn.classList.add('text-gray-500'); }
            });
        }

        // Modal Profissional
        const modalProf = document.getElementById('modal-profissional');
        function abrirModalProfissional() {
            document.getElementById('modal-titulo').innerText = "Novo Profissional";
            document.getElementById('prof-id').value = "";
            document.getElementById('prof-nome').value = "";
            document.getElementById('prof-funcao').value = "";
            document.getElementById('prof-inicio').value = "";
            document.getElementById('prof-fim').value = "";
            document.getElementById('preview-foto').classList.add('hidden');
            modalProf.classList.remove('hidden');
        }
        function editarProfissional(dados) {
            document.getElementById('modal-titulo').innerText = "Editar Profissional";
            document.getElementById('prof-id').value = dados.id;
            document.getElementById('prof-nome').value = dados.nome;
            document.getElementById('prof-funcao').value = dados.funcao;
            document.getElementById('prof-inicio').value = dados.inicio_intervalo;
            document.getElementById('prof-fim').value = dados.fim_intervalo;
            
            if(dados.foto) {
                const img = document.getElementById('preview-foto');
                img.src = dados.foto;
                img.classList.remove('hidden');
            } else {
                document.getElementById('preview-foto').classList.add('hidden');
            }
            modalProf.classList.remove('hidden');
        }
        function fecharModalProfissional() { modalProf.classList.add('hidden'); }
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('preview-foto');
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        const toggleBtn = document.getElementById('theme-toggle'); const icon = document.getElementById('theme-icon'); 
        const updateIcon = () => icon.innerText = document.documentElement.classList.contains('dark') ? '☀️' : '🌙'; updateIcon();
        toggleBtn.addEventListener('click', () => { document.documentElement.classList.toggle('dark'); localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light'); updateIcon(); });
    </script>
</body>
</html>