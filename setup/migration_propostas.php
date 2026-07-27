<?php
/**
 * Migração: Criar tabela de propostas
 * Este script adiciona a estrutura necessária para o gerador de propostas.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::get();
    echo "Iniciando migração de propostas...\n";

    $sql = "CREATE TABLE IF NOT EXISTS propostas (
        id VARCHAR(32) PRIMARY KEY,
        cliente_id VARCHAR(32) NULL,
        cliente_nome VARCHAR(255) NOT NULL,
        tipo VARCHAR(50) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        subtitulo VARCHAR(255) NULL,
        oportunidade_id VARCHAR(32) NULL,
        data_evento DATE NULL,
        validade DATE NULL,
        dados_json TEXT NOT NULL,
        valor_total NUMERIC(15,2) DEFAULT 0.00,
        status VARCHAR(50) DEFAULT 'rascunho',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT idx_slug UNIQUE (slug)
    )";

    $db->exec($sql);
    echo "Tabela 'propostas' criada/verificada com sucesso.\n";

} catch (Exception $e) {
    echo "ERRO na migração: " . $e->getMessage() . "\n";
}
