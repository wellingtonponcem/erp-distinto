<?php
echo "Drivers PDO instalados:\n";
print_r(PDO::getAvailableDrivers());

require_once __DIR__ . '/config/env.php';
try {
    $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=require';
    echo "\nTentando conectar ao DSN: $dsn\n";
    $test = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "Conexão realizada com sucesso!\n";
} catch (PDOException $e) {
    echo "ERRO DE CONEXÃO: " . $e->getMessage() . "\n";
}
