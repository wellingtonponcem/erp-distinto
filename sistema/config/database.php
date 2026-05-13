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
        try {
            $isMysql = (DB_PORT == 3306);
            
            // Verifica coluna 'nivel'
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

            // Garantir admin
            self::$instance->exec("UPDATE users SET nivel = 1 WHERE (email = 'faustinosdg@gmail.com' OR id = (SELECT id FROM (SELECT id FROM users ORDER BY " . ($isMysql ? "id" : "criado_em") . " ASC LIMIT 1) as t)) AND nivel != 1");

            // Verifica tabela de histórico
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
        } catch (Exception $e) {
            // Ignorar erros de migração silenciosamente
        }

        return self::$instance;
    }
}
