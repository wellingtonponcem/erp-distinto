<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/financeiro_custos.php';

try {
    $db = Database::get();
    garantirEstruturaFinanceira($db);
    
    $apiKey = '$aact_hmlg_000MzkwODA2MWY2OGM3MWRlMDU2NWM3MzJlNzZmNGZhZGY6OjY3MGIyMDc0LTcyOTQtNGYzNS1hNjI3LTNhNWI4ZjdiMTIwNjo6JGFhY2hfYWQ3YzE3MjMtNDRlZS00NTU2LWE3N2QtYjFhZTVlMzg0MmY5';
    $mode = 'test'; // sandbox
    $webhookToken = '22a88fc7-1f04-473d-b743-638bee69f58d';
    
    $stmt = $db->query("SELECT id FROM configuracao_empresa WHERE id = 'principal' LIMIT 1");
    if (!$stmt->fetch()) {
        $db->exec("INSERT INTO configuracao_empresa (id, nome) VALUES ('principal', 'Minha Empresa')");
    }

    $stmtUp = $db->prepare("UPDATE configuracao_empresa SET asaas_api_key = ?, asaas_mode = ?, asaas_webhook_token = ? WHERE id = 'principal'");
    $stmtUp->execute([$apiKey, $mode, $webhookToken]);
    
    echo "<h1>Chaves do Asaas configuradas com sucesso no banco de dados local!</h1>";
    echo "<p>Modo: Sandbox</p>";
    echo "<p>Token do Webhook: " . htmlspecialchars($webhookToken) . "</p>";
} catch (Exception $e) {
    echo "<h1>Erro ao configurar chaves do Asaas:</h1>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
