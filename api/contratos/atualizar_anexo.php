<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ia_propostas.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d = lerCorpo();
$contratoId = $d['contrato_id'] ?? '';

if (!$contratoId) {
    responderJson(['erro' => 'contrato_id é obrigatório.'], 422);
}

$db = Database::get();

$stmt = $db->prepare("SELECT * FROM contratos WHERE id = ?");
$stmt->execute([$contratoId]);
$contrato = $stmt->fetch();

if (!$contrato) {
    responderJson(['erro' => 'Contrato não encontrado.'], 404);
}

if (($contrato['status'] ?? 'rascunho') !== 'rascunho') {
    responderJson(['erro' => 'Contrato já enviado para assinatura. Edições não permitidas.'], 422);
}

$stmtP = $db->prepare("SELECT * FROM propostas WHERE id = ?");
$stmtP->execute([$contrato['proposta_id']]);
$proposta = $stmtP->fetch();

if (!$proposta) {
    responderJson(['erro' => 'Proposta vinculada não encontrada.'], 404);
}

$dadosProposta = json_decode($proposta['dados_json'], true) ?: [];
$dadosProposta['cliente_nome'] = $proposta['cliente_nome'];
$dadosProposta['tipo'] = $proposta['tipo'];
$dadosProposta['titulo'] = $proposta['titulo'];
$dadosProposta['valor_total'] = $proposta['valor_total'];

try {
    $htmlAnexo = IAPropostas::gerarAnexoI($dadosProposta);
} catch (Exception $e) {
    responderJson(['erro' => 'Falha ao processar com a IA: ' . $e->getMessage()], 500);
}

$dadosJson = json_decode($contrato['dados_json'], true) ?: [];
$dadosJson['anexo_texto'] = $htmlAnexo;

try {
    $stmtUp = $db->prepare("UPDATE contratos SET dados_json = ? WHERE id = ?");
    $stmtUp->execute([json_encode($dadosJson, JSON_UNESCAPED_UNICODE), $contratoId]);

    responderJson([
        'success' => true,
        'html' => $htmlAnexo
    ]);
} catch (Exception $e) {
    responderJson(['erro' => 'Erro ao salvar anexo: ' . $e->getMessage()], 500);
}
