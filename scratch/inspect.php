<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
$db = Database::get();
$stmt = $db->prepare("SELECT dados_json FROM propostas WHERE cliente_nome = ?");
$stmt->execute(['Wellington Poncem & Jeane Nunes']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
file_put_contents(__DIR__ . '/proposta_dados.txt', json_encode(json_decode($row['dados_json'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "OK\n";
