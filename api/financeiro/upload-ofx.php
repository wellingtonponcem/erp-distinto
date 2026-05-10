<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/financeiro_custos.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['erro' => 'Nenhum arquivo enviado ou erro no upload']);
    exit;
}

$db = Database::get();
try {
    garantirEstruturaFinanceira($db);
} catch (Throwable $e) {}

$content = file_get_contents($_FILES['arquivo']['tmp_name']);

$transactions = [];
$parts = preg_split('/<STMTTRN>/i', $content);
array_shift($parts); // Remove the header

$fitids_to_check = [];
$parsed_txns = [];

foreach ($parts as $txn) {
    preg_match('/<TRNTYPE>\s*(.*?)(?:\r|\n|<)/i', $txn, $typeMatch);
    preg_match('/<DTPOSTED>\s*(.*?)(?:\r|\n|<)/i', $txn, $dateMatch);
    preg_match('/<TRNAMT>\s*(.*?)(?:\r|\n|<)/i', $txn, $amtMatch);
    preg_match('/<FITID>\s*(.*?)(?:\r|\n|<)/i', $txn, $idMatch);
    preg_match('/<MEMO>\s*(.*?)(?:\r|\n|<)/i', $txn, $memoMatch);

    $dateStr = substr(trim($dateMatch[1] ?? ''), 0, 8);
    $date = '';
    if (strlen($dateStr) === 8) {
        $date = substr($dateStr, 0, 4) . '-' . substr($dateStr, 4, 2) . '-' . substr($dateStr, 6, 2);
    }

    $amountRaw = (float)trim($amtMatch[1] ?? '0');
    $tipo = $amountRaw >= 0 ? 'receber' : 'pagar';
    $amount = abs($amountRaw);
    
    // Some banks use ISO-8859-1 in OFX
    $memo = trim($memoMatch[1] ?? '');
    if (!mb_check_encoding($memo, 'UTF-8')) {
        $memo = mb_convert_encoding($memo, 'UTF-8', 'ISO-8859-1');
    }

    $fitid = trim($idMatch[1] ?? uniqid());

    if ($date && $amount > 0) {
        $parsed_txns[] = [
            'fitid' => $fitid,
            'tipo' => $tipo,
            'data' => $date,
            'valor' => $amount,
            'descricao' => $memo,
        ];
        if ($fitid) $fitids_to_check[] = $fitid;
    }
}

$existing_fitids = [];
if (!empty($fitids_to_check)) {
    $in = str_repeat('?,', count($fitids_to_check) - 1) . '?';
    $stmt = $db->prepare("SELECT ofx_fitid FROM lancamentos WHERE ofx_fitid IN ($in) AND ofx_fitid IS NOT NULL AND ofx_fitid != ''");
    $stmt->execute($fitids_to_check);
    $existing_fitids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

foreach ($parsed_txns as $t) {
    if (!in_array($t['fitid'], $existing_fitids, true)) {
        $transactions[] = $t;
    }
}

if (empty($transactions)) {
    http_response_code(400);
    echo json_encode(['erro' => empty($parsed_txns) ? 'Nenhuma transação encontrada no arquivo OFX.' : 'Todas as transações deste arquivo OFX já foram importadas anteriormente.']);
    exit;
}

echo json_encode(['ok' => true, 'transacoes' => $transactions]);
