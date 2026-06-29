<?php
/**
 * Script temporário de diagnóstico e reparo do Git no servidor de produção Hostinger
 */
header('Content-Type: text/plain; charset=utf-8');

$action = $_GET['action'] ?? '';

function rodar_comando($cmd) {
    echo "Executando: $cmd\n";
    $output = shell_exec($cmd . ' 2>&1');
    echo "Retorno:\n$output\n";
    echo str_repeat('-', 40) . "\n";
}

echo "=== DIAGNÓSTICO DO GIT EM PRODUÇÃO ===\n\n";

// Executa comandos básicos de diagnóstico
rodar_comando('git status');
rodar_comando('git log -n 3 --oneline');

if ($action === 'restore_css') {
    echo "\n=== TENTANDO RESTAURAR ASSETS ===\n";
    rodar_comando('git checkout -- assets/css/tailwind.css');
    rodar_comando('git checkout -- favicon_io/site.webmanifest');
    rodar_comando('git status');
} elseif ($action === 'force_reset') {
    echo "\n=== FORÇANDO RESET DO REPOSITÓRIO ===\n";
    rodar_comando('git fetch origin main');
    rodar_comando('git reset --hard origin/main');
    rodar_comando('git status');
}

echo "\n=== VERIFICAÇÃO FÍSICA DOS ARQUIVOS ===\n";
$css_file = __DIR__ . '/assets/css/tailwind.css';
$manifest_file = __DIR__ . '/favicon_io/site.webmanifest';

echo "tailwind.css existe? " . (file_exists($css_file) ? "SIM (Tamanho: " . filesize($css_file) . " bytes)" : "NÃO") . "\n";
echo "site.webmanifest existe? " . (file_exists($manifest_file) ? "SIM (Tamanho: " . filesize($manifest_file) . " bytes)" : "NÃO") . "\n";
