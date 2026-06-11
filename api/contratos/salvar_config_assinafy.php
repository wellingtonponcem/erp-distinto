<?php
/**
 * API: Salvar Configurações do Assinafy
 * Recebe as chaves de API e salva no banco de dados.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

// Suporta tanto JSON payload quanto FormData tradicional
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$apiKey = trim($input['assinafy_api_key'] ?? '');
$accountId = trim($input['assinafy_account_id'] ?? '');
$mode = in_array($input['assinafy_mode'] ?? '', ['test', 'prod']) ? $input['assinafy_mode'] : 'test';

if (!$accountId) {
    responderJson(['erro' => 'O ID da Conta (Account ID) é obrigatório.'], 422);
}

$db = Database::get();

try {
    // Busca chaves atuais para não sobrescrever com campo vazio (se for password e vier vazio)
    $stmt = $db->query("SELECT assinafy_api_key FROM configuracao_empresa WHERE id = 'principal' LIMIT 1");
    $configAtual = $stmt->fetch();
    
    // Se a API Key veio vazia e já temos uma salva, mantém a antiga
    if (empty($apiKey) && !empty($configAtual['assinafy_api_key'])) {
        $apiKey = $configAtual['assinafy_api_key'];
    } elseif (empty($apiKey)) {
        responderJson(['erro' => 'A API Key do Assinafy é obrigatória.'], 422);
    }

    $stmtUpdate = $db->prepare("
        UPDATE configuracao_empresa 
        SET assinafy_api_key = ?, assinafy_account_id = ?, assinafy_mode = ? 
        WHERE id = 'principal'
    ");
    $stmtUpdate->execute([$apiKey, $accountId, $mode]);

    responderJson(['success' => true, 'message' => 'Configurações do Assinafy atualizadas com sucesso!']);
} catch (Exception $e) {
    responderJson(['erro' => 'Erro ao salvar configurações no banco de dados: ' . $e->getMessage()], 500);
}
