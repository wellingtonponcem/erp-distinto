<?php
require_once __DIR__ . '/env.php';

class Database {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            try {
                // Suporte para SNI no Neon (necessário para libpq antiga)
                $endpoint = explode('.', DB_HOST)[0];
                $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=require;options=\'endpoint=' . $endpoint . '\'';
                
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode(['erro' => 'Falha na conexão com o banco de dados.']));
            }
        }
        
        // Auto-migration para Nível de Usuário (PostgreSQL)
        try {
            $stmt = self::$instance->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name='users' AND column_name='nivel'");
            if ($stmt->fetchColumn() == 0) {
                self::$instance->exec("ALTER TABLE users ADD COLUMN nivel INTEGER DEFAULT 0");
            }
            // Garantir que o primeiro usuário ou o email específico seja admin
            self::$instance->exec("UPDATE users SET nivel = 1 WHERE (email = 'faustinosdg@gmail.com' OR id = (SELECT id FROM users ORDER BY criado_em ASC LIMIT 1)) AND nivel != 1");
            // Auto-migration para Histórico de Propostas
            $stmtH = self::$instance->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name='propostas_historico'");
            if ($stmtH->fetchColumn() == 0) {
                self::$instance->exec("CREATE TABLE propostas_historico (
                    id SERIAL PRIMARY KEY,
                    proposta_id TEXT NOT NULL,
                    user_id TEXT NOT NULL,
                    tipo TEXT DEFAULT 'nota',
                    conteudo TEXT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            }
        } catch (Exception $e) {
            // Ignorar erros de migração silenciosamente
        }

        return self::$instance;
    }
}
