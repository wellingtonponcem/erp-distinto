<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();

echo "--- ESTRUTURA DA TABELA SERVICOS ---\n";
$stmt = $db->query("DESCRIBE servicos");
while ($row = $stmt->fetch()) {
    echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Default']}\n";
}

echo "\n--- AMOSTRA DE DADOS ---\n";
$stmt = $db->query("SELECT * FROM servicos LIMIT 20");
while ($row = $stmt->fetch()) {
    echo "{$row['nome']} | Preco: {$row['preco_venda']} | Periodicidade: " . ($row['periodicidade'] ?? 'N/A') . " | Recorrente: " . ($row['recorrente'] ?? 'N/A') . "\n";
}
