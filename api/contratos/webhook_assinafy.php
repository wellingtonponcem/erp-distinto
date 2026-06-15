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

function extrairSignatariosWebhookAssinafy(array $payload): array {
    $candidatos = [
        $payload['signers'] ?? null,
        $payload['assignments'] ?? null,
        $payload['recipients'] ?? null,
        $payload['participants'] ?? null,
        $payload['data']['signers'] ?? null,
        $payload['data']['assignments'] ?? null,
        $payload['document']['signers'] ?? null,
        $payload['document']['assignments'] ?? null,
        $payload['data']['document']['signers'] ?? null,
        $payload['data']['document']['assignments'] ?? null,
    ];

    foreach ($candidatos as $lista) {
        if (is_array($lista) && count($lista) > 0) {
            return $lista;
        }
    }

    return [];
}

function signatarioWebhookAssinadoAssinafy(array $signatario): bool {
    $status = strtolower(trim((string)(
        $signatario['status']
        ?? $signatario['signature_status']
        ?? $signatario['signing_status']
        ?? ''
    )));

    if (in_array($status, ['signed', 'completed', 'ready', 'assinado', 'finalizado', 'certificated', 'registrado'], true)) {
        return true;
    }

    foreach (['signed_at', 'signedAt', 'signature_date', 'signatureDate', 'completed_at', 'completedAt'] as $campo) {
        if (!empty($signatario[$campo])) {
            return true;
        }
    }

    return !empty($signatario['signed']) || !empty($signatario['completed']);
}

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
        str_contains($eventNormalizado, 'certificated') ||
        str_contains($eventNormalizado, 'registrado') ||
        $statusNormalizado === 'completed' ||
        $statusNormalizado === 'signed' ||
        $statusNormalizado === 'ready' ||
        $statusNormalizado === 'assinado' ||
        $statusNormalizado === 'finalizado' ||
        $statusNormalizado === 'certificated' ||
        $statusNormalizado === 'registrado'
    ) {
        // Verificar se todos os signatários já assinaram
        $signatariosAssinaram = false;
        $signers = extrairSignatariosWebhookAssinafy($d);
        if (is_array($signers) && count($signers) > 0) {
            $todosAssinaram = true;
            foreach ($signers as $signer) {
                if (!is_array($signer) || !signatarioWebhookAssinadoAssinafy($signer)) {
                    $todosAssinaram = false;
                    break;
                }
            }
            $signatariosAssinaram = $todosAssinaram;
        }
        
        // Se o evento indica assinatura parcial ou os signatários estão todos com status de assinado
        if (!str_contains($eventNormalizado, 'signer_signed') || $signatariosAssinaram) {
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
               
            // Se o contrato foi ganho (assinado), atualiza a etapa da oportunidade vinculada para 'ganha' no CRM
            if ($novoStatus === 'assinado') {
                $stmtProp = $db->prepare("SELECT oportunidade_id FROM propostas WHERE id = ?");
                $stmtProp->execute([$contrato['proposta_id']]);
                $prop = $stmtProp->fetch();
                
                if ($prop && !empty($prop['oportunidade_id'])) {
                    $db->prepare("UPDATE oportunidades SET etapa = 'ganha', atualizado_em = CURRENT_TIMESTAMP WHERE id = ?")
                       ->execute([$prop['oportunidade_id']]);
                }
            }

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

        // Se o contrato foi assinado, atualizar os dados de CPF/CNPJ e contato do cliente cadastrado
        if ($novoStatus === 'assinado' && !empty($contrato['cliente_id'])) {
            $dadosJson = json_decode($contrato['dados_json'], true) ?: [];
            $sig1 = $dadosJson['signatario_1'] ?? null;
            if ($sig1 && !empty($sig1['nome'])) {
                $stmtGetCli = $db->prepare("SELECT cpf_cnpj, contato FROM clientes WHERE id = ?");
                $stmtGetCli->execute([$contrato['cliente_id']]);
                $cliExistente = $stmtGetCli->fetch();
                
                if ($cliExistente) {
                    $novoCpf = $cliExistente['cpf_cnpj'];
                    if (empty($novoCpf) && !empty($sig1['cpf'])) {
                        $novoCpf = $sig1['cpf'];
                    }
                    $novoContato = $cliExistente['contato'];
                    if (empty($novoContato) && !empty($sig1['email'])) {
                        $novoContato = $sig1['email'];
                    }
                    
                    $stmtUpCli = $db->prepare("UPDATE clientes SET cpf_cnpj = ?, contato = ? WHERE id = ?");
                    $stmtUpCli->execute([$novoCpf, $novoContato, $contrato['cliente_id']]);
                }
            }
        }
        
        echo json_encode(['success' => true, 'mensagem' => "Contrato atualizado para {$novoStatus} com sucesso."]);
    } else {
        echo json_encode(['success' => true, 'mensagem' => 'Evento recebido mas nenhuma transição de status foi necessária.']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
