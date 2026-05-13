<?php
require_once __DIR__ . '/config/database.php';
try {
    $db = Database::get();
    $res = $db->query("SELECT id, nome_arquivo, LENGTH(texto_extraido) as len FROM roteiros_conhecimento")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($res);
    
    $mem = $db->query("SELECT LENGTH(conteudo) as len FROM roteiros_memoria")->fetch(PDO::FETCH_ASSOC);
    echo "\nMemória Mestra: ";
    print_r($mem);
    echo "</pre>";
} catch (Exception $e) {
    echo $e->getMessage();
}
