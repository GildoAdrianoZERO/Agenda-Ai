<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Se já estiver logado, manda pro painel
if (isset($_SESSION['user_id'])) { header('Location: painel.php'); exit; }

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_loja = filter_input(INPUT_POST, 'nome_loja', FILTER_SANITIZE_SPECIAL_CHARS);
    $nome_user = filter_input(INPUT_POST, 'nome_user', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    $confirma_senha = $_POST['confirma_senha'];

    if ($senha !== $confirma_senha) {
        $erro = "As senhas não coincidem!";
    } else {
        try {
            // Verifica se email já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $erro = "Este e-mail já está cadastrado.";
            } else {
                // INICIA TRANSAÇÃO (Tudo ou Nada)
                $pdo->beginTransaction();

                // 1. Cria o Estabelecimento
                $stmtLoja = $pdo->prepare("INSERT INTO estabelecimentos (nome_fantasia, status_conta, criado_em) VALUES (?, 'ativo', NOW())");
                $stmtLoja->execute([$nome_loja]);
                $loja_id = $pdo->lastInsertId();

                // 2. Cria o Usuário Admin vinculado à Loja
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmtUser = $pdo->prepare("INSERT INTO usuarios (estabelecimento_id, nome, email, senha, nivel) VALUES (?, ?, ?, ?, 'admin')");
                $stmtUser->execute([$loja_id, $nome_user, $email, $senha_hash]);
                $user_id = $pdo->lastInsertId();

                // 3. Cria um Serviço Padrão (Exemplo)
                $stmtServ = $pdo->prepare("INSERT INTO servicos (estabelecimento_id, nome, preco, duracao_minutos) VALUES (?, 'Corte Masculino', 35.00, 40)");
                $stmtServ->execute([$loja_id]);

                $pdo->commit();

                // Loga o usuário automaticamente
                $_SESSION['user_id'] = $user_id;
                $_SESSION['loja_id'] = $loja_id;
                $_SESSION['user_nome'] = $nome_user;
                
                header('Location: painel.php');
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = "Erro ao cadastrar: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crie sua conta - AgendaAí</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { gold: '#F59E0B', dark: { bg: '#0f0f0f', surface: '#18181b', border: '#27272a' } },
                    fontFamily: { sans: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-dark-bg text-white font-sans min-h-screen flex items-center justify-center">

    <div class="w-full h-screen flex overflow-hidden">
        
        <div class="hidden md:block w-1/2 lg:w-3/5 bg-cover bg-center relative" style="background-image: url('https://images.unsplash.com/photo-1503951914875-452162b7f300?q=80&w=2070&auto=format&fit=crop');">
            <div class="absolute inset-0 bg-black/60 flex flex-col justify-center px-12">
                <div class="max-w-lg">
                    <h1 class="text-5xl font-bold text-white mb-4">Gerencie seu Estabelecimento com <span class="text-gold">estilo</span>.</h1>
                    <p class="text-gray-300 text-lg">Junte-se a centenas de profissionais que modernizaram seus agendamentos.</p>
                    
                    <div class="mt-8 flex gap-4">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-full bg-gold/20 flex items-center justify-center text-gold">✓</div>
                            <span class="font-semibold">Agenda Online</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-full bg-gold/20 flex items-center justify-center text-gold">✓</div>
                            <span class="font-semibold">Painel Financeiro</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full md:w-1/2 lg:w-2/5 bg-dark-surface border-l border-dark-border flex flex-col justify-center px-8 md:px-16 py-12 overflow-y-auto">
            
            <div class="w-full max-w-md mx-auto">
                <div class="flex items-center gap-2 mb-8">
                    <div class="bg-gold text-black w-10 h-10 flex items-center justify-center rounded-xl font-bold text-xl">✂️</div>
                    <span class="text-2xl font-bold tracking-tight">AgendaAí</span>
                </div>

                <h2 class="text-3xl font-bold text-white mb-2">Crie sua conta</h2>
                <p class="text-gray-400 mb-8">Comece a usar o sistema gratuitamente.</p>

                <?php if($erro): ?>
                    <div class="bg-red-500/10 border border-red-500/50 text-red-500 p-4 rounded-xl mb-6 text-sm font-bold flex items-center gap-2">
                        ⚠️ <?= $erro ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nome do Estabelecimento</label>
                        <input type="text" name="nome_loja" required placeholder="Ex: Viking Barber" class="w-full bg-dark-bg border border-dark-border rounded-xl p-3.5 focus:border-gold outline-none transition text-white placeholder-gray-600">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Seu Nome Completo</label>
                        <input type="text" name="nome_user" required placeholder="Ex: João Silva" class="w-full bg-dark-bg border border-dark-border rounded-xl p-3.5 focus:border-gold outline-none transition text-white placeholder-gray-600">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">E-mail de Acesso</label>
                        <input type="email" name="email" required placeholder="seu@email.com" class="w-full bg-dark-bg border border-dark-border rounded-xl p-3.5 focus:border-gold outline-none transition text-white placeholder-gray-600">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Senha</label>
                            <input type="password" name="senha" required placeholder="******" class="w-full bg-dark-bg border border-dark-border rounded-xl p-3.5 focus:border-gold outline-none transition text-white placeholder-gray-600">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Confirmar</label>
                            <input type="password" name="confirma_senha" required placeholder="******" class="w-full bg-dark-bg border border-dark-border rounded-xl p-3.5 focus:border-gold outline-none transition text-white placeholder-gray-600">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-gold hover:bg-yellow-500 text-black font-bold py-4 rounded-xl shadow-lg shadow-gold/20 transition transform hover:scale-[1.02] mt-4">
                        🚀 Criar minha conta
                    </button>
                </form>

                <p class="text-center text-gray-500 mt-8 text-sm">
                    Já tem uma conta? 
                    <a href="login.php" class="text-gold font-bold hover:underline">Fazer Login</a>
                </p>
            </div>

        </div>
    </div>

</body>
</html>