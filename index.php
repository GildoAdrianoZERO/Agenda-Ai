<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// --- LÓGICA DE BUSCA CORRIGIDA ---
$busca = filter_input(INPUT_GET, 'q', FILTER_SANITIZE_SPECIAL_CHARS);
$local = filter_input(INPUT_GET, 'loc', FILTER_SANITIZE_SPECIAL_CHARS);
$categoria = filter_input(INPUT_GET, 'cat', FILTER_SANITIZE_SPECIAL_CHARS);

$sql = "SELECT * FROM estabelecimentos WHERE status_conta = 'ativo'";
$params = [];

// CORREÇÃO DO ERRO HY093:
// Usamos nomes únicos (:b1, :b2...) para cada campo, pois o PDO seguro não aceita repetir :busca
if ($busca) {
    $sql .= " AND (nome_fantasia LIKE :b1 OR endereco LIKE :b2 OR tags LIKE :b3 OR descricao_curta LIKE :b4)";
    $termo = "%$busca%";
    $params['b1'] = $termo;
    $params['b2'] = $termo;
    $params['b3'] = $termo;
    $params['b4'] = $termo;
}

if ($local) {
    $sql .= " AND endereco LIKE :local";
    $params['local'] = "%$local%";
}

if ($categoria) {
    $sql .= " AND categoria = :categoria";
    $params['categoria'] = $categoria;
}

$sql .= " ORDER BY avaliacao DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $lojas = $stmt->fetchAll();
} catch (PDOException $e) {
    // Se der erro, mostra na tela para facilitar o debug agora
    die("Erro na consulta: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgendaAí - Encontre seu estilo</title>
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
        .cursor-blink { animation: blink 1s step-end infinite; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
    </style>
</head>

<body class="bg-light-bg text-gray-900 dark:bg-dark-bg dark:text-white font-sans selection:bg-gold selection:text-black transition-colors duration-300">

    <nav class="w-full py-6 px-4 border-b border-light-border dark:border-dark-border/50 bg-light-surface dark:bg-dark-bg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-2">
                <div class="bg-gold text-black w-8 h-8 flex items-center justify-center rounded-full font-bold text-lg">✂️</div>
                <span class="text-gold text-xl font-bold tracking-wide">AgendaAí</span>
            </a>
            <div class="flex items-center gap-4">
                <button id="theme-toggle" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-800 transition">
                    <span id="theme-icon">☀️</span>
                </button>
                <a href="login.php" class="text-sm font-semibold hover:text-gold transition">Sou Profissional</a>
            </div>
        </div>
    </nav>

    <div class="w-full flex flex-col items-center pt-16 pb-10 px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6 tracking-tight h-20 md:h-auto">
            Encontre <span id="typewriter" class="text-gold"></span><span class="cursor-blink text-gold">|</span>
        </h1>
        
        <form action="index.php" method="GET" class="w-full max-w-4xl bg-white dark:bg-dark-surface border border-gray-200 dark:border-dark-border rounded-2xl p-2 flex flex-col md:flex-row gap-2 shadow-2xl dark:shadow-black/50 relative z-10">
            <div class="flex-[1.5] flex items-center px-4 py-3 bg-transparent md:border-r border-gray-200 dark:border-dark-border">
                <span class="text-gray-400 mr-3">🔍</span>
                <input type="text" name="q" value="<?= htmlspecialchars($busca ?? '') ?>" 
                       placeholder="Busque por nome, serviço ou profissional..." 
                       class="w-full bg-transparent focus:outline-none placeholder-gray-500 font-medium">
            </div>
            <div class="flex-1 flex items-center px-4 py-3 bg-transparent relative">
                <span class="text-gray-400 mr-3">📍</span>
                <input type="text" name="loc" id="location-input" value="<?= htmlspecialchars($local ?? '') ?>"
                       placeholder="Onde você está?" 
                       class="w-full bg-transparent focus:outline-none placeholder-gray-500 font-medium pr-8">
                <button type="button" onclick="getLocation()" title="Usar localização" class="absolute right-2 p-2 text-gold hover:bg-gray-800 rounded-full transition group">
                    <svg class="w-5 h-5 group-hover:animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                </button>
            </div>
            <button type="submit" class="bg-gold text-black font-bold px-8 py-3 rounded-xl hover:bg-yellow-400 transition shadow-lg shadow-gold/20">
                Buscar
            </button>
        </form>

        <div class="mt-12 w-full max-w-4xl overflow-x-auto no-scrollbar">
            <div class="flex justify-start md:justify-center gap-4 md:gap-8 px-4 min-w-max">
                <a href="index.php" class="flex flex-col items-center gap-2 group cursor-pointer">
                    <div class="w-16 h-16 rounded-2xl bg-white dark:bg-dark-surface border border-gray-200 dark:border-dark-border flex items-center justify-center text-2xl group-hover:border-gold group-hover:scale-110 transition-all shadow-md">🏠</div>
                    <span class="text-xs font-bold text-gray-500 group-hover:text-gold uppercase">Todas</span>
                </a>
                <a href="index.php?cat=barbearia" class="flex flex-col items-center gap-2 group cursor-pointer">
                    <div class="w-16 h-16 rounded-2xl bg-white dark:bg-dark-surface border border-gray-200 dark:border-dark-border flex items-center justify-center text-2xl group-hover:border-gold group-hover:scale-110 transition-all shadow-md <?= $categoria == 'barbearia' ? 'border-gold ring-2 ring-gold/20' : '' ?>">💈</div>
                    <span class="text-xs font-bold text-gray-500 group-hover:text-gold uppercase">Barbearia</span>
                </a>
                <a href="index.php?cat=salao" class="flex flex-col items-center gap-2 group cursor-pointer">
                    <div class="w-16 h-16 rounded-2xl bg-white dark:bg-dark-surface border border-gray-200 dark:border-dark-border flex items-center justify-center text-2xl group-hover:border-gold group-hover:scale-110 transition-all shadow-md <?= $categoria == 'salao' ? 'border-gold ring-2 ring-gold/20' : '' ?>">💇‍♀️</div>
                    <span class="text-xs font-bold text-gray-500 group-hover:text-gold uppercase">Salão</span>
                </a>
                <a href="index.php?cat=tatuagem" class="flex flex-col items-center gap-2 group cursor-pointer">
                    <div class="w-16 h-16 rounded-2xl bg-white dark:bg-dark-surface border border-gray-200 dark:border-dark-border flex items-center justify-center text-2xl group-hover:border-gold group-hover:scale-110 transition-all shadow-md <?= $categoria == 'tatuagem' ? 'border-gold ring-2 ring-gold/20' : '' ?>">✒️</div>
                    <span class="text-xs font-bold text-gray-500 group-hover:text-gold uppercase">Tatuagem</span>
                </a>
                <a href="index.php?cat=estetica" class="flex flex-col items-center gap-2 group cursor-pointer">
                    <div class="w-16 h-16 rounded-2xl bg-white dark:bg-dark-surface border border-gray-200 dark:border-dark-border flex items-center justify-center text-2xl group-hover:border-gold group-hover:scale-110 transition-all shadow-md <?= $categoria == 'estetica' ? 'border-gold ring-2 ring-gold/20' : '' ?>">💆</div>
                    <span class="text-xs font-bold text-gray-500 group-hover:text-gold uppercase">Estética</span>
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 pb-20">
        <div class="flex items-end justify-between mb-8 border-b border-gray-200 dark:border-dark-border pb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    <?= $categoria ? ucfirst($categoria) . 's' : 'Destaques' ?>
                </h2>
                <p class="text-gray-500 text-sm mt-1">
                    <?= count($lojas) ?> resultado(s) <?= $local ? "perto de <strong>$local</strong>" : "" ?>
                </p>
            </div>
        </div>

        <?php if (count($lojas) == 0): ?>
            <div class="text-center py-20">
                <p class="text-4xl mb-4">😢</p>
                <p class="text-gray-500">Nada encontrado.</p>
                <a href="index.php" class="text-gold hover:underline">Limpar filtros</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($lojas as $loja): ?>
                <a href="perfil.php?id=<?= $loja['id'] ?>" class="group block bg-white dark:bg-dark-surface rounded-xl overflow-hidden border border-gray-200 dark:border-dark-border hover:border-gold dark:hover:border-gold transition-all duration-300 shadow-sm hover:shadow-xl hover:-translate-y-1">
                    <div class="h-56 w-full bg-cover bg-center relative" style="background-image: url('<?= empty($loja['foto_capa']) ? 'assets/img/default.jpg' : $loja['foto_capa'] ?>');">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
                        <div class="absolute top-4 right-4 bg-black/60 backdrop-blur-md text-white text-xs font-bold px-2 py-1 rounded border border-white/10"><?= $loja['faixa_preco'] ?></div>
                        <?php if($loja['avaliacao'] >= 4.8): ?><div class="absolute top-4 left-4 bg-gold text-black text-xs font-bold px-3 py-1 rounded-full uppercase">👑 Top</div><?php endif; ?>
                    </div>
                    <div class="p-5">
                        <div class="flex justify-between items-start">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white group-hover:text-gold transition"><?= e($loja['nome_fantasia']) ?></h3>
                            <span class="text-xs bg-gray-100 dark:bg-gray-800 text-gray-500 px-2 py-1 rounded capitalize"><?= $loja['categoria'] ?></span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm line-clamp-2 mt-2"><?= e($loja['descricao_curta']) ?></p>
                        <div class="flex items-center gap-2 mt-4 text-xs text-gray-500">
                            <span>📍 <?= e($loja['endereco']) ?></span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // TYPEWRITER (DIGITAÇÃO)
        const phrases = ["a barbearia perfeita", "o salão ideal", "o tatuador top", "a estética renovada"];
        const textElement = document.getElementById("typewriter");
        let phraseIndex = 0;
        let charIndex = 0;
        let isDeleting = false;
        let typeSpeed = 100;

        function type() {
            const currentPhrase = phrases[phraseIndex];
            if (isDeleting) {
                textElement.textContent = currentPhrase.substring(0, charIndex - 1);
                charIndex--;
                typeSpeed = 40;
            } else {
                textElement.textContent = currentPhrase.substring(0, charIndex + 1);
                charIndex++;
                typeSpeed = 100;
            }

            if (!isDeleting && charIndex === currentPhrase.length) {
                isDeleting = true; typeSpeed = 2000;
            } else if (isDeleting && charIndex === 0) {
                isDeleting = false; phraseIndex++;
                if (phraseIndex === phrases.length) phraseIndex = 0;
                typeSpeed = 500;
            }
            setTimeout(type, typeSpeed);
        }
        document.addEventListener('DOMContentLoaded', type);

        // GEOLOCALIZAÇÃO
        function getLocation() {
            const input = document.getElementById('location-input');
            const originalPlaceholder = input.placeholder;
            if (!navigator.geolocation) { alert("Sem suporte a GPS."); return; }
            input.placeholder = "Buscando..."; input.value = "";
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`)
                        .then(res => res.json())
                        .then(data => {
                            const addr = data.address;
                            const locName = addr.suburb || addr.neighbourhood || addr.city || addr.town;
                            input.value = locName || `${pos.coords.latitude}, ${pos.coords.longitude}`;
                        })
                        .catch(() => input.value = "Local Atual");
                },
                () => { alert("Erro no GPS."); input.placeholder = originalPlaceholder; }
            );
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