<?php
/**
 * API: Copilot de Contratos com IA
 * Permite otimizar redação de cláusulas ou realizar ajustes pontuais com base em instruções livres.
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
$texto = $d['texto'] ?? '';
$acao = $d['acao'] ?? 'otimizar'; // 'otimizar' ou 'custom'
$promptPersonalizado = $d['prompt'] ?? '';
$tipoContrato = $d['tipo_contrato'] ?? 'Prestação de Serviços';

if (!$texto) {
    responderJson(['erro' => 'O texto da cláusula é obrigatório.'], 422);
}

try {
    if ($acao === 'otimizar') {
        $resultado = IAPropostas::otimizarClausula($texto, $tipoContrato);
    } else {
        $prompt = "Você é um advogado especialista em direito civil e contratos comerciais para agências de marketing, filmagens e eventos sociais de luxo.
Sua tarefa é AJUSTAR a cláusula contratual descrita abaixo, seguindo estritamente a instrução do usuário.

Instrução do Usuário: \"$promptPersonalizado\"

Texto original da Cláusula:
\"\"\"
$texto
\"\"\"

Retorne APENAS o texto ajustado final da cláusula, sem introduções, sem explicações, sem comentários e sem aspas.";
        
        $resultado = IAPropostas::chamarGemini([['text' => $prompt]]);
        $resultado = trim(preg_replace('/^["\']|["\']$/', '', trim($resultado)));
    }

    responderJson([
        'success' => true,
        'resultado' => $resultado
    ]);
} catch (Exception $e) {
    responderJson(['erro' => 'Falha ao processar com a IA: ' . $e->getMessage()], 500);
}
