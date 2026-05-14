<?php
ini_set('display_errors', 1);
require_once __DIR__ . '/config/database.php';
$db = Database::get();

$out = [];
$saldoInicialTotal = (float)$db->query("SELECT SUM(saldo_inicial) FROM contas_bancarias WHERE ativo=1")->fetchColumn();
$out['saldoInicialTotal'] = $saldoInicialTotal;

$fluxoInnerAtivos = (float)$db->query("
    SELECT SUM(CASE WHEN l.tipo='receber' THEN l.valor_pago ELSE -l.valor_pago END) 
    FROM lancamentos l
    INNER JOIN contas_bancarias c ON l.conta_id = c.id
    WHERE l.status IN ('pago', 'efetivado') AND c.ativo = 1
")->fetchColumn();
$out['fluxoInnerAtivos_sem_parcial'] = $fluxoInnerAtivos;

$fluxoInnerAtivosParcial = (float)$db->query("
    SELECT SUM(CASE WHEN l.tipo='receber' THEN l.valor_pago ELSE -l.valor_pago END) 
    FROM lancamentos l
    INNER JOIN contas_bancarias c ON l.conta_id = c.id
    WHERE l.status IN ('pago', 'efetivado', 'pago_parcial') AND c.ativo = 1
")->fetchColumn();
$out['fluxoInnerAtivos_com_parcial'] = $fluxoInnerAtivosParcial;

$fluxoTodas = (float)$db->query("
    SELECT SUM(CASE WHEN tipo='receber' THEN valor_pago ELSE -valor_pago END) 
    FROM lancamentos
    WHERE status IN ('pago', 'efetivado')
")->fetchColumn();
$out['fluxoTodas_sem_parcial'] = $fluxoTodas;

$fluxoTodasParcial = (float)$db->query("
    SELECT SUM(CASE WHEN tipo='receber' THEN valor_pago ELSE -valor_pago END) 
    FROM lancamentos
    WHERE status IN ('pago', 'efetivado', 'pago_parcial')
")->fetchColumn();
$out['fluxoTodas_com_parcial'] = $fluxoTodasParcial;

// Check accounts
$contas = $db->query("SELECT id, nome, saldo_inicial FROM contas_bancarias WHERE ativo=1")->fetchAll(PDO::FETCH_ASSOC);
$contas_info = [];
foreach ($contas as $c) {
    $calc = $db->prepare("SELECT SUM(CASE WHEN tipo='receber' THEN valor_pago ELSE -valor_pago END) as f FROM lancamentos WHERE conta_id = ? AND status IN ('pago', 'efetivado', 'pago_parcial')");
    $calc->execute([$c['id']]);
    $f = $calc->fetchColumn();
    $contas_info[] = [
        'nome' => $c['nome'],
        'saldo_inicial' => $c['saldo_inicial'],
        'fluxo' => $f,
        'total' => $c['saldo_inicial'] + $f
    ];
}
$out['contas'] = $contas_info;

echo json_encode($out, JSON_PRETTY_PRINT);
