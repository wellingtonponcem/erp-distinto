<?php
/**
 * API: Atualizar Proposta
 * Recebe dados editados da proposta e salva no banco.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/ia_propostas.php';

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

function normalizarDataFormulario(?string $valor): string {
    $valor = trim((string)$valor);
    if ($valor === '') {
        return '';
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        return $valor;
    }

    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $valor, $m)) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
    }

    return $valor;
}

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

// --- PROCESSAMENTO DA ESCOLHA DO CASAL (ADMIN / WHATSAPP) ---
$clienteEscolha = $dadosAntigos['cliente_escolha'] ?? null;
$pacoteDadoAndamento = $d['pacote_dado_andamento'] ?? '';
$tipoProposta = $d['tipo'] ?? $propostaAtual['tipo'];
$valorTotal = !empty($d['valor_total']) ? (float)str_replace(['.', ','], ['', '.'], $d['valor_total']) : $propostaAtual['valor_total'];
$andamentoProposta = $d['andamento_proposta'] ?? ($dadosAntigos['andamento_proposta'] ?? '');
$nomeNoivo = trim((string)($d['nome_noivo'] ?? ($dadosAntigos['nome_noivo'] ?? '')));
$nomeNoiva = trim((string)($d['nome_noiva'] ?? ($dadosAntigos['nome_noiva'] ?? '')));
$responsavelManual = trim((string)($d['responsavel'] ?? ($dadosAntigos['responsavel_manual'] ?? $dadosAntigos['responsavel'] ?? '')));

$clienteNomeAtualizado = trim((string)($propostaAtual['cliente_nome'] ?? ''));
if ($tipoProposta === 'casamento') {
    $clienteId = null;
    if ($nomeNoivo !== '' && $nomeNoiva !== '') {
        $clienteNomeAtualizado = $nomeNoivo . ' & ' . $nomeNoiva;
    } elseif ($nomeNoivo !== '' || $nomeNoiva !== '') {
        $clienteNomeAtualizado = $nomeNoivo !== '' ? $nomeNoivo : $nomeNoiva;
    }
} else {
    $clienteNomeAtualizado = $responsavelManual !== '' ? $responsavelManual : ($d['cliente_nome'] ?? $clienteNomeAtualizado);
}

// Flags de visibilidade e upgrades estáticos
$showHeritage = isset($d['show_heritage']);
$showCinematic = isset($d['show_cinematic']);
$showEssencial = isset($d['show_essencial']);

$includeBoudoir = isset($d['include_boudoir']);
$includePrewedding = isset($d['include_prewedding']);

$includeBoudoirHeritage = isset($d['include_boudoir_heritage']);
$includePreweddingHeritage = isset($d['include_prewedding_heritage']);
$includeBoudoirCinematic = isset($d['include_boudoir_cinematic']);
$includePreweddingCinematic = isset($d['include_prewedding_cinematic']);
$includeBoudoirEssencial = isset($d['include_boudoir_essencial']);
$includePreweddingEssencial = isset($d['include_prewedding_essencial']);

$upgrades = $d['upgrades'] ?? ($dadosAntigos['upgrades'] ?? ['heritage' => [], 'cinematic' => [], 'essencial' => []]);

if ($tipoProposta === 'casamento') {
    if (!empty($pacoteDadoAndamento)) {
        $planoId = $pacoteDadoAndamento;
        $extras = [];
        $itensSelecionados = [];

        if (($d['escolha_boudoir'] ?? '') === '1') {
            $extras[] = 'boudoir_static';
            $itensSelecionados[] = 'Boudoir da Noiva';
        }
        if (($d['escolha_prewedding'] ?? '') === '1') {
            $extras[] = 'prewedding_static';
            $itensSelecionados[] = 'Ensaio Pré-Wedding';
        }

        $upgradesPost = $d['escolha_upgrades'] ?? [];
        $extrasDinamicos = [];
        foreach ($upgradesPost as $upgId => $ativo) {
            if ($ativo === '1') {
                $extrasDinamicos[] = $upgId;
                $extras[] = $upgId;
            }
        }

        if (!empty($extrasDinamicos)) {
            $placeholders = implode(',', array_fill(0, count($extrasDinamicos), '?'));
            $stmtExtras = $db->prepare("SELECT id, nome FROM servicos WHERE id IN ($placeholders) AND categoria = 'wedding' AND ativo = 1");
            $stmtExtras->execute($extrasDinamicos);
            foreach ($stmtExtras->fetchAll() as $extra) {
                $itensSelecionados[] = $extra['nome'];
            }
        }

        $valorTotalEscolha = (float)($d['escolha_valor_total'] ?? 0);

        // Se o plano mudou ou foi configurado agora, registrar no andamento
        $planoAnterior = $dadosAntigos['cliente_escolha']['plano_id'] ?? '';
        if ($planoId !== $planoAnterior) {
            $dataAtual = date('d/m/Y H:i');
            $nomePlano = $planoId === 'heritage' ? 'Experiência Heritage' : ($planoId === 'cinematic' ? 'Experiência Cinematic' : 'Registro Essencial');
            $novaLinhaAndamento = "{$dataAtual} | Administrador registrou fechamento no plano: {$nomePlano}";
            if (!empty($itensSelecionados)) {
                $novaLinhaAndamento .= " com upgrades (" . implode(', ', $itensSelecionados) . ")";
            }
            $novaLinhaAndamento .= " | Investimento: R$ " . number_format($valorTotalEscolha, 2, ',', '.') . " | Registrado via painel administrativo";
            
            if (trim($andamentoProposta) !== '') {
                $andamentoProposta = trim($andamentoProposta) . "\n" . $novaLinhaAndamento;
            } else {
                $andamentoProposta = $novaLinhaAndamento;
            }

            // Gravar no histórico secundário
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS propostas_historico (
                    id SERIAL PRIMARY KEY,
                    proposta_id TEXT NOT NULL,
                    user_id TEXT NOT NULL,
                    tipo TEXT DEFAULT 'nota',
                    conteudo TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $conteudoHist = "Administrador registrou fechamento no plano {$planoId} com investimento total de R$ " . number_format($valorTotalEscolha, 2, ',', '.') . ".";
                $stmtHist = $db->prepare("INSERT INTO propostas_historico (proposta_id, user_id, tipo, conteudo) VALUES (?, ?, ?, ?)");
                $userId = $_SESSION['usuario_id'] ?? 'admin';
                $stmtHist->execute([$d['id'], $userId, 'escolha_admin', $conteudoHist]);
            } catch (Exception $e) {
                // Silencioso
            }
        }

        $clienteEscolha = [
            'plano_id' => $planoId,
            'extras' => $extras,
            'itens_selecionados' => $itensSelecionados,
            'valor_total' => $valorTotalEscolha,
            'condicoes' => $d['escolha_condicoes'] ?? '',
            'selecionado_em' => $dadosAntigos['cliente_escolha']['selecionado_em'] ?? date('Y-m-d H:i:s'),
            'whatsapp_fechamento' => true,
        ];

        // Sincronizar o valor total oficial da proposta comercial com a escolha do admin
        $valorTotal = $valorTotalEscolha;

        // Sobrescrever flags globais de exibição/inclusão para manter consistência
        $showHeritage = ($planoId === 'heritage');
        $showCinematic = ($planoId === 'cinematic');
        $showEssencial = ($planoId === 'essencial');

        $includeBoudoir = in_array('boudoir_static', $extras, true);
        $includePrewedding = in_array('prewedding_static', $extras, true);

        $includeBoudoirHeritage = ($planoId === 'heritage' && $includeBoudoir);
        $includePreweddingHeritage = ($planoId === 'heritage' && $includePrewedding);
        
        $includeBoudoirCinematic = ($planoId === 'cinematic' && $includeBoudoir);
        $includePreweddingCinematic = ($planoId === 'cinematic' && $includePrewedding);

        $includeBoudoirEssencial = ($planoId === 'essencial' && $includeBoudoir);
        $includePreweddingEssencial = ($planoId === 'essencial' && $includePrewedding);

        // Upgrades do plano selecionado
        $upgrades[$planoId] = [];
        foreach ($extrasDinamicos as $upgId) {
            $upgrades[$planoId][$upgId] = true;
        }
    }
}
// ------------------------------------------------------------

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
        'nome_noivo' => $nomeNoivo,
        'nome_noiva' => $nomeNoiva,
        'responsavel' => $responsavelManual,
    ]),
    'responsavel_manual' => $responsavelManual,
    'whatsapp' => $d['whatsapp'] ?? ($dadosAntigos['whatsapp'] ?? ''),
    'is_plural' => $dadosAntigos['is_plural'] ?? false,
    'etapas_ativas' => $d['etapas_ativas'] ?? ($dadosAntigos['etapas_ativas'] ?? []),
    'etapas_dias' => $d['etapas_dias'] ?? ($dadosAntigos['etapas_dias'] ?? []),
    // Campos de Casamento
    'nome_noivo' => $nomeNoivo,
    'nome_noiva' => $nomeNoiva,
    'data_casamento' => normalizarDataFormulario($d['data_casamento'] ?? ($dadosAntigos['data_casamento'] ?? '')),
    'data_limite_desconto' => normalizarDataFormulario($d['data_limite_desconto'] ?? ($dadosAntigos['data_limite_desconto'] ?? '')),
    'condicao_especial' => $d['condicao_especial'] ?? ($dadosAntigos['condicao_especial'] ?? ''),
    'valor_heritage' => $d['valor_heritage'] ?? ($dadosAntigos['valor_heritage'] ?? ''),
    'itens_heritage' => $d['itens_heritage'] ?? ($dadosAntigos['itens_heritage'] ?? ''),
    'valor_cinematic' => $d['valor_cinematic'] ?? ($dadosAntigos['valor_cinematic'] ?? ''),
    'itens_cinematic' => $d['itens_cinematic'] ?? ($dadosAntigos['itens_cinematic'] ?? ''),
    'valor_essencial' => $d['valor_essencial'] ?? ($dadosAntigos['valor_essencial'] ?? ''),
    'itens_essencial' => $d['itens_essencial'] ?? ($dadosAntigos['itens_essencial'] ?? ''),
    'valor_boudoir' => $d['valor_boudoir'] ?? ($dadosAntigos['valor_boudoir'] ?? ''),
    'valor_prewedding' => $d['valor_prewedding'] ?? ($dadosAntigos['valor_prewedding'] ?? ''),
    'atualizacoes_versao' => $d['atualizacoes_versao'] ?? ($dadosAntigos['atualizacoes_versao'] ?? ''),
    'andamento_proposta' => $andamentoProposta,
    'mostrar_andamento_cliente' => isset($d['mostrar_andamento_cliente']),
    'versao_proposta' => $d['versao_proposta'] ?? ($dadosAntigos['versao_proposta'] ?? 'v1'),
    'itens_personalizados' => $d['itens_personalizados'] ?? ($dadosAntigos['itens_personalizados'] ?? ['heritage' => [], 'cinematic' => [], 'essencial' => []]),
    'mensagem_pessoal' => $d['mensagem_pessoal'] ?? ($dadosAntigos['mensagem_pessoal'] ?? ''),
    'prazo_previas' => $d['prazo_previas'] ?? ($dadosAntigos['prazo_previas'] ?? ''),
    'prazo_final' => $d['prazo_final'] ?? ($dadosAntigos['prazo_final'] ?? ''),
    'validade_proposta' => $d['validade_proposta'] ?? ($dadosAntigos['validade_proposta'] ?? ''),
    'instagram_handle' => $d['instagram_handle'] ?? ($dadosAntigos['instagram_handle'] ?? ''),
    'email_contato' => $d['email_contato'] ?? ($dadosAntigos['email_contato'] ?? ''),
    'whatsapp_numero' => $d['whatsapp_numero'] ?? ($dadosAntigos['whatsapp_numero'] ?? ''),
    // Flags de Visibilidade e Inclusão (Globais)
    'show_heritage' => $showHeritage,
    'show_cinematic' => $showCinematic,
    'show_essencial' => $showEssencial,
    'include_boudoir' => $includeBoudoir,
    'include_prewedding' => $includePrewedding,
    
    // Flags Específicas por Pacote
    'include_boudoir_heritage' => $includeBoudoirHeritage,
    'include_prewedding_heritage' => $includePreweddingHeritage,
    'include_boudoir_cinematic' => $includeBoudoirCinematic,
    'include_prewedding_cinematic' => $includePreweddingCinematic,
    'include_boudoir_essencial' => $includeBoudoirEssencial,
    'include_prewedding_essencial' => $includePreweddingEssencial,
    // Condições de Pagamento (Página 10)
    'condicoes_reserva' => $d['condicoes_reserva'] ?? ($dadosAntigos['condicoes_reserva'] ?? ''),
    'condicoes_heritage_cinematic' => $d['condicoes_heritage_cinematic'] ?? ($dadosAntigos['condicoes_heritage_cinematic'] ?? ''),
    'condicoes_essencial' => $d['condicoes_essencial'] ?? ($dadosAntigos['condicoes_essencial'] ?? ''),
    'contato_tipo' => $d['contato_tipo'] ?? ($dadosAntigos['contato_tipo'] ?? 'noiva'),
    'upgrades' => $upgrades,
    'pacote_dado_andamento' => $pacoteDadoAndamento,
    'cliente_escolha' => $clienteEscolha,
], JSON_UNESCAPED_UNICODE);

// 4. Atualizar no Banco
$stmt = $db->prepare("UPDATE propostas SET 
                        cliente_nome = ?,
                        titulo = ?, 
                        subtitulo = ?, 
                        validade = ?, 
                        dados_json = ?, 
                        valor_total = ?, 
                        status = ?, 
                        cliente_id = ?, 
                        oportunidade_id = ?
                      WHERE id = ?");

$valorTotal = ($tipoProposta === 'casamento' && !empty($pacoteDadoAndamento)) ? $valorTotal : (!empty($d['valor_total']) ? (float)str_replace(['.', ','], ['', '.'], $d['valor_total']) : $propostaAtual['valor_total']);
$status = $d['status'] ?? $propostaAtual['status'];

$stmt->execute([
    $clienteNomeAtualizado,
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
            modalidade, observacao, criado_em
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

$recomendacao = '';
try {
    $historico = [];
    $stmtHist = $db->prepare("SELECT tipo, conteudo, created_at FROM propostas_historico WHERE proposta_id = ? ORDER BY created_at DESC LIMIT 3");
    $stmtHist->execute([$d['id']]);
    $historico = $stmtHist->fetchAll();
} catch (Exception $e) {
    // tabela pode não existir ainda
}

$propostaParaIA = [
    'cliente_nome' => $propostaAtual['cliente_nome'],
    'tipo' => $propostaAtual['tipo'],
    'status' => $status,
    'responsavel' => ($d['tipo'] === 'casamento') ? contatoResponsavel([
        'contato_tipo' => $d['contato_tipo'] ?? ($dadosAntigos['contato_tipo'] ?? 'noiva'),
        'nome_noivo' => $d['nome_noivo'] ?? ($dadosAntigos['nome_noivo'] ?? ''),
        'nome_noiva' => $d['nome_noiva'] ?? ($dadosAntigos['nome_noiva'] ?? ''),
        'responsavel' => $d['responsavel'] ?? ($dadosAntigos['responsavel'] ?? ''),
    ]) : ($d['responsavel'] ?? ($dadosAntigos['responsavel'] ?? '')),
    'titulo' => $d['titulo'] ?? $propostaAtual['titulo'],
    'dados_json' => $dadosJson,
];

try {
    $recomendacao = IAPropostas::recomendarProximoPasso($propostaParaIA, $historico);
} catch (Exception $e) {
    $recomendacao = '';
}

responderJson([
    'success' => true,
    'id' => $d['id'],
    'slug' => $propostaAtual['slug'],
    'recomendacao' => $recomendacao
]);
