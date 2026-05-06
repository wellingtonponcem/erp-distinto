<?php
/**
 * API: Gerar Proposta
 * Recebe dados do cliente, gera conteúdo via IA e salva no banco.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ia_propostas.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

// Suporte a FormData do navegador
$d = !empty($_POST) ? $_POST : lerCorpo();

if (empty($d['tipo'])) {
    responderJson(['erro' => 'O tipo de serviço é obrigatório.'], 422);
}

$db = Database::get();
$modoCliente = $d['modo_cliente'] ?? 'cadastrado';
$clienteNome = '';
$responsavel = '';
$isPlural = false;

// 1. Identificar Cliente / Lead
if ($modoCliente === 'cadastrado') {
    if (empty($d['cliente_id'])) {
        responderJson(['erro' => 'Selecione um cliente.'], 422);
    }
    $stmtCliente = $db->prepare("SELECT nome FROM clientes WHERE id = ?");
    $stmtCliente->execute([$d['cliente_id']]);
    $cliente = $stmtCliente->fetch();
    if (!$cliente) responderJson(['erro' => 'Cliente não encontrado.'], 404);
    $clienteNome = $cliente['nome'];
    $responsavel = ''; // No banco de clientes o responsável pode variar, mas aqui usamos o nome da empresa como principal
} else {
    if (empty($d['empresa_nome']) || empty($d['responsavel'])) {
        responderJson(['erro' => 'Nome da empresa e responsável são obrigatórios para novos leads.'], 422);
    }
    $clienteNome = $d['empresa_nome'];
    $responsavel = $d['responsavel'];
    
    // Lógica de Pluralização Inteligente
    if (strpos($responsavel, ',') !== false || stripos($responsavel, ' e ') !== false) {
        $isPlural = true;
    }
}

// 2. Buscar Serviços (se houver)
$servicosInclusos = [];
if (!empty($d['servicos']) && is_array($d['servicos'])) {
    foreach ($d['servicos'] as $sid) {
        $stmtS = $db->prepare("SELECT nome, descricao FROM servicos WHERE id = ?");
        $stmtS->execute([$sid]);
        if ($s = $stmtS->fetch()) {
            $servicosInclusos[] = $s;
        }
    }
}

// 3. Gerar Slug Único
$baseSlug = slugify($clienteNome . '-' . $d['tipo'] . '-' . date('dmY'));
$slug = $baseSlug;
$i = 1;
while (true) {
    $stmt = $db->prepare("SELECT id FROM propostas WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    if (!$stmt->fetch()) break;
    $slug = $baseSlug . '-' . $i++;
}

// 4. Gerar Conteúdo via IA
$secoes = [];
$servicosStr = implode(', ', array_column($servicosInclusos, 'nome'));

try {
    $termoResponsavel = $isPlural ? "os responsáveis" : "o responsável";
    $contexto = [
        'cliente' => $clienteNome,
        'responsavel' => $responsavel,
        'termo_responsavel' => $termoResponsavel,
        'detalhes' => $d['briefing'] ?? '',
        'servicos' => $servicosStr
    ];

    if ($d['tipo'] === 'marketing') {
        $secoes['desafio'] = IAPropostas::gerarTextoSecao('marketing', 'desafio', $contexto);
    } elseif ($d['tipo'] === 'casamento') {
        $secoes['visao'] = IAPropostas::gerarTextoSecao('casamento', 'visao', $contexto);
    } elseif ($d['tipo'] === 'filmmaker') {
        $secoes['visao'] = IAPropostas::gerarTextoSecao('filmmaker', 'visao', $contexto);
    }
} catch (Exception $e) {
    $secoes['error'] = "IA Indisponível: " . $e->getMessage();
}

// 5. Salvar no Banco
$id = gerarId();
$dadosJson = json_encode([
    'secoes' => $secoes,
    'servicos' => $servicosInclusos,
    'briefing' => $d['briefing'] ?? '',
    'responsavel' => $responsavel,
    'is_plural' => $isPlural
], JSON_UNESCAPED_UNICODE);

$stmt = $db->prepare("INSERT INTO propostas (id, cliente_nome, tipo, slug, titulo, subtitulo, validade, dados_json, valor_total, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$validade = date('Y-m-d', strtotime('+15 days'));
$titulo = $d['titulo'] ?? ("Proposta Comercial - " . $clienteNome);

$stmt->execute([
    $id,
    $clienteNome,
    $d['tipo'],
    $slug,
    $titulo,
    $d['subtitulo'] ?? '',
    $validade,
    $dadosJson,
    $d['valor_total'] ?? 0.00,
    'rascunho'
]);

responderJson([
    'success' => true,
    'id' => $id,
    'slug' => $slug
], 201);

/**
 * Utilitário de Slug
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}
