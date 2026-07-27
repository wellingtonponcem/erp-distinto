<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::get();
    
    $stmt = $db->query("SELECT asaas_mode, asaas_api_key FROM configuracao_empresa WHERE id = 'principal' LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($config) {
        echo "Asaas Mode no Banco: " . $config['asaas_mode'] . "\n";
        echo "API Key (primeiros 10 chars): " . substr($config['asaas_api_key'], 0, 10) . "...\n";
    } else {
        echo "Configuração da empresa não encontrada.\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
