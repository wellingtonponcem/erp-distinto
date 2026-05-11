<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::get();
    
    // Criar tabela de histórico se não existir
    $db->exec("CREATE TABLE IF NOT EXISTS propostas_historico (
        id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
        proposta_id UUID NOT NULL,
        user_id INTEGER NOT NULL,
        tipo VARCHAR(50) NOT NULL,
        conteudo TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (proposta_id) REFERENCES propostas(id) ON DELETE CASCADE
    )");
    
    echo "Tabela propostas_historico criada com sucesso!\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
