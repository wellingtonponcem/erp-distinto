<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::get();
    
    // Criar tabela de conhecimento se não existir
    $db->exec("CREATE TABLE IF NOT EXISTS roteiros_conhecimento (
        id SERIAL PRIMARY KEY,
        nome_arquivo TEXT NOT NULL,
        caminho_arquivo TEXT NOT NULL,
        tipo_arquivo TEXT,
        texto_extraido TEXT,
        ativo BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    echo "Tabela roteiros_conhecimento garantida.";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
