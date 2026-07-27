<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

$rawPayload = '{
    "token": "ff8080814c11e237014c1ff593b57b4d",
    "issuer_id": "24",
    "payment_method_id": "master",
    "transaction_amount": 15,
    "installments": 1,
    "payer": {
        "email": "test@test.com",
        "identification": {
            "type": "CPF",
            "number": "07052350688"
        }
    },
    "plano": "mensal"
}';

// Vamos bater direto na API do backend simulando a sessão
$cookie = "distinto_session=" . (isset($_COOKIE['distinto_session']) ? $_COOKIE['distinto_session'] : '');

$ch = curl_init('https://wedistinto.com/sistema/api/assinatura/processar_pagamento.php');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $rawPayload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Cookie: ' . $cookie // Tentando passar o cookie se possível, mas como é cli, não temos o cookie do user
    ]
]);
$res = curl_exec($ch);
echo "Response:\n$res\n";
