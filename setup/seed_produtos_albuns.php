<?php
/**
 * Script de Seed: Cadastrar Produtos de Álbuns Fotográficos & Acabamentos Fotografados
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::get();

    // 1. Garantir tabela produtos_albuns
    $isMysql = (defined('DB_PORT') && DB_PORT == 3306);
    $createSql = $isMysql
        ? "CREATE TABLE IF NOT EXISTS produtos_albuns (
            id VARCHAR(64) PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            categoria VARCHAR(50) DEFAULT '15anos',
            categoria_original VARCHAR(50) NULL,
            tipo VARCHAR(20) DEFAULT 'produto',
            descricao TEXT NULL,
            custo_base DECIMAL(10,2) DEFAULT 0.00,
            investimento_cliente DECIMAL(10,2) DEFAULT 0.00,
            valor_lamina_extra DECIMAL(10,2) DEFAULT 0.00,
            estojo_json TEXT NULL,
            imagens_galeria_json TEXT NULL,
            acabamentos_detalhados_json TEXT NULL,
            ativo INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
          )"
        : "CREATE TABLE IF NOT EXISTS produtos_albuns (
            id TEXT PRIMARY KEY,
            nome TEXT NOT NULL,
            categoria TEXT DEFAULT '15anos',
            categoria_original TEXT NULL,
            tipo TEXT DEFAULT 'produto',
            descricao TEXT NULL,
            custo_base NUMERIC(10,2) DEFAULT 0.00,
            investimento_cliente NUMERIC(10,2) DEFAULT 0.00,
            valor_lamina_extra NUMERIC(10,2) DEFAULT 0.00,
            estojo_json TEXT NULL,
            imagens_galeria_json TEXT NULL,
            acabamentos_detalhados_json TEXT NULL,
            ativo INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
          )";
    $db->exec($createSql);

    // 2. Ler JSON
    $jsonPath = __DIR__ . '/../orcamento_albuns_15anos_v3.json';
    if (!file_exists($jsonPath)) {
        throw new Exception("Arquivo orcamento_albuns_15anos_v3.json não encontrado!");
    }

    $dados = json_decode(file_get_contents($jsonPath), true);
    $colecoes = $dados['colecao_albuns'] ?? [];
    $galeriaAcabamentos = $dados['galeria_acabamentos'] ?? [];

    $cont = 0;

    foreach ($colecoes as $c) {
        $id = $c['id'];
        $nome = $c['nome_comercial'];
        $categoria = '15anos';
        $categoriaOriginal = $c['categoria_original'] ?? 'Coleção Premium';
        $tipo = 'produto';
        $descricao = $c['descricao'];
        $custoBase = (float) ($c['custo_base_fullcolor'] ?? 0);
        $investimentoCliente = (float) $c['investimento_cliente'];
        $valorLaminaExtra = (float) ($c['valor_lamina_extra'] ?? 35);

        // Estojo
        $estojo = $c['estojo'] ?? [];
        $estojoJson = json_encode($estojo, JSON_UNESCAPED_UNICODE);

        // Galeria de Fotos do Produto
        $imagens = $c['imagens'] ?? [];
        $imagensJson = json_encode($imagens, JSON_UNESCAPED_UNICODE);

        // Acabamentos detalhados com fotografias individuais para cada um!
        $rawAcab = $c['acabamento_detalhado'] ?? [];
        $acabamentosLista = [];

        foreach ($rawAcab as $chave => $valorTexto) {
            $nomeItem = ucfirst(str_replace('_', ' ', $chave));
            $fotoUrl = '';

            // Buscar imagem de exemplo correspondente nos acabamentos ou imagens do produto
            if ($chave === 'capa' && !empty($imagens['capa'])) {
                $fotoUrl = $imagens['capa'];
            } elseif ($chave === 'papel') {
                $fotoUrl = 'https://m.media-amazon.com/images/I/71YvE9-9VFL._AC_SL1500_.jpg';
            } elseif ($chave === 'corte_lateral') {
                $fotoUrl = 'https://www.instagram.com/p/DZiX91-sEon/';
            } elseif (!empty($estojo['imagem_referencia'])) {
                $fotoUrl = $estojo['imagem_referencia'];
            }

            $acabamentosLista[] = [
                'chave' => $chave,
                'item' => $nomeItem,
                'texto' => $valorTexto,
                'imagem' => $fotoUrl
            ];
        }

        $acabamentosJson = json_encode($acabamentosLista, JSON_UNESCAPED_UNICODE);

        // Upsert
        $check = $db->prepare("SELECT id FROM produtos_albuns WHERE id = ? LIMIT 1");
        $check->execute([$id]);
        if ($check->fetch()) {
            $stmtUp = $db->prepare("UPDATE produtos_albuns SET nome=?, categoria=?, categoria_original=?, tipo=?, descricao=?, custo_base=?, investimento_cliente=?, valor_lamina_extra=?, estojo_json=?, imagens_galeria_json=?, acabamentos_detalhados_json=?, ativo=1 WHERE id=?");
            $stmtUp->execute([$nome, $categoria, $categoriaOriginal, $tipo, $descricao, $custoBase, $investimentoCliente, $valorLaminaExtra, $estojoJson, $imagensJson, $acabamentosJson, $id]);
        } else {
            $stmtIn = $db->prepare("INSERT INTO produtos_albuns (id, nome, categoria, categoria_original, tipo, descricao, custo_base, investimento_cliente, valor_lamina_extra, estojo_json, imagens_galeria_json, acabamentos_detalhados_json, ativo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)");
            $stmtIn->execute([$id, $nome, $categoria, $categoriaOriginal, $tipo, $descricao, $custoBase, $investimentoCliente, $valorLaminaExtra, $estojoJson, $imagensJson, $acabamentosJson]);
        }

        $cont++;
    }

    echo "<h1>Importação de Produtos de Álbuns Concluída!</h1>";
    echo "<p>Produtos de Álbuns cadastrados com fotos e acabamentos detalhados: <strong>{$cont}</strong></p>";

} catch (Exception $e) {
    echo "<h1 style='color:red;'>Erro no Seed de Produtos: " . htmlspecialchars($e->getMessage()) . "</h1>";
}
