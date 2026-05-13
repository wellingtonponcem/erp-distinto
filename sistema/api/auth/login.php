<?php
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$dados = lerCorpo();
$email = trim($dados['email'] ?? '');
$senha = trim($dados['senha'] ?? '');

if (!$email || !$senha) {
    responderJson(['erro' => 'E-mail e senha são obrigatórios'], 422);
}

$db   = Database::get();
$stmt = $db->prepare('SELECT id, nome, email, senha, nivel, subscription_status, subscription_plan FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($senha, $user['senha'])) {
    responderJson(['erro' => 'Credenciais inválidas'], 401);
}

logarUsuario($user);
responderJson(['ok' => true, 'nome' => $user['nome']]);
