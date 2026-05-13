<?php
require_once __DIR__ . '/../../config/database.php';

$db = Database::get();
$stmt = $db->query("SELECT id, nome, descricao FROM servicos LIMIT 10");
$rows = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($rows, JSON_PRETTY_PRINT);
