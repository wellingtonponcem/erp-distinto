<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

$email = 'jeaneponcem13@gmail.com';
$db = Database::get();

try {
    $stmt = $db->prepare("UPDATE users SET sistema_origem = 'roteiros' WHERE email = ?");
    $stmt->execute([$email]);
    $count = $stmt->rowCount();
    
    if ($count > 0) {
        echo "Usuário '$email' atualizado para 'roteiros' com sucesso!\n";
    } else {
        echo "Usuário '$email' não encontrado ou já estava atualizado.\n";
    }
} catch (Exception $e) {
    echo "Erro ao atualizar: " . $e->getMessage() . "\n";
}
