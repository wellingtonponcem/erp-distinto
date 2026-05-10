<?php

function garantirEstruturaFinanceira(PDO $db): void {
    try {
        garantirColuna($db, 'custos_fixos', 'dia_vencimento', "INT NOT NULL DEFAULT 5");
        garantirColuna($db, 'custos_fixos', 'forma_pagamento', "VARCHAR(50) NULL DEFAULT 'pix'");
        garantirColuna($db, 'lancamentos', 'forma_pagamento', "VARCHAR(50) NULL");
        garantirColuna($db, 'lancamentos', 'custo_fixo_id', "VARCHAR(32) NULL");
        garantirColuna($db, 'lancamentos', 'ofx_fitid', "VARCHAR(100) NULL");

        $categoria = colunaInfo($db, 'custos_fixos', 'categoria');
        if ($categoria && substr(strtolower($categoria['Type']), 0, 5) === 'enum(') {
            $db->exec("ALTER TABLE custos_fixos MODIFY categoria VARCHAR(100) NOT NULL DEFAULT 'outros'");
        }
    } catch (Exception $e) {
        // Em producao, a falta de permissao para ALTER nao pode derrubar o sistema.
    }
}

function garantirColuna(PDO $db, string $tabela, string $coluna, string $definicao): void {
    if (colunaInfo($db, $tabela, $coluna)) return;
    $db->exec("ALTER TABLE {$tabela} ADD COLUMN {$coluna} {$definicao}");
}

function colunaInfo(PDO $db, string $tabela, string $coluna): ?array {
    $tabelasPermitidas = ['custos_fixos', 'lancamentos'];
    if (!in_array($tabela, $tabelasPermitidas, true)) return null;

    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM {$tabela} LIKE ?");
        $stmt->execute([$coluna]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function tabelaTemColuna(PDO $db, string $tabela, string $coluna): bool {
    return colunaInfo($db, $tabela, $coluna) !== null;
}

function normalizarCategoriaParaTabela(PDO $db, string $tabela, string $categoria): string {
    $info = colunaInfo($db, $tabela, 'categoria');
    if (!$info) return $categoria ?: 'outros';

    $tipo = strtolower($info['Type'] ?? '');
    if (substr($tipo, 0, 5) !== 'enum(') return $categoria ?: 'outros';

    preg_match_all("/'([^']+)'/", $info['Type'], $matches);
    $permitidas = $matches[1] ?? [];
    if (in_array($categoria, $permitidas, true)) return $categoria;
    return in_array('outros', $permitidas, true) ? 'outros' : ($permitidas[0] ?? 'outros');
}

function sincronizarLancamentosCustosFixos(PDO $db, int $meses = 12): void {
    garantirEstruturaFinanceira($db);

    $temDia = tabelaTemColuna($db, 'custos_fixos', 'dia_vencimento');
    $temFormaCusto = tabelaTemColuna($db, 'custos_fixos', 'forma_pagamento');

    $campos = ['id', 'nome', 'valor', 'categoria', 'recorrencia'];
    if ($temDia) $campos[] = 'dia_vencimento';
    if ($temFormaCusto) $campos[] = 'forma_pagamento';

    $custos = $db->query("
        SELECT " . implode(', ', $campos) . "
        FROM custos_fixos
        WHERE ativo = 1
        ORDER BY nome
    ")->fetchAll();

    foreach ($custos as $custo) {
        gerarLancamentosParaCustoFixo($db, $custo, $meses);
    }
}

function gerarLancamentosParaCustoFixo(PDO $db, array $custo, int $meses = 12): void {
    $dia = max(1, min(28, (int)($custo['dia_vencimento'] ?? 5)));
    $hoje = new DateTime('today');
    $base = new DateTime('first day of this month');

    for ($i = 0; $i < $meses; $i++) {
        $venc = clone $base;
        $venc->modify("+{$i} months");
        $venc->setDate((int)$venc->format('Y'), (int)$venc->format('m'), $dia);

        if ($venc < $hoje) continue;
        if (($custo['recorrencia'] ?? 'mensal') === 'anual' && $i > 0) continue;
        if (existeLancamentoCustoFixoNoMes($db, $custo, $venc)) continue;

        inserirContaPagarCustoFixo($db, $custo, $venc);
    }
}

function existeLancamentoCustoFixoNoMes(PDO $db, array $custo, DateTime $venc): bool {
    if (tabelaTemColuna($db, 'lancamentos', 'custo_fixo_id')) {
        $stmt = $db->prepare("
            SELECT id FROM lancamentos
            WHERE custo_fixo_id = ?
              AND TO_CHAR(vencimento, 'YYYY-MM') = ?
              AND status != 'cancelado'
            LIMIT 1
        ");
        $stmt->execute([$custo['id'], $venc->format('Y-m')]);
        return (bool)$stmt->fetch();
    }

    $stmt = $db->prepare("
        SELECT id FROM lancamentos
        WHERE tipo = 'pagar'
          AND descricao = ?
          AND TO_CHAR(vencimento, 'YYYY-MM') = ?
          AND status != 'cancelado'
        LIMIT 1
    ");
    $stmt->execute([$custo['nome'], $venc->format('Y-m')]);
    return (bool)$stmt->fetch();
}

function inserirContaPagarCustoFixo(PDO $db, array $custo, DateTime $venc): void {
    $temFormaLanc = tabelaTemColuna($db, 'lancamentos', 'forma_pagamento');
    $temCustoId = tabelaTemColuna($db, 'lancamentos', 'custo_fixo_id');

    $colunas = ['id', 'tipo', 'descricao', 'valor', 'valor_pago', 'categoria', 'vencimento', 'status', 'modalidade'];
    $valoresSql = ['?', "'pagar'", '?', '?', '0', '?', '?', "'pendente'", "'avista'"];
    $params = [
        gerarId(),
        $custo['nome'],
        $custo['valor'],
        normalizarCategoriaParaTabela($db, 'lancamentos', $custo['categoria'] ?? 'outros'),
        $venc->format('Y-m-d'),
    ];

    if ($temFormaLanc) {
        $colunas[] = 'forma_pagamento';
        $valoresSql[] = '?';
        $params[] = $custo['forma_pagamento'] ?? 'pix';
    }

    if ($temCustoId) {
        $colunas[] = 'custo_fixo_id';
        $valoresSql[] = '?';
        $params[] = $custo['id'];
    }

    $stmt = $db->prepare('INSERT INTO lancamentos (' . implode(',', $colunas) . ') VALUES (' . implode(',', $valoresSql) . ')');
    $stmt->execute($params);
}
