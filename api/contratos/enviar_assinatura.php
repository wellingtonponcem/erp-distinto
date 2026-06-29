<?php
/**
 * API: Enviar Contrato para Assinatura Eletrônica (Assinafy)
 * Recebe o arquivo PDF do contrato gerado e realiza a integração com a API Assinafy.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$id = $_POST['id'] ?? '';
if (!$id) {
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

// 3. Gerar PDF temporariamente no servidor com Dompdf
$contratoTexto = $dadosJson['contrato_texto'] ?? '';
$anexoTexto = $dadosJson['anexo_texto'] ?? '';

// Carregar logotipo em Base64
$logoPath = __DIR__ . '/../../assets/logo-contrato.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
}

// Montar HTML estruturado
$html = '
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Contrato_' . htmlspecialchars($id) . '</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @page {
            margin: 20mm 18mm 20mm 18mm;
        }
        body {
            font-family: "Sora", "Arial", sans-serif;
            font-size: 9.5pt;
            color: #231f20;
            line-height: 1.45;
            background-color: #ffffff;
        }
        .pdf-logo-wrapper {
            margin-bottom: 25pt;
            text-align: left;
        }
        .pdf-logo {
            width: 170px;
            height: auto;
            display: block;
        }
        .pdf-body {
            text-align: justify;
        }
        h3 {
            font-size: 14pt;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
            margin: 0 0 15pt 0;
            line-height: 1.2;
        }
        .pdf-subtitle {
            font-size: 10pt;
            font-weight: 400;
            margin: 0 0 5pt 0;
            text-align: left;
        }
        .pdf-numero {
            font-size: 10pt;
            font-weight: 400;
            margin: 0 0 15pt 0;
            text-align: left;
        }
        h4 {
            font-size: 10pt;
            font-weight: 700;
            text-transform: uppercase;
            margin: 18pt 0 6pt 0;
            line-height: 1.2;
            page-break-after: avoid;
        }
        p {
            margin: 0 0 10pt 0;
            text-align: justify;
            text-indent: 20pt;
        }
        p.p0, p.pdf-subtitle, p.pdf-numero {
            text-indent: 0 !important;
        }
        p.p-closing {
            text-indent: 0 !important;
            margin-top: 25pt;
        }
        ul, ol {
            margin: 0 0 10pt 0;
            padding-left: 25pt;
        }
        li {
            margin-bottom: 5pt;
            text-align: justify;
        }
        .page-break {
            page-break-before: always;
        }
        .pdf-body p strong {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="pdf-logo-wrapper">
        ' . ($logoBase64 ? '<img src="' . $logoBase64 . '" class="pdf-logo" alt="Logo">' : '') . '
    </div>
    <div class="pdf-body">
        ' . $contratoTexto . '
    </div>
    <div class="page-break"></div>
    <div class="pdf-logo-wrapper">
        ' . ($logoBase64 ? '<img src="' . $logoBase64 . '" class="pdf-logo" alt="Logo">' : '') . '
    </div>
    <div class="pdf-body">
        ' . (!empty($anexoTexto) ? $anexoTexto : '<h4>ANEXO I - DESCRIÇÃO DOS SERVIÇOS</h4><p class="p0">A descrição detalhada dos serviços será incluída após a definição do escopo do evento.</p>') . '
    </div>
</body>
</html>
';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultMediaType', 'print');
$options->set('dpi', 150);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfOutput = $dompdf->output();

$tempDir = __DIR__ . '/../../assets/temp';
if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}
$tempPdfPath = $tempDir . '/contrato_' . $id . '_' . time() . '.pdf';

if (file_put_contents($tempPdfPath, $pdfOutput) === false) {
    responderJson(['erro' => 'Erro ao processar o arquivo PDF no servidor.'], 500);
}

class AssinafyApiException extends Exception
{
    public int $statusCode;
    public string $responseBody;

    public function __construct(int $statusCode, string $responseBody)
    {
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        parent::__construct("Erro Assinafy (HTTP {$statusCode}): {$responseBody}", $statusCode);
    }
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
    
    $method = strtoupper($method);

    if ($isMultipart) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    } elseif ($method !== 'GET' && $payload !== null) {
        $jsonPayload = json_encode($payload);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        $headers[] = 'Content-Type: application/json';
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        throw new Exception("Erro Assinafy (cURL): " . $curlError);
    }
    
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new AssinafyApiException($httpCode, (string)$response);
    }
    
    return $response;
}

function normalizarEmailAssinafy(?string $email): string {
    return strtolower(trim((string)$email));
}

function mensagemAssinafy(string $response): string {
    $payload = json_decode($response, true);
    if (is_array($payload)) {
        foreach (['message', 'erro', 'error'] as $campo) {
            if (!empty($payload[$campo]) && is_scalar($payload[$campo])) {
                return (string)$payload[$campo];
            }
        }
    }

    return $response;
}

function erroAssinafyEmailDuplicado(Throwable $e): bool {
    if (!$e instanceof AssinafyApiException || $e->statusCode !== 400) {
        return false;
    }

    $mensagem = strtolower(mensagemAssinafy($e->responseBody));
    return str_contains($mensagem, 'signat') && str_contains($mensagem, 'e-mail') && str_contains($mensagem, 'existe');
}

function extrairIdSignatarioAssinafy(array $payload): ?string {
    $candidatos = [
        $payload['id'] ?? null,
        $payload['signer_id'] ?? null,
        $payload['signerId'] ?? null,
        $payload['uuid'] ?? null,
        $payload['data']['id'] ?? null,
        $payload['data']['signer_id'] ?? null,
        $payload['data']['signerId'] ?? null,
    ];

    foreach ($candidatos as $id) {
        if (is_scalar($id) && trim((string)$id) !== '') {
            return (string)$id;
        }
    }

    return null;
}

function buscarSignatarioEmPayload(array $payload, string $email): ?string {
    $email = normalizarEmailAssinafy($email);
    $pilha = [$payload];

    while ($pilha) {
        $atual = array_pop($pilha);

        if (!is_array($atual)) {
            continue;
        }

        $emailAtual = normalizarEmailAssinafy($atual['email'] ?? $atual['email_address'] ?? null);
        if ($emailAtual !== '' && $emailAtual === $email) {
            $id = extrairIdSignatarioAssinafy($atual);
            if ($id) {
                return $id;
            }
        }

        foreach ($atual as $item) {
            if (is_array($item)) {
                $pilha[] = $item;
            }
        }
    }

    return null;
}

function buscarSignatarioExistenteAssinafy(string $accountId, string $email, string $apiKey, string $mode): ?string {
    $emailQuery = rawurlencode($email);
    $endpoints = [
        "/accounts/{$accountId}/signers?email={$emailQuery}",
        "/accounts/{$accountId}/signers?search={$emailQuery}",
        "/accounts/{$accountId}/signers",
    ];

    foreach ($endpoints as $endpoint) {
        try {
            $res = chamarAssinafy($endpoint, 'GET', null, false, $apiKey, $mode);
            $data = json_decode($res, true);
            if (is_array($data)) {
                $id = buscarSignatarioEmPayload($data, $email);
                if ($id) {
                    return $id;
                }
            }
        } catch (Throwable $e) {
            // Tenta o próximo formato de consulta, pois a API pode variar o filtro aceito.
        }
    }

    return null;
}

function criarOuObterSignatarioAssinafy(
    string $accountId,
    array $signatario,
    string $rotulo,
    string $apiKey,
    string $mode
): ?string {
    $email = trim((string)($signatario['email'] ?? ''));
    if ($email === '') {
        return null;
    }

    $payload = [
        'full_name' => trim((string)($signatario['nome'] ?? '')),
        'email' => $email,
        'whatsapp_phone_number' => preg_replace('/\D/', '', $signatario['telefone'] ?? '')
    ];

    try {
        $res = chamarAssinafy("/accounts/{$accountId}/signers", 'POST', $payload, false, $apiKey, $mode);
        $data = json_decode($res, true);
        if (is_array($data)) {
            return extrairIdSignatarioAssinafy($data);
        }
    } catch (Throwable $e) {
        if (erroAssinafyEmailDuplicado($e)) {
            if ($e instanceof AssinafyApiException) {
                $payloadErro = json_decode($e->responseBody, true);
                if (is_array($payloadErro)) {
                    $idResposta = buscarSignatarioEmPayload($payloadErro, $email) ?: extrairIdSignatarioAssinafy($payloadErro);
                    if ($idResposta) {
                        return $idResposta;
                    }
                }
            }

            $idExistente = buscarSignatarioExistenteAssinafy($accountId, $email, $apiKey, $mode);
            if ($idExistente) {
                return $idExistente;
            }

            throw new Exception("A Assinafy informou que o e-mail {$email} ({$rotulo}) já existe, mas não retornou o ID para vincular ao contrato.");
        }

        $mensagem = $e instanceof AssinafyApiException ? mensagemAssinafy($e->responseBody) : $e->getMessage();
        throw new Exception("Falha ao registrar {$rotulo} ({$email}) no Assinafy: {$mensagem}");
    }

    throw new Exception("A Assinafy não retornou o ID do {$rotulo} ({$email}).");
}

function resumirRespostaAssinafy($payload): string {
    $texto = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE);
    $texto = trim((string)$texto);
    if ($texto === '') {
        return 'Resposta vazia.';
    }
    return substr($texto, 0, 800);
}

function extrairIdAssinafy(array $payload): ?string {
    $candidatos = [
        $payload['id'] ?? null,
        $payload['document_id'] ?? null,
        $payload['documentId'] ?? null,
        $payload['uuid'] ?? null,
        $payload['data']['id'] ?? null,
        $payload['data']['document_id'] ?? null,
        $payload['data']['documentId'] ?? null,
        $payload['document']['id'] ?? null,
        $payload['document']['uuid'] ?? null,
    ];

    foreach ($candidatos as $id) {
        if (is_scalar($id) && trim((string)$id) !== '') {
            return (string)$id;
        }
    }

    return null;
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
    if (!is_array($uploadData)) {
        throw new Exception("Resposta de upload invalida do Assinafy: " . resumirRespostaAssinafy($uploadRes));
    }

    $documentId = extrairIdAssinafy($uploadData);
    
    if (!$documentId) {
        throw new Exception("Resposta de upload invalida do Assinafy: " . resumirRespostaAssinafy($uploadData));
    }
    
    // 6. Passo 2 no Assinafy: criar ou reutilizar signatários
    $signerIds = [];

    $sig1Id = criarOuObterSignatarioAssinafy($accountId, $sig1, 'Signatário 1', $apiKey, $mode);
    if ($sig1Id) {
        $signerIds[] = $sig1Id;
    }

    if ($sig2 && !empty($sig2['nome']) && !empty($sig2['email'])) {
        $sig2Id = criarOuObterSignatarioAssinafy($accountId, $sig2, 'Signatário 2', $apiKey, $mode);
        if ($sig2Id) {
            $signerIds[] = $sig2Id;
        }
    }

    if ($sigDistinto && !empty($sigDistinto['nome']) && !empty($sigDistinto['email'])) {
        $sigDistintoId = criarOuObterSignatarioAssinafy($accountId, $sigDistinto, 'Signatário Distinto', $apiKey, $mode);
        if ($sigDistintoId) {
            $signerIds[] = $sigDistintoId;
        }
    }

    $signerIds = array_values(array_unique(array_filter($signerIds)));

    if (empty($signerIds)) {
        throw new Exception('Falha ao registrar ou localizar os signatários no Assinafy.');
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
        $linkAssinatura = "https://app.assinafy.com.br/documento/{$documentId}";
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
