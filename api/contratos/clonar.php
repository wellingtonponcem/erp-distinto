<?php
/**
 * API: Clonar Contrato
 * Duplica um contrato existente e define seu status como rascunho para fins de testes.
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
        responderJson(['success' => false, 'erro' => 'ID do contrato original é obrigatório.'], 422);
    }

    $db = Database::get();

    // 1. Buscar o contrato original
    $stmt = $db->prepare("SELECT * FROM contratos WHERE id = ?");
    $stmt->execute([$id]);
    $contrato = $stmt->fetch();

    if (!$contrato) {
        responderJson(['success' => false, 'erro' => 'Contrato original não encontrado.'], 404);
    }

    // 2. Gerar dados para o clone
    $novoId = gerarId();
    $novoTitulo = $contrato['titulo'] . ' - Cópia';
    
    // Inserir clone com status de rascunho e limpando as informações da Assinafy/Asaas
    $stmtInsert = $db->prepare("
        INSERT INTO contratos (
            id, proposta_id, cliente_id, cliente_nome, titulo, valor_total, 
            condicoes_pagamento, data_contrato, local_contrato, status, 
            dados_json, documento_assinatura_id, link_assinatura, asaas_cobranca_gerada
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, 'rascunho', 
            ?, NULL, NULL, 0
        )
    ");

    $stmtInsert->execute([
        $novoId,
        $contrato['proposta_id'] ?: null,
        $contrato['cliente_id'] ?: null,
        $contrato['cliente_nome'],
        $novoTitulo,
        $contrato['valor_total'],
        $contrato['condicoes_pagamento'],
        $contrato['data_contrato'],
        $contrato['local_contrato'],
        $contrato['dados_json']
    ]);

    responderJson([
        'success' => true,
        'mensagem' => 'Contrato clonado com sucesso!',
        'novo_id' => $novoId
    ]);

} catch (Exception $e) {
    responderJson([
        'success' => false,
        'erro' => 'Erro ao clonar contrato: ' . $e->getMessage()
    ], 500);
}
