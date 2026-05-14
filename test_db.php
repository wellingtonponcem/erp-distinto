<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();
$mesInicio = date('Y-m-01');
$mesFim = date('Y-m-t');
$queryResumo = $db->prepare("
    SELECT
        SUM(CASE WHEN tipo='receber' THEN valor_pago ELSE 0 END) as total_recebido,
        SUM(CASE WHEN tipo='pagar' THEN valor_pago ELSE 0 END) as total_pago,
        SUM(CASE WHEN tipo='receber' AND vencimento BETWEEN ? AND ? AND status NOT IN ('pago','cancelado') THEN (valor - valor_pago) ELSE 0 END) as receber_mes,
        SUM(CASE WHEN tipo='pagar'   AND vencimento BETWEEN ? AND ? AND status NOT IN ('pago','cancelado') THEN (valor - valor_pago) ELSE 0 END) as pagar_mes
    FROM lancamentos WHERE status != 'cancelado'
");
$queryResumo->execute([$mesInicio, $mesFim, $mesInicio, $mesFim]);
$resumo = $queryResumo->fetch();

$c = $db->query("SELECT id, tipo, valor, valor_pago, status, vencimento FROM lancamentos ORDER BY id DESC LIMIT 5")->fetchAll();

echo json_encode(['resumo' => $resumo, 'lancamentos' => $c], JSON_PRETTY_PRINT);
