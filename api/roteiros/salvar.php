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

    $views = (int)($d['views'] ?? 0);
    $likes = (int)($d['likes'] ?? 0);
    $shares = (int)($d['shares'] ?? 0);
    $reposts = (int)($d['reposts'] ?? 0);

    // Cálculo do Score: Peso 1 para views, 2 para likes, 5 para shares, 10 para reposts
    $score = ($views * 0.1) + ($likes * 2) + ($shares * 5) + ($reposts * 10);

    if (!empty($d['id'])) {
        // Update
        $stmt = $db->prepare("UPDATE roteiros SET 
            titulo = ?, gancho = ?, quebra_crenca = ?, desenvolvimento = ?, conexao = ?, fechamento = ?, 
            cta = ?, tags = ?, formato = ?, status = ?, views = ?, likes = ?, shares = ?, reposts = ?, score = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        
        $stmt->execute([
            $d['titulo'], $d['gancho'] ?? '', $d['quebra_crenca'] ?? '', 
            $d['desenvolvimento'] ?? '', $d['conexao'] ?? '', $d['fechamento'] ?? '',
            $d['cta'] ?? '', $d['tags'] ?? '', $d['formato'] ?? '', $d['status'] ?? 'pendente',
            $views, $likes, $shares, $reposts, $score, $d['id']
        ]);
        
        responderJson(['success' => true, 'id' => $d['id'], 'score' => $score]);
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO roteiros 
            (titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, tags, formato, status, views, likes, shares, reposts, score) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $d['titulo'], $d['gancho'] ?? '', $d['quebra_crenca'] ?? '',
            $d['desenvolvimento'] ?? '', $d['conexao'] ?? '', $d['fechamento'] ?? '',
            $d['cta'] ?? '', $d['tags'] ?? '', $d['formato'] ?? '', $d['status'] ?? 'pendente',
            $views, $likes, $shares, $reposts, $score
        ]);
        
        responderJson(['success' => true, 'id' => $db->lastInsertId(), 'score' => $score]);
    }

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
