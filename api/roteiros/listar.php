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

    $query = "SELECT id, numero, titulo, status, formato, score, created_at FROM roteiros ORDER BY created_at DESC";
    $params = [];

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $roteiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Migração em tempo real: Popular números que estão como 0 ou nulos
    $scripts_sem_numero = array_filter($roteiros, fn($r) => !$r['numero']);
    
    if (count($scripts_sem_numero) > 0) {
        foreach ($roteiros as &$r) {
            if (!$r['numero']) {
                // Pegar a posição dele na ordem de ID
                $st_pos = $db->prepare("SELECT COUNT(*) FROM roteiros WHERE id <= ?");
                $st_pos->execute([$r['id']]);
                $auto_num = $st_pos->fetchColumn();
                
                $db->prepare("UPDATE roteiros SET numero = ? WHERE id = ?")->execute([$auto_num, $r['id']]);
                $r['numero'] = $auto_num;
            }
        }
    }

    responderJson(['success' => true, 'data' => $roteiros]);

} catch (Exception $e) {
    responderJson(['success' => false, 'error' => $e->getMessage()], 500);
}
