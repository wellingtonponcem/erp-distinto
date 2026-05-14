<?php
/**
 * API: Listar Roteiros
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/helpers.php';

exigirAutenticacao();

try {
    $db = Database::get();
    
    $status = $_GET['status'] ?? null;
    $tag = $_GET['tag'] ?? null;

    $usuario = usuarioAtual();
    $query   = "SELECT id, numero, titulo, status, formato, score, created_at FROM roteiros WHERE user_id = ? ORDER BY created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([$usuario['id']]);
    $roteiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Migração em tempo real: Popular números que estão como 0 ou nulos
    $scripts_sem_numero = array_filter($roteiros, fn($r) => !$r['numero']);
    $userId = $usuario['id'];
    
    if (count($scripts_sem_numero) > 0) {
        foreach ($roteiros as &$r) {
            if (!$r['numero']) {
                // Pegar a posição dele com base na data de criação (ou gerar um número aleatório se preferir)
                // Usar ID como text para evitar erro de tipo do Postgres
                $st_pos = $db->prepare("SELECT COUNT(*) FROM roteiros WHERE created_at <= (SELECT created_at FROM roteiros WHERE id = ?)");
                $st_pos->execute([$r['id']]);
                $auto_num = $st_pos->fetchColumn();
                
                if (!$auto_num) {
                    $auto_num = rand(100, 999);
                }
                
                $db->prepare("UPDATE roteiros SET numero = ? WHERE id = ? AND user_id = ?")->execute([$auto_num, $r['id'], $userId]);
                $r['numero'] = $auto_num;
            }
        }
    }

    responderJson(['success' => true, 'data' => $roteiros]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
