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
$mensagens = $d['mensagens'] ?? []; // Histórico da conversa

if (!$s || empty($s['nome'])) {
    responderJson(['erro' => 'Dê pelo menos um nome ao serviço'], 422);
}

$db = Database::get();
$configDb = $db->query("SELECT groq_api_key FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
$apiKey = !empty($configDb['groq_api_key']) ? $configDb['groq_api_key'] : (defined('GROQ_API_KEY') ? GROQ_API_KEY : '');

if (!$apiKey) {
    responderJson(['erro' => 'Groq API Key não configurada'], 503);
}

$systemPrompt = <<<PROMPT
Você é um especialista em estruturação de serviços para agências de marketing digital e produção audiovisual.
Seu objetivo é conversar com o usuário para REVER e MELHORAR os detalhes de um serviço.

DADOS ATUAIS DO SERVIÇO:
- Nome: {$s['nome']}
- Descrição: {$s['descricao']}
- Entregáveis: {$s['entregaveis']}

REGRAS CRÍTICAS PARA OS CAMPOS:
1. O NOME do serviço deve ser sempre em MAIÚSCULAS, SIMPLES e DIRETO.
2. A DESCRIÇÃO deve ser profissional e focada em valor.
3. Os ENTREGÁVEIS devem ser listados de forma clara.

FORMATO DE RESPOSTA:
Você deve retornar SEMPRE um objeto JSON contendo:
{
  "mensagem": "Sua resposta textual para o usuário no chat",
  "servico_atualizado": {
    "nome": "NOME EM MAIUSCULO",
    "descricao": "Texto profissional",
    "entregaveis": "Item 1, Item 2..."
  }
}
Se o usuário pedir apenas uma alteração simples, atualize o campo correspondente no objeto e responda no campo 'mensagem'.
PROMPT;

$historicoModel = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($mensagens as $msg) {
    $historicoModel[] = ['role' => $msg['role'], 'content' => $msg['content']];
}

$payload = json_encode([
    'model'      => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama3-70b-8192',
    'messages'   => $historicoModel,
    'response_format' => ['type' => 'json_object'],
    'max_tokens' => 1000,
    'temperature'=> 0.7,
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
$resultado = json_decode($jsonStr, true);

if (empty($resultado['servico_atualizado'])) {
    responderJson(['erro' => 'A IA não conseguiu gerar uma resposta válida'], 500);
}

responderJson([
    'ok' => true,
    'mensagem' => $resultado['mensagem'] ?? 'Serviço atualizado.',
    'nome' => strtoupper($resultado['servico_atualizado']['nome']),
    'descricao' => $resultado['servico_atualizado']['descricao'] ?? '',
    'entregaveis' => $resultado['servico_atualizado']['entregaveis'] ?? ''
]);
