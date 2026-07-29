<?php
/**
 * Script de Seed: Cadastrar Coleções e Acabamentos na Tabela de Preços (servicos)
 */
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = Database::get();

    // 1. Garantir colunas na tabela servicos
    $isMysql = (defined('DB_PORT') && DB_PORT == 3306);
    try {
        if ($isMysql) {
            $stmtCols = $db->query("SHOW COLUMNS FROM servicos");
            $colunasExistentes = [];
            while ($row = $stmtCols->fetch()) {
                $colunasExistentes[] = strtolower($row['Field'] ?? $row['field'] ?? '');
            }
        } else {
            $stmtCols = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = ?");
            $stmtCols->execute(['servicos']);
            $colunasExistentes = array_map('strtolower', $stmtCols->fetchAll(PDO::FETCH_COLUMN));
        }
    } catch (Exception $e) {
        $colunasExistentes = [];
    }

    $novas = [
        'imagens_json'       => "TEXT NULL",
        'acabamento_json'    => "TEXT NULL",
        'estojo_json'        => "TEXT NULL",
        'valor_lamina_extra' => "DECIMAL(10,2) DEFAULT 0.00",
        'categoria_original' => "VARCHAR(50) NULL"
    ];

    foreach ($novas as $col => $def) {
        if (!in_array(strtolower($col), $colunasExistentes, true)) {
            try {
                $db->exec("ALTER TABLE servicos ADD COLUMN {$col} {$def}");
            } catch (Exception $e) {}
        }
    }

    // 2. Ler JSON de 15 Anos
    $jsonPath = __DIR__ . '/../orcamento_albuns_15anos_v3.json';
    if (!file_exists($jsonPath)) {
        throw new Exception("Arquivo orcamento_albuns_15anos_v3.json não encontrado.");
    }

    $dados = json_decode(file_get_contents($jsonPath), true);
    $colecoes = $dados['colecao_albuns'] ?? [];
    $acabamentos = $dados['galeria_acabamentos'] ?? [];

    $contColecoes = 0;
    $contAcabamentos = 0;

    // 3. Inserir/Atualizar Coleções
    foreach ($colecoes as $c) {
        $id = 'srv_' . $c['id'];
        $nome = $c['nome_comercial'];
        $categoria = '15anos';
        $tipo = 'colecao';
        $descricao = $c['descricao'];
        $precoVenda = (float) $c['investimento_cliente'];
        $custoProducao = (float) ($c['custo_base_fullcolor'] ?? 0);
        $valorLaminaExtra = (float) ($c['valor_lamina_extra'] ?? 0);
        $categoriaOriginal = $c['categoria_original'] ?? '';
        $acabamentoJson = json_encode($c['acabamento_detalhado'] ?? [], JSON_UNESCAPED_UNICODE);
        $estojoJson = json_encode($c['estojo'] ?? [], JSON_UNESCAPED_UNICODE);
        $imagensJson = json_encode($c['imagens'] ?? [], JSON_UNESCAPED_UNICODE);

        // Upsert
        $check = $db->prepare("SELECT id FROM servicos WHERE id = ? LIMIT 1");
        $check->execute([$id]);
        if ($check->fetch()) {
            $stmtUp = $db->prepare("UPDATE servicos SET nome=?, categoria=?, tipo=?, descricao=?, preco_venda=?, custo_producao=?, valor_lamina_extra=?, categoria_original=?, acabamento_json=?, estojo_json=?, imagens_json=?, ativo=1 WHERE id=?");
            $stmtUp->execute([$nome, $categoria, $tipo, $descricao, $precoVenda, $custoProducao, $valorLaminaExtra, $categoriaOriginal, $acabamentoJson, $estojoJson, $imagensJson, $id]);
        } else {
            $stmtIn = $db->prepare("INSERT INTO servicos (id, nome, categoria, tipo, descricao, preco_venda, custo_producao, valor_lamina_extra, categoria_original, acabamento_json, estojo_json, imagens_json, ativo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)");
            $stmtIn->execute([$id, $nome, $categoria, $tipo, $descricao, $precoVenda, $custoProducao, $valorLaminaExtra, $categoriaOriginal, $acabamentoJson, $estojoJson, $imagensJson]);
        }
        $contColecoes++;
    }

    // 4. Inserir/Atualizar Acabamentos de Destaque
    foreach ($acabamentos as $index => $a) {
        $id = 'srv_acabamento_' . slugify($a['item']);
        $nome = $a['item'];
        $categoria = '15anos';
        $tipo = 'acabamento';
        $descricao = $a['descricao'];
        $imagensJson = json_encode(['imagem_exemplo' => $a['imagem_exemplo'] ?? ''], JSON_UNESCAPED_UNICODE);

        $check = $db->prepare("SELECT id FROM servicos WHERE id = ? LIMIT 1");
        $check->execute([$id]);
        if ($check->fetch()) {
            $stmtUp = $db->prepare("UPDATE servicos SET nome=?, categoria=?, tipo=?, descricao=?, imagens_json=?, ativo=1 WHERE id=?");
            $stmtUp->execute([$nome, $categoria, $tipo, $descricao, $imagensJson, $id]);
        } else {
            $stmtIn = $db->prepare("INSERT INTO servicos (id, nome, categoria, tipo, descricao, imagens_json, ativo) VALUES (?,?,?,?,?,?,1)");
            $stmtIn->execute([$id, $nome, $categoria, $tipo, $descricao, $imagensJson]);
        }
        $contAcabamentos++;
    }

    echo "<h1>Importação para Tabela de Preços Concluída!</h1>";
    echo "<p>Coleções importadas: <strong>{$contColecoes}</strong></p>";
    echo "<p>Acabamentos importados: <strong>{$contAcabamentos}</strong></p>";
    echo "<p><a href='../precificacao/servicos.php'>Ir para Tabela de Preços</a></p>";

} catch (Exception $e) {
    echo "<h1 style='color:red;'>Erro no Seed de Serviços!</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
