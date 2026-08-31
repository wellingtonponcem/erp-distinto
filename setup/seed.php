<?php
// Cria o usuário administrador inicial — PROTEGIDO: exige admin autenticado.
// Em produção deve ser removido do deploy. Acesso sem auth retorna 403.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

// Primeiro boot: se users vazio, permite sem auth (criação inicial). Caso contrário exige admin.
$dbCheck = Database::get();
try { $cnt = (int)$dbCheck->query("SELECT COUNT(*) FROM users")->fetchColumn(); } catch(Throwable $e){ $cnt = 1; }
if ($cnt > 0) {
    exigirAutenticacao();
    $__u = usuarioAtual();
    if (($__u['nivel'] ?? 0) != 1) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['erro' => 'Acesso negado: apenas administradores. Remova setup/seed.php em produção.']);
        exit;
    }
    $db = $dbCheck;
} else {
    $db = $dbCheck;
}

$nome  = 'Administrador';
$email = getenv('SEED_ADMIN_EMAIL') ?: 'admin@distinto.com.br';
$senha = getenv('SEED_ADMIN_PASSWORD') ?: 'Distinto@2026';  // Definir SEED_ADMIN_PASSWORD no env em produção e trocar após primeiro login

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
