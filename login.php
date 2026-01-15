<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$erro = '';

// LÓGICA DE LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];

    // Busca o usuário pelo email
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verifica se existe e se a senha bate (password_verify compara com o hash)
    if ($user && password_verify($senha, $user['senha'])) {
        // Sucesso! Salva na sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nome'] = $user['nome'];
        $_SESSION['loja_id'] = $user['estabelecimento_id'];
        
        header('Location: painel.php'); // Redireciona para o painel
        exit;
    } else {
        $erro = "E-mail ou senha incorretos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Profissional - AgendaAí</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: { colors: { gold: '#F59E0B', dark: { bg: '#0a0a0a', surface: '#121212', border: '#2A2A2A' } } }
            }
        }
    </script>
</head>
<body class="bg-dark-bg text-white h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gold text-black rounded-full text-3xl font-bold mb-4">✂️</div>
            <h1 class="text-3xl font-bold">Área do Profissional</h1>
            <p class="text-gray-400 mt-2">Gerencie sua agenda com facilidade.</p>
        </div>

        <div class="bg-dark-surface border border-dark-border p-8 rounded-2xl shadow-2xl">
            
            <?php if($erro): ?>
                <div class="bg-red-500/10 border border-red-500/50 text-red-500 p-3 rounded-lg mb-6 text-sm text-center">
                    <?= $erro ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-400 mb-2">E-mail</label>
                    <input type="email" name="email" required placeholder="admin@exemplo.com"
                           class="w-full bg-black/30 border border-dark-border rounded-xl p-3 text-white focus:border-gold outline-none transition">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-400 mb-2">Senha</label>
                    <input type="password" name="senha" required placeholder="••••••••"
                           class="w-full bg-black/30 border border-dark-border rounded-xl p-3 text-white focus:border-gold outline-none transition">
                </div>

                <button type="submit" class="w-full bg-gold hover:bg-yellow-400 text-black font-bold py-3 rounded-xl transition shadow-lg shadow-gold/10">
                    Entrar no Sistema
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="index.php" class="text-sm text-gray-500 hover:text-white transition">← Voltar para o site</a>
            </div>
        </div>
        
        <p class="text-center text-gray-600 text-xs mt-8">
            Login de Teste: admin@teste.com / 123456
        </p>
    </div>

</body>
</html>