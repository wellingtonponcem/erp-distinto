<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/financeiro_custos.php';

exigirAutenticacao();

$db     = Database::get();
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    garantirEstruturaFinanceira($db);
    switch ($metodo) {
    case 'GET':
        sincronizarLancamentosCustosFixos($db);
        $rows = $db->query("
            SELECT *,
              CASE
                WHEN valor_pago >= valor THEN 'pago'
                WHEN valor_pago > 0 THEN 'pago_parcial'
                WHEN vencimento < CURRENT_DATE AND status NOT IN ('pago','cancelado') THEN 'atrasado'
                ELSE status
              END AS status
            FROM lancamentos
            ORDER BY vencimento DESC
        ")->fetchAll();
        responderJson($rows);

    case 'POST':
        $d = lerCorpo();
        validarLancamento($d);

        // Se marcado como custo fixo, criar/vincular custo_fixo
        if (($d['tipo'] ?? '') === 'pagar' && !empty($d['e_custo_fixo']) && empty($d['custo_fixo_id'])) {
            $d['custo_fixo_id'] = criarCustoFixoFromLancamento($db, $d);
        }

        criarLancamento($db, $d);
        responderJson(['ok' => true], 201);

    case 'PUT':
        $d = lerCorpo();
        if (empty($d['id'])) responderJson(['erro' => 'ID obrigatório'], 422);
        validarLancamento($d);

        if (($d['tipo'] ?? '') === 'pagar' && !empty($d['e_custo_fixo']) && empty($d['custo_fixo_id'])) {
            $d['custo_fixo_id'] = criarCustoFixoFromLancamento($db, $d);
        }

        $sets = ['tipo=?','descricao=?','valor=?','categoria=?','cliente_fornecedor=?','vencimento=?','modalidade=?','forma_pagamento=?','observacao=?'];
        $params = [
            $d['tipo'], $d['descricao'], $d['valor'], $d['categoria'] ?? 'outros',
            empty($d['cliente_fornecedor']) ? null : $d['cliente_fornecedor'], $d['vencimento'],
            $d['modalidade'] ?? 'avista', empty($d['forma_pagamento']) ? null : $d['forma_pagamento'],
            empty($d['observacao']) ? null : $d['observacao']
        ];
        
        if (isset($d['status'])) {
            $sets[] = 'status=?';
            $params[] = $d['status'];
        }
        if (isset($d['valor_pago'])) {
            $sets[] = 'valor_pago=?';
            $params[] = $d['valor_pago'];
        }

        if (tabelaTemColuna($db, 'lancamentos', 'custo_fixo_id')) {
            $sets[] = 'custo_fixo_id=?';
            $params[] = empty($d['custo_fixo_id']) ? null : $d['custo_fixo_id'];
        }
        if (tabelaTemColuna($db, 'lancamentos', 'conta_id')) {
            $sets[] = 'conta_id=?';
            $params[] = empty($d['conta_id']) ? null : $d['conta_id'];
        }
        $params[] = $d['id'];
        
        $stmt = $db->prepare('UPDATE lancamentos SET ' . implode(',', $sets) . ' WHERE id=?');
        $stmt->execute($params);
        responderJson(['ok' => true]);

    case 'DELETE':
        $corpo = json_decode(file_get_contents('php://input'), true) ?: [];
        $ids = !empty($corpo['ids']) ? $corpo['ids'] : (!empty($_GET['id']) ? [$_GET['id']] : []);
        if (empty($ids)) responderJson(['erro' => 'ID obrigatório'], 422);

        $inQuery = implode(',', array_fill(0, count($ids), '?'));
        // Excluir filhos
        $db->prepare("DELETE FROM lancamentos WHERE lancamento_pai_id IN ($inQuery)")->execute($ids);
        // Excluir pais/itens
        $db->prepare("DELETE FROM lancamentos WHERE id IN ($inQuery)")->execute($ids);
        responderJson(['ok' => true]);

        default:
            responderJson(['erro' => 'Método não permitido'], 405);
    }
} catch (Throwable $e) {
    responderJson(['erro' => 'Erro interno: ' . $e->getMessage() . ' na linha ' . $e->getLine()], 500);
}

function validarLancamento(array $d): void {
    if (empty($d['descricao']) || empty($d['valor']) || empty($d['vencimento'])) {
        responderJson(['erro' => 'Descrição, valor e vencimento são obrigatórios'], 422);
    }
}

function criarCustoFixoFromLancamento(PDO $db, array $d): string {
    $id  = gerarId();
    $dia = (int)date('d', strtotime($d['vencimento']));
    $dia = max(1, min(28, $dia));

    $colunas = ['id', 'nome', 'valor', 'categoria', 'recorrencia', 'ativo'];
    $valores = ['?', '?', '?', '?', "'mensal'", '1'];
    $categoria = normalizarCategoriaParaTabela($db, 'custos_fixos', $d['categoria'] ?? 'outros');
    $params = [$id, $d['descricao'], $d['valor'], $categoria];

    if (tabelaTemColuna($db, 'custos_fixos', 'dia_vencimento')) {
        $colunas[] = 'dia_vencimento';
        $valores[] = '?';
        $params[] = $dia;
    }
    if (tabelaTemColuna($db, 'custos_fixos', 'forma_pagamento')) {
        $colunas[] = 'forma_pagamento';
        $valores[] = '?';
        $params[] = empty($d['forma_pagamento']) ? 'pix' : $d['forma_pagamento'];
    }

    $stmt = $db->prepare('INSERT INTO custos_fixos (' . implode(',', $colunas) . ') VALUES (' . implode(',', $valores) . ')');
    $stmt->execute($params);
    return $id;
}

function criarLancamento(PDO $db, array $d): void {
    $modalidade = $d['modalidade'] ?? 'avista';

    if ($modalidade === 'parcelado') {
        $total        = max(2, min(120, (int)($d['total_parcelas'] ?? 2)));
        $valorParcela = round((float)$d['valor'] / $total, 2);
        $vencBase     = new DateTime($d['vencimento']);
        $paiId        = gerarId();

        inserirLancamento($db, $paiId, $d, $valorParcela, $vencBase->format('Y-m-d'), null, 1, $total);
        for ($i = 2; $i <= $total; $i++) {
            $vencBase->modify('+30 days');
            inserirLancamento($db, gerarId(), $d, $valorParcela, $vencBase->format('Y-m-d'), $paiId, $i, $total);
        }

    } elseif ($modalidade === 'recorrente') {
        $freq     = $d['frequencia'] ?? 'mensal';
        $termino  = !empty($d['data_termino']) ? new DateTime($d['data_termino']) : null;
        $limite   = (new DateTime())->modify('+12 months');
        $fim      = $termino && $termino < $limite ? $termino : $limite;
        $venc     = new DateTime($d['vencimento']);
        $intervalo = match($freq) { 'semanal' => 'P7D', 'anual' => 'P1Y', default => 'P1M' };
        $paiId    = null;

        while ($venc <= $fim) {
            $id = gerarId();
            inserirLancamento($db, $id, $d, $d['valor'], $venc->format('Y-m-d'), $paiId);
            if (!$paiId) $paiId = $id;
            $venc->add(new DateInterval($intervalo));
        }
    } else {
        inserirLancamento($db, gerarId(), $d, $d['valor'], $d['vencimento']);
    }
}

function inserirLancamento(PDO $db, string $id, array $d, float $valor, string $venc, ?string $paiId = null, int $parcelaAtual = 1, ?int $totalParcelas = null): void {
    $colunas = ['id','tipo','descricao','valor','valor_pago','categoria','cliente_fornecedor','vencimento','status','modalidade','total_parcelas','parcela_atual','lancamento_pai_id','frequencia','data_termino','observacao'];
    $valores = ['?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','?'];
    
    $status = $d['status'] ?? 'pendente';
    $valorPago = isset($d['valor_pago']) ? (float)$d['valor_pago'] : 0;
    
    $params = [
        $id, $d['tipo'], $d['descricao'], $valor, $valorPago,
        $d['categoria'] ?? 'outros', empty($d['cliente_fornecedor']) ? null : $d['cliente_fornecedor'],
        $venc, $status, $d['modalidade'] ?? 'avista',
        $totalParcelas, $parcelaAtual, $paiId,
        empty($d['frequencia']) ? null : $d['frequencia'], 
        empty($d['data_termino']) ? null : $d['data_termino'],
        empty($d['observacao']) ? null : $d['observacao']
    ];

    if (tabelaTemColuna($db, 'lancamentos', 'forma_pagamento')) {
        $colunas[] = 'forma_pagamento';
        $valores[] = '?';
        $params[] = empty($d['forma_pagamento']) ? null : $d['forma_pagamento'];
    }
    if (tabelaTemColuna($db, 'lancamentos', 'custo_fixo_id')) {
        $colunas[] = 'custo_fixo_id';
        $valores[] = '?';
        $params[] = $d['custo_fixo_id'] ?? null;
    }
    if (tabelaTemColuna($db, 'lancamentos', 'conta_id')) {
        $colunas[] = 'conta_id';
        $valores[] = '?';
        $params[] = $d['conta_id'] ?? null;
    }

    $stmt = $db->prepare('INSERT INTO lancamentos (' . implode(',', $colunas) . ') VALUES (' . implode(',', $valores) . ')');
    $stmt->execute($params);
}
