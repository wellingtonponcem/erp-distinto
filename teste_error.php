<?php
/**
 * Script de teste de erros para diagnóstico completo em produção
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

echo "=== INICIANDO TESTE DE CARREGAMENTO ===\n\n";

try {
    echo "1. Carregando env.php...\n";
    require_once __DIR__ . '/config/env.php';
    echo "✓ env.php OK\n\n";

    echo "2. Carregando auth.php...\n";
    require_once __DIR__ . '/config/auth.php';
    echo "✓ auth.php OK\n\n";

    echo "3. Carregando database.php...\n";
    require_once __DIR__ . '/config/database.php';
    echo "✓ database.php OK\n\n";

    echo "4. Carregando helpers.php...\n";
    require_once __DIR__ . '/includes/helpers.php';
    echo "✓ helpers.php OK\n\n";

    echo "5. Inicializando Sessão...\n";
    iniciarSessao();
    echo "Sessão ID: " . session_id() . "\n";
    echo "Usuário logado: " . (!empty($_SESSION) ? "SIM" : "NÃO") . "\n";
    if (!empty($_SESSION)) {
        echo "Dados da sessão:\n";
        print_r($_SESSION);
    }
    echo "\n";

    echo "6. Testando conexão de banco...\n";
    $db = Database::get();
    echo "✓ Banco Conectado!\n\n";

    echo "7. Executando query de lancamentos.php (clientes/fornecedores)...\n";
    $clientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome ASC LIMIT 5")->fetchAll();
    echo "✓ Clientes consultados: " . count($clientes) . "\n";
    $fornecedores = $db->query("SELECT id, nome FROM fornecedores ORDER BY nome ASC LIMIT 5")->fetchAll();
    echo "✓ Fornecedores consultados: " . count($fornecedores) . "\n\n";

    echo "8. Carregando head.php...\n";
    ob_start();
    include __DIR__ . '/includes/layout/head.php';
    ob_end_clean();
    echo "✓ head.php OK\n\n";

    echo "9. Carregando sidebar.php...\n";
    ob_start();
    include __DIR__ . '/includes/layout/sidebar.php';
    ob_end_clean();
    echo "✓ sidebar.php OK\n\n";

    echo "10. Carregando top_nav.php...\n";
    ob_start();
    include __DIR__ . '/includes/layout/top_nav.php';
    ob_end_clean();
    echo "✓ top_nav.php OK\n\n";

    echo "=== TESTE CONCLUÍDO COM SUCESSO! ===\n";

} catch (Throwable $e) {
    echo "\n✗ ERRO DETECTADO:\n";
    echo "Classe: " . get_class($e) . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
