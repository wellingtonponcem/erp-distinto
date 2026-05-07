<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

$dados = lerCorpo();
$mensagens = $dados['mensagens'] ?? [];

if (empty($mensagens)) responderJson(['erro' => 'Sem mensagens'], 400);

$db = Database::get();
$config = $db->query("SELECT groq_api_key, memoria_ia FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
$apiKey = !empty($config['groq_api_key']) ? $config['groq_api_key'] : GROQ_API_KEY;
$memoriaAtual = $config['memoria_ia'] ?? "";

$historicoStr = "";
foreach($mensagens as $m) {
    $historicoStr .= "{$m['role']}: {$m['content']}\n";
}

$prompt = <<<PROMPT
Você é um estrategista de dados e extrator de fatos operacionais.
Abaixo está uma conversa entre um consultor e um dono de agência, além da memória atual de fatos.

SUA TAREFA:
1. Extrair FATOS PERMANENTES (equipamentos, diárias, processos, parceiros) da conversa recente.
2. Atualizar a MEMÓRIA ATUAL consolidando os novos fatos.
3. CRÍTICO: Remova duplicatas. Se um fato já existe ou foi repetido com palavras diferentes, mantenha apenas a versão mais completa.
4. CRÍTICO: Organize por categorias (Equipamentos, Custos, Equipe, Processos).
5. CRÍTICO: Remova prefixos inúteis como "Fatos novos:", "Identifiquei que...", etc. Vá direto ao ponto.
6. Mantenha o texto limpo, profissional e sem redundâncias.

MEMÓRIA ATUAL:
{$memoriaAtual}

CONVERSA RECENTE:
{$historicoStr}

Responda APENAS com o bloco de texto da Memória Consolidada e Organizada, sem comentários adicionais.
PROMPT;

$payload = json_encode([
    'model' => GROQ_MODEL,
    'messages' => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.1
]);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]
]);

// 1. Salvar no histórico (Armazenar tudo) antes de consolidar
try {
    $stmtHist = $db->prepare("INSERT INTO memorias (conteudo, tipo) VALUES (?, 'bruto')");
    $stmtHist->execute(["Conversa memorizada em " . date('d/m/Y H:i') . ":\n" . $historicoStr]);
} catch (Exception $e) {}

$resposta = curl_exec($ch);

$dadosIa = json_decode($resposta, true);
$novaMemoria = $dadosIa['choices'][0]['message']['content'] ?? $memoriaAtual;

// 2. Salvar consolidado no banco principal
$stmt = $db->prepare("UPDATE configuracao_empresa SET memoria_ia = ? WHERE id = 'principal'");
$stmt->execute([$novaMemoria]);

// 3. Salvar versão consolidada no histórico também
try {
    $stmtHist = $db->prepare("INSERT INTO memorias (conteudo, tipo) VALUES (?, 'consolidado')");
    $stmtHist->execute([$novaMemoria]);
} catch (Exception $e) {}

responderJson(['ok' => true, 'memoria' => $novaMemoria]);
