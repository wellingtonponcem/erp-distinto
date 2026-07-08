<?php
/**
 * Retorno de Assinatura (Redirect)
 * Cliente é redirecionado para cá após assinar o contrato no Assinafy.
 * Verifica o status da assinatura e processa o contrato automaticamente.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/contratos.php';

$contratoId = $_GET['contrato_id'] ?? '';
if (!$contratoId) {
    header('Location: ' . raizUrl('/gerenciamento/contratos.php'));
    exit;
}

try {
    $db = Database::get();

    $stmt = $db->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmt->execute([$contratoId]);
    $contrato = $stmt->fetch();

    if ($contrato && $contrato['status'] === 'pendente' && !empty($contrato['documento_assinatura_id'])) {
        $stmtConfig = $db->query("SELECT assinafy_api_key, assinafy_account_id, assinafy_mode FROM configuracao_empresa WHERE id = 'principal' LIMIT 1");
        $config = $stmtConfig->fetch();
        $apiKey = $config['assinafy_api_key'] ?? '';
        $mode = $config['assinafy_mode'] ?? 'test';

        if ($apiKey) {
            $baseUrl = ($mode === 'prod') ? 'https://api.assinafy.com.br/v1' : 'https://sandbox.assinafy.com.br/v1';
            $documentId = $contrato['documento_assinatura_id'];

            $ch = curl_init($baseUrl . '/documents/' . urlencode($documentId));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'X-Api-Key: ' . $apiKey,
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response && $httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                $docData = $data['data'] ?? $data;
                $statusApi = strtolower(trim($docData['status'] ?? $docData['document_status'] ?? ''));

                if (in_array($statusApi, ['completed', 'signed', 'ready', 'assinado', 'certificated', 'registrado'])) {
                    $stmtUpdate = $db->prepare("UPDATE contratos SET status = 'assinado' WHERE id = ?");
                    $stmtUpdate->execute([$contratoId]);
                    processarAssinaturaContrato($contratoId);
                }
            }
        }
    }
} catch (Exception $e) {
    // Silencioso - apenas redireciona para a página de visualização
}

header('Location: ' . raizUrl('/gerenciamento/contrato_visualizar.php?id=' . $contratoId));
exit;
