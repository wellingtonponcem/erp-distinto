<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/financeiro_custos.php';

exigirAutenticacao();

$db = Database::get();
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    garantirEstruturaFinanceira($db);
    switch ($metodo) {
    case 'GET':
        sincronizarLancamentosCustosFixos($db);
        $rows = $db->query('SELECT * FROM custos_fixos ORDER BY categoria, nome')->fetchAll();
        responderJson($rows);

    case 'POST':
        $d = lerCorpo();
        validarCustoFixo($d);

        $id = gerarId();
        $colunas = ['id', 'nome', 'valor', 'categoria', 'recorrencia', 'ativo'];
        $valores = ['?', '?', '?', '?', '?', '1'];
        $params = [
            $id,
            $d['nome'],
            $d['valor'],
            normalizarCategoriaParaTabela($db, 'custos_fixos', $d['categoria'] ?? 'outros'),
            $d['recorrencia'] ?? 'mensal',
        ];
        if (tabelaTemColuna($db, 'custos_fixos', 'dia_vencimento')) {
            $colunas[] = 'dia_vencimento';
            $valores[] = '?';
            $params[] = $d['dia_vencimento'] ?? 5;
        }
        if (tabelaTemColuna($db, 'custos_fixos', 'forma_pagamento')) {
            $colunas[] = 'forma_pagamento';
            $valores[] = '?';
            $params[] = $d['forma_pagamento'] ?? 'pix';
        }
        $stmt = $db->prepare('INSERT INTO custos_fixos (' . implode(',', $colunas) . ') VALUES (' . implode(',', $valores) . ')');
        $stmt->execute($params);

        $d['id'] = $id;
        gerarLancamentosParaCustoFixo($db, $d);
        responderJson(['ok' => true, 'id' => $id], 201);

    case 'PUT':
        $d = lerCorpo();
        if (empty($d['id'])) responderJson(['erro' => 'ID obrigatorio'], 422);
        validarCustoFixo($d);

        $sets = ['nome=?', 'valor=?', 'categoria=?', 'recorrencia=?', 'ativo=?'];
        $params = [
            $d['nome'],
            $d['valor'],
            normalizarCategoriaParaTabela($db, 'custos_fixos', $d['categoria'] ?? 'outros'),
            $d['recorrencia'] ?? 'mensal',
            $d['ativo'] ?? 1,
        ];
        if (tabelaTemColuna($db, 'custos_fixos', 'dia_vencimento')) {
            $sets[] = 'dia_vencimento=?';
            $params[] = $d['dia_vencimento'] ?? 5;
        }
        if (tabelaTemColuna($db, 'custos_fixos', 'forma_pagamento')) {
            $sets[] = 'forma_pagamento=?';
            $params[] = $d['forma_pagamento'] ?? 'pix';
        }
        $params[] = $d['id'];
        $stmt = $db->prepare('UPDATE custos_fixos SET ' . implode(', ', $sets) . ' WHERE id=?');
        $stmt->execute($params);

        atualizarLancamentosFuturos($db, $d['id'], $d);
        sincronizarLancamentosCustosFixos($db);
        responderJson(['ok' => true]);

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (!$id) responderJson(['erro' => 'ID obrigatorio'], 422);
        if (tabelaTemColuna($db, 'lancamentos', 'custo_fixo_id')) {
            $db->prepare("
                DELETE FROM lancamentos
                WHERE custo_fixo_id=?
                  AND status IN ('pendente','atrasado')
                  AND vencimento >= CURDATE()
            ")->execute([$id]);
        }
        $db->prepare('DELETE FROM custos_fixos WHERE id=?')->execute([$id]);
        responderJson(['ok' => true]);

        default:
            responderJson(['erro' => 'Metodo nao permitido'], 405);
    }
} catch (Throwable $e) {
    responderJson(['erro' => 'Erro interno: ' . $e->getMessage() . ' na linha ' . $e->getLine()], 500);
}

function validarCustoFixo(array $d): void {
    if (empty($d['nome']) || empty($d['valor'])) {
        responderJson(['erro' => 'Nome e valor sao obrigatorios'], 422);
    }
}

function atualizarLancamentosFuturos(PDO $db, string $custoId, array $d): void {
    if (!tabelaTemColuna($db, 'lancamentos', 'custo_fixo_id')) return;

    $sets = ['descricao=?', 'valor=?', 'categoria=?'];
    $params = [
        $d['nome'],
        $d['valor'],
        normalizarCategoriaParaTabela($db, 'lancamentos', $d['categoria'] ?? 'outros'),
    ];
    if (tabelaTemColuna($db, 'lancamentos', 'forma_pagamento')) {
        $sets[] = 'forma_pagamento=?';
        $params[] = $d['forma_pagamento'] ?? 'pix';
    }
    $params[] = $custoId;

    $stmt = $db->prepare("
        UPDATE lancamentos
        SET " . implode(', ', $sets) . "
        WHERE custo_fixo_id=?
          AND status IN ('pendente','atrasado')
          AND vencimento >= CURDATE()
    ");
    $stmt->execute($params);
}
