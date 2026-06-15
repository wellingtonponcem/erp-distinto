<?php
/**
 * API: Sincronizar Status do Contrato com Assinafy
 * Consulta a API do Assinafy diretamente para obter o status em tempo real do documento.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Captura global de erros para retornar sempre JSON válido e evitar erros 500 secos da hospedagem
try {
    exigirAutenticacao();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJson(['success' => false, 'erro' => 'Método não permitido']);
    }

    $id = $_POST['id'] ?? '';
    if (!$id) {
        responderJson(['success' => false, 'erro' => 'ID do contrato é obrigatório.']);
    }

    $db = Database::get();

    // 1. Buscar credenciais Assinafy
    $stmtConfig = $db->query("SELECT assinafy_api_key, assinafy_account_id, assinafy_mode FROM configuracao_empresa WHERE id = 'principal' LIMIT 1");
    $config = $stmtConfig->fetch();

    $apiKey = $config ? ($config['assinafy_api_key'] ?? '') : '';
    $accountId = $config ? ($config['assinafy_account_id'] ?? '') : '';
    $mode = $config ? ($config['assinafy_mode'] ?? 'test') : 'test';

    if (!$apiKey || !$accountId) {
        responderJson(['success' => false, 'erro' => 'Chave de API ou ID da Conta do Assinafy não configurados no painel.']);
    }

    // 2. Buscar contrato local
    $stmtContrato = $db->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmtContrato->execute([$id]);
    $contrato = $stmtContrato->fetch();

    if (!$contrato) {
        responderJson(['success' => false, 'erro' => 'Contrato não encontrado.']);
    }

    // Corrigir domínio do Assinafy no link caso esteja com o subdomínio antigo
    if (!empty($contrato['link_assinatura']) && strpos($contrato['link_assinatura'], 'painel.assinafy.com.br') !== false) {
        $linkAssinaturaLimpo = str_replace('painel.assinafy.com.br', 'app.assinafy.com.br', $contrato['link_assinatura']);
        $stmtUpdateLink = $db->prepare("UPDATE contratos SET link_assinatura = ? WHERE id = ?");
        $stmtUpdateLink->execute([$linkAssinaturaLimpo, $id]);
        $contrato['link_assinatura'] = $linkAssinaturaLimpo;
    }

    $documentId = $contrato['documento_assinatura_id'] ?? '';
    if (!$documentId) {
        responderJson(['success' => false, 'erro' => 'Este contrato ainda não foi enviado para assinatura.']);
    }

    // Função interna helper
    if (!function_exists('chamarAssinafyGet')) {
        function chamarAssinafyGet(string $endpoint, string $apiKey, string $mode): string {
            $baseUrl = ($mode === 'prod') ? 'https://api.assinafy.com.br/v1' : 'https://sandbox.assinafy.com.br/v1';
            
            if (strpos($endpoint, '/') !== 0) {
                $endpoint = '/' . $endpoint;
            }
            
            $url = $baseUrl . $endpoint;
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            
            $headers = [
                'X-Api-Key: ' . $apiKey,
                'Content-Type: application/json'
            ];
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($response === false) {
                throw new Exception("Erro de conexão ao servidor do Assinafy.");
            }
            
            if ($httpCode < 200 || $httpCode >= 300) {
                $errData = json_decode($response, true);
                $errMsg = $errData['message'] ?? $errData['erro'] ?? $errData['error'] ?? $response;
                throw new Exception("Erro Assinafy (HTTP $httpCode): " . $errMsg);
            }
            
            return $response;
        }
    }

    if (!function_exists('normalizarStatusAssinafy')) {
        function normalizarStatusAssinafy($valor): string {
            return strtolower(trim((string)$valor));
        }
    }

    if (!function_exists('extrairSignatariosAssinafy')) {
        function extrairSignatariosAssinafy(array $docData): array {
            $candidatos = [
                $docData['assignments'] ?? null,
                $docData['signers'] ?? null,
                $docData['recipients'] ?? null,
                $docData['participants'] ?? null,
                $docData['data']['assignments'] ?? null,
                $docData['data']['signers'] ?? null,
                $docData['document']['assignments'] ?? null,
                $docData['document']['signers'] ?? null,
            ];

            foreach ($candidatos as $lista) {
                if (is_array($lista) && count($lista) > 0) {
                    return $lista;
                }
            }

            return [];
        }
    }

    if (!function_exists('signatarioAssinadoAssinafy')) {
        function signatarioAssinadoAssinafy(array $signatario): bool {
            $status = normalizarStatusAssinafy(
                $signatario['status']
                ?? $signatario['signature_status']
                ?? $signatario['signing_status']
                ?? ''
            );

            if (in_array($status, ['signed', 'completed', 'ready', 'assinado', 'finalizado'], true)) {
                return true;
            }

            foreach (['signed_at', 'signedAt', 'signature_date', 'signatureDate', 'completed_at', 'completedAt'] as $campo) {
                if (!empty($signatario[$campo])) {
                    return true;
                }
            }

            return !empty($signatario['signed']) || !empty($signatario['completed']);
        }
    }

    // Buscar detalhes do documento na API
    $responseJson = chamarAssinafyGet("/documents/{$documentId}", $apiKey, $mode);
    $data = json_decode($responseJson, true);
    
    if (!is_array($data)) {
        throw new Exception("Resposta de consulta inválida da API do Assinafy.");
    }
    
    $docData = $data['data'] ?? $data;
    $statusApi = normalizarStatusAssinafy($docData['status'] ?? $docData['document_status'] ?? $docData['state'] ?? '');
    
    $novoStatus = null;
    $mensagemHistorico = '';
    
    // Se o status geral for assinado/completo
    if (in_array($statusApi, ['completed', 'signed', 'ready', 'assinado', 'certificated', 'registrado'])) {
        $novoStatus = 'assinado';
        $mensagemHistorico = "Contrato comercial atualizado para ASSINADO após sincronização direta com Assinafy.";
    } 
    // Ou se houver recusa/cancelamento
    elseif (in_array($statusApi, ['cancelled', 'canceled', 'rejected', 'cancelado'])) {
        $novoStatus = 'cancelado';
        $mensagemHistorico = "Contrato comercial atualizado para CANCELADO após sincronização direta com Assinafy.";
    } 
    // Caso de redundância protetiva: se todos os signatários individuais já assinaram, consideramos assinado
    else {
        $assignments = extrairSignatariosAssinafy($docData);
        if (is_array($assignments) && count($assignments) > 0) {
            $todosAssinaram = true;
            foreach ($assignments as $a) {
                if (!is_array($a) || !signatarioAssinadoAssinafy($a)) {
                    $todosAssinaram = false;
                    break;
                }
            }
            
            if ($todosAssinaram) {
                $novoStatus = 'assinado';
                $mensagemHistorico = "Contrato comercial atualizado para ASSINADO após verificar que todos os signatários assinaram individualmente (Sincronização ERP/Assinafy).";
            }
        }
    }
    
    if ($novoStatus) {
        // Atualizar status no banco do ERP
        $stmtUpdate = $db->prepare("UPDATE contratos SET status = ? WHERE id = ?");
        $stmtUpdate->execute([$novoStatus, $id]);
        
        // Se houver proposta vinculada, atualizar
        if (!empty($contrato['proposta_id'])) {
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

            // Gravar histórico
            $stmtHist = $db->prepare("
                INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo)
                VALUES (?, ?, 'documento', ?)
            ");
            $usuario = usuarioAtual();
            $stmtHist->execute([
                $contrato['proposta_id'],
                $usuario['id'] ?? 'sistema',
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
        
        responderJson([
            'success' => true,
            'status' => $novoStatus,
            'mensagem' => "Status atualizado localmente para: " . strtoupper($novoStatus)
        ]);
    } else {
        responderJson([
            'success' => true,
            'status' => $contrato['status'],
            'mensagem' => "O documento ainda está com status '" . strtoupper($statusApi) . "' no Assinafy e possui assinaturas pendentes."
        ]);
    }

} catch (Throwable $e) {
    // Retorna erro amigável sempre com status HTTP 200 para não ser bloqueado por proxies/servidores de hospedagem
    responderJson([
        'success' => false,
        'erro' => $e->getMessage()
    ]);
}
