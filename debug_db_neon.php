<?php
// Diagnóstico de conexão com Neon PostgreSQL
// Acesse: wedistinto.com/sistema/debug_db_neon.php
// REMOVA ESTE ARQUIVO APÓS O DIAGNÓSTICO

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE CONEXÃO NEON ===\n\n";

// 1. Versão do PHP
echo "PHP Version: " . PHP_VERSION . "\n\n";

// 2. Drivers PDO disponíveis
echo "Drivers PDO disponíveis:\n";
$drivers = PDO::getAvailableDrivers();
print_r($drivers);
echo "\n";

$temPgsql = in_array('pgsql', $drivers);
echo "pdo_pgsql disponível: " . ($temPgsql ? "✓ SIM" : "✗ NÃO - ESSE É O PROBLEMA") . "\n\n";

// 3. Extensões carregadas (filtrado)
$exts = get_loaded_extensions();
$pgExts = array_filter($exts, fn($e) => stripos($e, 'pg') !== false || stripos($e, 'pdo') !== false || stripos($e, 'curl') !== false);
echo "Extensões relacionadas:\n";
print_r(array_values($pgExts));
echo "\n";

// 4. Tentativa de conexão
require_once __DIR__ . '/config/env.php';
echo "Config carregada:\n";
echo "  HOST: " . DB_HOST . "\n";
echo "  PORT: " . DB_PORT . "\n";
echo "  NAME: " . DB_NAME . "\n";
echo "  USER: " . DB_USER . "\n\n";

if ($temPgsql) {
    try {
        // Suporte para SNI no Neon (necessário para libpq antiga)
        $endpoint = explode('.', DB_HOST)[0];
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=require;options=\'endpoint=' . $endpoint . '\'';
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "✓ CONEXÃO BEM-SUCEDIDA!\n\n";

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Usuários no banco: " . $row['total'] . "\n";

        $stmt2 = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public' ORDER BY table_name");
        $tables = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        echo "Tabelas encontradas: " . implode(', ', $tables) . "\n";

    } catch (PDOException $e) {
        echo "✗ ERRO DE CONEXÃO: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Não é possível conectar: driver pdo_pgsql não instalado.\n";
    echo "\nSOLUÇÕES:\n";
    echo "1. Hostinger hPanel → PHP Configuration → habilitar extensão pgsql/pdo_pgsql\n";
    echo "2. Se VPS: sudo apt install php" . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . "-pgsql && sudo systemctl restart apache2\n";
    echo "3. Contate o suporte da Hostinger pedindo para habilitar pdo_pgsql\n";

    // 5. Teste via cURL (fallback HTTP)
    echo "\n--- Testando conectividade via cURL (sem pdo_pgsql) ---\n";
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://' . DB_HOST . ':' . DB_PORT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $errno  = curl_errno($ch);
        $error  = curl_error($ch);
        curl_close($ch);
        echo "cURL disponível: SIM\n";
        echo "Teste TCP ao host Neon: " . ($errno === 0 || $errno === 56 ? "✓ Host alcançável" : "✗ Erro: $error (errno $errno)") . "\n";
    } else {
        echo "cURL: não disponível\n";
    }
}
