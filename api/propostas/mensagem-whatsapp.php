<?php
/**
 * API: Gerar mensagem de WhatsApp para envio de proposta
 * Usa Groq para criar uma mensagem personalizada com base nos dados da proposta.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return;
    responderJson(['erro' => "PHP Error: $message"], 500);
});

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d = !empty($_POST) ? $_POST : lerCorpo();

if (empty($d['id'])) {
    responderJson(['erro' => 'ID da proposta obrigatório.'], 422);
}

$db = Database::get();
$stmt = $db->prepare("SELECT * FROM propostas WHERE id = ? LIMIT 1");
$stmt->execute([$d['id']]);
$proposta = $stmt->fetch();

if (!$proposta) {
    responderJson(['erro' => 'Proposta não encontrada.'], 404);
}

$dados    = json_decode($proposta['dados_json'], true) ?? [];
$cliente  = $proposta['cliente_nome'];
$titulo   = $proposta['titulo'];
$subtitulo = $proposta['subtitulo'] ?? '';
$link     = rtrim(APP_URL, '/') . '/p/' . $proposta['slug'];

// Montar resumo dos serviços
$servicos = $dados['servicos'] ?? [];
$nomesServicos = array_map(fn($s) => $s['nome'] ?? '', $servicos);
$nomesServicos = array_filter($nomesServicos);
$resumoServicos = implode(', ', $nomesServicos);

// Briefing e objetivo, se existirem
$briefing  = $dados['briefing'] ?? '';
$objetivo  = $dados['objetivo_original'] ?? '';
$meses     = $dados['meses_contrato'] ?? 12;

// Primeiro nome do cliente
$primeiroNome = explode(' ', trim($cliente))[0];

// --- Chamar Groq ---
$apiKey = GROQ_API_KEY;
if (!$apiKey) {
    // Fallback sem IA
    $mensagem = "Oi, {$primeiroNome}! Tudo bem?\n\nAcabei de subir o material do {$titulo} aqui no sistema. Deixei tudo bem visual pra você conseguir enxergar o projeto ganhando forma, exatamente como a gente conversou.\n\nDá uma olhada aqui:\n👉 {$link}";
    responderJson(['mensagem' => $mensagem]);
    exit;
}

$contexto = "Proposta: {$titulo}" .
    ($subtitulo ? " — {$subtitulo}" : '') .
    ($resumoServicos ? "\nServiços: {$resumoServicos}" : '') .
    ($objetivo ? "\nObjetivo do cliente: {$objetivo}" : '') .
    ($briefing ? "\nBriefing: {$briefing}" : '') .
    "\nDuração do contrato: {$meses} meses";

$prompt = <<<PROMPT
Você é um estrategista de comunicação de uma agência de marketing premium brasileira chamada Distinto.
Escreva uma mensagem de WhatsApp curta e natural para enviar ao cliente *{$cliente}* comunicando que a proposta comercial está pronta.

Tom: descontraído, próximo, confiante — como um amigo profissional que quer que o cliente se anime com o projeto.
NÃO use "prezado", "segue em anexo", "cordialmente" ou linguagem corporativa.
NÃO invente informações que não estejam no contexto.
Use o primeiro nome do cliente: {$primeiroNome}.

Estrutura obrigatória:
1. Saudação informal (uma linha só)
2. Anunciar que a proposta está pronta — transmitir empolgação genuína com o projeto, conectando com o que foi conversado (use o contexto abaixo)
3. Uma linha de call to action com o link (já incluído no placeholder [LINK])
4. Fechamento curto e caloroso (uma linha)

Contexto da proposta:
{$contexto}

IMPORTANTE: onde deve aparecer o link, escreva exatamente o placeholder [LINK] — sem alterar, sem colocar URL real.
Responda APENAS com o texto da mensagem, sem explicações, sem aspas, sem markdown.
PROMPT;

$payload = json_encode([
    'model'       => GROQ_MODEL,
    'messages'    => [
        ['role' => 'user', 'content' => $prompt]
    ],
    'temperature' => 0.75,
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
    CURLOPT_TIMEOUT => 15,
]);

$resposta = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    // Fallback se IA falhar
    $mensagem = "Oi, {$primeiroNome}! Tudo bem?\n\nAcabei de subir o material do {$titulo} aqui no sistema. Deixei tudo bem visual pra você conseguir enxergar o projeto ganhando forma, exatamente como a gente conversou.\n\nDá uma olhada aqui:\n👉 {$link}";
    responderJson(['mensagem' => $mensagem]);
    exit;
}

$result = json_decode($resposta, true);
$mensagemIA = trim($result['choices'][0]['message']['content'] ?? '');

if (!$mensagemIA) {
    $mensagemIA = "Oi, {$primeiroNome}! Tudo bem?\n\nAcabei de subir o material do {$titulo} aqui no sistema. Deixei tudo bem visual pra você conseguir enxergar o projeto ganhando forma, exatamente como a gente conversou.\n\nDá uma olhada aqui:\n👉 {$link}";
}

// Substituir placeholder pelo link real
$mensagemFinal = str_replace('[LINK]', "👉 {$link}", $mensagemIA);

responderJson(['mensagem' => $mensagemFinal]);
