<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$dados = lerCorpo();
$mensagens = $dados['mensagens'] ?? [];

if (empty($mensagens)) {
    responderJson(['erro' => 'Histórico de mensagens vazio'], 400);
}

// Migração e Busca de Memória
try {
    $db = Database::get();
    
    // Tabela de configuração
    $stmt = $db->query("SHOW COLUMNS FROM configuracao_empresa LIKE 'memoria_ia'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE configuracao_empresa ADD COLUMN memoria_ia LONGTEXT NULL");
    }

    // Tabela de histórico de memórias (Armazena tudo)
    $db->exec("CREATE TABLE IF NOT EXISTS memorias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conteudo TEXT NOT NULL,
        tipo ENUM('bruto', 'consolidado') DEFAULT 'bruto',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

} catch (Exception $e) {}

$config = $db->query("SELECT groq_api_key, memoria_ia FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
if (!$config) {
    $db->exec("INSERT IGNORE INTO configuracao_empresa (id) VALUES ('principal')");
    $config = ['groq_api_key' => null, 'memoria_ia' => null];
}
$apiKey = !empty($config['groq_api_key']) ? $config['groq_api_key'] : GROQ_API_KEY;
$memoriaAgencia = $config['memoria_ia'] ?? "Ainda não há fatos específicos memorizados sobre equipamentos ou processos.";

$custos = $db->query("SELECT nome, valor, recorrencia FROM custos_fixos WHERE ativo=1")->fetchAll();
$totalCustosFixos = array_reduce($custos, function($carry, $c) {
    return $carry + ($c['recorrencia'] === 'anual' ? $c['valor'] / 12 : $c['valor']);
}, 0);

$custosStr = "";
foreach($custos as $c) {
    $custosStr .= "- {$c['nome']}: R$ " . number_format($c['valor'], 2, ',', '.') . " ({$c['recorrencia']})\n";
}

$systemPrompt = <<<PROMPT
Você é um Consultor de Precificação Estratégica para uma agência de serviços variados (Marketing, Audiovisual, Design, etc.).
Seu objetivo é ajudar o dono da agência a chegar no preço ideal para um serviço específico através de uma conversa consultiva.

CONTEXTO FINANCEIRO DA AGÊNCIA:
- Custo Fixo Mensal Total: R$ {$totalCustosFixos}
- Detalhes dos custos:
{$custosStr}

FATOS E RECURSOS MEMORIZADOS (Use isso para não perguntar novamente):
{$memoriaAgencia}

REGRAS DA CONVERSA:
1. Comece sendo cordial e pergunte qual serviço ele deseja precificar hoje.
2. Uma vez que ele responder o serviço, faça perguntas inteligentes para entender os custos variáveis e a complexidade:
   - Se for Audiovisual, pergunte sobre equipamentos (depreciação), diárias, locação, equipe.
   - Se for Marketing/Design, pergunte sobre horas estimadas, ferramentas específicas, nível de senioridade exigido.
   - Sempre pergunte se haverá terceirização ou custos diretos (anúncios, viagens, materiais).
3. Seja breve em cada interação. Não faça 10 perguntas de uma vez. Faça 2 ou 3 perguntas chave por vez para manter a conversa fluida.
4. Quando tiver informações suficientes, apresente um CÁLCULO SUGERIDO:
   - Baseie o preço na: (Parcela do Custo Fixo + Custos Diretos + Margem de Lucro desejada).
   - Sugira um valor final estratégico (valor cheio/arredondado).
5. Use tom profissional, consultivo e focado em lucratividade. Responda em Português do Brasil e use Markdown.

MEMORIZAÇÃO AUTOMÁTICA:
Se você identificar um fato NOVO e PERMANENTE sobre a agência (equipamentos, diárias, processos, ferramentas), você deve incluí-lo ao final da sua resposta dentro de uma tag <memory>fatos aqui...</memory>. 
Não repita fatos que já estão no contexto de MEMÓRIA ATUAL acima. Se não houver nada novo, não use a tag.
PROMPT;

$payload = json_encode([
    'model' => GROQ_MODEL,
    'messages' => array_merge(
        [['role' => 'system', 'content' => $systemPrompt]],
        $mensagens
    ),
    'temperature' => 0.7,
    'max_tokens' => 1000
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
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    $errRes = json_decode($resposta, true);
    $msg = $errRes['error']['message'] ?? 'Erro desconhecido na API Groq';
    responderJson(['erro' => 'Erro na IA: ' . $msg], 502);
}

$dadosIa = json_decode($resposta, true);
$textoIa = $dadosIa['choices'][0]['message']['content'] ?? 'Desculpe, tive um problema ao processar sua resposta.';

// Processar Memória Automática
$temNovaMemoria = false;
if (preg_match('/<memory>(.*?)<\/memory>/s', $textoIa, $matches)) {
    $novosFatos = trim($matches[1]);
    $textoIa = str_replace($matches[0], '', $textoIa); // Limpar da resposta do usuário
    
    // 1. Salvar no histórico (Armazenar tudo)
    $stmt = $db->prepare("INSERT INTO memorias (conteudo, tipo) VALUES (?, 'bruto')");
    $stmt->execute([$novosFatos]);
    $temNovaMemoria = true;
}

// Responder ao usuário imediatamente
echo json_encode([
    'resposta' => trim($textoIa),
    'memoria' => $memoriaAgencia,
    'otimizando' => $temNovaMemoria
]);

// Se houver nova memória, rodar otimização em "segundo plano"
if ($temNovaMemoria) {
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    // Otimização via IA
    $promptOtimizacao = <<<PROMPT
Você é um organizador de base de conhecimento.
MEMÓRIA ATUAL:
{$memoriaAgencia}

NOVOS FATOS:
{$novosFatos}

TAREFA:
1. Consolide os NOVOS FATOS na MEMÓRIA ATUAL.
2. Remova duplicatas e informações redundantes.
3. Organize por categorias (Equipamentos, Custos, Equipe, Processos).
4. Mantenha o texto limpo e direto ao ponto.
Responda APENAS com a memória final consolidada.
PROMPT;

    $payloadOtimizar = json_encode([
        'model' => GROQ_MODEL,
        'messages' => [['role' => 'user', 'content' => $promptOtimizacao]],
        'temperature' => 0.1
    ]);

    $ch2 = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payloadOtimizar,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]
    ]);

    $resOtimizada = curl_exec($ch2);
    $dadosOtimizados = json_decode($resOtimizada, true);
    $novaMemoriaConsolidada = $dadosOtimizados['choices'][0]['message']['content'] ?? null;

    if ($novaMemoriaConsolidada) {
        $stmt = $db->prepare("UPDATE configuracao_empresa SET memoria_ia = ? WHERE id = 'principal'");
        $stmt->execute([$novaMemoriaConsolidada]);
        
        // Salvar versão consolidada no histórico também
        $stmt = $db->prepare("INSERT INTO memorias (conteudo, tipo) VALUES (?, 'consolidado')");
        $stmt->execute([$novaMemoriaConsolidada]);
    }
}
exit;
