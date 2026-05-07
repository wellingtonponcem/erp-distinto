<?php
/**
 * API: Atualizar Proposta
 * Recebe dados editados da proposta e salva no banco.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Ativar captura de erros para retornar JSON sempre
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return;
    responderJson(['erro' => "PHP Error: $message in $file on line $line"], 500);
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        header('Content-Type: application/json');
        echo json_encode(['erro' => "PHP Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']]);
    }
});

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d = !empty($_POST) ? $_POST : lerCorpo();

if (empty($d['id'])) {
    responderJson(['erro' => 'O ID da proposta é obrigatório.'], 422);
}

$db = Database::get();

// 1. Buscar proposta atual para manter dados_json original se necessário
$stmtOld = $db->prepare("SELECT * FROM propostas WHERE id = ?");
$stmtOld->execute([$d['id']]);
$propostaAtual = $stmtOld->fetch();

if (!$propostaAtual) {
    responderJson(['erro' => 'Proposta não encontrada.'], 404);
}

$dadosAntigos = json_decode($propostaAtual['dados_json'], true);

// 2. Processar Serviços
$servicosInclusos = [];
if (!empty($d['servicos']) && is_array($d['servicos'])) {
    foreach ($d['servicos'] as $item) {
        if (empty($item['id'])) continue;
        
        $stmtS = $db->prepare("SELECT id, nome, descricao FROM servicos WHERE id = ?");
        $stmtS->execute([$item['id']]);
        if ($s = $stmtS->fetch()) {
            $s['id'] = $item['id']; // Garante que o ID seja salvo no JSON
            $s['valor_individual'] = (float)($item['valor'] ?? 0);
            $servicosInclusos[] = $s;
        }
    }
}

// 3. Montar novo JSON de dados
// Mantemos as seções de IA originais se elas existirem e não estiverem sendo sobrescritas
$secoes = $dadosAntigos['secoes'] ?? [];
if (!empty($d['secoes']) && is_array($d['secoes'])) {
    foreach ($d['secoes'] as $key => $val) {
        $secoes[$key] = $val;
    }
}

$dadosJson = json_encode([
    'secoes' => $secoes,
    'servicos' => $servicosInclusos,
    'briefing' => $d['briefing'] ?? ($dadosAntigos['briefing'] ?? ''),
    'objetivo_original' => $d['objetivo'] ?? ($dadosAntigos['objetivo_original'] ?? ''),
    'data_inicio' => $d['data_inicio'] ?? ($dadosAntigos['data_inicio'] ?? date('Y-m-d')),
    'meses_contrato' => $d['meses_contrato'] ?? ($dadosAntigos['meses_contrato'] ?? 12),
    'forma_pagamento' => $d['forma_pagamento'] ?? ($dadosAntigos['forma_pagamento'] ?? 'boleto_pix'),
    'adicional' => [
        'titulo' => $d['adicional_titulo'] ?? ($dadosAntigos['adicional']['titulo'] ?? ''),
        'valor' => $d['adicional_valor'] ?? ($dadosAntigos['adicional']['valor'] ?? 0),
        'descricao' => $d['adicional_descricao'] ?? ($dadosAntigos['adicional']['descricao'] ?? '')
    ],
    'responsavel' => $d['responsavel'] ?? ($dadosAntigos['responsavel'] ?? ''),
    'whatsapp' => $d['whatsapp'] ?? ($dadosAntigos['whatsapp'] ?? ''),
    'is_plural' => $dadosAntigos['is_plural'] ?? false,
    'etapas_ativas' => $d['etapas_ativas'] ?? ($dadosAntigos['etapas_ativas'] ?? [])
], JSON_UNESCAPED_UNICODE);

// 4. Atualizar no Banco
$stmt = $db->prepare("UPDATE propostas SET 
                        titulo = ?, 
                        subtitulo = ?, 
                        validade = ?, 
                        dados_json = ?, 
                        valor_total = ?, 
                        status = ?
                      WHERE id = ?");

$valorTotal = !empty($d['valor_total']) ? (float)str_replace(['.', ','], ['', '.'], $d['valor_total']) : $propostaAtual['valor_total'];
$status = $d['status'] ?? $propostaAtual['status'];

$stmt->execute([
    $d['titulo'] ?? $propostaAtual['titulo'],
    $d['subtitulo'] ?? $propostaAtual['subtitulo'],
    $d['validade'] ?? $propostaAtual['validade'],
    $dadosJson,
    $valorTotal,
    $status,
    $d['id']
]);

responderJson([
    'success' => true,
    'id' => $d['id'],
    'slug' => $propostaAtual['slug']
]);
