<?php
/**
 * Script temporário para criar a tabela de propostas
 */
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::get();
    
    $sql = "CREATE TABLE IF NOT EXISTS propostas (
        id CHAR(36) PRIMARY KEY,
        cliente_nome VARCHAR(255) NOT NULL,
        tipo VARCHAR(100) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        titulo VARCHAR(255),
        subtitulo VARCHAR(255),
        validade DATE,
        dados_json LONGTEXT,
        valor_total DECIMAL(10, 2),
        status VARCHAR(50) DEFAULT 'pendente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sql);
    
    echo "<h1>Sucesso!</h1>";
    echo "<p>A tabela <strong>propostas</strong> foi criada ou já existia no banco de dados.</p>";
    echo "<p><a href='gerenciamento/proposta_nova.php'>Voltar para o sistema</a></p>";
    
} catch (Exception $e) {
    echo "<h1>Erro!</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
