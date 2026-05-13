<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();
$rows = $db->query("SELECT id, tipo, valor, valor_pago, status, vencimento FROM lancamentos ORDER BY id DESC LIMIT 10")->fetchAll();
echo json_encode($rows, JSON_PRETTY_PRINT);
