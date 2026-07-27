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
$equipe  = $d['equipe'] ?? '';
$jornada = $d['jornada'] ?? '';
$obs     = $d['obs'] ?? '';

if (!$equipe || !$jornada) {
    responderJson(['erro' => 'Equipe e jornada são obrigatórios'], 422);
}

$db = Database::get();
$configDb = $db->query("SELECT groq_api_key FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
$apiKey = !empty($configDb['groq_api_key']) ? $configDb['groq_api_key'] : (defined('GROQ_API_KEY') ? GROQ_API_KEY : '');

if (!$apiKey) {
    responderJson(['erro' => 'Groq API Key não configurada. Vá em Configurações e salve sua chave.'], 503);
}

$prompt = <<<PROMPT
Você é um especialista em gestão de operações de agências. 
Seu objetivo é calcular a CAPACIDADE MENSAL REAL em horas de uma equipe produtiva.

DADOS:
- Equipe: {$equipe}
- Jornada/Dias: {$jornada}
- Observações de produtividade: {$obs}

REGRAS DE CÁLCULO:
1. Considere 22 dias úteis por mês em média.
2. Aplique um fator de produtividade real (geralmente entre 70% e 80%) para descontar pausas, reuniões internas, idas ao banheiro, etc.
3. Se o usuário informar perda de tempo nas observações, ajuste o fator.
4. Retorne APENAS o número inteiro final (ex: 280). Não use vírgulas, pontos ou símbolos de moeda. Apenas o número puro.
5. Se não tiver certeza, use sua melhor estimativa profissional baseada nos dados. NUNCA diga que não pode calcular. Estime.

EXEMPLO DE SAÍDA:
160
PROMPT;

$systemPrompt = "Você é um robô de cálculo de capacidade. Responda APENAS com o número inteiro de horas mensais. Não escreva textos, justificativas ou explicações. Apenas o número.";

$payload = json_encode([
    'model'      => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama3-70b-8192',
    'messages'   => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $prompt]
    ],
    'max_tokens' => 10,
    'temperature'=> 0,
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
    $errData = json_decode($resposta, true);
    $msg = $errData['error']['message'] ?? 'Erro desconhecido na API de IA';
    responderJson(['erro' => $msg], 502);
}

$dados = json_decode($resposta, true);
$texto = trim($dados['choices'][0]['message']['content'] ?? '');

// Extrair apenas o número
preg_match('/\d+/', $texto, $matches);
if (!isset($matches[0])) {
    responderJson(['erro' => 'A IA não conseguiu determinar um número. Tente ser mais específico nas respostas.'], 422);
}
$horas = (int)$matches[0];

responderJson(['ok' => true, 'horas' => $horas]);
