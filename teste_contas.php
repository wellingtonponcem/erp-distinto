<?php
/**
 * Script de teste de erro específico para contas.php em produção
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Muda para a pasta financeiro para simular o escopo relativo
    chdir(__DIR__ . '/financeiro');
    include __DIR__ . '/financeiro/contas.php';
} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "ERRO DETECTADO NO CONTAS:\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    echo "Stack:\n" . $e->getTraceAsString() . "\n";
}
