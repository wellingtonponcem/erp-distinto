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
    $stmt = $db->query("DESCRIBE servicos");
    $colunasExistentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $novas = [
        'entregaveis'    => "TEXT NULL",
        'ferramentas'    => "TEXT NULL",
        'terceirizacao'  => "TEXT NULL",
        'periodicidade'  => "VARCHAR(20) NOT NULL DEFAULT 'mensal'",
        'prazo_minimo'   => "INT NOT NULL DEFAULT 0",
        'preco_venda'    => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'preco_venda_pontual' => "DECIMAL(10,2) NOT NULL DEFAULT 0.00",
        'categoria'      => "VARCHAR(50) DEFAULT 'marketing'",
        'tipo'           => "VARCHAR(20) DEFAULT 'servico'",
        'itens_json'     => "TEXT NULL"
    ];

    foreach ($novas as $col => $def) {
        if (!in_array($col, $colunasExistentes, true)) {
            $db->exec("ALTER TABLE servicos ADD COLUMN {$col} {$def}");
        }
    }
}

try {
    garantirEstruturaServicos($db);
} catch (Exception $e) {
    // Se falhar a migração automática, o erro aparecerá no INSERT/UPDATE
}

switch ($metodo) {
    case 'GET':
        $rows = $db->query('SELECT * FROM servicos WHERE ativo=1 ORDER BY nome')->fetchAll();
        responderJson($rows);
        break;

    case 'POST':
        $d = lerCorpo();
        if (empty($d['nome'])) responderJson(['erro' => 'Nome obrigatório'], 422);
        $id = gerarId();
        try {
            $stmt = $db->prepare('INSERT INTO servicos (id, nome, categoria, tipo, itens_json, descricao, entregaveis, ferramentas, terceirizacao, periodicidade, prazo_minimo, preco_venda, preco_venda_pontual, horas_estimadas, custo_producao, custos_variaveis, markup) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $id, 
                $d['nome'], 
                $d['categoria'] ?? 'marketing',
                $d['tipo'] ?? 'servico',
                $d['itens_json'] ?? null,
                $d['descricao'] ?? null, 
                $d['entregaveis'] ?? null,
                $d['ferramentas'] ?? null,
                $d['terceirizacao'] ?? null,
                $d['periodicidade'] ?? 'mensal',
                $d['prazo_minimo'] ?? 0,
                $d['preco_venda'] ?? 0,
                $d['preco_venda_pontual'] ?? 0,
                $d['horas_estimadas'] ?? 0, 
                $d['custo_producao'] ?? 0, 
                $d['custos_variaveis'] ?? 0, 
                $d['markup'] ?? 30
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
            $stmt = $db->prepare('UPDATE servicos SET nome=?, categoria=?, tipo=?, itens_json=?, descricao=?, entregaveis=?, ferramentas=?, terceirizacao=?, periodicidade=?, prazo_minimo=?, preco_venda=?, preco_venda_pontual=?, horas_estimadas=?, custo_producao=?, custos_variaveis=?, markup=? WHERE id=?');
            $stmt->execute([
                $d['nome'], 
                $d['categoria'] ?? 'marketing',
                $d['tipo'] ?? 'servico',
                $d['itens_json'] ?? null,
                $d['descricao'] ?? null, 
                $d['entregaveis'] ?? null,
                $d['ferramentas'] ?? null,
                $d['terceirizacao'] ?? null,
                $d['periodicidade'] ?? 'mensal',
                $d['prazo_minimo'] ?? 0,
                $d['preco_venda'] ?? 0,
                $d['preco_venda_pontual'] ?? 0,
                $d['horas_estimadas'] ?? 0, 
                $d['custo_producao'] ?? 0, 
                $d['custos_variaveis'] ?? 0, 
                $d['markup'] ?? 30, 
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
