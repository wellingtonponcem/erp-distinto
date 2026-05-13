<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();

$tables = ['users', 'lancamentos', 'contas_bancarias', 'servicos', 'custos_fixos', 'categorias'];

echo "Iniciando unificação de collation...\n";

foreach ($tables as $t) {
    try {
        $db->exec("ALTER TABLE $t CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Tabela $t: OK\n";
    } catch (Exception $e) {
        echo "Tabela $t: ERRO - " . $e->getMessage() . "\n";
    }
}

echo "Concluído.\n";
