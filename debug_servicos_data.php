<?php
require_once __DIR__ . '/config/database.php';
$db = Database::get();
$rows = $db->query("SELECT id, nome, descricao FROM servicos")->fetchAll();
echo "<table border='1'><tr><th>ID</th><th>Nome</th><th>Descrição</th></tr>";
foreach($rows as $r) {
    echo "<tr><td>{$r['id']}</td><td>{$r['nome']}</td><td>{$r['descricao']}</td></tr>";
}
echo "</table>";
