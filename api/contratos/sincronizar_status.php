<?php
/**
 * API: Sincronizar Status do Contrato com Assinafy
 * Consulta a API do Assinafy diretamente para obter o status em tempo real do documento.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$id = $_POST['id'] ?? '';
if (!$id) {
    responderJson(['erro' => 'ID do contrato é obrigatório.'], 422);
}

$db = Database::get();

// 1. Buscar credenciais Assinafy
$stmtConfig = $db->query("SELECT assinafy_api_key, assinafy_account_id, assinafy_mode FROM configuracao_empresa WHERE id = 'principal' LIMIT 1");
$config = $stmtConfig->fetch();

$apiKey = $config['assinafy_api_key'] ?? '';
$accountId = $config['assinafy_account_id'] ?? '';
$mode = $config['assinafy_mode'] ?? 'test';

if (!$apiKey || !$accountId) {
    responderJson(['erro' => 'Configurações do Assinafy incompletas.'], 400);
}

// 2. Buscar contrato local
$stmtContrato = $db->prepare("SELECT * FROM contratos WHERE id = ?");
$stmtContrato->execute([$id]);
$contrato = $stmtContrato->fetch();

if (!$contrato) {
    responderJson(['erro' => 'Contrato não encontrado.'], 404);
}

$documentId = $contrato['documento_assinatura_id'] ?? '';
if (!$documentId) {
    responderJson(['erro' => 'Este contrato ainda não foi enviado para assinatura.'], 400);
}

function chamarAssinafyGet(string $endpoint, string $apiKey, string $mode): string {
    $baseUrl = ($mode === 'prod') ? 'https://api.assinafy.com.br/v1' : 'https://sandbox.assinafy.com.br/v1';
    
    if (strpos($endpoint, '/') !== 0) {
        $endpoint = '/' . $endpoint;
    }
    
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    
    $headers = [
        'X-Api-Key: ' . $apiKey,
        'Content-Type: application/json'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false) {
        throw new Exception("Erro de conexão ao Assinafy.");
    }
    
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("Erro Assinafy (HTTP $httpCode): " . $response);
    }
    
    return $response;
}

try {
    // Buscar detalhes do documento na API
    $responseJson = chamarAssinafyGet("/accounts/{$accountId}/documents/{$documentId}", $apiKey, $mode);
    $data = json_decode($responseJson, true);
    
    if (!is_array($data)) {
        throw new Exception("Resposta de consulta inválida da API.");
    }
    
    $docData = $data['data'] ?? $data;
    $statusApi = strtolower((string)($docData['status'] ?? ''));
    
    $novoStatus = null;
    $mensagemHistorico = '';
    
    // Se o status geral for assinado/completo
    if (in_array($statusApi, ['completed', 'signed', 'ready', 'assinado'])) {
        $novoStatus = 'assinado';
        $mensagemHistorico = "Contrato comercial atualizado para ASSINADO após sincronização direta com Assinafy.";
    } 
    // Ou se houver recusa/cancelamento
    elseif (in_array($statusApi, ['cancelled', 'canceled', 'rejected', 'cancelado'])) {
        $novoStatus = 'cancelado';
        $mensagemHistorico = "Contrato comercial atualizado para CANCELADO após sincronização direta com Assinafy.";
    } 
    // Caso de redundância protetiva: se todos os signatários individuais já assinaram, consideramos assinado
    else {
        $assignments = $docData['assignments'] ?? $docData['signers'] ?? [];
        if (is_array($assignments) && count($assignments) > 0) {
            $todosAssinaram = true;
            foreach ($assignments as $a) {
                $statusSigner = strtolower((string)($a['status'] ?? ''));
                if (!in_array($statusSigner, ['signed', 'assinado', 'completed'])) {
                    $todosAssinaram = false;
                    break;
                }
            }
            
            if ($todosAssinaram) {
                $novoStatus = 'assinado';
                $mensagemHistorico = "Contrato comercial atualizado para ASSINADO após verificar que todos os signatários assinaram individualmente (Sincronização ERP/Assinafy).";
            }
        }
    }
    
    if ($novoStatus) {
        // Atualizar status no banco do ERP
        $stmtUpdate = $db->prepare("UPDATE contratos SET status = ? WHERE id = ?");
        $stmtUpdate->execute([$novoStatus, $id]);
        
        // Se houver proposta vinculada, atualizar
        if (!empty($contrato['proposta_id'])) {
            $statusProposta = ($novoStatus === 'assinado') ? 'aceita' : 'rascunho';
            $db->prepare("UPDATE propostas SET status = ? WHERE id = ?")
               ->execute([$statusProposta, $contrato['proposta_id']]);
               
            // Gravar histórico
            $stmtHist = $db->prepare("
                INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo)
                VALUES (?, ?, 'documento', ?)
            ");
            $usuario = usuarioAtual();
            $stmtHist->execute([
                $contrato['proposta_id'],
                $usuario['id'] ?? 'sistema',
                $mensagemHistorico
            ]);
        }
        
        responderJson([
            'success' => true,
            'status' => $novoStatus,
            'mensagem' => "Status atualizado localmente para: " . strtoupper($novoStatus)
        ]);
    } else {
        responderJson([
            'success' => true,
            'status' => $contrato['status'],
            'mensagem' => "O documento ainda está com status '" . strtoupper($statusApi) . "' no Assinafy e possui assinaturas pendentes."
        ]);
    }
    
} catch (Exception $e) {
    responderJson(['erro' => $e->getMessage()], 500);
}
