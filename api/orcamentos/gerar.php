<?php
/**
 * API: Criar Novo Orçamento
 */
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();
header('Content-Type: application/json; charset=utf-8');

try {
    $d = lerCorpo();
    
    $clienteNome = trim($d['cliente_nome'] ?? '');
    $titulo = trim($d['titulo'] ?? '');
    $subtitulo = trim($d['subtitulo'] ?? '');
    $tipo = trim($d['tipo'] ?? 'albuns_15anos');
    $validade = !empty($d['validade']) ? $d['validade'] : date('Y-m-d', strtotime('+30 days'));
    $valorTotal = (float) ($d['valor_total'] ?? 0);
    $dadosJson = is_string($d['dados_json'] ?? null) ? $d['dados_json'] : json_encode($d['dados_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    if (empty($clienteNome) || empty($titulo)) {
        responderJson(['erro' => 'Nome do cliente e título são obrigatórios.'], 422);
    }

    $db = Database::get();

    // Gerar ID e Slug único
    $id = 'orc_' . substr(bin2hex(random_bytes(8)), 0, 12);
    $slugBase = slugify($titulo . '-' . $clienteNome);
    $slug = $slugBase;
    $counter = 1;

    while (true) {
        $check = $db->prepare("SELECT id FROM orcamentos WHERE slug = ? LIMIT 1");
        $check->execute([$slug]);
        if (!$check->fetch()) break;
        $slug = $slugBase . '-' . $counter++;
    }

    $stmt = $db->prepare("INSERT INTO orcamentos (id, cliente_id, cliente_nome, tipo, slug, titulo, subtitulo, validade, dados_json, valor_total, status, created_at) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', CURRENT_TIMESTAMP)");
    $stmt->execute([
        $id,
        $clienteNome,
        $tipo,
        $slug,
        $titulo,
        $subtitulo,
        $validade,
        $dadosJson,
        $valorTotal
    ]);

    responderJson([
        'success' => true,
        'id' => $id,
        'slug' => $slug,
        'url_publica' => rtrim(APP_URL, '/') . '/o/' . $slug
    ]);

} catch (Exception $e) {
    responderJson(['erro' => 'Erro ao criar orçamento: ' . $e->getMessage()], 500);
}
