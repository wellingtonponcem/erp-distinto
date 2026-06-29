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
                    $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=require';
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
                $createTableSql = $isMysql
                    ? "CREATE TABLE IF NOT EXISTS propostas_historico (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        proposta_id VARCHAR(255) NOT NULL,
                        user_id VARCHAR(255) NOT NULL,
                        tipo VARCHAR(50) DEFAULT 'nota',
                        conteudo TEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                      )"
                    : "CREATE TABLE IF NOT EXISTS propostas_historico (
                        id SERIAL PRIMARY KEY,
                        proposta_id TEXT NOT NULL,
                        user_id TEXT NOT NULL,
                        tipo TEXT DEFAULT 'nota',
                        conteudo TEXT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                      )";
                self::$instance->exec($createTableSql);
            } catch (Exception $e) {}

            // 5.5. Resolução de conflito com tabelas legadas (se houver coluna 'inicio')
            try {
                $checkConflito = $isMysql
                    ? "SHOW COLUMNS FROM contratos LIKE 'inicio'"
                    : "SELECT 1 FROM information_schema.columns WHERE table_name='contratos' AND column_name='inicio'";
                
                $stmtConf = self::$instance->query($checkConflito);
                if ($stmtConf && $stmtConf->fetch()) {
                    self::$instance->exec("DROP TABLE contratos");
                }
            } catch (Exception $e) {}

            // 6. Verifica tabela de contratos
            try {
                $createContratosSql = $isMysql
                    ? "CREATE TABLE IF NOT EXISTS contratos (
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
                    : "CREATE TABLE IF NOT EXISTS contratos (
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
            } catch (Exception $e) {}

            // 7. Garante que todas as colunas existem na tabela de contratos (caso a tabela já existisse de forma incompleta)
            try {
                $colunasExistentes = [];
                if ($isMysql) {
                    $stmtCols = self::$instance->query("SHOW COLUMNS FROM contratos");
                    while ($col = $stmtCols->fetch()) {
                        $colunasExistentes[] = strtolower($col['Field'] ?? $col['field'] ?? '');
                    }
                } else {
                    $stmtCols = self::$instance->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = ?");
                    $stmtCols->execute(['contratos']);
                    $colunasExistentes = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
                    $colunasExistentes = array_map('strtolower', $colunasExistentes);
                }
                
                if (!empty($colunasExistentes)) {
                    $colunasNecessarias = [
                        'proposta_id' => $isMysql ? "ALTER TABLE contratos ADD COLUMN proposta_id VARCHAR(32) NULL" : "ALTER TABLE contratos ADD COLUMN proposta_id TEXT NULL",
                        'cliente_id' => $isMysql ? "ALTER TABLE contratos ADD COLUMN cliente_id VARCHAR(32) NULL" : "ALTER TABLE contratos ADD COLUMN cliente_id TEXT NULL",
                        'cliente_nome' => $isMysql ? "ALTER TABLE contratos ADD COLUMN cliente_nome VARCHAR(255) NULL" : "ALTER TABLE contratos ADD COLUMN cliente_nome TEXT NULL",
                        'titulo' => $isMysql ? "ALTER TABLE contratos ADD COLUMN titulo VARCHAR(255) NULL" : "ALTER TABLE contratos ADD COLUMN titulo TEXT NULL",
                        'valor_total' => $isMysql ? "ALTER TABLE contratos ADD COLUMN valor_total DECIMAL(10,2) DEFAULT 0.00" : "ALTER TABLE contratos ADD COLUMN valor_total NUMERIC(10,2) DEFAULT 0.00",
                        'condicoes_pagamento' => "ALTER TABLE contratos ADD COLUMN condicoes_pagamento TEXT NULL",
                        'data_contrato' => "ALTER TABLE contratos ADD COLUMN data_contrato DATE NULL",
                        'local_contrato' => $isMysql ? "ALTER TABLE contratos ADD COLUMN local_contrato VARCHAR(255) NULL" : "ALTER TABLE contratos ADD COLUMN local_contrato TEXT NULL",
                        'status' => $isMysql ? "ALTER TABLE contratos ADD COLUMN status VARCHAR(50) DEFAULT 'rascunho'" : "ALTER TABLE contratos ADD COLUMN status TEXT DEFAULT 'rascunho'",
                        'documento_assinatura_id' => $isMysql ? "ALTER TABLE contratos ADD COLUMN documento_assinatura_id VARCHAR(255) NULL" : "ALTER TABLE contratos ADD COLUMN documento_assinatura_id TEXT NULL",
                        'link_assinatura' => $isMysql ? "ALTER TABLE contratos ADD COLUMN link_assinatura VARCHAR(512) NULL" : "ALTER TABLE contratos ADD COLUMN link_assinatura TEXT NULL",
                        'dados_json' => "ALTER TABLE contratos ADD COLUMN dados_json TEXT NULL",
                        'created_at' => "ALTER TABLE contratos ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
                    ];
                    
                    foreach ($colunasNecessarias as $colName => $alterSql) {
                        if (!in_array(strtolower($colName), $colunasExistentes)) {
                            self::$instance->exec($alterSql);
                        }
                    }
                }
            } catch (Exception $e) {}
        } catch (Exception $e) {
            // Ignorar erros de migração geral
        }

        return self::$instance;
    }
}
