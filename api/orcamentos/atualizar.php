<?php
/**
 * API: Atualizar Orçamento Existente
 */
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();
header('Content-Type: application/json; charset=utf-8');

try {
    $d = lerCorpo();
    $id = $d['id'] ?? '';

    if (empty($id)) {
        responderJson(['erro' => 'ID do orçamento é obrigatório.'], 422);
    }

    $db = Database::get();
    $stmt = $db->prepare("SELECT * FROM orcamentos WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $orcamento = $stmt->fetch();

    if (!$orcamento) {
        responderJson(['erro' => 'Orçamento não encontrado.'], 404);
    }

    $clienteNome = trim($d['cliente_nome'] ?? $orcamento['cliente_nome']);
    $titulo = trim($d['titulo'] ?? $orcamento['titulo']);
    $subtitulo = trim($d['subtitulo'] ?? $orcamento['subtitulo']);
    $tipo = trim($d['tipo'] ?? $orcamento['tipo']);
    $validade = !empty($d['validade']) ? $d['validade'] : $orcamento['validade'];
    $valorTotal = (float) ($d['valor_total'] ?? $orcamento['valor_total']);
    $status = trim($d['status'] ?? $orcamento['status']);

    $dadosJson = isset($d['dados_json']) 
        ? (is_string($d['dados_json']) ? $d['dados_json'] : json_encode($d['dados_json'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) 
        : $orcamento['dados_json'];

    $updateStmt = $db->prepare("UPDATE orcamentos SET cliente_nome = ?, titulo = ?, subtitulo = ?, tipo = ?, validade = ?, valor_total = ?, status = ?, dados_json = ? WHERE id = ?");
    $updateStmt->execute([
        $clienteNome,
        $titulo,
        $subtitulo,
        $tipo,
        $validade,
        $valorTotal,
        $status,
        $dadosJson,
        $id
    ]);

    responderJson([
        'success' => true,
        'mensagem' => 'Orçamento atualizado com sucesso!',
        'id' => $id,
        'slug' => $orcamento['slug']
    ]);

} catch (Exception $e) {
    responderJson(['erro' => 'Erro ao atualizar orçamento: ' . $e->getMessage()], 500);
}
