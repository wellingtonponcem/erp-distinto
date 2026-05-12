<?php
/**
 * API: Registro público de novo usuário (trial gratuito)
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d     = lerCorpo();
$nome  = trim($d['nome']  ?? '');
$email = strtolower(trim($d['email'] ?? ''));
$senha = $d['senha'] ?? '';
$conf  = $d['confirmar_senha'] ?? '';

// Validações
if (!$nome || !$email || !$senha) {
    responderJson(['erro' => 'Nome, e-mail e senha são obrigatórios.'], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responderJson(['erro' => 'E-mail inválido.'], 422);
}

if (strlen($senha) < 8) {
    responderJson(['erro' => 'A senha deve ter pelo menos 8 caracteres.'], 422);
}

if ($conf && $conf !== $senha) {
    responderJson(['erro' => 'As senhas não coincidem.'], 422);
}

try {
    $db = Database::get();

    // Verificar e-mail único
    $existe = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $existe->execute([$email]);
    if ($existe->fetchColumn() > 0) {
        responderJson(['erro' => 'Este e-mail já está cadastrado. Faça login.'], 409);
    }

    // Criar usuário
    $id    = gerarId();
    $hash  = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $db->prepare(
        "INSERT INTO users (id, nome, email, senha, nivel, trial_started_at, subscription_status, sistema_origem)
         VALUES (?, ?, ?, ?, 0, CURRENT_TIMESTAMP, 'trial', 'roteiros')"
    );
    $stmt->execute([$id, $nome, $email, $hash]);

    // Auto-login
    logarUsuario([
        'id'                  => $id,
        'nome'                => $nome,
        'email'               => $email,
        'nivel'               => 0,
        'sistema_origem'      => 'roteiros',
        'subscription_status' => 'trial',
        'subscription_plan'   => null,
    ]);

    responderJson([
        'ok'       => true,
        'nome'     => $nome,
        'redirect' => raizUrl('/roteiros/index.php'),
    ]);

} catch (Exception $e) {
    responderJson(['erro' => 'Erro ao criar conta: ' . $e->getMessage()], 500);
}
