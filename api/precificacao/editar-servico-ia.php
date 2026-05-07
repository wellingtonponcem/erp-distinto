<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d = lerCorpo();
$s = $d['servico'] ?? null;

if (!$s || empty($s['nome'])) {
    responderJson(['erro' => 'Dê pelo menos um nome ao serviço'], 422);
}

$db = Database::get();
$configDb = $db->query("SELECT groq_api_key FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
$apiKey = !empty($configDb['groq_api_key']) ? $configDb['groq_api_key'] : (defined('GROQ_API_KEY') ? GROQ_API_KEY : '');

if (!$apiKey) {
    responderJson(['erro' => 'Groq API Key não configurada'], 503);
}

$prompt = <<<PROMPT
Você é um especialista em estruturação de serviços para agências de marketing digital e produção audiovisual.
Seu objetivo é REVER e MELHORAR os detalhes de um serviço para torná-lo mais profissional, atraente e claro para o cliente.

DADOS ATUAIS:
- Nome: {$s['nome']}
- Descrição Atual: {$s['descricao']}
- Entregáveis Atuais: {$s['entregaveis']}

REGRAS CRÍTICAS:
1. O NOME do serviço deve ser sempre em MAIÚSCULAS, SIMPLES e DIRETO (ex: "GESTÃO DE TRÁFEGO", "FILMAGEM CORPORATIVA"). Evite nomes fantasiosos ou "invencionices".
2. A DESCRIÇÃO deve ser profissional, focada em benefícios e valor para o cliente.
3. Os ENTREGÁVEIS devem ser listados de forma clara e organizada (itens separados por vírgula ou ponto).
4. Retorne APENAS um objeto JSON no formato: {"nome": "NOME EM MAIUSCULO", "descricao": "Texto profissional", "entregaveis": "Item 1, Item 2, Item 3"}

PROMPT;

$payload = json_encode([
    'model'      => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama3-70b-8192',
    'messages'   => [['role' => 'user', 'content' => $prompt]],
    'response_format' => ['type' => 'json_object'],
    'max_tokens' => 600,
    'temperature'=> 0.6,
]);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
]);

$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    responderJson(['erro' => 'Erro na API de IA'], 502);
}

$dados = json_decode($resposta, true);
$jsonStr = $dados['choices'][0]['message']['content'] ?? '{}';
$melhoria = json_decode($jsonStr, true);

if (empty($melhoria['nome'])) {
    responderJson(['erro' => 'A IA não conseguiu gerar uma melhoria válida'], 500);
}

responderJson([
    'ok' => true, 
    'nome' => strtoupper($melhoria['nome']), 
    'descricao' => $melhoria['descricao'] ?? '',
    'entregaveis' => $melhoria['entregaveis'] ?? ''
]);
