<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();
$stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'roteiros'");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $cols);
