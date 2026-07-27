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
$config = $db->query("SELECT groq_api_key FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
$apiKey = !empty($config['groq_api_key']) ? $config['groq_api_key'] : GROQ_API_KEY;

$historicoStr = "";
foreach($mensagens as $m) {
    $historicoStr .= "{$m['role']}: {$m['content']}\n";
}

$prompt = <<<PROMPT
Você é um assistente que extrai dados estruturados de conversas de precificação.
Abaixo está uma conversa onde um serviço foi precificado.
Sua tarefa é extrair os detalhes para criar um novo registro no banco de dados.

CONVERSA:
{$historicoStr}

INSTRUÇÕES:
Retorne APENAS um objeto JSON com os seguintes campos (não inclua explicações):
- nome: Nome curto do serviço.
- descricao: Descrição comercial resumida.
- entregaveis: Lista de itens que serão entregues (separados por vírgula).
- horas_estimadas: Total de horas estimadas (número).
- preco_venda: O valor final sugerido (número, use apenas o valor numérico, sem R$).
- periodicidade: 'mensal' ou 'pontual'.

Se algum dado não estiver claro, faça sua melhor estimativa baseada no contexto.
PROMPT;

$payload = json_encode([
    'model' => GROQ_MODEL,
    'messages' => [['role' => 'user', 'content' => $prompt]],
    'temperature' => 0.1,
    'response_format' => ['type' => 'json_object']
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

$resposta = curl_exec($ch);
curl_close($ch);

$dadosIa = json_decode($resposta, true);
$jsonStr = $dadosIa['choices'][0]['message']['content'] ?? '';
$info = json_decode($jsonStr, true);

if (!$info || empty($info['nome'])) {
    responderJson(['erro' => 'IA não conseguiu extrair os dados do serviço.'], 502);
}

// Inserir no banco
try {
    $id = gerarId();
    $stmt = $db->prepare("INSERT INTO servicos (id, nome, descricao, entregaveis, horas_estimadas, periodicidade, ativo, criado_em) VALUES (?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)");
    // Nota: A tabela servicos não tem preco_venda direto em alguns schemas antigos, mas o usuário pediu para salvar o preço.
    // Vamos verificar se a coluna existe, senão salvamos na descrição ou apenas as horas.
    // Baseado no arquivo servicos.php anterior, o preço é calculado. 
    // Mas o usuário quer salvar o preço. Vou garantir que a coluna existe.
    
    $stmtCols = $db->query("SHOW COLUMNS FROM servicos LIKE 'preco_venda'");
    if (!$stmtCols->fetch()) {
        $db->exec("ALTER TABLE servicos ADD COLUMN preco_venda DECIMAL(15,2) DEFAULT 0");
    }

    $stmt = $db->prepare("INSERT INTO servicos (id, nome, descricao, entregaveis, horas_estimadas, periodicidade, preco_venda, ativo, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)");
    $stmt->execute([
        $id,
        $info['nome'],
        $info['descricao'],
        $info['entregaveis'],
        (int)$info['horas_estimadas'],
        $info['periodicidade'],
        (float)$info['preco_venda']
    ]);

    responderJson(['ok' => true, 'id' => $id]);
} catch (Exception $e) {
    responderJson(['erro' => 'Erro ao salvar no banco: ' . $e->getMessage()], 500);
}
