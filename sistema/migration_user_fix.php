<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

// Proteção simples
$token = $_GET['token'] ?? '';
if ($token !== 'fix_meus_roteiros_2025') {
    die('Acesso negado.');
}

$db = Database::get();
$email = 'jeaneponcem13@gmail.com';

try {
    // 1. Garante que a coluna existe (repetindo por segurança)
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS sistema_origem VARCHAR(20) DEFAULT 'distinto'");
    
    // 2. Atualiza o usuário específico
    $stmt = $db->prepare("UPDATE users SET sistema_origem = 'roteiros' WHERE email = ?");
    $stmt->execute([$email]);
    
    echo "Sucesso! O usuário $email agora pertence ao sistema 'Meus Roteiros'.\n";
    echo "Agora ele verá o menu correto e terá os acessos restritos ao SaaS.";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
