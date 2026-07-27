<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/asaas.php';

exigirAutenticacao();

$db = Database::get();
$metodo = $_SERVER['REQUEST_METHOD'];

// Migração Automática
try {
    $db->exec("CREATE TABLE IF NOT EXISTS contas_bancarias (
        id VARCHAR(50) PRIMARY KEY,
        nome VARCHAR(100) NOT NULL,
        saldo_inicial DECIMAL(15,2) DEFAULT 0,
        cor VARCHAR(20) DEFAULT '#2a2a2a',
        ativo TINYINT(1) DEFAULT 1,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Adicionar conta_id em lancamentos se não existir (compatível com MySQL e PostgreSQL)
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'pgsql') {
        $stmt = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_name='lancamentos' AND column_name='conta_id'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE lancamentos ADD COLUMN conta_id VARCHAR(50)");
        }
    } else {
        $stmt = $db->query("SHOW COLUMNS FROM lancamentos LIKE 'conta_id'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE lancamentos ADD COLUMN conta_id VARCHAR(50) NULL");
        }
    }
} catch (Exception $e) {}

switch ($metodo) {
    case 'GET':
        $contas = $db->query("SELECT * FROM contas_bancarias WHERE ativo=1 ORDER BY nome")->fetchAll();
        
        $asaas = new AsaasService();
        $asaasConfigurado = $asaas->estaConfigurado();
        $saldoAsaasApi = null;

        if ($asaasConfigurado) {
            try {
                $dadosPainel = $asaas->obterSaldoEExtrato();
                $saldoAsaasApi = $dadosPainel['saldo'] ?? null;
            } catch (Exception $e) {
                // Silencioso — fallback para cálculo local
            }
        }

        // Calcular saldo atual somando saldo inicial + lancamentos (receber - pagar)
        foreach ($contas as &$c) {
            // Para a conta Asaas, usar saldo real da API quando disponível
            if ($c['id'] === 'asaas' && $saldoAsaasApi !== null) {
                $c['saldo_atual'] = $saldoAsaasApi;
                $c['saldo_asaas_api'] = true;
            } else {
                $calc = $db->prepare("
                    SELECT 
                        SUM(CASE WHEN tipo='receber' THEN valor_pago ELSE -valor_pago END) as fluxo
                    FROM lancamentos 
                    WHERE conta_id = ? AND valor_pago > 0
                ");
                $calc->execute([$c['id']]);
                $fluxo = $calc->fetch()['fluxo'] ?? 0;
                $c['saldo_atual'] = (float)$c['saldo_inicial'] + (float)$fluxo;
                $c['saldo_asaas_api'] = false;
            }
        }
        
        responderJson($contas);
        break;

    case 'POST':
        $d = lerCorpo();
        if (empty($d['nome'])) responderJson(['erro' => 'Nome obrigatório'], 422);
        
        $id = gerarId();
        $stmt = $db->prepare("INSERT INTO contas_bancarias (id, nome, saldo_inicial, cor) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $id,
            $d['nome'],
            $d['saldo_inicial'] ?? 0,
            $d['cor'] ?? '#2a2a2a'
        ]);
        
        responderJson(['ok' => true, 'id' => $id], 201);
        break;

    case 'PUT':
        $d = lerCorpo();
        if (empty($d['id'])) responderJson(['erro' => 'ID obrigatório'], 422);

        $ajuste_saldo = isset($d['ajuste_saldo']) ? (float)$d['ajuste_saldo'] : 0;
        $tipo_ajuste = $d['tipo_ajuste'] ?? 'lancamento';

        if ($ajuste_saldo != 0) {
            if ($tipo_ajuste === 'inicial') {
                $d['saldo_inicial'] = (float)($d['saldo_inicial'] ?? 0) + $ajuste_saldo;
            } else if ($tipo_ajuste === 'lancamento') {
                $idLanc = gerarId();
                $tipoLanc = $ajuste_saldo > 0 ? 'receber' : 'pagar';
                $valorAbs = abs($ajuste_saldo);
                $hoje = date('Y-m-d');
                $stmtLanc = $db->prepare("
                    INSERT INTO lancamentos 
                    (id, tipo, descricao, valor, valor_pago, vencimento, status, categoria, conta_id, data_pagamento)
                    VALUES (?, ?, ?, ?, ?, ?, 'pago', 'outros', ?, ?)
                ");
                $stmtLanc->execute([
                    $idLanc,
                    $tipoLanc,
                    'Ajuste de Saldo',
                    $valorAbs,
                    $valorAbs,
                    $hoje,
                    $d['id'],
                    $hoje
                ]);
            }
        }
        
        $stmt = $db->prepare("UPDATE contas_bancarias SET nome=?, saldo_inicial=?, cor=? WHERE id=?");
        $stmt->execute([
            $d['nome'],
            $d['saldo_inicial'] ?? 0,
            $d['cor'] ?? '#2a2a2a',
            $d['id']
        ]);
        
        responderJson(['ok' => true]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (!$id) responderJson(['erro' => 'ID obrigatório'], 422);
        
        // Soft delete
        $db->prepare("UPDATE contas_bancarias SET ativo=0 WHERE id=?")->execute([$id]);
        responderJson(['ok' => true]);
        break;

    default:
        responderJson(['erro' => 'Método não permitido'], 405);
}
