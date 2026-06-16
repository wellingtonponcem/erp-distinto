<?php

function garantirEstruturaFinanceira(PDO $db): void {
    try {
        if (tabelaTemColuna($db, 'contratos', 'asaas_cobranca_gerada')) {
            return;
        }

        garantirColuna($db, 'custos_fixos', 'dia_vencimento', "INT NOT NULL DEFAULT 5");
        garantirColuna($db, 'custos_fixos', 'forma_pagamento', "VARCHAR(50) NULL DEFAULT 'pix'");
        garantirColuna($db, 'lancamentos', 'forma_pagamento', "VARCHAR(50) NULL");
        garantirColuna($db, 'lancamentos', 'custo_fixo_id', "VARCHAR(32) NULL");
        garantirColuna($db, 'lancamentos', 'ofx_fitid', "VARCHAR(100) NULL");
        garantirColuna($db, 'lancamentos', 'data_pagamento', "DATE NULL");
        garantirColuna($db, 'fornecedores', 'cpf_cnpj', "VARCHAR(20) NULL");

        // Colunas do Asaas
        garantirColuna($db, 'configuracao_empresa', 'asaas_api_key', "TEXT NULL");
        garantirColuna($db, 'configuracao_empresa', 'asaas_mode', "VARCHAR(10) DEFAULT 'test'");
        garantirColuna($db, 'configuracao_empresa', 'asaas_webhook_token', "VARCHAR(255) NULL");
        garantirColuna($db, 'clientes', 'asaas_customer_id', "VARCHAR(100) NULL");
        garantirColuna($db, 'lancamentos', 'asaas_id', "VARCHAR(100) NULL");
        garantirColuna($db, 'lancamentos', 'asaas_boleto_url', "VARCHAR(512) NULL");
        garantirColuna($db, 'lancamentos', 'asaas_pix_qr_code', "TEXT NULL");
        garantirColuna($db, 'lancamentos', 'asaas_invoice_url', "VARCHAR(512) NULL");
        garantirColuna($db, 'contratos', 'asaas_cobranca_gerada', "INT DEFAULT 0");

        // Garantir constraint único para ofx_fitid (evita duplicatas)
        garantirConstraintUnico($db, 'lancamentos', 'ofx_fitid', 'unique_ofx_fitid');

        $categoria = colunaInfo($db, 'custos_fixos', 'categoria');
        if ($categoria && substr(strtolower($categoria['Type']), 0, 5) === 'enum(') {
            $db->exec("ALTER TABLE custos_fixos MODIFY categoria VARCHAR(100) NOT NULL DEFAULT 'outros'");
        }
    } catch (Exception $e) {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            try {
                $db->exec('ROLLBACK');
            } catch (Exception $rollEx) {}
        }
    }
}

function garantirConstraintUnico(PDO $db, string $tabela, string $coluna, string $constraintName): void {
    try {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            // PostgreSQL - Primeiro limpa duplicatas, depois cria o index único parcial
            $db->exec("
                DELETE FROM {$tabela} t1 USING {$tabela} t2
                WHERE t1.id > t2.id 
                  AND t1.{$coluna} = t2.{$coluna} 
                  AND t1.{$coluna} IS NOT NULL 
                  AND t1.{$coluna} != ''
            ");
            $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS {$constraintName} ON {$tabela} ({$coluna}) WHERE {$coluna} IS NOT NULL AND {$coluna} != ''");
        } else {
            // MySQL - verifica se já existe index único
            $stmt = $db->prepare("SHOW INDEX FROM {$tabela} WHERE Key_name = ? AND Column_name = ?");
            $stmt->execute([$constraintName, $coluna]);
            if (!$stmt->fetch()) {
                // Primeiro limpa duplicatas, depois cria o constraint
                $dupStmt = $db->prepare("
                    DELETE t1 FROM lancamentos t1
                    INNER JOIN lancamentos t2 
                    WHERE t1.id > t2.id 
                      AND t1.ofx_fitid = t2.ofx_fitid 
                      AND t1.ofx_fitid IS NOT NULL 
                      AND t1.ofx_fitid != ''
                ");
                $dupStmt->execute();
                $db->exec("ALTER TABLE {$tabela} ADD CONSTRAINT {$constraintName} UNIQUE ({$coluna})");
            }
        }
    } catch (Exception $e) {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            try {
                $db->exec('ROLLBACK');
            } catch (Exception $rollEx) {}
        }
    }
}

function garantirColuna(PDO $db, string $tabela, string $coluna, string $definicao): void {
    if (colunaInfo($db, $tabela, $coluna)) return;
    $db->exec("ALTER TABLE {$tabela} ADD COLUMN {$coluna} {$definicao}");
}

function colunaInfo(PDO $db, string $tabela, string $coluna): ?array {
    $tabelasPermitidas = ['custos_fixos', 'lancamentos', 'clientes', 'fornecedores', 'configuracao_empresa', 'contratos'];
    if (!in_array($tabela, $tabelasPermitidas, true)) return null;

    try {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $stmt = $db->prepare("SELECT column_name as \"Field\", data_type as \"Type\" FROM information_schema.columns WHERE table_name = ? AND column_name = ?");
            $stmt->execute([$tabela, $coluna]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } else {
            $stmt = $db->prepare("SHOW COLUMNS FROM {$tabela} LIKE ?");
            $stmt->execute([$coluna]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        }
    } catch (Exception $e) {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            try {
                $db->exec('ROLLBACK');
            } catch (Exception $rollEx) {}
        }
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
