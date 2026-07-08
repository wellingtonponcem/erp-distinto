<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::get();
    
    echo "--- LISTA DE CLIENTES ---\n";
    $stmt = $db->query("SELECT id, nome, cpf_cnpj, contato, asaas_customer_id FROM clientes");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
