<?php
// reset_senha.php
require_once 'config/database.php';

$email = 'admin@teste.com';
$nova_senha = '123456';

// 1. Gera o hash compatível com o SEU servidor
$hash = password_hash($nova_senha, PASSWORD_DEFAULT);

try {
    // 2. Tenta atualizar o usuário existente
    $stmt = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE email = :email");
    $stmt->execute(['senha' => $hash, 'email' => $email]);

    if ($stmt->rowCount() > 0) {
        echo "<h1>Sucesso!</h1>";
        echo "<p>A senha do usuário <b>$email</b> foi alterada para: <b>$nova_senha</b></p>";
    } else {
        // 3. Se não atualizou nada, é porque o usuário não existe. Vamos criar!
        echo "<p>Usuário não encontrado. Criando um novo...</p>";
        
        $stmtInsert = $pdo->prepare("INSERT INTO usuarios (estabelecimento_id, nome, email, senha) VALUES (1, 'Gildo Admin', :email, :senha)");
        $stmtInsert->execute(['email' => $email, 'senha' => $hash]);
        
        echo "<h1>Sucesso!</h1>";
        echo "<p>Usuário <b>$email</b> criado com a senha: <b>$nova_senha</b></p>";
    }
    
    echo "<br><a href='login.php'>Ir para o Login</a>";

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>