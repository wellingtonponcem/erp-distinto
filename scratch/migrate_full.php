<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::get();
    
    $colunas = [
        "gancho TEXT",
        "quebra_crenca TEXT",
        "desenvolvimento TEXT",
        "conexao TEXT",
        "fechamento TEXT",
        "cta TEXT",
        "tags TEXT",
        "formato TEXT",
        "status TEXT DEFAULT 'pendente'",
        "likes INTEGER DEFAULT 0",
        "comentarios INTEGER DEFAULT 0",
        "shares INTEGER DEFAULT 0",
        "reposts INTEGER DEFAULT 0",
        "salvamentos INTEGER DEFAULT 0",
        "score FLOAT DEFAULT 0",
        "numero INTEGER DEFAULT 0",
        "intencao TEXT",
        "tema TEXT"
    ];

    foreach ($colunas as $col) {
        try {
            $db->exec("ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS $col");
            echo "Sucesso: $col\n";
        } catch (Exception $e) {
            echo "Info: Coluna já existe ou erro ignorado (" . $e->getMessage() . ")\n";
        }
    }
    
    echo "Fim da migração.";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
