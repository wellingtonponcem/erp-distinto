<?php
// Script para remover duplicatas de lançamentos OFX
// Executar via CLI: php cleanup_ofx_duplicates.php

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::get();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    // Encontrar duplicatas de ofx_fitid (compatível com MySQL e PostgreSQL)
    if ($driver === 'pgsql') {
        $stmt = $db->prepare("
            SELECT ofx_fitid, COUNT(*) as cnt
            FROM lancamentos 
            WHERE ofx_fitid IS NOT NULL AND ofx_fitid != '' 
            GROUP BY ofx_fitid 
            HAVING COUNT(*) > 1
        ");
        $stmt->execute();
        $duplicates = $stmt->fetchAll();
        
        echo "Duplicatas encontradas: " . count($duplicates) . "\n";
        
        $totalRemoved = 0;
        foreach ($duplicates as $dup) {
            // Pegar todos os IDs exceto o primeiro
            $idsStmt = $db->prepare("
                SELECT id FROM lancamentos 
                WHERE ofx_fitid = ? 
                ORDER BY id 
                OFFSET 1
            ");
            $idsStmt->execute([$dup['ofx_fitid']]);
            $toRemove = $idsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($toRemove as $id) {
                $db->prepare("DELETE FROM lancamentos WHERE id = ?")->execute([$id]);
                $totalRemoved++;
                echo "Removido: $id (fitid: {$dup['ofx_fitid']})\n";
            }
        }
    } else {
        // MySQL
        $stmt = $db->prepare("
            SELECT ofx_fitid, COUNT(*) as cnt, GROUP_CONCAT(id ORDER BY id) as ids
            FROM lancamentos 
            WHERE ofx_fitid IS NOT NULL AND ofx_fitid != '' 
            GROUP BY ofx_fitid 
            HAVING COUNT(*) > 1
        ");
        $stmt->execute();
        $duplicates = $stmt->fetchAll();

        echo "Duplicatas encontradas: " . count($duplicates) . "\n";

        $totalRemoved = 0;
        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup['ids']);
            // Manter o primeiro, remover os demais
            $toRemove = array_slice($ids, 1);
            foreach ($toRemove as $id) {
                $db->prepare("DELETE FROM lancamentos WHERE id = ?")->execute([$id]);
                $totalRemoved++;
                echo "Removido: $id (fitid: {$dup['ofx_fitid']})\n";
            }
        }
    }

    echo "\nTotal removido: $totalRemoved\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}