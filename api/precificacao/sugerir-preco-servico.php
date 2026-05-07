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
$tipoPreco = $d['tipo'] ?? 'recorrente'; // recorrente ou pontual
$totalCustosFixos = $d['totalCustosFixos'] ?? 0;
$horasMensais = $d['horasMensais'] ?? 160;
$precoMinimo = $d['precoMinimo'] ?? 0;

if (!$s || empty($s['nome'])) {
    responderJson(['erro' => 'Dados do serviço incompletos'], 422);
}

$contextoTipo = ($tipoPreco === 'pontual') 
    ? "Este é um PROJETO PONTUAL (Único). O valor DEVE ser significativamente MAIOR (geralmente entre 30% a 60% mais caro) que o valor recorrente equivalente. Justificativa: Não há garantia de receita futura, exige esforço de setup isolado e mobilização imediata de equipe."
    : "Este é um SERVIÇO RECORRENTE (Mensal). O valor deve ser competitivo para garantir a retenção (LTV). Considere que a previsibilidade de caixa permite uma margem ligeiramente menor comparada a um projeto único.";

$db = Database::get();
$configDb = $db->query("SELECT groq_api_key FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
$apiKey = !empty($configDb['groq_api_key']) ? $configDb['groq_api_key'] : (defined('GROQ_API_KEY') ? GROQ_API_KEY : '');

if (!$apiKey) {
    responderJson(['erro' => 'Groq API Key não configurada'], 503);
}

$prompt = <<<PROMPT
Você é um consultor sênior de precificação para agências de marketing e publicidade no Brasil.
Seu objetivo é sugerir um PREÇO DE VENDA estratégico e realista para o serviço descrito abaixo.

ESTRATÉGIA DE NEGÓCIO:
{$contextoTipo}

DADOS DO SERVIÇO:
- Nome: {$s['nome']}
- Descrição: {$s['descricao']}
- Entregáveis: {$s['entregaveis']}
- Ferramentas: {$s['ferramentas']}
- Terceirização: {$s['terceirizacao']}
- periodicidade: {$s['periodicidade']}
- Horas Estimadas: {$s['horas_estimadas']}h
- Custos Diretos: R$ {$s['custo_producao']} + R$ {$s['custos_variaveis']}

DADOS DA AGÊNCIA:
- Custo Fixo Mensal Total: R$ {$totalCustosFixos}
- Capacidade Mensal em Horas: {$horasMensais}h
- PREÇO DE PISO (Mínimo absoluto): R$ {$precoMinimo}

REGRAS OBRIGATÓRIAS:
1. O preço sugerido deve ser um VALOR CHEIO e arredondado (ex: R$ 1.500, R$ 2.900).
2. NUNCA sugira um valor abaixo do PREÇO DE PISO (R$ {$precoMinimo}).
3. Se o tipo for PONTUAL, o valor sugerido deve ser visivelmente superior ao que seria cobrado mensalmente.
4. Retorne APENAS um objeto JSON no formato: {"preco": 2500.00, "markup_sugerido": 45, "justificativa": "Explique por que este valor é ideal considerando a periodicidade"}

PROMPT;

$payload = json_encode([
    'model'      => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama3-70b-8192',
    'messages'   => [['role' => 'user', 'content' => $prompt]],
    'response_format' => ['type' => 'json_object'],
    'max_tokens' => 300,
    'temperature'=> 0.5,
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
$sugestao = json_decode($jsonStr, true);

if (empty($sugestao['preco'])) {
    responderJson(['erro' => 'A IA não conseguiu gerar uma sugestão válida'], 500);
}

responderJson([
    'ok' => true, 
    'preco' => (float)$sugestao['preco'], 
    'markup_sugerido' => (float)($sugestao['markup_sugerido'] ?? 30),
    'justificativa' => $sugestao['justificativa'] ?? ''
]);
