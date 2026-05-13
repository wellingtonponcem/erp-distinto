<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

$db   = Database::get();
$dias = (int)($_GET['dias'] ?? 30);
$dias = in_array($dias, [30, 60, 90]) ? $dias : 30;
$hoje = date('Y-m-d');

$stmt = $db->prepare("
    SELECT
        DATE(vencimento) as data,
        SUM(CASE WHEN tipo='receber' THEN valor ELSE 0 END) as entradas,
        SUM(CASE WHEN tipo='pagar'   THEN valor ELSE 0 END) as saidas
    FROM lancamentos
    WHERE vencimento BETWEEN ? AND DATE_ADD(?, INTERVAL ? DAY)
      AND status IN ('pendente','pago_parcial')
    GROUP BY DATE(vencimento)
    ORDER BY data ASC
");
$stmt->execute([$hoje, $hoje, $dias]);
$rows = $stmt->fetchAll();

responderJson($rows);
