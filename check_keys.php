<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

$db = Database::get();
$row = $db->query("SELECT groq_api_key, gemini_api_key FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();

echo "<pre>";
echo "groq_api_key:   " . (empty($row['groq_api_key'])   ? "❌ NÃO SALVA" : "✅ SALVA (" . substr($row['groq_api_key'],0,8) . "...)") . "\n";
echo "gemini_api_key: " . (empty($row['gemini_api_key']) ? "❌ NÃO SALVA" : "✅ SALVA (" . substr($row['gemini_api_key'],0,8) . "...)") . "\n";
echo "</pre>";
