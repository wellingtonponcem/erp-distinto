<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ia_roteiros.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$dados = lerCorpo();
$imagemBase64 = $dados['imagem'] ?? null; // Espera base64 com prefixo data:image/...

if (!$imagemBase64) {
    responderJson(['erro' => 'Nenhuma imagem fornecida'], 400);
}

// Extrair mime type e base64 real
if (preg_match('/^data:(image\/[a-z]+);base64,(.*)$/', $imagemBase64, $matches)) {
    $mimeType = $matches[1];
    $base64Data = $matches[2];
} else {
    // Tenta tratar como base64 puro se falhar o regex
    $mimeType = 'image/jpeg';
    $base64Data = $imagemBase64;
}

$prompt = "Analise este comprovante, nota fiscal ou boleto e extraia os dados para um lançamento financeiro. 
Responda APENAS um objeto JSON válido, sem markdown, com os seguintes campos:
- tipo: 'receber' (se for entrada de dinheiro/recebimento) ou 'pagar' (se for saída/pagamento/boleto/nota fiscal de compra)
- descricao: uma descrição curta do que se trata (ex: 'Pagamento de Energia', 'Venda de Produto X')
- valor: número (float)
- vencimento: data no formato YYYY-MM-DD
- entidade_nome: nome da empresa ou pessoa (cliente ou fornecedor)
- entidade_documento: CPF ou CNPJ (apenas números)
- categoria: uma sugestão curta (ex: 'impostos', 'serviços', 'folha', 'aluguel', 'marketing', 'outros')

Tente ser o mais preciso possível. Se não encontrar algum dado, deixe null.";

try {
    $respostaIa = IARoteiros::chamarGemini([
        ['text' => $prompt],
        ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64Data]]
    ], 'gemini-2.0-flash');

    // Limpar markdown se a IA ignorar a instrução de "APENAS JSON"
    $jsonLimpo = preg_replace('/```json\n?|\n?```/', '', $respostaIa);
    $dadosExtraidos = json_decode(trim($jsonLimpo), true);

    if (!$dadosExtraidos) {
        responderJson(['erro' => 'Não foi possível interpretar a resposta da IA', 'raw' => $respostaIa], 500);
    }

    // Processar CNPJ se encontrado
    if (!empty($dadosExtraidos['entidade_documento'])) {
        $doc = preg_replace('/\D/', '', $dadosExtraidos['entidade_documento']);
        if (strlen($doc) === 14) {
            $dadosExtraidos['receita_federal'] = consultarCnpj($doc);
        }
    }

    responderJson($dadosExtraidos);

} catch (Throwable $e) {
    responderJson(['erro' => 'Erro ao processar imagem: ' . $e->getMessage()], 500);
}

/**
 * Consulta CNPJ na BrasilAPI (gratuito)
 */
function consultarCnpj(string $cnpj): ?array {
    $ch = curl_init("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($res, true);
        return [
            'razao_social' => $data['razao_social'] ?? null,
            'nome_fantasia' => $data['nome_fantasia'] ?? null,
            'logradouro' => $data['logradouro'] ?? null,
            'numero' => $data['numero'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'municipio' => $data['municipio'] ?? null,
            'uf' => $data['uf'] ?? null,
            'cep' => $data['cep'] ?? null
        ];
    }
    return null;
}
