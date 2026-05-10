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

    $likes = (int)($d['likes'] ?? 0);
    $comentarios = (int)($d['comentarios'] ?? 0);
    $shares = (int)($d['shares'] ?? 0);
    $reposts = (int)($d['reposts'] ?? 0);
    $salvamentos = (int)($d['salvamentos'] ?? 0);

    // Novo Cálculo do Score: Pesos estratégicos para IG
    $score = ($likes * 1) + ($comentarios * 5) + ($shares * 10) + ($reposts * 15) + ($salvamentos * 20);

    if (!empty($d['id'])) {
        // Update
        $stmt = $db->prepare("UPDATE roteiros SET 
            titulo = ?, gancho = ?, quebra_crenca = ?, desenvolvimento = ?, 
            conexao = ?, fechamento = ?, cta = ?, tags = ?, status = ?, 
            likes = ?, comentarios = ?, shares = ?, reposts = ?, salvamentos = ?, score = ?,
            intencao = ?, tema = ?, numero = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        
        $stmt->execute([
            $d['titulo'], $d['gancho'] ?? '', $d['quebra_crenca'] ?? '', 
            $d['desenvolvimento'] ?? '', $d['conexao'] ?? '', $d['fechamento'] ?? '',
            $d['cta'] ?? '', $d['tags'] ?? '', $d['status'] ?? 'pendente',
            $likes, $comentarios, $shares, $reposts, $salvamentos, $score,
            $d['intencao'] ?? '', $d['tema'] ?? '', $d['numero'] ?? 0,
            $d['id']
        ]);
        
        responderJson(['success' => true, 'id' => $d['id'], 'score' => $score]);
    } else {
        // Insert
        $stmt = $db->prepare("INSERT INTO roteiros 
            (titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, tags, formato, status, 
            likes, comentarios, shares, reposts, salvamentos, score) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $d['titulo'], $d['gancho'] ?? '', $d['quebra_crenca'] ?? '',
            $d['desenvolvimento'] ?? '', $d['conexao'] ?? '', $d['fechamento'] ?? '',
            $d['cta'] ?? '', $d['tags'] ?? '', $d['formato'] ?? '', $d['status'] ?? 'pendente',
            $likes, $comentarios, $shares, $reposts, $salvamentos, $score
        ]);
        
        responderJson(['success' => true, 'id' => $db->lastInsertId(), 'score' => $score]);
    }

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
