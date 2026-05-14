<?php
/**
 * Migração: Criar tabela de depoimentos
 * Acessar UMA vez: http://seu-site.com/setup/migration_depoimentos.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::get();

    $db->exec("CREATE TABLE IF NOT EXISTS depoimentos (
        id VARCHAR(32) PRIMARY KEY,
        texto TEXT NOT NULL,
        autor VARCHAR(255) NOT NULL,
        categoria ENUM('casamento','filmmaker','15anos','marketing') NOT NULL,
        ativo TINYINT(1) NOT NULL DEFAULT 1,
        ordem INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_categoria (categoria),
        KEY idx_ativo (ativo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "✅ Tabela 'depoimentos' criada com sucesso.<br><br>";

    // Seeds iniciais para casamento
    $seeds = [
        ['casamento', 'Foi a melhor escolha que fizemos. Eles capturaram a essência do nosso dia de uma forma que nunca imaginamos.', 'Fernanda & Thiago'],
        ['casamento', 'A sensibilidade da equipe é indescritível. Cada vez que vemos o vídeo, nos emocionamos como se estivéssemos lá de novo.', 'Mariana & Lucas'],
        ['filmmaker', 'O resultado superou todas as nossas expectativas. Um trabalho cinematográfico de verdade.', 'Bruno Alves'],
        ['15anos', 'Minha festa ficou registrada de um jeito que eu vou guardar para a vida toda. Incrível.', 'Isabela Martins'],
        ['marketing', 'Desde que a Distinto assumiu nossa comunicação, as nossas redes engajaram de verdade.', 'André Costa – CEO da Volta'],
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO depoimentos (id, texto, autor, categoria, ordem) VALUES (?, ?, ?, ?, ?)");
    foreach ($seeds as $i => $s) {
        $stmt->execute([md5($s[0] . $s[2]), $s[0], $s[2], $s[0], $i + 1]);
    }

    // Corrigir: texto/autor trocados acima — refazer corretamente
    $db->exec("DELETE FROM depoimentos");
    $stmt = $db->prepare("INSERT INTO depoimentos (id, texto, autor, categoria, ordem) VALUES (?, ?, ?, ?, ?)");
    foreach ($seeds as $i => $s) {
        [$cat, $texto, $autor] = $s;
        $stmt->execute([md5($cat . $autor), $texto, $autor, $cat, $i + 1]);
    }

    echo "✅ Depoimentos iniciais inseridos.<br>";
    echo "<br><strong style='color:orange'>Apague este arquivo após usar.</strong>";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage();
}
