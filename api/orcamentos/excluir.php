<?php
/**
 * API: Excluir Orçamento
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
    $stmt = $db->prepare("DELETE FROM orcamentos WHERE id = ?");
    $stmt->execute([$id]);

    responderJson([
        'success' => true,
        'mensagem' => 'Orçamento excluído com sucesso!'
    ]);

} catch (Exception $e) {
    responderJson(['erro' => 'Erro ao excluir orçamento: ' . $e->getMessage()], 500);
}
