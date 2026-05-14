<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();

$saldoInicialTotal = (float)$db->query("SELECT SUM(saldo_inicial) FROM contas_bancarias WHERE ativo=1")->fetchColumn();
$saldoInicialTodas = (float)$db->query("SELECT SUM(saldo_inicial) FROM contas_bancarias")->fetchColumn();

$fluxoInnerAtivos = (float)$db->query("
    SELECT SUM(CASE WHEN l.tipo='receber' THEN l.valor_pago ELSE -l.valor_pago END) 
    FROM lancamentos l
    INNER JOIN contas_bancarias c ON l.conta_id = c.id
    WHERE l.status IN ('pago', 'efetivado') AND c.ativo = 1
")->fetchColumn();

$fluxoTodas = (float)$db->query("
    SELECT SUM(CASE WHEN tipo='receber' THEN valor_pago ELSE -valor_pago END) 
    FROM lancamentos
    WHERE status IN ('pago', 'efetivado')
")->fetchColumn();

$fluxoComParciais = (float)$db->query("
    SELECT SUM(CASE WHEN tipo='receber' THEN valor_pago ELSE -valor_pago END) 
    FROM lancamentos
    WHERE status IN ('pago', 'efetivado', 'pago_parcial')
")->fetchColumn();

echo "Inner Ativos: " . ($saldoInicialTotal + $fluxoInnerAtivos) . "\n";
echo "Todas (sem saldo inicial inativas): " . ($saldoInicialTotal + $fluxoTodas) . "\n";
echo "Todas (com saldo inicial inativas): " . ($saldoInicialTodas + $fluxoTodas) . "\n";
echo "Todas + Parciais: " . ($saldoInicialTodas + $fluxoComParciais) . "\n";
