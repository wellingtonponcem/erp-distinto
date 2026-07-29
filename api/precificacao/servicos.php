<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

$db     = Database::get();
$metodo = $_SERVER['REQUEST_METHOD'];

// Garantir estrutura
function garantirEstruturaServicos(PDO $db): void {
    $isMysql = (defined('DB_PORT') && DB_PORT == 3306);
    try {
        if ($isMysql) {
            $stmt = $db->query("SHOW COLUMNS FROM servicos");
            $colunasExistentes = [];
            while ($row = $stmt->fetch()) {
                $colunasExistentes[] = strtolower($row['Field'] ?? $row['field'] ?? '');
            }
        } else {
            $stmt = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_name = ?");
            $stmt->execute(['servicos']);
            $colunasExistentes = array_map('strtolower', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }
    } catch (Exception $e) {
        $colunasExistentes = [];
    }
    
    $novas = [
        'entregaveis'          => "TEXT NULL",
        'ferramentas'          => "TEXT NULL",
        'terceirizacao'        => "TEXT NULL",
        'periodicidade'        => "VARCHAR(20) NOT NULL DEFAULT 'mensal'",
        'prazo_minimo'         => "INT NOT NULL DEFAULT 0",
        'preco_venda'          => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'preco_venda_pontual'  => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'categoria'            => "VARCHAR(50) DEFAULT 'marketing'",
        'tipo'                 => "VARCHAR(20) DEFAULT 'servico'",
        'itens_json'           => "TEXT NULL",
        'subtitulo'            => "TEXT NULL",
        'beneficios_json'      => "TEXT NULL",
        'condicoes_comerciais' => "TEXT NULL",
        'imagens_json'         => "TEXT NULL",
        'acabamento_json'      => "TEXT NULL",
        'estojo_json'          => "TEXT NULL",
        'valor_lamina_extra'   => "DECIMAL(10,2) DEFAULT 0.00",
        'categoria_original'   => "VARCHAR(50) NULL"
    ];

    foreach ($novas as $col => $def) {
        if (!in_array(strtolower($col), $colunasExistentes, true)) {
            try {
                $db->exec("ALTER TABLE servicos ADD COLUMN {$col} {$def}");
            } catch (Exception $e) {}
        }
    }
}

try {
    garantirEstruturaServicos($db);
} catch (Exception $e) {}

switch ($metodo) {
    case 'GET':
        $rows = $db->query('SELECT * FROM servicos WHERE ativo=1 ORDER BY categoria ASC, nome ASC')->fetchAll();
        responderJson($rows);
        break;

    case 'POST':
        $d = lerCorpo();
        if (empty($d['nome'])) responderJson(['erro' => 'Nome obrigatório'], 422);
        $id = !empty($d['id']) ? $d['id'] : gerarId();
        try {
            $stmt = $db->prepare('INSERT INTO servicos (id, nome, categoria, tipo, itens_json, descricao, entregaveis, ferramentas, terceirizacao, periodicidade, prazo_minimo, preco_venda, preco_venda_pontual, horas_estimadas, custo_producao, custos_variaveis, markup, subtitulo, beneficios_json, imagens_json, acabamento_json, estojo_json, valor_lamina_extra, categoria_original) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $id, 
                $d['nome'], 
                $d['categoria'] ?? '15anos',
                $d['tipo'] ?? 'colecao',
                is_array($d['itens_json'] ?? null) ? json_encode($d['itens_json']) : ($d['itens_json'] ?? null),
                $d['descricao'] ?? null, 
                $d['entregaveis'] ?? null,
                $d['ferramentas'] ?? null,
                $d['terceirizacao'] ?? null,
                $d['periodicidade'] ?? 'pontual',
                $d['prazo_minimo'] ?? 0,
                $d['preco_venda'] ?? 0,
                $d['preco_venda_pontual'] ?? 0,
                $d['horas_estimadas'] ?? 0, 
                $d['custo_producao'] ?? 0, 
                $d['custos_variaveis'] ?? 0, 
                $d['markup'] ?? 30,
                $d['subtitulo'] ?? null,
                is_array($d['beneficios_json'] ?? null) ? json_encode($d['beneficios_json']) : ($d['beneficios_json'] ?? null),
                is_array($d['imagens_json'] ?? null) ? json_encode($d['imagens_json']) : ($d['imagens_json'] ?? null),
                is_array($d['acabamento_json'] ?? null) ? json_encode($d['acabamento_json']) : ($d['acabamento_json'] ?? null),
                is_array($d['estojo_json'] ?? null) ? json_encode($d['estojo_json']) : ($d['estojo_json'] ?? null),
                $d['valor_lamina_extra'] ?? 0,
                $d['categoria_original'] ?? null
            ]);
            responderJson(['ok' => true, 'id' => $id], 201);
        } catch (Exception $e) {
            responderJson(['erro' => 'Erro ao salvar serviço: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        $d = lerCorpo();
        if (empty($d['id'])) responderJson(['erro' => 'ID obrigatório'], 422);
        try {
            $stmt = $db->prepare('UPDATE servicos SET nome=?, categoria=?, tipo=?, itens_json=?, descricao=?, entregaveis=?, ferramentas=?, terceirizacao=?, periodicidade=?, prazo_minimo=?, preco_venda=?, preco_venda_pontual=?, horas_estimadas=?, custo_producao=?, custos_variaveis=?, markup=?, subtitulo=?, beneficios_json=?, imagens_json=?, acabamento_json=?, estojo_json=?, valor_lamina_extra=?, categoria_original=? WHERE id=?');
            $stmt->execute([
                $d['nome'], 
                $d['categoria'] ?? '15anos',
                $d['tipo'] ?? 'colecao',
                is_array($d['itens_json'] ?? null) ? json_encode($d['itens_json']) : ($d['itens_json'] ?? null),
                $d['descricao'] ?? null, 
                $d['entregaveis'] ?? null,
                $d['ferramentas'] ?? null,
                $d['terceirizacao'] ?? null,
                $d['periodicidade'] ?? 'pontual',
                $d['prazo_minimo'] ?? 0,
                $d['preco_venda'] ?? 0,
                $d['preco_venda_pontual'] ?? 0,
                $d['horas_estimadas'] ?? 0, 
                $d['custo_producao'] ?? 0, 
                $d['custos_variaveis'] ?? 0, 
                $d['markup'] ?? 30,
                $d['subtitulo'] ?? null,
                is_array($d['beneficios_json'] ?? null) ? json_encode($d['beneficios_json']) : ($d['beneficios_json'] ?? null),
                is_array($d['imagens_json'] ?? null) ? json_encode($d['imagens_json']) : ($d['imagens_json'] ?? null),
                is_array($d['acabamento_json'] ?? null) ? json_encode($d['acabamento_json']) : ($d['acabamento_json'] ?? null),
                is_array($d['estojo_json'] ?? null) ? json_encode($d['estojo_json']) : ($d['estojo_json'] ?? null),
                $d['valor_lamina_extra'] ?? 0,
                $d['categoria_original'] ?? null,
                $d['id']
            ]);
            responderJson(['ok' => true]);
        } catch (Exception $e) {
            responderJson(['erro' => 'Erro ao atualizar serviço: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (!$id) responderJson(['erro' => 'ID obrigatório'], 422);
        $db->prepare('UPDATE servicos SET ativo=0 WHERE id=?')->execute([$id]);
        responderJson(['ok' => true]);
        break;

    default:
        responderJson(['erro' => 'Método não permitido'], 405);
}
