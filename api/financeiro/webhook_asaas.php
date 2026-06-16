<?php
/**
 * Webhook: Integração de Retorno Asaas
 * Recebe atualizações de cobranças (pagamentos, vencimentos) em tempo real e atualiza o financeiro local.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/financeiro_custos.php';

// Definir cabeçalho de resposta rápida
header('Content-Type: application/json');

$d = lerCorpo();

try {
    $db = Database::get();
    garantirEstruturaFinanceira($db);

    // Garantir tabela de logs de webhook existe
    $db->exec("CREATE TABLE IF NOT EXISTS log_webhooks_asaas (
        id " . ((defined('DB_PORT') && (int)DB_PORT === 3306) ? "INT AUTO_INCREMENT PRIMARY KEY" : "INTEGER PRIMARY KEY AUTOINCREMENT") . ",
        evento VARCHAR(100) NULL,
        payload TEXT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $eventoRecebido = $d['event'] ?? 'unknown';
    
    // Grava log
    $stmtLog = $db->prepare("INSERT INTO log_webhooks_asaas (evento, payload) VALUES (?, ?)");
    $stmtLog->execute([$eventoRecebido, json_encode($d, JSON_UNESCAPED_UNICODE)]);

    // 1. Validar Token de Segurança do Webhook
    // O Asaas envia o token de segurança configurado no header 'asaas-access-token' ou no header HTTP_ASAAS_ACCESS_TOKEN
    $headers = getallheaders();
    $tokenEnviado = $headers['asaas-access-token'] ?? $_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? '';

    $stmtConfig = $db->query("SELECT asaas_webhook_token FROM configuracao_empresa WHERE id = 'principal' LIMIT 1");
    $webhookToken = $stmtConfig->fetchColumn();

    if (!empty($webhookToken) && $tokenEnviado !== $webhookToken) {
        http_response_code(401);
        echo json_encode(['erro' => 'Token do webhook inválido ou não autorizado.']);
        exit;
    }

    $payment = $d['payment'] ?? null;
    if (!$payment || empty($payment['id'])) {
        echo json_encode(['success' => true, 'mensagem' => 'Evento sem dados de pagamento.']);
        exit;
    }

    $asaasId = $payment['id'];
    $externalReference = $payment['externalReference'] ?? '';
    
    // Buscar lançamento pelo asaas_id
    $stmtL = $db->prepare("SELECT * FROM lancamentos WHERE asaas_id = ? LIMIT 1");
    $stmtL->execute([$asaasId]);
    $lancamento = $stmtL->fetch();

    // Se não achou pelo asaas_id, mas tem externalReference, tenta buscar pelo ID do lançamento
    if (!$lancamento && !empty($externalReference)) {
        $stmtL2 = $db->prepare("SELECT * FROM lancamentos WHERE id = ? LIMIT 1");
        $stmtL2->execute([$externalReference]);
        $lancamento = $stmtL2->fetch();
    }

    if (!$lancamento) {
        // Retorna 200 para que o Asaas não tente reenviar o webhook indefinidamente
        echo json_encode(['success' => true, 'mensagem' => 'Cobrança não rastreada pelo ERP local. Log salvo.']);
        exit;
    }

    $statusAsaas = strtolower($payment['status'] ?? '');
    $novoStatus = null;
    $valorPago = (float)($payment['value'] ?? $lancamento['valor']);
    $dataPagamento = null;

    if ($eventoRecebido === 'PAYMENT_RECEIVED' || $eventoRecebido === 'PAYMENT_CONFIRMED' || $statusAsaas === 'received' || $statusAsaas === 'confirmed') {
        $novoStatus = 'pago';
        $valorPago = (float)($payment['value'] ?? $lancamento['valor']);
        $dataPagamento = $payment['paymentDate'] ?? $payment['clientPaymentDate'] ?? date('Y-m-d');
    } elseif ($eventoRecebido === 'PAYMENT_OVERDUE' || $statusAsaas === 'overdue') {
        $novoStatus = 'atrasado';
    } elseif ($eventoRecebido === 'PAYMENT_DELETED' || $statusAsaas === 'deleted') {
        $novoStatus = 'cancelado';
    }

    if ($novoStatus) {
        $sets = ["status = ?"];
        $params = [$novoStatus];

        if ($novoStatus === 'pago') {
            $sets[] = "valor_pago = ?";
            $params[] = $valorPago;
            
            $sets[] = "data_pagamento = ?";
            $params[] = $dataPagamento;
        }

        // Se ainda não estava preenchido o asaas_id, vincula
        if (empty($lancamento['asaas_id'])) {
            $sets[] = "asaas_id = ?";
            $params[] = $asaasId;
        }

        $params[] = $lancamento['id'];

        $stmtUp = $db->prepare("UPDATE lancamentos SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmtUp->execute($params);

        echo json_encode([
            'success' => true, 
            'mensagem' => "Lançamento {$lancamento['id']} atualizado para status {$novoStatus} com sucesso."
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'mensagem' => 'Evento de webhook processado, mas nenhuma transição de status local ocorreu.'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}

// Fallback do helper getallheaders para servidores Nginx/FPM
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
