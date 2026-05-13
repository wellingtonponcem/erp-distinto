<?php
// Cria o usuário administrador inicial
// Acessar UMA vez: http://seu-site.com/setup/seed.php
// APAGAR ESTE ARQUIVO após criar o usuário!

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$nome  = 'Administrador';
$email = 'admin@distinto.com.br';
$senha = 'Distinto@2026';  // Alterar após primeiro login

$db = Database::get();

$existe = $db->prepare('SELECT id FROM users WHERE email = ?');
$existe->execute([$email]);

if ($existe->fetch()) {
    echo '✅ Usuário já existe. Faça login com: ' . $email;
} else {
    $id   = gerarId();
    $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $db->prepare('INSERT INTO users (id, nome, email, senha) VALUES (?, ?, ?, ?)');
    $stmt->execute([$id, $nome, $email, $hash]);

    echo '✅ Usuário criado com sucesso!<br>';
    echo '📧 Email: <strong>' . $email . '</strong><br>';
    echo '🔑 Senha: <strong>' . $senha . '</strong><br>';
    echo '<br><strong style="color:red">⚠️ APAGUE ESTE ARQUIVO AGORA!</strong>';
}
