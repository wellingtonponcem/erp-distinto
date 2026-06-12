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
            SELECT /* bypass_cache_v2 */ *,
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

        // Prevenir duplicação de importação OFX - verificar se fitid já existe
        if (!empty($d['ofx_fitid'])) {
            $stmt = $db->prepare("SELECT id FROM lancamentos WHERE ofx_fitid = ? AND ofx_fitid IS NOT NULL AND ofx_fitid != '' LIMIT 1");
            $stmt->execute([$d['ofx_fitid']]);
            if ($stmt->fetch()) {
                responderJson(['erro' => 'Esta transação OFX já foi importada anteriormente.'], 409);
            }
        }

        // Se marcado como custo fixo, criar/vincular custo_fixo
        if (($d['tipo'] ?? '') === 'pagar' && !empty($d['e_custo_fixo']) && empty($d['custo_fixo_id'])) {
            $d['custo_fixo_id'] = criarCustoFixoFromLancamento($db, $d);
        }

        // Auto-cadastro de cliente/fornecedor se vier documento
        if (!empty($d['entidade_documento'])) {
            $d = autoCadastrarEntidade($db, $d);
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
        
        if (tabelaTemColuna($db, 'lancamentos', 'cliente_id')) {
            $sets[] = 'cliente_id=?';
            $params[] = empty($d['cliente_id']) ? null : $d['cliente_id'];
        }
        if (tabelaTemColuna($db, 'lancamentos', 'fornecedor_id')) {
            $sets[] = 'fornecedor_id=?';
            $params[] = empty($d['fornecedor_id']) ? null : $d['fornecedor_id'];
        }
        
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

function obterNomeClienteFornecedor(PDO $db, string $id, string $tipo): ?string {
    if ($tipo === 'clientes') {
        $stmt = $db->prepare('SELECT nome FROM clientes WHERE id = ?');
    } elseif ($tipo === 'fornecedores') {
        $stmt = $db->prepare('SELECT nome FROM fornecedores WHERE id = ?');
    } else {
        return null;
    }
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? $row['nome'] : null;
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
    $dataPagamento = isset($d['valor_pago']) && $d['valor_pago'] > 0 ? date('Y-m-d') : null;
    $clienteFornecedorTexto = empty($d['cliente_fornecedor']) ? null : $d['cliente_fornecedor'];
    if (empty($clienteFornecedorTexto) && !empty($d['cliente_id'])) {
        $clienteFornecedorTexto = obterNomeClienteFornecedor($db, $d['cliente_id'], 'clientes');
    }
    if (empty($clienteFornecedorTexto) && !empty($d['fornecedor_id'])) {
        $clienteFornecedorTexto = obterNomeClienteFornecedor($db, $d['fornecedor_id'], 'fornecedores');
    }

    $params = [
        $id, $d['tipo'], $d['descricao'], $valor, $valorPago,
        $d['categoria'] ?? 'outros', $clienteFornecedorTexto,
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
    if (tabelaTemColuna($db, 'lancamentos', 'cliente_id')) {
        $colunas[] = 'cliente_id';
        $valores[] = '?';
        $params[] = $d['cliente_id'] ?? null;
    }
    if (tabelaTemColuna($db, 'lancamentos', 'fornecedor_id')) {
        $colunas[] = 'fornecedor_id';
        $valores[] = '?';
        $params[] = $d['fornecedor_id'] ?? null;
    }
    if (tabelaTemColuna($db, 'lancamentos', 'conta_id')) {
        $colunas[] = 'conta_id';
        $valores[] = '?';
        $params[] = $d['conta_id'] ?? null;
    }
    if (tabelaTemColuna($db, 'lancamentos', 'ofx_fitid')) {
        $colunas[] = 'ofx_fitid';
        $valores[] = '?';
        $params[] = $d['ofx_fitid'] ?? null;
    }
    if (tabelaTemColuna($db, 'lancamentos', 'data_pagamento')) {
        $colunas[] = 'data_pagamento';
        $valores[] = '?';
        $params[] = $dataPagamento;
    }

    $stmt = $db->prepare('INSERT INTO lancamentos (' . implode(',', $colunas) . ') VALUES (' . implode(',', $valores) . ')');
    $stmt->execute($params);
}

function autoCadastrarEntidade(PDO $db, array $d): array {
    $tipo = $d['tipo'] === 'receber' ? 'clientes' : 'fornecedores';
    $doc = preg_replace('/\D/', '', $d['entidade_documento']);
    $nome = $d['cliente_fornecedor'] ?: 'Nova Entidade';
    
    // Busca por documento
    $colDoc = $tipo === 'clientes' ? 'cpf_cnpj' : 'cpf_cnpj'; // Agora ambos têm cpf_cnpj
    $stmt = $db->prepare("SELECT id FROM $tipo WHERE $colDoc = ? LIMIT 1");
    $stmt->execute([$doc]);
    $id = $stmt->fetchColumn();

    if (!$id) {
        $id = gerarId();
        if ($tipo === 'clientes') {
            $stmt = $db->prepare("INSERT INTO clientes (id, nome, cpf_cnpj) VALUES (?, ?, ?)");
            $stmt->execute([$id, $nome, $doc]);
        } else {
            // Fornecedores podem ter campos diferentes, mas garantimos cpf_cnpj
            $stmt = $db->prepare("INSERT INTO fornecedores (id, nome, cpf_cnpj) VALUES (?, ?, ?)");
            $stmt->execute([$id, $nome, $doc]);
        }
    }

    if ($d['tipo'] === 'receber') {
        $d['cliente_id'] = $id;
    } else {
        $d['fornecedor_id'] = $id;
    }

    return $d;
}
