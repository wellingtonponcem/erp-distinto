<?php
/**
 * API: Resetar Contrato
 * Reverte o status de um contrato enviado/assinado de volta para rascunho.
 * Útil para fins de testes e reenvios.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

try {
    exigirAutenticacao();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJson(['success' => false, 'erro' => 'Método não permitido.'], 405);
    }

    $id = $_POST['id'] ?? '';
    if (!$id) {
        responderJson(['success' => false, 'erro' => 'ID do contrato é obrigatório.'], 422);
    }

    $db = Database::get();

    // 1. Buscar o contrato
    $stmt = $db->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmt->execute([$id]);
    $contrato = $stmt->fetch();

    if (!$contrato) {
        responderJson(['success' => false, 'erro' => 'Contrato não encontrado.'], 404);
    }

    // 2. Atualizar status para rascunho e limpar informações de assinatura
    $stmtUpdate = $db->prepare("
        UPDATE contratos 
        SET status = 'rascunho',
            documento_assinatura_id = NULL,
            link_assinatura = NULL,
            asaas_cobranca_gerada = 0
        WHERE id = ?
    ");
    $stmtUpdate->execute([$id]);

    responderJson([
        'success' => true,
        'mensagem' => 'Contrato revertido para rascunho com sucesso!'
    ]);

} catch (Exception $e) {
    responderJson([
        'success' => false,
        'erro' => 'Erro ao reverter contrato: ' . $e->getMessage()
    ], 500);
}
