<?php
/**
 * Migração: CRM inicial
 * Cria tabelas de fornecedores, oportunidades e adiciona vínculos financeiros.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::get();
    echo "Iniciando migração do CRM...\n";

    $db->exec("CREATE TABLE IF NOT EXISTS fornecedores (
        id VARCHAR(32) NOT NULL PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        contato VARCHAR(255),
        telefone VARCHAR(50),
        email VARCHAR(255),
        categoria VARCHAR(100),
        observacao TEXT,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE IF NOT EXISTS oportunidades (
        id VARCHAR(32) NOT NULL PRIMARY KEY,
        cliente_id VARCHAR(32) NULL,
        nome VARCHAR(255) NOT NULL,
        valor_estimado DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        etapa ENUM('novo','qualificado','proposta','negociacao','ganha','perdida') NOT NULL DEFAULT 'novo',
        previsao DATE NULL,
        responsavel VARCHAR(255),
        descricao TEXT,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_cliente_id (cliente_id),
        KEY idx_etapa (etapa)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $columnsLancamentos = $db->query("SHOW COLUMNS FROM lancamentos LIKE 'cliente_id'")->fetch();
    if (!$columnsLancamentos) {
        $db->exec("ALTER TABLE lancamentos 
            ADD COLUMN cliente_id VARCHAR(32) NULL AFTER cliente_fornecedor,
            ADD COLUMN fornecedor_id VARCHAR(32) NULL AFTER cliente_id,
            ADD KEY idx_cliente_id (cliente_id),
            ADD KEY idx_fornecedor_id (fornecedor_id);");
    }

    echo "Migração CRM concluída com sucesso.\n";
} catch (Exception $e) {
    echo "ERRO na migração CRM: " . $e->getMessage() . "\n";
}
