<?php
/**
 * API: Gerar Anexo I via IA
 * Recebe o ID da proposta, busca seus dados e utiliza o Gemini para redigir o detalhamento dos serviços.
 */

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
$propostaId = $d['proposta_id'] ?? '';

if (!$propostaId) {
    responderJson(['erro' => 'O proposta_id é obrigatório.'], 422);
}

$db = Database::get();
$stmt = $db->prepare("SELECT * FROM propostas WHERE id = ?");
$stmt->execute([$propostaId]);
$proposta = $stmt->fetch();

if (!$proposta) {
    responderJson(['erro' => 'Proposta não encontrada.'], 404);
}

$dadosProposta = json_decode($proposta['dados_json'], true) ?: [];
$dadosProposta['cliente_nome'] = $proposta['cliente_nome'];
$dadosProposta['tipo'] = $proposta['tipo'];
$dadosProposta['titulo'] = $proposta['titulo'];
$dadosProposta['valor_total'] = $proposta['valor_total'];

try {
    $htmlAnexo = IAPropostas::gerarAnexoI($dadosProposta);
    responderJson([
        'success' => true,
        'html' => $htmlAnexo
    ]);
} catch (Exception $e) {
    responderJson(['erro' => 'Falha ao processar com a IA: ' . $e->getMessage()], 500);
}
