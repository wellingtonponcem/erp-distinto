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
    );");

    $db->exec("CREATE TABLE IF NOT EXISTS oportunidades (
        id VARCHAR(32) NOT NULL PRIMARY KEY,
        cliente_id VARCHAR(32) NULL,
        nome VARCHAR(255) NOT NULL,
        valor_estimado DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        etapa VARCHAR(50) NOT NULL DEFAULT 'novo',
        previsao DATE NULL,
        responsavel VARCHAR(255),
        descricao TEXT,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    $columnsLancamentos = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = 'lancamentos' AND column_name = 'cliente_id'");
    $columnsLancamentos->execute();
    $exists = $columnsLancamentos->fetch();
    if (!$exists) {
        $db->exec("ALTER TABLE lancamentos ADD COLUMN cliente_id VARCHAR(32) NULL;");
        $db->exec("ALTER TABLE lancamentos ADD COLUMN fornecedor_id VARCHAR(32) NULL;");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_lancamentos_cliente_id ON lancamentos (cliente_id);");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_lancamentos_fornecedor_id ON lancamentos (fornecedor_id);");
    }

    echo "Migração CRM concluída com sucesso.\n";
} catch (Exception $e) {
    echo "ERRO na migração CRM: " . $e->getMessage() . "\n";
}
