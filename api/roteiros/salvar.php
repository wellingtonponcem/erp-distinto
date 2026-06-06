<?php
/**
 * API: Salvar/Atualizar Roteiro
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/assinatura.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d       = lerCorpo();
$usuario = usuarioAtual();
$userId  = function_exists('roteirosUserId') ? roteirosUserId($usuario) : $usuario['id'];

try {
    $db = Database::get();
    if (($usuario['sistema_origem'] ?? '') === 'distinto' && function_exists('normalizarRoteirosDistinto')) normalizarRoteirosDistinto($db);
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

    // Auto-migração: Garantir que todas as colunas novas existam
    $cols = ["gancho", "quebra_crenca", "desenvolvimento", "conexao", "fechamento", "cta", "intencao", "tema", "numero", "score", "likes", "comentarios", "shares", "reposts", "salvamentos", "cliente_id"];
    foreach ($cols as $c) {
        try { $db->exec("ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS $c TEXT"); } catch(Exception $e) {}
    }
    try { $db->exec("ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS public_token VARCHAR(64)"); } catch(Exception $e) {}
    // Ajustar tipos numéricos (Postgres IF NOT EXISTS no ALTER é chatinho, então fazemos um a um)
    try { $db->exec("ALTER TABLE roteiros ALTER COLUMN numero TYPE INTEGER USING (numero::integer)"); } catch(Exception $e) {}
    try { $db->exec("ALTER TABLE roteiros ALTER COLUMN score TYPE FLOAT USING (score::float)"); } catch(Exception $e) {}

    $likes = (int)($d['likes'] ?? 0);
    $comentarios = (int)($d['comentarios'] ?? 0);
    $shares = (int)($d['shares'] ?? 0);
    $reposts = (int)($d['reposts'] ?? 0);
    $salvamentos = (int)($d['salvamentos'] ?? 0);

    // Novo Cálculo do Score: Pesos estratégicos para IG
    $score = ($likes * 1) + ($comentarios * 5) + ($shares * 10) + ($reposts * 15) + ($salvamentos * 20);

    if (!empty($d['id'])) {
        $stmtAntes = $db->prepare("SELECT titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, status, score, cliente_id FROM roteiros WHERE id = ? AND user_id = ? LIMIT 1");
        $stmtAntes->execute([$d['id'], $userId]);
        $roteiroAntes = $stmtAntes->fetch(PDO::FETCH_ASSOC) ?: [];

        // UPDATE — garantir que o roteiro pertence ao usuário
        $stmt = $db->prepare("UPDATE roteiros SET
            titulo = ?, gancho = ?, quebra_crenca = ?, desenvolvimento = ?,
            conexao = ?, fechamento = ?, cta = ?, tags = ?, status = ?,
            likes = ?, comentarios = ?, shares = ?, reposts = ?, salvamentos = ?, score = ?,
            intencao = ?, tema = ?, cliente_id = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?");

        $stmt->execute([
            $d['titulo'], $d['gancho'] ?? '', $d['quebra_crenca'] ?? '',
            $d['desenvolvimento'] ?? '', $d['conexao'] ?? '', $d['fechamento'] ?? '',
            $d['cta'] ?? '', $d['tags'] ?? '', $d['status'] ?? 'pendente',
            $likes, $comentarios, $shares, $reposts, $salvamentos, $score,
            $d['intencao'] ?? '', $d['tema'] ?? '', $d['cliente_id'] ?? null,
            $d['id'], $userId
        ]);

        $script_id = $d['id'];

        $clienteHistorico = $d['cliente_id'] ?? ($roteiroAntes['cliente_id'] ?? null);
        $camposTexto = ['titulo', 'gancho', 'quebra_crenca', 'desenvolvimento', 'conexao', 'fechamento', 'cta'];
        foreach ($camposTexto as $campo) {
            $antes = trim((string)($roteiroAntes[$campo] ?? ''));
            $depois = trim((string)($d[$campo] ?? ''));
            if ($antes !== $depois && $depois !== '') {
                $stmtHist = $db->prepare("INSERT INTO roteiros_feedback_historico (id, roteiro_id, user_id, cliente_id, tipo, campo, conteudo, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtHist->execute([
                    gerarId(), $script_id, $userId, $clienteHistorico, 'ajuste_manual', $campo,
                    "O campo foi ajustado manualmente para: " . mb_substr($depois, 0, 1200),
                    json_encode(['antes' => mb_substr($antes, 0, 1200)], JSON_UNESCAPED_UNICODE)
                ]);
            }
        }

        if (($roteiroAntes['status'] ?? '') !== ($d['status'] ?? 'pendente')) {
            $stmtHist = $db->prepare("INSERT INTO roteiros_feedback_historico (id, roteiro_id, user_id, cliente_id, tipo, campo, conteudo, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtHist->execute([
                gerarId(), $script_id, $userId, $clienteHistorico, 'status_manual', 'status',
                "Status alterado para " . ($d['status'] ?? 'pendente') . ".",
                json_encode(['antes' => $roteiroAntes['status'] ?? ''], JSON_UNESCAPED_UNICODE)
            ]);
        }

        if ((float)($roteiroAntes['score'] ?? 0) !== (float)$score && $score > 0) {
            $stmtHist = $db->prepare("INSERT INTO roteiros_feedback_historico (id, roteiro_id, user_id, cliente_id, tipo, campo, conteudo, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtHist->execute([
                gerarId(), $script_id, $userId, $clienteHistorico, 'metricas', 'score',
                "Roteiro recebeu score {$score}. Likes: {$likes}; comentários: {$comentarios}; envios: {$shares}; reposts: {$reposts}; salvamentos: {$salvamentos}.",
                json_encode(['score_anterior' => $roteiroAntes['score'] ?? 0], JSON_UNESCAPED_UNICODE)
            ]);
        }
    } else {
        // INSERT — verificar limite diário do trial antes de criar
        $limite = verificarLimiteDiario($userId);
        if (!$limite['ok']) {
            $motivo = $limite['motivo'];
            if ($motivo === 'trial_expirado') {
                responderJson(['success' => false, 'paywall' => true, 'motivo' => 'trial_expirado',
                    'mensagem' => 'Seu período de teste encerrou. Assine para continuar criando roteiros.'], 403);
            }
            responderJson(['success' => false, 'paywall' => true, 'motivo' => 'limite_diario',
                'mensagem' => "Você atingiu o limite de {$limite['limite']} roteiros hoje. Volte amanhã ou assine o plano.",
                'limite' => $limite['limite'], 'usados' => $limite['usados']], 403);
        }

        // Próximo número de sequência do usuário
        $st   = $db->prepare("SELECT COALESCE(MAX(numero), 0) + 1 AS prox FROM roteiros WHERE user_id = ?");
        $st->execute([$userId]);
        $prox = $st->fetch(PDO::FETCH_ASSOC)['prox'];

        $stmt = $db->prepare("INSERT INTO roteiros
            (titulo, public_token, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, tags, formato, status,
            likes, comentarios, shares, reposts, salvamentos, score, numero, intencao, tema, cliente_id, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id");

        $stmt->execute([
            $d['titulo'], bin2hex(random_bytes(24)), $d['gancho'] ?? '', $d['quebra_crenca'] ?? '',
            $d['desenvolvimento'] ?? '', $d['conexao'] ?? '', $d['fechamento'] ?? '',
            $d['cta'] ?? '', $d['tags'] ?? '', $d['formato'] ?? '', $d['status'] ?? 'pendente',
            $likes, $comentarios, $shares, $reposts, $salvamentos, $score,
            $prox, $d['intencao'] ?? '', $d['tema'] ?? '', $d['cliente_id'] ?? null, $userId
        ]);
        $script_id = $stmt->fetchColumn();

        if ($score > 0 || ($d['status'] ?? 'pendente') !== 'pendente') {
            $stmtHist = $db->prepare("INSERT INTO roteiros_feedback_historico (id, roteiro_id, user_id, cliente_id, tipo, campo, conteudo, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtHist->execute([
                gerarId(), $script_id, $userId, $d['cliente_id'] ?? null, 'criacao_manual', null,
                "Roteiro criado manualmente com status " . ($d['status'] ?? 'pendente') . " e score {$score}.",
                null
            ]);
        }
    }

    // --- LOOP DE APRENDIZAGEM (VOZ DO USUÁRIO) ---
    // Pega o conteúdo do roteiro e joga na base de conhecimento como texto
    // Mas SÓ faz isso se não for um roteiro recém-criado 100% pela IA (para ela não aprender com ela mesma)
    $is_ia_generated = isset($d['is_ia_generated']) && $d['is_ia_generated'] === true;

    if (!$is_ia_generated) {
        try { $db->exec("ALTER TABLE roteiros_conhecimento ADD COLUMN IF NOT EXISTS cliente_id VARCHAR(36) DEFAULT NULL"); } catch(Exception $e) {}
        $textoCompleto = trim(
            "Roteiro Escrito/Editado pelo Usuário (Aprender Voz e Estilo):\n\n" .
            "TÍTULO: {$d['titulo']}\n" .
            "GANCHO: " . ($d['gancho'] ?? '') . "\n" .
            "DESENVOLVIMENTO: " . trim(($d['quebra_crenca'] ?? '') . " " . ($d['desenvolvimento'] ?? '') . " " . ($d['conexao'] ?? '')) . "\n" .
            "FECHAMENTO/CTA: " . trim(($d['fechamento'] ?? '') . " " . ($d['cta'] ?? ''))
        );

        // Salva apenas se tiver conteúdo substancial
        if (strlen($textoCompleto) > 100) {
            $caminho_interno = 'roteiro_interno_' . (isset($script_id) ? $script_id : $d['id']);
            
            $stmtFonte = $db->prepare("SELECT id FROM roteiros_conhecimento WHERE caminho_arquivo = ? AND user_id = ? LIMIT 1");
            $stmtFonte->execute([$caminho_interno, $userId]);
            $fonte_id = $stmtFonte->fetchColumn();

            $nomeArquivo = "📝 Roteiro: {$d['titulo']}";

            if ($fonte_id) {
                $db->prepare("UPDATE roteiros_conhecimento SET texto_extraido = ?, sincronizado = FALSE, nome_arquivo = ?, cliente_id = ? WHERE id = ? AND user_id = ?")
                   ->execute([$textoCompleto, $nomeArquivo, $d['cliente_id'] ?? null, $fonte_id, $userId]);
            } else {
                $db->prepare("INSERT INTO roteiros_conhecimento (nome_arquivo, caminho_arquivo, tipo_arquivo, texto_extraido, sincronizado, user_id, cliente_id) VALUES (?, ?, 'text', ?, FALSE, ?, ?)")
                   ->execute([$nomeArquivo, $caminho_interno, $textoCompleto, $userId, $d['cliente_id'] ?? null]);
            }
        }
    }

    responderJson(['success' => true, 'id' => $script_id, 'score' => $score]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
