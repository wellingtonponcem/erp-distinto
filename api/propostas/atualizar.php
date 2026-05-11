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
            $s['id']              = $item['id'];
            $s['valor_individual'] = (float)($item['valor'] ?? 0);
            $s['valor_mensal']    = (float)($item['valor_mensal'] ?? $item['valor'] ?? 0);
            $s['tipo_cobranca']   = $item['tipo_cobranca'] ?? 'recorrente';
            $s['frequencia']      = $item['frequencia'] ?? '';
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

// Processar fases do cronograma
$fasesCronograma = [];
if (!empty($d['fases']) && is_array($d['fases'])) {
    foreach ($d['fases'] as $fase) {
        if (empty($fase['nome'])) continue;
        $fasesCronograma[] = [
            'nome'      => trim($fase['nome']),
            'dias'      => max(0, (int)($fase['dias'] ?? 0)),
            'descricao' => trim($fase['descricao'] ?? ''),
        ];
    }
}

$clienteId = array_key_exists('cliente_id', $d) ? ($d['cliente_id'] ?: null) : $propostaAtual['cliente_id'];
$oportunidadeId = array_key_exists('oportunidade_id', $d) ? ($d['oportunidade_id'] ?: null) : $propostaAtual['oportunidade_id'];

if (!empty($oportunidadeId)) {
    $stmtO = $db->prepare("SELECT cliente_id FROM oportunidades WHERE id = ?");
    $stmtO->execute([$oportunidadeId]);
    $oportunidade = $stmtO->fetch();
    if (!$oportunidade) {
        responderJson(['erro' => 'Oportunidade não encontrada.'], 404);
    }
    if (!empty($oportunidade['cliente_id'])) {
        $clienteId = $oportunidade['cliente_id'];
    }
}

$dadosJson = json_encode([
    'secoes' => $secoes,
    'servicos' => $servicosInclusos,
    'fases_cronograma' => $fasesCronograma ?: ($dadosAntigos['fases_cronograma'] ?? []),
    'briefing' => $d['briefing'] ?? ($dadosAntigos['briefing'] ?? ''),
    'objetivo_original' => $d['objetivo'] ?? ($dadosAntigos['objetivo_original'] ?? ''),
    'data_inicio' => $d['data_inicio'] ?? ($dadosAntigos['data_inicio'] ?? date('Y-m-d')),
    'meses_contrato' => $d['meses_contrato'] ?? ($dadosAntigos['meses_contrato'] ?? 12),
    'forma_pagamento' => $d['forma_pagamento'] ?? ($dadosAntigos['forma_pagamento'] ?? 'boleto_pix'),
    'adicional' => [
        'titulo' => $d['adicional_titulo'] ?? ($dadosAntigos['adicional']['titulo'] ?? ''),
        'valor' => $d['adicional_valor'] ?? ($dadosAntigos['adicional']['valor'] ?? 0),
        'descricao' => $d['adicional_descricao'] ?? ($dadosAntigos['adicional']['descricao'] ?? ''),
        'fornecedor_id' => $d['adicional_fornecedor_id'] ?? ($dadosAntigos['adicional']['fornecedor_id'] ?? '')
    ],
    'responsavel' => contatoResponsavel([
        'tipo' => $d['tipo'] ?? $propostaAtual['tipo'] ?? '',
        'contato_tipo' => $d['contato_tipo'] ?? ($dadosAntigos['contato_tipo'] ?? 'noiva'),
        'nome_noivo' => $d['nome_noivo'] ?? ($dadosAntigos['nome_noivo'] ?? ''),
        'nome_noiva' => $d['nome_noiva'] ?? ($dadosAntigos['nome_noiva'] ?? ''),
        'responsavel' => $d['responsavel'] ?? ($dadosAntigos['responsavel'] ?? ''),
    ]),
    'whatsapp' => $d['whatsapp'] ?? ($dadosAntigos['whatsapp'] ?? ''),
    'is_plural' => $dadosAntigos['is_plural'] ?? false,
    'etapas_ativas' => $d['etapas_ativas'] ?? ($dadosAntigos['etapas_ativas'] ?? []),
    'etapas_dias' => $d['etapas_dias'] ?? ($dadosAntigos['etapas_dias'] ?? []),
    // Campos de Casamento
    'nome_noivo' => $d['nome_noivo'] ?? ($dadosAntigos['nome_noivo'] ?? ''),
    'nome_noiva' => $d['nome_noiva'] ?? ($dadosAntigos['nome_noiva'] ?? ''),
    'data_casamento' => $d['data_casamento'] ?? ($dadosAntigos['data_casamento'] ?? ''),
    'data_limite_desconto' => $d['data_limite_desconto'] ?? ($dadosAntigos['data_limite_desconto'] ?? ''),
    'condicao_especial' => $d['condicao_especial'] ?? ($dadosAntigos['condicao_especial'] ?? ''),
    'valor_heritage' => $d['valor_heritage'] ?? ($dadosAntigos['valor_heritage'] ?? ''),
    'itens_heritage' => $d['itens_heritage'] ?? ($dadosAntigos['itens_heritage'] ?? ''),
    'valor_cinematic' => $d['valor_cinematic'] ?? ($dadosAntigos['valor_cinematic'] ?? ''),
    'itens_cinematic' => $d['itens_cinematic'] ?? ($dadosAntigos['itens_cinematic'] ?? ''),
    'valor_essencial' => $d['valor_essencial'] ?? ($dadosAntigos['valor_essencial'] ?? ''),
    'itens_essencial' => $d['itens_essencial'] ?? ($dadosAntigos['itens_essencial'] ?? ''),
    'valor_boudoir' => $d['valor_boudoir'] ?? ($dadosAntigos['valor_boudoir'] ?? ''),
    'valor_prewedding' => $d['valor_prewedding'] ?? ($dadosAntigos['valor_prewedding'] ?? ''),
    // Flags de Visibilidade e Inclusão (Globais)
    'show_heritage' => isset($d['show_heritage']),
    'show_cinematic' => isset($d['show_cinematic']),
    'show_essencial' => isset($d['show_essencial']),
    'include_boudoir' => isset($d['include_boudoir']),
    'include_prewedding' => isset($d['include_prewedding']),
    
    // Flags Específicas por Pacote
    'include_boudoir_heritage' => isset($d['include_boudoir_heritage']),
    'include_prewedding_heritage' => isset($d['include_prewedding_heritage']),
    'include_boudoir_cinematic' => isset($d['include_boudoir_cinematic']),
    'include_prewedding_cinematic' => isset($d['include_prewedding_cinematic']),
    'include_boudoir_essencial' => isset($d['include_boudoir_essencial']),
    'include_prewedding_essencial' => isset($d['include_prewedding_essencial']),
    // Condições de Pagamento (Página 10)
    'condicoes_reserva' => $d['condicoes_reserva'] ?? ($dadosAntigos['condicoes_reserva'] ?? ''),
    'condicoes_heritage_cinematic' => $d['condicoes_heritage_cinematic'] ?? ($dadosAntigos['condicoes_heritage_cinematic'] ?? ''),
    'condicoes_essencial' => $d['condicoes_essencial'] ?? ($dadosAntigos['condicoes_essencial'] ?? ''),
    'contato_tipo' => $d['contato_tipo'] ?? ($dadosAntigos['contato_tipo'] ?? 'noiva'),
    'upgrades' => $d['upgrades'] ?? ($dadosAntigos['upgrades'] ?? ['heritage' => [], 'cinematic' => [], 'essencial' => []]),
], JSON_UNESCAPED_UNICODE);

// 4. Atualizar no Banco
$stmt = $db->prepare("UPDATE propostas SET 
                        titulo = ?, 
                        subtitulo = ?, 
                        validade = ?, 
                        dados_json = ?, 
                        valor_total = ?, 
                        status = ?, 
                        cliente_id = ?, 
                        oportunidade_id = ?
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
    $clienteId,
    $oportunidadeId,
    $d['id']
]);

// --- ASSOCIAÇÃO AUTOMÁTICA FINANCEIRA ---
if ($status === 'aceita' && !empty($clienteId)) {
    // 1. Verificar se já existe um lançamento para esta proposta
    $checkObs = "Ref. Proposta: " . $d['id'];
    $stmtCheck = $db->prepare("SELECT id FROM lancamentos WHERE observacao LIKE ?");
    $stmtCheck->execute(["%$checkObs%"]);
    
    if (!$stmtCheck->fetch()) {
        // 2. Criar novo lançamento
        $idLancamento = gerarId();
        $desc = "Fechamento: " . ($d['titulo'] ?? $propostaAtual['titulo']);
        $venc = date('Y-m-d'); // Vencimento hoje por padrão
        
        // Buscar nome do cliente para a descrição/cliente_fornecedor
        $stmtCli = $db->prepare("SELECT nome FROM clientes WHERE id = ?");
        $stmtCli->execute([$clienteId]);
        $cliente = $stmtCli->fetch();
        $clienteNome = $cliente ? $cliente['nome'] : $propostaAtual['cliente_nome'];

        $stmtIns = $db->prepare("INSERT INTO lancamentos (
            id, tipo, descricao, valor, valor_pago, categoria, 
            cliente_fornecedor, cliente_id, vencimento, status, 
            modalidade, observacao, created_at
        ) VALUES (?, 'receber', ?, ?, 0, 'serviços', ?, ?, ?, 'pendente', 'avista', ?, NOW())");
        
        $stmtIns->execute([
            $idLancamento,
            $desc,
            $valorTotal,
            $clienteNome,
            $clienteId,
            $venc,
            "Gerado automaticamente. " . $checkObs
        ]);
    }
}
// ----------------------------------------

responderJson([
    'success' => true,
    'id' => $d['id'],
    'slug' => $propostaAtual['slug']
]);
