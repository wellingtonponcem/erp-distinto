<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();
$stmt = $db->query("SELECT nome, tipo, categoria FROM servicos WHERE categoria = 'wedding'");
$res = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($res, JSON_PRETTY_PRINT);
