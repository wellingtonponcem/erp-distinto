<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::get();
try {
    $stmt = $db->query("SELECT gemini_api_key FROM configuracao_empresa LIMIT 1");
    $key = $stmt->fetchColumn();
    echo "Key in DB: " . ($key ? substr($key, 0, 8) . "..." : "EMPTY") . "\n";
} catch (Exception $e) {
    echo "Error checking DB: " . $e->getMessage() . "\n";
}

require_once __DIR__ . '/../config/env.php';
echo "Key in env.php: " . (defined('GEMINI_API_KEY') ? substr(GEMINI_API_KEY, 0, 8) . "..." : "NOT DEFINED") . "\n";
