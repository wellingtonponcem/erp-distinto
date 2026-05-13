<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::get();
    
    // 1. Criar tabela de pastas
    $sqlPastas = "CREATE TABLE IF NOT EXISTS pastas_propostas (
        id CHAR(36) PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        cor VARCHAR(50) DEFAULT '#252525',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $db->exec($sqlPastas);

    // 2. Adicionar coluna pasta_id na tabela propostas se não existir
    $checkColumn = $db->query("SHOW COLUMNS FROM propostas LIKE 'pasta_id'");
    if (!$checkColumn->fetch()) {
        $db->exec("ALTER TABLE propostas ADD COLUMN pasta_id CHAR(36) NULL AFTER id;");
    }

    echo "<h1>Migration de Pastas Concluída!</h1>";
    echo "<p>Estrutura de pastas preparada com sucesso.</p>";
    echo "<p><a href='gerenciamento/propostas.php'>Ir para Propostas</a></p>";
    
} catch (Exception $e) {
    echo "<h1>Erro na Migration!</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
