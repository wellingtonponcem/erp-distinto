<?php
/**
 * Script de Diagnóstico Temporário de Erros 500 em Produção
 * Remova após o diagnóstico.
 */

// Apenas permitir acesso se o token correto for informado
if (($_GET['token'] ?? '') !== 'distinto_diag_771') {
    http_response_code(403);
    die("Acesso proibido.");
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE PRODUÇÃO ===\n\n";

// 1. Informações básicas do servidor
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Current File: " . __FILE__ . "\n\n";

// 2. Procurar por arquivos error_log no servidor
echo "=== BUSCANDO ARQUIVOS DE LOG DE ERRO (error_log) ===\n";
$paths_to_check = [
    __DIR__ . '/error_log',
    __DIR__ . '/financeiro/error_log',
    __DIR__ . '/api/financeiro/error_log',
    __DIR__ . '/../error_log'
];

foreach ($paths_to_check as $path) {
    if (file_exists($path)) {
        echo "Log encontrado em: {$path}\n";
        echo "Tamanho: " . filesize($path) . " bytes\n";
        echo "Últimas 15 linhas:\n";
        $lines = file($path);
        if ($lines) {
            $last_lines = array_slice($lines, -15);
            echo implode("", $last_lines);
        } else {
            echo "Não foi possível ler as linhas.\n";
        }
        echo "----------------------------------------\n\n";
    } else {
        echo "Log não existe em: {$path}\n";
    }
}

// 3. Testar drivers PDO e conexão
echo "=== TESTANDO CONEXÃO DE BANCO DE DADOS ===\n";
$drivers = PDO::getAvailableDrivers();
echo "Drivers PDO disponíveis: " . implode(', ', $drivers) . "\n";

try {
    require_once __DIR__ . '/config/env.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/includes/helpers.php';
    
    echo "Configuração carregada:\n";
    echo "  DB_HOST: " . DB_HOST . "\n";
    echo "  DB_PORT: " . DB_PORT . "\n";
    echo "  DB_NAME: " . DB_NAME . "\n";
    echo "  DB_USER: " . DB_USER . "\n\n";

    echo "Tentando Database::get()...\n";
    $db = Database::get();
    echo "✓ Conectado com sucesso!\n\n";
    
    echo "Testando consulta de clientes...\n";
    $clientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome ASC LIMIT 5")->fetchAll();
    echo "✓ Tabela clientes OK! Encontrados: " . count($clientes) . "\n";
    
    echo "Testando consulta de fornecedores...\n";
    $fornecedores = $db->query("SELECT id, nome FROM fornecedores ORDER BY nome ASC LIMIT 5")->fetchAll();
    echo "✓ Tabela fornecedores OK! Encontrados: " . count($fornecedores) . "\n\n";

} catch (Throwable $e) {
    echo "✗ ERRO NO BANCO: " . $e->getMessage() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n\n";
}

// 4. Testar a função getallheaders() e sua posição
echo "=== TESTANDO GETALLHEADERS() ===\n";
echo "function_exists('getallheaders'): " . (function_exists('getallheaders') ? "SIM" : "NÃO") . "\n";

try {
    echo "Tentando chamar getallheaders()...\n";
    $headers = getallheaders();
    echo "✓ Chamada bem-sucedida! Headers retornados: " . count($headers) . "\n";
} catch (Throwable $e) {
    echo "✗ ERRO AO CHAMAR GETALLHEADERS(): " . $e->getMessage() . "\n\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
