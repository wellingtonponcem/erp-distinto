<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d  = lerCorpo();
$id = $d['id'] ?? '';
$valorNovo = (float)($d['valor'] ?? 0);

if (!$id || $valorNovo <= 0) {
    responderJson(['erro' => 'ID e valor são obrigatórios'], 422);
}

$db = Database::get();
$stmt = $db->prepare('SELECT id, valor, valor_pago, conciliado FROM lancamentos WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$lanc = $stmt->fetch();

if (!$lanc) {
    responderJson(['erro' => 'Lançamento não encontrado'], 404);
}

$novoPago = min((float)$lanc['valor_pago'] + $valorNovo, (float)$lanc['valor']);
$novoStatus = calcularStatusAtualizado((float)$lanc['valor'], $novoPago, date('Y-m-d'));

$conciliado = !empty($d['conciliado']) ? 1 : 0;
$novoConciliado = ( (!empty($lanc['conciliado']) && (int)$lanc['conciliado'] === 1) || $conciliado === 1 ) ? 1 : 0;

$dataPagamento = null;
if ($novoPago >= (float)$lanc['valor']) {
    $dataPagamento = $d['data_pagamento'] ?? date('Y-m-d');
}

$upd = $db->prepare('UPDATE lancamentos SET valor_pago=?, status=?, conciliado=?, data_pagamento=? WHERE id=?');
$upd->execute([$novoPago, $novoStatus, $novoConciliado, $dataPagamento, $id]);

responderJson(['ok' => true, 'status' => $novoStatus, 'valor_pago' => $novoPago, 'conciliado' => $novoConciliado]);
