<?php
/**
 * API: Gerar Mensagem de WhatsApp para Envio de Orçamento
 */
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();
header('Content-Type: application/json; charset=utf-8');

$d = lerCorpo();
$id = $d['id'] ?? '';

if (empty($id)) {
    responderJson(['erro' => 'ID do orçamento obrigatório.'], 422);
}

$db = Database::get();
$stmt = $db->prepare("SELECT * FROM orcamentos WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$orcamento = $stmt->fetch();

if (!$orcamento) {
    responderJson(['erro' => 'Orçamento não encontrado.'], 404);
}

$cliente  = $orcamento['cliente_nome'];
$titulo   = $orcamento['titulo'];
$subtitulo = $orcamento['subtitulo'] ?? '';
$link     = rtrim(APP_URL, '/') . '/o/' . $orcamento['slug'];
$primeiroNome = explode(' ', trim($cliente))[0];

// IA Groq
$apiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
if (!$apiKey) {
    $msg = "Oi, {$primeiroNome}! Tudo bem?\n\nPreparei o seu *{$titulo}* com todas as opções de acabamentos e detalhes exatamente como conversamos.\n\nVocê pode visualizar e escolher sua coleção diretamente neste link exclusivo:\n👉 {$link}\n\nFico à disposição para qualquer dúvida!";
    responderJson(['mensagem' => $msg]);
    exit;
}

$prompt = <<<PROMPT
Você é um consultor comercial da agência de fotografia e design de luxo Distinto.
Escreva uma mensagem de WhatsApp curta, elegante e descontraída para enviar ao cliente *{$cliente}* apresentando o seu orçamento comercial.

Tom: próximo, profissional, elegante, entusiasmado.
NÃO use "prezado", "segue em anexo", "cordialmente" ou linguagem ultrapassada.
Use o primeiro nome do cliente: {$primeiroNome}.

Estrutura:
1. Saudação simpática (uma linha)
2. Apresentar que o orçamento está pronto e destacar que ele pode interagir e simular as opções de coleções diretamente na página.
3. Call to action com a tag exata [LINK]
4. Fechamento cordial.

Onde deve entrar a URL, escreva rigorosamente [LINK]. Responda APENAS com o texto final.
PROMPT;

$payload = json_encode([
    'model'       => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.3-70b-versatile',
    'messages'    => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.7,
    'max_tokens'  => 300,
]);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 10,
]);

$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $msg = "Oi, {$primeiroNome}! Tudo bem?\n\nPreparei o seu *{$titulo}* com todas as opções de acabamentos e detalhes de coleções.\n\nVocê pode visualizar e simular sua coleção direto por este link:\n👉 {$link}\n\nQualquer dúvida estou por aqui!";
    responderJson(['mensagem' => $msg]);
    exit;
}

$result = json_decode($resposta, true);
$mensagemIA = trim($result['choices'][0]['message']['content'] ?? '');

if (!$mensagemIA) {
    $mensagemIA = "Oi, {$primeiroNome}! Tudo bem?\n\nPreparei o seu *{$titulo}* com todas as opções de acabamentos e coleções.\n\nVisualizar orçamento:\n👉 {$link}";
}

$mensagemFinal = str_replace('[LINK]', "👉 {$link}", $mensagemIA);
responderJson(['mensagem' => $mensagemFinal]);
