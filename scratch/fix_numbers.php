<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = Database::get();
    
    // Pegar todos os roteiros ordenados por ID (ordem de criação original)
    $st = $db->query("SELECT id FROM roteiros ORDER BY id ASC");
    $scripts = $st->fetchAll(PDO::FETCH_ASSOC);
    
    $num = 1;
    foreach ($scripts as $s) {
        $up = $db->prepare("UPDATE roteiros SET numero = ? WHERE id = ?");
        $up->execute([$num, $s['id']]);
        echo "Script ID {$s['id']} -> Numero $num\n";
        $num++;
    }
    
    echo "Fim da migração forçada.";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
