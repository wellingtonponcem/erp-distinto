<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::get();
$contratos = $db->query("SELECT id, titulo, dados_json FROM contratos ORDER BY id DESC LIMIT 5")->fetchAll();
foreach ($contratos as $c) {
    echo "ID: " . $c['id'] . " - " . $c['titulo'] . "\n";
    $dados = json_decode($c['dados_json'], true);
    $html = $dados['contrato_texto'] ?? '';
    if (!$html) {
        echo "contrato_texto not found in dados_json!\n\n";
        continue;
    }
    
    // Check what tags are around CLÁUSULA TERCEIRA
    if (preg_match('/CLÁUSULA TERCEIRA[^\n<]*/i', $html, $m)) {
        echo "Found text: " . $m[0] . "\n";
        $pos = strpos($html, $m[0]);
        $start = max(0, $pos - 100);
        $chunk = substr($html, $start, 600);
        echo "HTML Context:\n" . htmlspecialchars($chunk) . "\n\n";
    } else {
        echo "CLÁUSULA TERCEIRA NOT FOUND in HTML!\n";
        // Let's print a small chunk of the HTML
        echo "HTML Beginning:\n" . htmlspecialchars(substr($html, 0, 500)) . "\n\n";
    }
}
