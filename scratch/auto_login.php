<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

iniciarSessao();

$db = Database::get();
$user = $db->query("SELECT * FROM users LIMIT 1")->fetch();

if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_nivel'] = $user['nivel'];
    $_SESSION['user_sistema_origem'] = $user['sistema_origem'] ?? 'distinto';
    
    header('Location: ' . raizUrl('/gerenciamento/contrato_visualizar.php?id=7580df7b2fd57dfd5dc60307eede8cdf'));
    exit;
} else {
    echo "Nenhum usuario encontrado no banco.";
}
