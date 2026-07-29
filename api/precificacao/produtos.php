<?php
/**
 * API — Gestão de Catálogo de Produtos (Álbuns Fotográficos & Acabamentos)
 */
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

$db = Database::get();
$metodo = $_SERVER['REQUEST_METHOD'];

// Garantir estrutura da tabela produtos_albuns
function garantirEstruturaProdutos(PDO $db): void {
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
    try {
        $db->exec($createSql);
    } catch (Exception $e) {}
}

try {
    garantirEstruturaProdutos($db);
} catch (Exception $e) {}

switch ($metodo) {
    case 'GET':
        $rows = $db->query('SELECT * FROM produtos_albuns WHERE ativo=1 ORDER BY categoria ASC, investimento_cliente ASC')->fetchAll();
        responderJson($rows);
        break;

    case 'POST':
        $d = lerCorpo();
        if (empty($d['nome'])) responderJson(['erro' => 'Nome do produto obrigatório'], 422);
        $id = !empty($d['id']) ? $d['id'] : ('prod_' . uniqid());
        try {
            $stmt = $db->prepare('INSERT INTO produtos_albuns (id, nome, categoria, categoria_original, tipo, descricao, custo_base, investimento_cliente, valor_lamina_extra, estojo_json, imagens_galeria_json, acabamentos_detalhados_json, ativo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,1)');
            $stmt->execute([
                $id,
                $d['nome'],
                $d['categoria'] ?? '15anos',
                $d['categoria_original'] ?? 'Coleção Premium',
                $d['tipo'] ?? 'produto',
                $d['descricao'] ?? null,
                $d['custo_base'] ?? 0,
                $d['investimento_cliente'] ?? 0,
                $d['valor_lamina_extra'] ?? 0,
                is_array($d['estojo_json'] ?? null) ? json_encode($d['estojo_json'], JSON_UNESCAPED_UNICODE) : ($d['estojo_json'] ?? null),
                is_array($d['imagens_galeria_json'] ?? null) ? json_encode($d['imagens_galeria_json'], JSON_UNESCAPED_UNICODE) : ($d['imagens_galeria_json'] ?? null),
                is_array($d['acabamentos_detalhados_json'] ?? null) ? json_encode($d['acabamentos_detalhados_json'], JSON_UNESCAPED_UNICODE) : ($d['acabamentos_detalhados_json'] ?? null)
            ]);
            responderJson(['ok' => true, 'id' => $id], 201);
        } catch (Exception $e) {
            responderJson(['erro' => 'Erro ao salvar produto: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        $d = lerCorpo();
        if (empty($d['id'])) responderJson(['erro' => 'ID do produto obrigatório'], 422);
        try {
            $stmt = $db->prepare('UPDATE produtos_albuns SET nome=?, categoria=?, categoria_original=?, tipo=?, descricao=?, custo_base=?, investimento_cliente=?, valor_lamina_extra=?, estojo_json=?, imagens_galeria_json=?, acabamentos_detalhados_json=? WHERE id=?');
            $stmt->execute([
                $d['nome'],
                $d['categoria'] ?? '15anos',
                $d['categoria_original'] ?? 'Coleção Premium',
                $d['tipo'] ?? 'produto',
                $d['descricao'] ?? null,
                $d['custo_base'] ?? 0,
                $d['investimento_cliente'] ?? 0,
                $d['valor_lamina_extra'] ?? 0,
                is_array($d['estojo_json'] ?? null) ? json_encode($d['estojo_json'], JSON_UNESCAPED_UNICODE) : ($d['estojo_json'] ?? null),
                is_array($d['imagens_galeria_json'] ?? null) ? json_encode($d['imagens_galeria_json'], JSON_UNESCAPED_UNICODE) : ($d['imagens_galeria_json'] ?? null),
                is_array($d['acabamentos_detalhados_json'] ?? null) ? json_encode($d['acabamentos_detalhados_json'], JSON_UNESCAPED_UNICODE) : ($d['acabamentos_detalhados_json'] ?? null),
                $d['id']
            ]);
            responderJson(['ok' => true]);
        } catch (Exception $e) {
            responderJson(['erro' => 'Erro ao atualizar produto: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? '';
        if (!$id) responderJson(['erro' => 'ID obrigatório'], 422);
        $db->prepare('UPDATE produtos_albuns SET ativo=0 WHERE id=?')->execute([$id]);
        responderJson(['ok' => true]);
        break;

    default:
        responderJson(['erro' => 'Método não permitido'], 405);
}
