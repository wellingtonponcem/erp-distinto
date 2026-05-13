<?php
require_once dirname(__DIR__) . '/includes/db.php';

$email = 'faustinosdg@gmail.com';
$senha = '1234';
$hash = password_hash($senha, PASSWORD_DEFAULT);

try {
    // Verifica se o usuário já existe
    $stmt = db()->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = db()->prepare("UPDATE users SET senha = :senha, nivel = 1 WHERE email = :email");
        $stmt->execute([
            ':senha' => $hash,
            ':email' => $email
        ]);
        echo "Usuário <b>$email</b> atualizado com sucesso. (Senha redefinida para: $senha)";
    } else {
        // Tenta inserir o novo usuário
        // Campos básicos baseados no uso do sistema
        $stmt = db()->prepare("INSERT INTO users (nome, email, senha, nivel) VALUES (:nome, :email, :senha, 1)");
        $stmt->execute([
            ':nome' => 'Faustino',
            ':email' => $email,
            ':senha' => $hash
        ]);
        echo "Usuário <b>$email</b> criado com sucesso. (Senha: $senha)";
    }
} catch (Exception $e) {
    echo "Erro ao processar: " . htmlspecialchars($e->getMessage());
}
