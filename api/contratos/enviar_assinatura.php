<?php
/**
 * API: Enviar Contrato para Assinatura Eletrônica (Assinafy)
 * Recebe o arquivo PDF do contrato gerado e realiza a integração com a API Assinafy.
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
if (!$id || empty($_FILES['pdf'])) {
    responderJson(['erro' => 'Dados incompletos.'], 422);
}

$db = Database::get();

// 1. Buscar credenciais Assinafy nas configurações
$stmtConfig = $db->query("SELECT assinafy_api_key, assinafy_account_id, assinafy_mode FROM configuracao_empresa WHERE id = 'principal' LIMIT 1");
$config = $stmtConfig->fetch();

$apiKey = $config['assinafy_api_key'] ?? '';
$accountId = $config['assinafy_account_id'] ?? '';
$mode = $config['assinafy_mode'] ?? 'test';

if (!$apiKey || !$accountId) {
    responderJson(['erro' => 'Chave de API ou ID da Conta do Assinafy não configurados. Acesse Ajustes Gerais.'], 400);
}

// 2. Buscar dados do contrato
$stmtContrato = $db->prepare("SELECT * FROM contratos WHERE id = ?");
$stmtContrato->execute([$id]);
$contrato = $stmtContrato->fetch();

if (!$contrato) {
    responderJson(['erro' => 'Contrato não encontrado.'], 404);
}

$dadosJson = json_decode($contrato['dados_json'], true) ?: [];
$sig1 = $dadosJson['signatario_1'] ?? null;
$sig2 = $dadosJson['signatario_2'] ?? null;
$sigDistinto = $dadosJson['signatario_distinto'] ?? ['nome' => 'Jeane Poncem', 'email' => 'jeaneponcemsm@gmail.com', 'telefone' => ''];

if (!$sig1 || empty($sig1['nome']) || empty($sig1['email'])) {
    responderJson(['erro' => 'O Signatário 1 (Nome e E-mail) é obrigatório para enviar o contrato.'], 422);
}

// 3. Salvar arquivo PDF temporariamente
$pdfFile = $_FILES['pdf'];
$tempDir = __DIR__ . '/../../assets/temp';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}
$tempPdfPath = $tempDir . '/contrato_' . $id . '_' . time() . '.pdf';

if (!move_uploaded_file($pdfFile['tmp_name'], $tempPdfPath)) {
    responderJson(['erro' => 'Erro ao processar o arquivo PDF no servidor.'], 500);
}

// 4. Helper para chamar a API Assinafy
function chamarAssinafy(string $endpoint, string $method, $payload, bool $isMultipart, string $apiKey, string $mode): string {
    $baseUrl = ($mode === 'prod') ? 'https://api.assinafy.com.br/v1' : 'https://sandbox.assinafy.com.br/v1';
    
    if (strpos($endpoint, '/') !== 0) {
        $endpoint = '/' . $endpoint;
    }
    
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = [
        'X-Api-Key: ' . $apiKey
    ];
    
    if ($isMultipart) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    } else {
        $jsonPayload = json_encode($payload);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        $headers[] = 'Content-Type: application/json';
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("Erro Assinafy (HTTP $httpCode): " . $response);
    }
    
    return $response;
}

try {
    // 5. Passo 1 no Assinafy: Upload do Documento (Multipart)
    $cfile = new CURLFile($tempPdfPath, 'application/pdf', 'Contrato_' . $contrato['cliente_nome'] . '.pdf');
    $uploadPayload = [
        'file' => $cfile,
        'name' => 'Contrato ' . $contrato['cliente_nome']
    ];
    
    $uploadRes = chamarAssinafy("/accounts/{$accountId}/documents", 'POST', $uploadPayload, true, $apiKey, $mode);
    $uploadData = json_decode($uploadRes, true);
    $documentId = $uploadData['id'] ?? null;
    
    if (!$documentId) {
        throw new Exception("Resposta de upload inválida do Assinafy.");
    }
    
    // 6. Passo 2 no Assinafy: Criar Signatários
    $signerIds = [];
    
    // Signatário 1
    $signer1Payload = [
        'full_name' => $sig1['nome'],
        'email' => $sig1['email'],
        'whatsapp_phone_number' => preg_replace('/\D/', '', $sig1['telefone'] ?? '')
    ];
    $sig1Res = chamarAssinafy("/accounts/{$accountId}/signers", 'POST', $signer1Payload, false, $apiKey, $mode);
    $sig1Data = json_decode($sig1Res, true);
    $sig1Id = $sig1Data['data']['id'] ?? $sig1Data['id'] ?? null;
    if ($sig1Id) {
        $signerIds[] = $sig1Id;
    }
    
    // Signatário 2 (se preenchido)
    if ($sig2 && !empty($sig2['nome']) && !empty($sig2['email'])) {
        $signer2Payload = [
            'full_name' => $sig2['nome'],
            'email' => $sig2['email'],
            'whatsapp_phone_number' => preg_replace('/\D/', '', $sig2['telefone'] ?? '')
        ];
        try {
            $sig2Res = chamarAssinafy("/accounts/{$accountId}/signers", 'POST', $signer2Payload, false, $apiKey, $mode);
            $sig2Data = json_decode($sig2Res, true);
            $sig2Id = $sig2Data['data']['id'] ?? $sig2Data['id'] ?? null;
            if ($sig2Id) {
                $signerIds[] = $sig2Id;
            }
        } catch (Exception $e) {
            // Se falhar o segundo signatário, tentamos seguir sem ele ou reportamos
        }
    }

    // Signatário Distinto (Contratada)
    if ($sigDistinto && !empty($sigDistinto['nome']) && !empty($sigDistinto['email'])) {
        $signerDistintoPayload = [
            'full_name' => $sigDistinto['nome'],
            'email' => $sigDistinto['email'],
            'whatsapp_phone_number' => preg_replace('/\D/', '', $sigDistinto['telefone'] ?? '')
        ];
        try {
            $sigDistintoRes = chamarAssinafy("/accounts/{$accountId}/signers", 'POST', $signerDistintoPayload, false, $apiKey, $mode);
            $sigDistintoData = json_decode($sigDistintoRes, true);
            $sigDistintoId = $sigDistintoData['data']['id'] ?? $sigDistintoData['id'] ?? null;
            if ($sigDistintoId) {
                $signerIds[] = $sigDistintoId;
            }
        } catch (Exception $e) {
            // Se falhar o signatário da Distinto, logamos ou tentamos seguir
        }
    }
    
    if (empty($signerIds)) {
        throw new Exception("Falha ao registrar os signatários no Assinafy.");
    }
    
    // 7. Passo 3 no Assinafy: Associar Assinaturas (Assignments)
    $assignPayload = [
        'method' => 'virtual',
        'signerIds' => $signerIds,
        'message' => 'Por favor, assine o contrato de prestação de serviços da Distinto | Poncem Studio.'
    ];
    
    $assignRes = chamarAssinafy("/documents/{$documentId}/assignments", 'POST', $assignPayload, false, $apiKey, $mode);
    $assignData = json_decode($assignRes, true);
    
    // Encontrar link de assinatura
    $linkAssinatura = '';
    if (!empty($assignData['signing_urls']) && is_array($assignData['signing_urls'])) {
        $linkAssinatura = $assignData['signing_urls'][0]['url'] ?? '';
    }
    
    // Fallback caso a API retorne outra estrutura
    if (!$linkAssinatura) {
        $linkAssinatura = "https://painel.assinafy.com.br/documento/{$documentId}";
    }
    
    // 8. Atualizar banco de dados do ERP
    $stmtUpdate = $db->prepare("
        UPDATE contratos 
        SET status = 'pendente', documento_assinatura_id = ?, link_assinatura = ? 
        WHERE id = ?
    ");
    $stmtUpdate->execute([$documentId, $linkAssinatura, $id]);
    
    // 9. Registrar no histórico da proposta se houver
    if (!empty($contrato['proposta_id'])) {
        $stmtHist = $db->prepare("
            INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo)
            VALUES (?, ?, 'documento', ?)
        ");
        $usuario = usuarioAtual();
        $stmtHist->execute([
            $contrato['proposta_id'],
            $usuario['id'] ?? 'sistema',
            "Contrato comercial enviado para assinatura eletrônica (Documento ID: {$documentId})."
        ]);
        
        // Também atualizar o status da proposta para aceita ou em andamento
        $db->prepare("UPDATE propostas SET status = 'pendente' WHERE id = ? AND status = 'rascunho'")
           ->execute([$contrato['proposta_id']]);
    }
    
    // Limpar arquivo temporário
    unlink($tempPdfPath);
    
    responderJson([
        'success' => true,
        'document_id' => $documentId,
        'link_assinatura' => $linkAssinatura
    ]);
    
} catch (Exception $e) {
    if (file_exists($tempPdfPath)) {
        unlink($tempPdfPath);
    }
    responderJson(['erro' => $e->getMessage()], 500);
}
