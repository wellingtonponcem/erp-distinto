<?php
/**
 * API: Sugestões de alteração dos roteiros.
 * - sugerir: público, sem autenticação
 * - aceitar/recusar: exige usuário autenticado
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d = lerCorpo();
$action = $d['action'] ?? '';

try {
    $db = Database::get();
    $db->exec("CREATE TABLE IF NOT EXISTS roteiros_sugestoes (
        id VARCHAR(32) PRIMARY KEY,
        roteiro_id VARCHAR(32) NOT NULL,
        campo VARCHAR(80) NOT NULL,
        texto_original TEXT NULL,
        texto_sugerido TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'pendente',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS roteiros_feedback_historico (
        id VARCHAR(32) PRIMARY KEY,
        roteiro_id VARCHAR(32) NOT NULL,
        user_id VARCHAR(32) NOT NULL,
        cliente_id VARCHAR(36) NULL,
        tipo VARCHAR(40) NOT NULL,
        campo VARCHAR(80) NULL,
        conteudo TEXT NOT NULL,
        metadata TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    if ($action === 'sugerir') {
        $roteiroId = trim((string)($d['roteiro_id'] ?? ''));
        $token = trim((string)($d['token'] ?? ''));
        $campo = trim((string)($d['campo'] ?? ''));
        $textoSugerido = trim((string)($d['texto_sugerido'] ?? ''));
        $textoOriginal = trim((string)($d['texto_original'] ?? ''));

        $camposPermitidos = ['titulo', 'gancho', 'quebra_crenca', 'desenvolvimento', 'conexao', 'fechamento', 'cta'];
        if (!$roteiroId || $token === '' || !in_array($campo, $camposPermitidos, true) || $textoSugerido === '') {
            responderJson(['erro' => 'Dados inválidos'], 422);
        }

        try { $db->exec("ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS public_token VARCHAR(64)"); } catch (Exception $e) {}
        $stmtRot = $db->prepare("SELECT id, user_id, cliente_id FROM roteiros WHERE id = ? AND public_token = ? LIMIT 1");
        $stmtRot->execute([$roteiroId, $token]);
        $roteiro = $stmtRot->fetch(PDO::FETCH_ASSOC);
        if (!$roteiro) responderJson(['erro' => 'Roteiro não encontrado'], 404);

        $id = gerarId();
        $stmt = $db->prepare("INSERT INTO roteiros_sugestoes (id, roteiro_id, campo, texto_original, texto_sugerido, status) VALUES (?, ?, ?, ?, ?, 'pendente')");
        $stmt->execute([$id, $roteiroId, $campo, $textoOriginal, $textoSugerido]);

        $stmtHist = $db->prepare("INSERT INTO roteiros_feedback_historico (id, roteiro_id, user_id, cliente_id, tipo, campo, conteudo, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtHist->execute([
            gerarId(), $roteiroId, $roteiro['user_id'], $roteiro['cliente_id'] ?? null,
            'sugestao_cliente', $campo,
            "Cliente sugeriu alterar para: " . mb_substr($textoSugerido, 0, 1200),
            json_encode(['original' => mb_substr($textoOriginal, 0, 1200)], JSON_UNESCAPED_UNICODE)
        ]);

        responderJson(['success' => true, 'id' => $id]);
    }

    exigirAutenticacao();
    $usuario = usuarioAtual();
    $userId = function_exists('roteirosUserId') ? roteirosUserId($usuario) : $usuario['id'];

    if (!in_array($action, ['aceitar', 'recusar'], true)) {
        responderJson(['erro' => 'Ação inválida'], 422);
    }

    $id = trim((string)($d['id'] ?? ''));
    if (!$id) responderJson(['erro' => 'Sugestão inválida'], 422);

    $stmtSug = $db->prepare("
        SELECT s.*, r.user_id, r.cliente_id
        FROM roteiros_sugestoes s
        INNER JOIN roteiros r ON r.id = s.roteiro_id
        WHERE s.id = ?
          AND r.user_id = ?
        LIMIT 1
    ");
    $stmtSug->execute([$id, $userId]);
    $sug = $stmtSug->fetch(PDO::FETCH_ASSOC);
    if (!$sug) responderJson(['erro' => 'Sugestão não encontrada'], 404);

    if ($action === 'aceitar') {
        $camposPermitidos = ['titulo', 'gancho', 'quebra_crenca', 'desenvolvimento', 'conexao', 'fechamento', 'cta'];
        if (!in_array($sug['campo'], $camposPermitidos, true)) {
            responderJson(['erro' => 'Campo inválido'], 422);
        }

        $campo = $sug['campo'];
        $stmtUpdRot = $db->prepare("UPDATE roteiros SET {$campo} = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ?");
        $stmtUpdRot->execute([$sug['texto_sugerido'], $sug['roteiro_id'], $userId]);
        $novoStatus = 'aceita';
        $conteudoHist = "Sugestão aceita. Novo texto: " . mb_substr($sug['texto_sugerido'], 0, 1200);
    } else {
        $novoStatus = 'recusada';
        $conteudoHist = "Sugestão recusada. Texto sugerido: " . mb_substr($sug['texto_sugerido'], 0, 1200);
    }

    $stmtUpd = $db->prepare("UPDATE roteiros_sugestoes SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmtUpd->execute([$novoStatus, $id]);

    $stmtHist = $db->prepare("INSERT INTO roteiros_feedback_historico (id, roteiro_id, user_id, cliente_id, tipo, campo, conteudo, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtHist->execute([
        gerarId(), $sug['roteiro_id'], $userId, $sug['cliente_id'] ?? null,
        $action === 'aceitar' ? 'sugestao_aceita' : 'sugestao_recusada',
        $sug['campo'],
        $conteudoHist,
        json_encode(['original' => $sug['texto_original']], JSON_UNESCAPED_UNICODE)
    ]);

    responderJson(['success' => true]);
} catch (Exception $e) {
    responderJson(['success' => false, 'erro' => $e->getMessage()], 500);
}
