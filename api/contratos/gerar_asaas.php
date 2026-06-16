<?php
/**
 * API: Gerar Faturamento do Contrato no Asaas manualmente
 * Recebe o ID do contrato e chama a lógica de processamento de assinatura/faturamento.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/contratos.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['success' => false, 'erro' => 'Método não permitido'], 405);
}

$id = $_POST['id'] ?? '';

if (!$id) {
    responderJson(['success' => false, 'erro' => 'ID do contrato é obrigatório.'], 422);
}

try {
    $db = Database::get();

    // Busca contrato para ver status e se já foi faturado
    $stmt = $db->prepare("SELECT status, asaas_cobranca_gerada FROM contratos WHERE id = ?");
    $stmt->execute([$id]);
    $contrato = $stmt->fetch();

    if (!$contrato) {
        responderJson(['success' => false, 'erro' => 'Contrato não encontrado.'], 404);
    }

    if ($contrato['status'] !== 'assinado') {
        responderJson(['success' => false, 'erro' => 'Apenas contratos no status "Assinado" podem ter cobranças automáticas geradas no Asaas.'], 400);
    }

    if ((int)$contrato['asaas_cobranca_gerada'] === 1) {
        responderJson(['success' => false, 'erro' => 'A cobrança do Asaas para este contrato já foi gerada anteriormente.'], 400);
    }

    // Processa a assinatura e faturamento no Asaas
    $res = processarAssinaturaContrato($id);

    if ($res['success']) {
        if ($res['asaas_cobranca']) {
            responderJson([
                'success' => true,
                'mensagem' => 'Cobranças geradas com sucesso no Asaas e integradas no financeiro!'
            ]);
        } else {
            $erroMsg = !empty($res['erros']) ? implode(', ', $res['erros']) : 'O Asaas não está ativo ou não configurado.';
            responderJson([
                'success' => false,
                'erro' => 'Não foi possível gerar a cobrança: ' . $erroMsg
            ]);
        }
    } else {
        responderJson([
            'success' => false,
            'erro' => $res['erro'] ?? 'Erro desconhecido ao processar faturamento.'
        ]);
    }

} catch (Exception $e) {
    responderJson(['success' => false, 'erro' => 'Erro interno do servidor: ' . $e->getMessage()], 500);
}
