<?php
/**
 * Migration Script: Criar tabela orcamentos e semear o Orçamento de Álbuns 15 Anos
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::get();
    
    // 1. Criar Tabela caso não exista (Database::get já executa auto-migração, mas garantimos aqui)
    $isMysql = (DB_PORT == 3306);
    $createTableSql = $isMysql
        ? "CREATE TABLE IF NOT EXISTS orcamentos (
            id VARCHAR(32) PRIMARY KEY,
            cliente_id VARCHAR(32) NULL,
            cliente_nome VARCHAR(255) NOT NULL,
            tipo VARCHAR(100) DEFAULT 'albuns_15anos',
            slug VARCHAR(255) UNIQUE NOT NULL,
            titulo VARCHAR(255) NOT NULL,
            subtitulo VARCHAR(255) NULL,
            validade DATE NULL,
            dados_json TEXT NOT NULL,
            valor_total DECIMAL(10,2) NULL,
            status VARCHAR(50) DEFAULT 'pendente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
          )"
        : "CREATE TABLE IF NOT EXISTS orcamentos (
            id TEXT PRIMARY KEY,
            cliente_id TEXT NULL,
            cliente_nome TEXT NOT NULL,
            tipo TEXT DEFAULT 'albuns_15anos',
            slug TEXT UNIQUE NOT NULL,
            titulo TEXT NOT NULL,
            subtitulo TEXT NULL,
            validade DATE NULL,
            dados_json TEXT NOT NULL,
            valor_total NUMERIC(10,2) NULL,
            status TEXT DEFAULT 'pendente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
          )";
    $db->exec($createTableSql);

    // Garantir telefone da empresa configurado para WhatsApp
    try {
        $db->exec("UPDATE configuracao_empresa SET telefone = '5527988586935' WHERE id = 'principal'");
    } catch (Exception $e) {}

    // 2. Carregar o arquivo JSON do orçamento de 15 Anos
    $jsonPath = __DIR__ . '/../orcamento_albuns_15anos_v3.json';
    if (!file_exists($jsonPath)) {
        throw new Exception("Arquivo orcamento_albuns_15anos_v3.json não encontrado!");
    }
    
    $jsonContent = file_get_contents($jsonPath);
    $dadosObj = json_decode($jsonContent, true);
    if (!$dadosObj) {
        throw new Exception("Falha ao decodificar o arquivo JSON!");
    }

    $slug = 'orcamento-albuns-15anos';
    $id = 'orc_15anos_debutante';
    $clienteNome = 'Debutante Premium / Cliente 15 Anos';
    $tipo = 'albuns_15anos';
    $titulo = 'Orçamento de Álbuns Premium — 15 Anos';
    $subtitulo = $dadosObj['evento'] . ' (' . $dadosObj['localidade'] . ')';
    $validade = date('Y-m-d', strtotime('+30 days'));
    $valorTotal = 1250.00; // Valor inicial da coleção base (Essencial)
    $status = 'pendente';

    // Verificar se já existe pelo slug ou ID
    $checkStmt = $db->prepare("SELECT id FROM orcamentos WHERE slug = ? OR id = ? LIMIT 1");
    $checkStmt->execute([$slug, $id]);
    $existente = $checkStmt->fetch();

    if ($existente) {
        // Atualiza o registro
        $updateStmt = $db->prepare("UPDATE orcamentos SET cliente_nome = ?, tipo = ?, titulo = ?, subtitulo = ?, validade = ?, dados_json = ?, valor_total = ?, status = ? WHERE slug = ? OR id = ?");
        $updateStmt->execute([
            $clienteNome,
            $tipo,
            $titulo,
            $subtitulo,
            $validade,
            $jsonContent,
            $valorTotal,
            $status,
            $slug,
            $id
        ]);
        $mensagem = "Orçamento '<strong>{$titulo}</strong>' atualizado com sucesso!";
    } else {
        // Insere novo registro
        $insertStmt = $db->prepare("INSERT INTO orcamentos (id, cliente_id, cliente_nome, tipo, slug, titulo, subtitulo, validade, dados_json, valor_total, status, created_at) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
        $insertStmt->execute([
            $id,
            $clienteNome,
            $tipo,
            $slug,
            $titulo,
            $subtitulo,
            $validade,
            $jsonContent,
            $valorTotal,
            $status
        ]);
        $mensagem = "Orçamento '<strong>{$titulo}</strong>' criado com sucesso!";
    }

    echo "<h1>Sucesso na Migração!</h1>";
    echo "<p>{$mensagem}</p>";
    echo "<p><strong>Slug Público:</strong> <a href='../o/{$slug}' target='_blank'>/o/{$slug}</a></p>";
    echo "<p><a href='../gerenciamento/orcamentos.php'>Ir para Gerenciamento de Orçamentos</a></p>";

} catch (Exception $e) {
    echo "<h1 style='color:red;'>Erro na Migração!</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
