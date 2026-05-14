<?php
$content = "<STMTTRN>
<TRNTYPE>DEBIT
<DTPOSTED>20231010120000[-3:BRT]
<TRNAMT>-15.50
<FITID>12345
<MEMO>Compra padaria
<STMTTRN>
<TRNTYPE>CREDIT
<DTPOSTED>20231011120000
<TRNAMT>150.00
<FITID>54321
<MEMO>Pix recebido
</BANKTRANLIST>";

$parts = preg_split('/<STMTTRN>/i', $content);
array_shift($parts);

$transactions = [];
foreach ($parts as $txn) {
    preg_match('/<TRNTYPE>\s*(.*?)(?:\r|\n|<)/i', $txn, $typeMatch);
    preg_match('/<DTPOSTED>\s*(.*?)(?:\r|\n|<)/i', $txn, $dateMatch);
    preg_match('/<TRNAMT>\s*(.*?)(?:\r|\n|<)/i', $txn, $amtMatch);
    preg_match('/<FITID>\s*(.*?)(?:\r|\n|<)/i', $txn, $idMatch);
    preg_match('/<MEMO>\s*(.*?)(?:\r|\n|<)/i', $txn, $memoMatch);
    var_dump($typeMatch[1] ?? null, $dateMatch[1] ?? null, $amtMatch[1] ?? null, $memoMatch[1] ?? null);
}
