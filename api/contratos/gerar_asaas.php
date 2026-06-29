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
    $stmt = $db->prepare("SELECT id, status, asaas_cobranca_gerada, cliente_id, titulo FROM contratos WHERE id = ?");
    $stmt->execute([$id]);
    $contrato = $stmt->fetch();

    if (!$contrato) {
        responderJson(['success' => false, 'erro' => 'Contrato não encontrado.'], 404);
    }

    if ($contrato['status'] !== 'assinado') {
        responderJson(['success' => false, 'erro' => 'Apenas contratos no status "Assinado" podem ter cobranças automáticas geradas no Asaas.'], 400);
    }

    $regularizarSemLancamentos = false;
    if ((int)$contrato['asaas_cobranca_gerada'] === 1) {
        $stmtLanc = $db->prepare("SELECT id FROM lancamentos WHERE cliente_id = ? AND (descricao LIKE ? OR observacao LIKE ?) LIMIT 1");
        $stmtLanc->execute([
            $contrato['cliente_id'],
            '%' . $contrato['titulo'] . '%',
            '%Contrato: ' . $contrato['id'] . '%'
        ]);

        if ($stmtLanc->fetch()) {
            responderJson(['success' => false, 'erro' => 'A cobrança/financeiro deste contrato já foi gerado anteriormente.'], 400);
        }

        $regularizarSemLancamentos = true;
    }
    $entradaStatus = $_POST['entrada_status'] ?? '';
    $opcoesFaturamento = [
        'asaas_billing_type' => $_POST['asaas_billing_type'] ?? null,
        'asaas_total_parcelas' => isset($_POST['asaas_total_parcelas']) ? max(1, (int)$_POST['asaas_total_parcelas']) : null,
        'asaas_first_due_date' => $_POST['asaas_first_due_date'] ?? null,
        'asaas_valor_sinal' => isset($_POST['asaas_valor_sinal']) ? decimalContratoFormulario($_POST['asaas_valor_sinal']) : null,
        'asaas_sinal_vencimento' => $_POST['asaas_sinal_vencimento'] ?? null,
        'entrada_status' => in_array($entradaStatus, ['pago', 'pendente', 'nao_aplica'], true) ? $entradaStatus : null,
        'entrada_forma_pagamento' => $_POST['entrada_forma_pagamento'] ?? null,
        'entrada_conta' => $_POST['entrada_conta'] ?? null,
        'entrada_observacao' => $_POST['entrada_observacao'] ?? null,
        'gerar_apenas_saldo' => !empty($_POST['gerar_apenas_saldo']) ? 1 : 0,
        'ignorar_flag_asaas' => $regularizarSemLancamentos ? 1 : null,
    ];
    $opcoesFaturamento = array_filter($opcoesFaturamento, static fn($valor) => $valor !== null && $valor !== '');

    // Processa a assinatura e faturamento no Asaas
    $res = processarAssinaturaContrato($id, $opcoesFaturamento);

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

function decimalContratoFormulario($valor): float {
    if (is_numeric($valor)) {
        return (float)$valor;
    }

    $valor = preg_replace('/[^\d,.-]/', '', (string)$valor);
    if (str_contains($valor, ',') && str_contains($valor, '.')) {
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);
    } else {
        $valor = str_replace(',', '.', $valor);
    }

    return round((float)$valor, 2);
}
