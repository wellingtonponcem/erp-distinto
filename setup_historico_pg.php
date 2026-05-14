<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::get();
    $db->exec("CREATE TABLE IF NOT EXISTS propostas_historico (
        id SERIAL PRIMARY KEY,
        proposta_id TEXT NOT NULL,
        user_id TEXT NOT NULL,
        tipo TEXT DEFAULT 'nota',
        conteudo TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "Tabela propostas_historico criada com sucesso no PostgreSQL!\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
