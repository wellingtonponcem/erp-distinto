<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo json_encode($tables);
