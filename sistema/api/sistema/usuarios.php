<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

try {
    $db = Database::get();
    $metodo = $_SERVER['REQUEST_METHOD'];

    // Migração Automática para PostgreSQL
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name='users' AND column_name='nivel'");
        if ($stmt->fetchColumn() == 0) {
            $db->exec("ALTER TABLE users ADD COLUMN nivel INTEGER DEFAULT 0");
            $db->exec("UPDATE users SET nivel = 1 WHERE id = (SELECT id FROM users ORDER BY criado_em ASC LIMIT 1)");
        }
    } catch (Exception $e) {
        // Log ou ignorar se já existir
    }

    // Apenas admins podem gerenciar usuários
    $me = usuarioAtual();
    $stmt = $db->prepare("SELECT nivel FROM users WHERE id = ?");
    $stmt->execute([$me['id']]);
    $meNivel = (int)$stmt->fetchColumn();

    if ($meNivel !== 1 && $metodo !== 'GET') {
        responderJson(['erro' => 'Acesso negado. Apenas administradores podem realizar esta ação.'], 403);
    }

    switch ($metodo) {
        case 'GET':
            $users = $db->query("SELECT id, nome, email, nivel FROM users ORDER BY nome")->fetchAll();
            responderJson($users);
            break;

        case 'POST':
            $d = lerCorpo();
            if (empty($d['nome']) || empty($d['email']) || empty($d['senha'])) {
                responderJson(['erro' => 'Nome, e-mail e senha são obrigatórios'], 422);
            }
            
            // Verificar se e-mail já existe
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$d['email']]);
            if ($stmt->fetch()) responderJson(['erro' => 'Este e-mail já está cadastrado'], 422);

            $id = gerarId();
            $senhaHash = password_hash($d['senha'], PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("INSERT INTO users (id, nome, email, senha, nivel) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $id,
                $d['nome'],
                $d['email'],
                $senhaHash,
                $d['nivel'] ?? 0
            ]);
            
            responderJson(['ok' => true, 'id' => $id], 201);
            break;

        case 'PUT':
            $d = lerCorpo();
            if (empty($d['id'])) responderJson(['erro' => 'ID obrigatório'], 422);
            
            $params = [$d['nome'], $d['email'], (int)($d['nivel'] ?? 0), $d['id']];
            $sql = "UPDATE users SET nome=?, email=?, nivel=? WHERE id=?";
            
            if (!empty($d['senha'])) {
                $senhaHash = password_hash($d['senha'], PASSWORD_DEFAULT);
                $sql = "UPDATE users SET nome=?, email=?, nivel=?, senha=? WHERE id=?";
                $params = [$d['nome'], $d['email'], (int)($d['nivel'] ?? 0), $senhaHash, $d['id']];
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            responderJson(['ok' => true]);
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? '';
            if (!$id) responderJson(['erro' => 'ID obrigatório'], 422);
            if ($id === $me['id']) responderJson(['erro' => 'Você não pode excluir a si mesmo'], 422);
            
            $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
            responderJson(['ok' => true]);
            break;

        default:
            responderJson(['erro' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    responderJson(['erro' => 'Erro interno: ' . $e->getMessage()], 500);
}

