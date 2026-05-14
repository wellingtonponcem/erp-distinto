<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();
$rows = $db->query("SELECT id, nome, descricao FROM servicos LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
