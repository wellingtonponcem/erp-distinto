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

// Ativar captura de erros para retornar JSON sempre
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return;
    responderJson(['erro' => "PHP Error: $message in $file on line $line"], 500);
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        header('Content-Type: application/json');
        echo json_encode(['erro' => "PHP Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']]);
    }
});

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

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
    $responsavel = ''; // Para clientes cadastrados, o responsável é opcional ou fixo
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
        
        // Melhorar Objetivo com IA se fornecido
        if (!empty($d['objetivo'])) {
            $secoes['objetivo'] = IAPropostas::melhorarObjetivo($d['objetivo'], $contexto);
        }
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
    'objetivo_original' => $d['objetivo'] ?? '',
    'data_inicio' => $d['data_inicio'] ?? date('Y-m-d'),
    'responsavel' => $responsavel,
    'is_plural' => $isPlural
], JSON_UNESCAPED_UNICODE);

$stmt = $db->prepare("INSERT INTO propostas (id, cliente_nome, tipo, slug, titulo, subtitulo, validade, dados_json, valor_total, status) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$validade = date('Y-m-d', strtotime('+15 days'));
$tituloOriginal = !empty($d['titulo']) ? $d['titulo'] : ("Proposta Comercial - " . $clienteNome);

// Refinar título via IA para marketing
$titulo = $tituloOriginal;
if ($d['tipo'] === 'marketing') {
    try {
        $tituloIA = IAPropostas::refinarTitulo($tituloOriginal, $servicosStr);
        if ($tituloIA && !str_contains($tituloIA, 'Erro')) {
            $titulo = $tituloIA;
        }
    } catch (Exception $e) {
        // Mantém o original se der erro
    }
}

$valorTotal = !empty($d['valor_total']) ? (float)str_replace(['.', ','], ['', '.'], $d['valor_total']) : 0.00;

$stmt->execute([
    $id,
    $clienteNome,
    $d['tipo'],
    $slug,
    $titulo,
    $d['subtitulo'] ?? '',
    $validade,
    $dadosJson,
    $valorTotal,
    'rascunho'
]);

responderJson([
    'success' => true,
    'id' => $id,
    'slug' => $slug
], 201);

/**
 * Utilitário de Slug (Versão robusta sem iconv)
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = strtolower($text);
    $text = preg_replace('~-+~', '-', $text);
    
    // Mapa de acentos básicos para evitar iconv
    $map = [
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ü' => 'u', 'ç' => 'c',
        'Á' => 'a', 'À' => 'a', 'Ã' => 'a', 'Â' => 'a', 'É' => 'e', 'Ê' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ú' => 'u', 'Ü' => 'u', 'Ç' => 'c'
    ];
    $text = strtr($text, $map);
    $text = preg_replace('~[^-\w]+~', '', $text);
    
    return empty($text) ? 'n-a' : $text;
}
