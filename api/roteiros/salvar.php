<?php
/**
 * API: Salvar/Atualizar Roteiro
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJson(['erro' => 'Método não permitido'], 405);
}

$d = lerCorpo();

try {
    $db = Database::get();

    // Auto-migração: Garantir que todas as colunas novas existam
    $cols = ["gancho", "quebra_crenca", "desenvolvimento", "conexao", "fechamento", "cta", "intencao", "tema", "numero", "score", "likes", "comentarios", "shares", "reposts", "salvamentos"];
    foreach ($cols as $c) {
        try { $db->exec("ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS $c TEXT"); } catch(Exception $e) {}
    }
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
        // Update - Numero is NOT updated here to remain permanent
        $stmt = $db->prepare("UPDATE roteiros SET 
            titulo = ?, gancho = ?, quebra_crenca = ?, desenvolvimento = ?, 
            conexao = ?, fechamento = ?, cta = ?, tags = ?, status = ?, 
            likes = ?, comentarios = ?, shares = ?, reposts = ?, salvamentos = ?, score = ?,
            intencao = ?, tema = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        
        $stmt->execute([
            $d['titulo'], $d['gancho'] ?? '', $d['quebra_crenca'] ?? '', 
            $d['desenvolvimento'] ?? '', $d['conexao'] ?? '', $d['fechamento'] ?? '',
            $d['cta'] ?? '', $d['tags'] ?? '', $d['status'] ?? 'pendente',
            $likes, $comentarios, $shares, $reposts, $salvamentos, $score,
            $d['intencao'] ?? '', $d['tema'] ?? '',
            $d['id']
        ]);
        
        responderJson(['success' => true, 'id' => $d['id'], 'score' => $score]);
    } else {
        // Insert - Calculate next sequence number
        $st = $db->query("SELECT COALESCE(MAX(numero), 0) + 1 as prox FROM roteiros");
        $prox = $st->fetch(PDO::FETCH_ASSOC)['prox'];

        $stmt = $db->prepare("INSERT INTO roteiros 
            (titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, tags, formato, status, 
            likes, comentarios, shares, reposts, salvamentos, score, numero, intencao, tema) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $d['titulo'], $d['gancho'] ?? '', $d['quebra_crenca'] ?? '',
            $d['desenvolvimento'] ?? '', $d['conexao'] ?? '', $d['fechamento'] ?? '',
            $d['cta'] ?? '', $d['tags'] ?? '', $d['formato'] ?? '', $d['status'] ?? 'pendente',
            $likes, $comentarios, $shares, $reposts, $salvamentos, $score,
            $prox, $d['intencao'] ?? '', $d['tema'] ?? ''
        ]);
        
        responderJson(['success' => true, 'id' => $db->lastInsertId(), 'score' => $score]);
    }

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
