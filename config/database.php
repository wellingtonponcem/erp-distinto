<?php
require_once __DIR__ . '/env.php';

class Database {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            try {
                if (DB_PORT == 3306) {
                    // Conexão MySQL (Hostinger)
                    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                } else {
                    // Conexão PostgreSQL (Neon)
                    $endpoint = explode('.', DB_HOST)[0];
                    $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=require;options=\'endpoint=' . $endpoint . '\'';
                }
                
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode(['erro' => 'Falha na conexão com o banco de dados: ' . $e->getMessage()]));
            }
        }
        
        // Auto-migration compatível com MySQL e PostgreSQL
        // Auto-migration compatível com MySQL e PostgreSQL
        try {
            $isMysql = (DB_PORT == 3306);
            
            // 1. Verifica coluna 'nivel'
            try {
                $checkColSql = $isMysql 
                    ? "SHOW COLUMNS FROM users LIKE 'nivel'"
                    : "SELECT 1 FROM information_schema.columns WHERE table_name='users' AND column_name='nivel'";
                
                $stmt = self::$instance->query($checkColSql);
                if (!$stmt->fetch()) {
                    $alterSql = $isMysql 
                        ? "ALTER TABLE users ADD COLUMN nivel INT DEFAULT 0"
                        : "ALTER TABLE users ADD COLUMN nivel INTEGER DEFAULT 0";
                    self::$instance->exec($alterSql);
                }
            } catch (Exception $e) {}

            // 2. Verifica coluna 'roteiros_workspace_id'
            try {
                $checkWorkspaceColSql = $isMysql
                    ? "SHOW COLUMNS FROM users LIKE 'roteiros_workspace_id'"
                    : "SELECT 1 FROM information_schema.columns WHERE table_name='users' AND column_name='roteiros_workspace_id'";

                $stmtWorkspace = self::$instance->query($checkWorkspaceColSql);
                if (!$stmtWorkspace->fetch()) {
                    self::$instance->exec("ALTER TABLE users ADD COLUMN roteiros_workspace_id VARCHAR(64)");
                }
            } catch (Exception $e) {}

            // 3. Cria tabela 'roteiros_workspaces'
            try {
                self::$instance->exec("CREATE TABLE IF NOT EXISTS roteiros_workspaces (
                    id VARCHAR(64) PRIMARY KEY,
                    nome VARCHAR(120) NOT NULL,
                    owner_user_id VARCHAR(32) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                
                $stmtDistintoWorkspace = self::$instance->prepare("SELECT id FROM roteiros_workspaces WHERE id = ? LIMIT 1");
                $stmtDistintoWorkspace->execute(['distinto']);
                if (!$stmtDistintoWorkspace->fetch()) {
                    self::$instance->prepare("INSERT INTO roteiros_workspaces (id, nome) VALUES (?, ?)")
                        ->execute(['distinto', 'Equipe Distinto']);
                }
                
                self::$instance->exec("UPDATE users SET roteiros_workspace_id = 'distinto' WHERE sistema_origem = 'distinto' AND (roteiros_workspace_id IS NULL OR roteiros_workspace_id = '')");
            } catch (Exception $e) {}

            // 4. Garantir admin
            try {
                self::$instance->exec("UPDATE users SET nivel = 1 WHERE (email = 'faustinosdg@gmail.com' OR id = (SELECT id FROM (SELECT id FROM users ORDER BY " . ($isMysql ? "id" : "criado_em") . " ASC LIMIT 1) as t)) AND nivel != 1");
            } catch (Exception $e) {}

            // 5. Verifica tabela de histórico
            try {
                $checkTableSql = $isMysql
                    ? "SHOW TABLES LIKE 'propostas_historico'"
                    : "SELECT 1 FROM information_schema.tables WHERE table_name='propostas_historico'";
                    
                $stmtH = self::$instance->query($checkTableSql);
                if (!$stmtH->fetch()) {
                    $createTableSql = $isMysql
                        ? "CREATE TABLE propostas_historico (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            proposta_id VARCHAR(255) NOT NULL,
                            user_id VARCHAR(255) NOT NULL,
                            tipo VARCHAR(50) DEFAULT 'nota',
                            conteudo TEXT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                          )"
                        : "CREATE TABLE propostas_historico (
                            id SERIAL PRIMARY KEY,
                            proposta_id TEXT NOT NULL,
                            user_id TEXT NOT NULL,
                            tipo TEXT DEFAULT 'nota',
                            conteudo TEXT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                          )";
                    self::$instance->exec($createTableSql);
                }
            } catch (Exception $e) {}

            // 6. Verifica tabela de contratos
            try {
                $checkContratosSql = $isMysql
                    ? "SHOW TABLES LIKE 'contratos'"
                    : "SELECT 1 FROM information_schema.tables WHERE table_name='contratos'";
                    
                $stmtC = self::$instance->query($checkContratosSql);
                if (!$stmtC->fetch()) {
                    $createContratosSql = $isMysql
                        ? "CREATE TABLE contratos (
                            id VARCHAR(32) PRIMARY KEY,
                            proposta_id VARCHAR(32) NULL,
                            cliente_id VARCHAR(32) NULL,
                            cliente_nome VARCHAR(255) NOT NULL,
                            titulo VARCHAR(255) NOT NULL,
                            valor_total DECIMAL(10,2) NOT NULL,
                            condicoes_pagamento TEXT NULL,
                            data_contrato DATE NULL,
                            local_contrato VARCHAR(255) NULL,
                            status VARCHAR(50) DEFAULT 'rascunho',
                            documento_assinatura_id VARCHAR(255) NULL,
                            link_assinatura VARCHAR(512) NULL,
                            dados_json TEXT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                          )"
                        : "CREATE TABLE contratos (
                            id TEXT PRIMARY KEY,
                            proposta_id TEXT NULL,
                            cliente_id TEXT NULL,
                            cliente_nome TEXT NOT NULL,
                            titulo TEXT NOT NULL,
                            valor_total NUMERIC(10,2) NOT NULL,
                            condicoes_pagamento TEXT NULL,
                            data_contrato DATE NULL,
                            local_contrato TEXT NULL,
                            status TEXT DEFAULT 'rascunho',
                            documento_assinatura_id TEXT NULL,
                            link_assinatura TEXT NULL,
                            dados_json TEXT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                          )";
                    self::$instance->exec($createContratosSql);
                }
            } catch (Exception $e) {}
        } catch (Exception $e) {
            // Ignorar erros de migração geral
        }

        return self::$instance;
    }
}
