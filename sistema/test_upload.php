<?php
// Let's create a dummy OFX file and simulate the upload to see how the parser reacts
$ofx = "<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20231010120000[-3:BRT]
<TRNAMT>-15.50
<FITID>12345
<MEMO>Compra padaria
</STMTTRN>";

$_FILES['arquivo'] = [
    'tmp_name' => '/tmp/dummy.ofx',
    'error' => UPLOAD_ERR_OK
];
file_put_contents('/tmp/dummy.ofx', $ofx);

// Mock the environment
$_SERVER['REQUEST_METHOD'] = 'POST';

// We cannot easily run upload-ofx.php because it has exigirAutenticacao() which will redirect or die.
// Instead, let's just test the exact logic used in upload-ofx.php
$content = file_get_contents($_FILES['arquivo']['tmp_name']);

$transactions = [];
$parts = preg_split('/<STMTTRN>/i', $content);
array_shift($parts); // Remove the header

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
    
    $memo = trim($memoMatch[1] ?? '');
    if (!mb_check_encoding($memo, 'UTF-8')) {
        $memo = utf8_encode($memo);
    }

    if ($date && $amount > 0) {
        $transactions[] = [
            'fitid' => trim($idMatch[1] ?? uniqid()),
            'tipo' => $tipo,
            'data' => $date,
            'valor' => $amount,
            'descricao' => $memo,
        ];
    }
}
var_dump($transactions);
