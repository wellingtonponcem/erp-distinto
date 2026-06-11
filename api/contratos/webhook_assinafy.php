<?php
/**
 * Webhook: Integração de Retorno Assinafy
 * Recebe atualizações de status de documentos em tempo real e atualiza o banco de dados local.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Definir cabeçalho de resposta rápida para o webhook
header('Content-Type: application/json');

$d = lerCorpo();

// Logger de Webhook para depuração/rastreamento
try {
    $db = Database::get();
    
    // Garantir tabela de logs de webhook existe
    $db->exec("CREATE TABLE IF NOT EXISTS log_webhooks_assinafy (
        id " . ((defined('DB_PORT') && (int)DB_PORT === 3306) ? "INT AUTO_INCREMENT PRIMARY KEY" : "INTEGER PRIMARY KEY AUTOINCREMENT") . ",
        evento VARCHAR(100) NULL,
        payload TEXT NULL,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $eventoRecebido = $d['event'] ?? $d['status'] ?? 'unknown';
    $stmtLog = $db->prepare("INSERT INTO log_webhooks_assinafy (evento, payload) VALUES (?, ?)");
    $stmtLog->execute([$eventoRecebido, json_encode($d, JSON_UNESCAPED_UNICODE)]);
    
} catch (Exception $e) {
    // Silencioso para não travar a resposta do webhook se o log falhar
}

// Extrair ID do documento do Assinafy do payload
$documentId = $d['document_id']
    ?? $d['documentId']
    ?? $d['document']['id']
    ?? $d['data']['document_id']
    ?? $d['data']['documentId']
    ?? $d['data']['document']['id']
    ?? $d['assignment']['document_id']
    ?? $d['assignment']['document']['id']
    ?? $d['id']
    ?? '';
$event = $d['event'] ?? '';
$status = $d['status'] ?? '';
$eventNormalizado = strtolower((string)$event);
$statusNormalizado = strtolower((string)$status);

if (!$documentId) {
    echo json_encode(['erro' => 'Document ID não fornecido no payload'], 400);
    exit;
}

try {
    // Buscar se o contrato existe no ERP
    $stmtC = $db->prepare("SELECT * FROM contratos WHERE documento_assinatura_id = ?");
    $stmtC->execute([$documentId]);
    $contrato = $stmtC->fetch();
    
    if (!$contrato) {
        // Retorna status 200 para que o Assinafy não fique repetindo a requisição desnecessariamente
        echo json_encode(['success' => true, 'mensagem' => 'Documento não rastreado por este ERP. Log salvo.']);
        exit;
    }
    
$novoStatus = null;
    $mensagemHistorico = '';
    
    // Normalizar eventos de status de assinatura.
    if (
        str_contains($eventNormalizado, 'document_ready') ||
        str_contains($eventNormalizado, 'completed') ||
        str_contains($eventNormalizado, 'signed') ||
        str_contains($eventNormalizado, 'signer_signed') ||
        str_contains($eventNormalizado, 'all_signers_signed') ||
        $statusNormalizado === 'completed' ||
        $statusNormalizado === 'signed' ||
        $statusNormalizado === 'ready' ||
        $statusNormalizado === 'assinado' ||
        $statusNormalizado === 'finalizado'
    ) {
        // Verificar se todos os signatários já assinaram
        $signatariosAssinaram = false;
        $signers = $d['signers'] ?? $d['data']['signers'] ?? $d['document']['signers'] ?? [];
        if (is_array($signers) && count($signers) > 0) {
            $todosAssinaram = true;
            foreach ($signers as $signer) {
                $signerStatus = strtolower($signer['status'] ?? $signer['signed_at'] ?? '');
                if (empty($signerStatus) && empty($signer['signed_at'])) {
                    $todosAssinaram = false;
                    break;
                }
            }
            $signatariosAssinaram = $todosAssinaram;
        }
        
        // Se o evento indica assinatura parcial ou os signatários estão todos com status de assinado
        if (str_contains($eventNormalizado, 'signer_signed') || $signatariosAssinaram || empty($signers)) {
            $novoStatus = 'assinado';
            $mensagemHistorico = "Contrato comercial assinado com sucesso por todas as partes (Assinafy).";
        }
    } elseif (
        str_contains($eventNormalizado, 'signer_rejected_document') ||
        str_contains($eventNormalizado, 'user_rejected_document') ||
        str_contains($eventNormalizado, 'cancelled') ||
        str_contains($eventNormalizado, 'canceled') ||
        str_contains($eventNormalizado, 'rejected') ||
        $statusNormalizado === 'cancelled' ||
        $statusNormalizado === 'canceled' ||
        $statusNormalizado === 'rejected' ||
        $statusNormalizado === 'cancelado'
    ) {
        $novoStatus = 'cancelado';
        $mensagemHistorico = "Assinatura do contrato cancelada ou recusada no Assinafy.";
    }
    
    if ($novoStatus) {
        // Atualizar status do contrato
        $stmtUpdate = $db->prepare("UPDATE contratos SET status = ? WHERE documento_assinatura_id = ?");
        $stmtUpdate->execute([$novoStatus, $documentId]);
        
        // Se houver proposta vinculada, atualiza a proposta e o histórico dela
        if (!empty($contrato['proposta_id'])) {
            // Atualiza status da proposta para aceita (se assinado) ou rascunho (se cancelado)
            $statusProposta = ($novoStatus === 'assinado') ? 'aceita' : 'rascunho';
            $db->prepare("UPDATE propostas SET status = ? WHERE id = ?")
               ->execute([$statusProposta, $contrato['proposta_id']]);
               
            // Gravar histórico de auditoria
            $stmtHist = $db->prepare("
                INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo)
                VALUES (?, 'webhook_assinafy', 'documento', ?)
            ");
            $stmtHist->execute([
                $contrato['proposta_id'],
                $mensagemHistorico
            ]);
        }
        
        echo json_encode(['success' => true, 'mensagem' => "Contrato atualizado para {$novoStatus} com sucesso."]);
    } else {
        echo json_encode(['success' => true, 'mensagem' => 'Evento recebido mas nenhuma transição de status foi necessária.']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
