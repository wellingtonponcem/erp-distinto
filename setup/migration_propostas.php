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
        tipo ENUM('marketing', 'casamento', '15anos') NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        titulo VARCHAR(255) NOT NULL,
        subtitulo VARCHAR(255) NULL,
        data_evento DATE NULL,
        validade DATE NULL,
        dados_json LONGTEXT NOT NULL, -- Armazena as seções e textos gerados
        valor_total DECIMAL(15,2) DEFAULT 0.00,
        status ENUM('rascunho', 'enviada', 'aceita', 'recusada') DEFAULT 'rascunho',
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_slug (slug),
        KEY idx_tipo (tipo),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $db->exec($sql);
    echo "Tabela 'propostas' criada/verificada com sucesso.\n";

} catch (Exception $e) {
    echo "ERRO na migração: " . $e->getMessage() . "\n";
}
