<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Metodo nao permitido'], 405);
}

$d = lerCorpo();
$slug = $d['slug'] ?? '';
$planoId = $d['plano_id'] ?? '';
$extras = is_array($d['extras'] ?? null) ? $d['extras'] : [];

if (!$slug || !in_array($planoId, ['heritage', 'cinematic', 'essencial'], true)) {
    responderJson(['erro' => 'Dados invalidos'], 422);
}

$db = Database::get();
$stmt = $db->prepare("SELECT * FROM propostas WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$proposta = $stmt->fetch();

if (!$proposta) {
    responderJson(['erro' => 'Proposta nao encontrada'], 404);
}

$dados = json_decode($proposta['dados_json'], true) ?? [];

$mapaValor = [
    'heritage' => 'valor_heritage',
    'cinematic' => 'valor_cinematic',
    'essencial' => 'valor_essencial',
];
$mapaShow = [
    'heritage' => 'show_heritage',
    'cinematic' => 'show_cinematic',
    'essencial' => 'show_essencial',
];

$valorBase = (float)($dados[$mapaValor[$planoId]] ?? 0);
if ($valorBase <= 0) {
    $like = $planoId === 'essencial' ? '%essencial%' : ($planoId === 'cinematic' ? '%cinematic%' : '%heritage%');
    $stmtPkg = $db->prepare("SELECT preco_venda FROM servicos WHERE categoria = 'wedding' AND tipo = 'plano' AND LOWER(nome) LIKE ? AND ativo = 1 LIMIT 1");
    $stmtPkg->execute([$like]);
    $valorBase = (float)($stmtPkg->fetchColumn() ?: 0);
}

$total = $valorBase;
$itensSelecionados = [];

if (in_array('boudoir_static', $extras, true)) {
    $total += (float)($dados['valor_boudoir'] ?: 500);
    $itensSelecionados[] = 'Boudoir da Noiva';
}
if (in_array('prewedding_static', $extras, true)) {
    $total += (float)($dados['valor_prewedding'] ?: 1100);
    $itensSelecionados[] = 'Ensaio Pre-Wedding';
}

$extrasDinamicos = array_values(array_filter($extras, fn($id) => !str_ends_with((string)$id, '_static')));
if (!empty($extrasDinamicos)) {
    $placeholders = implode(',', array_fill(0, count($extrasDinamicos), '?'));
    $stmtExtras = $db->prepare("SELECT id, nome, preco_venda FROM servicos WHERE id IN ($placeholders) AND categoria = 'wedding' AND ativo = 1");
    $stmtExtras->execute($extrasDinamicos);
    foreach ($stmtExtras->fetchAll() as $extra) {
        $total += (float)$extra['preco_venda'];
        $itensSelecionados[] = $extra['nome'];
    }
}

foreach ($mapaShow as $id => $campo) {
    $dados[$campo] = $id === $planoId;
}

foreach (['heritage', 'cinematic', 'essencial'] as $pkg) {
    $dados["include_boudoir_{$pkg}"] = $pkg === $planoId && in_array('boudoir_static', $extras, true);
    $dados["include_prewedding_{$pkg}"] = $pkg === $planoId && in_array('prewedding_static', $extras, true);
}

$dados['upgrades'][$planoId] = $dados['upgrades'][$planoId] ?? [];
foreach ($extrasDinamicos as $extraId) {
    $dados['upgrades'][$planoId][$extraId] = true;
}

$dados['cliente_escolha'] = [
    'plano_id' => $planoId,
    'extras' => $extras,
    'itens_selecionados' => $itensSelecionados,
    'valor_total' => $total,
    'condicoes' => $d['condicoes'] ?? '',
    'selecionado_em' => date('Y-m-d H:i:s'),
];

// Atualizar o andamento da proposta no dados_json de forma automática
$dataAtual = date('d/m/Y H:i');
$nomePlano = $planoId === 'heritage' ? 'Experiência Heritage' : ($planoId === 'cinematic' ? 'Experiência Cinematic' : 'Registro Essencial');
$novaLinhaAndamento = "{$dataAtual} | Cliente selecionou o plano: {$nomePlano}";
if (!empty($itensSelecionados)) {
    $novaLinhaAndamento .= " com upgrades (" . implode(', ', $itensSelecionados) . ")";
}
$novaLinhaAndamento .= " | Investimento: " . formatarMoeda($total) . " | Escolha realizada via proposta web";

$andamentoAtual = $dados['andamento_proposta'] ?? '';
if (trim($andamentoAtual) !== '') {
    $dados['andamento_proposta'] = trim($andamentoAtual) . "\n" . $novaLinhaAndamento;
} else {
    $dados['andamento_proposta'] = $novaLinhaAndamento;
}

$stmtUpdate = $db->prepare("UPDATE propostas SET dados_json = ?, valor_total = ?, status = 'pendente' WHERE id = ?");
$stmtUpdate->execute([json_encode($dados, JSON_UNESCAPED_UNICODE), $total, $proposta['id']]);

try {
    $db->exec("CREATE TABLE IF NOT EXISTS propostas_historico (
        id SERIAL PRIMARY KEY,
        proposta_id TEXT NOT NULL,
        user_id TEXT NOT NULL,
        tipo TEXT DEFAULT 'nota',
        conteudo TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $conteudo = "Cliente escolheu o plano {$planoId} com investimento total de " . formatarMoeda($total) . ".";
    $stmtHist = $db->prepare("INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo) VALUES (?, ?, ?, ?)");
    $stmtHist->execute([$proposta['id'], 'publico', 'escolha_cliente', $conteudo]);
} catch (Exception $e) {
    // O registro da escolha na proposta ja foi feito; historico e complementar.
}

responderJson(['success' => true, 'valor_total' => $total]);
