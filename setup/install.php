<?php
/**
 * Distinto Studio — Setup do Banco de Dados Neon PostgreSQL
 * Acesse este arquivo UMA VEZ após o deploy: https://seudominio.com/setup/install.php
 * Depois APAGUE ou proteja este arquivo!
 */

require_once dirname(__DIR__) . '/includes/db.php';

$pdo = db();
$errors = [];
$success = [];

$sql_blocks = [

    'Tabela: settings' => "
        CREATE TABLE IF NOT EXISTS settings (
            key   VARCHAR(100) PRIMARY KEY,
            value TEXT NOT NULL DEFAULT ''
        );
    ",

    'Tabela: project_categories' => "
        CREATE TABLE IF NOT EXISTS project_categories (
            id   SERIAL PRIMARY KEY,
            slug VARCHAR(100) UNIQUE NOT NULL,
            name VARCHAR(100) NOT NULL
        );
    ",

    'Tabela: projects' => "
        CREATE TABLE IF NOT EXISTS projects (
            id                  SERIAL PRIMARY KEY,
            title               VARCHAR(255) NOT NULL,
            thumbnail_url       TEXT DEFAULT '',
            media_type          VARCHAR(10) DEFAULT 'image',
            video_url           TEXT DEFAULT '',
            overlay_title       VARCHAR(255) DEFAULT '',
            storytelling        TEXT DEFAULT '',
            impact_title        VARCHAR(255) DEFAULT '',
            narrative_text      TEXT DEFAULT '',
            storytelling_quote  TEXT DEFAULT '',
            menu_order          INTEGER DEFAULT 0,
            status              VARCHAR(20) DEFAULT 'published',
            created_at          TIMESTAMPTZ DEFAULT NOW()
        );
    ",

    'Tabela: project_category_relations' => "
        CREATE TABLE IF NOT EXISTS project_category_relations (
            project_id  INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
            category_id INTEGER NOT NULL REFERENCES project_categories(id) ON DELETE CASCADE,
            PRIMARY KEY (project_id, category_id)
        );
    ",

    'Tabela: project_gallery' => "
        CREATE TABLE IF NOT EXISTS project_gallery (
            id          SERIAL PRIMARY KEY,
            project_id  INTEGER NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
            image_url   TEXT NOT NULL,
            sort_order  INTEGER DEFAULT 0
        );
    ",

    'Tabela: contact_messages' => "
        CREATE TABLE IF NOT EXISTS contact_messages (
            id           SERIAL PRIMARY KEY,
            name         VARCHAR(255) NOT NULL,
            email        VARCHAR(255) NOT NULL,
            project_type VARCHAR(100) DEFAULT '',
            message      TEXT DEFAULT '',
            created_at   TIMESTAMPTZ DEFAULT NOW()
        );
    ",

    'Categorias padrão' => "
        INSERT INTO project_categories (slug, name) VALUES
            ('casamentos', 'Casamentos'),
            ('eventos',    'Eventos'),
            ('branding',   'Branding')
        ON CONFLICT (slug) DO NOTHING;
    ",

    'Settings padrão' => "
        INSERT INTO settings (key, value) VALUES
            ('hero_headline',  'NARRATIVAS VISUAIS QUE CONECTAM'),
            ('hero_serif',     'estratégia, estética e emoção'),
            ('hero_video_url', 'https://player.vimeo.com/video/928167937'),
            ('cta_title',      'Para marcas que querem ser percebidas.'),
            ('cta_subtitle',   'Para histórias que merecem ser eternas.'),
            ('service_capturar_sub', 'Captamos mais do que imagem. Captamos intenção, detalhe e o que realmente importa.'),
            ('service_narrar_sub',   'Toda marca e toda história precisa de direção. Aqui, transformamos momentos em narrativa.'),
            ('service_construir_sub','Não entregamos conteúdo. Construímos percepção, presença e valor.'),
            ('service_capturar_vid', 'https://player.vimeo.com/external/371433846.sd.mp4?s=231da6ab57f95c6ef4247514a6e8770dabc88730&profile_id=164&oauth2_token_id=57447761'),
            ('service_narrar_vid',   'https://player.vimeo.com/external/494252666.sd.mp4?s=72bd74720e74f1d46c6463fb75ec8ca7d08f582d&profile_id=164&oauth2_token_id=57447761'),
            ('service_construir_vid','https://player.vimeo.com/external/373111261.sd.mp4?s=1f1ec45c3ac0e2c8845fc86c8a306c28f7311749&profile_id=164&oauth2_token_id=57447761')
        ON CONFLICT (key) DO NOTHING;
    ",
];

foreach ($sql_blocks as $label => $sql) {
    try {
        $pdo->exec($sql);
        $success[] = $label;
    } catch (PDOException $e) {
        $errors[] = "$label: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Setup — Distinto Studio</title>
<style>
body { font-family: monospace; background: #0e0e0e; color: #e2e2e2; padding: 40px; max-width: 800px; margin: 0 auto; }
h1 { font-size: 18px; text-transform: uppercase; letter-spacing: .2em; margin-bottom: 32px; }
.ok   { color: #86efac; margin-bottom: 8px; }
.fail { color: #fca5a5; margin-bottom: 8px; }
.warn { background: #7f1d1d44; border: 1px solid #7f1d1d; color: #fca5a5; padding: 16px; margin-top: 32px; border-radius: 4px; }
.info { background: #14532d44; border: 1px solid #16a34a44; color: #86efac; padding: 16px; margin-top: 32px; border-radius: 4px; }
</style>
</head>
<body>
<h1>Distinto Studio — Setup do Banco</h1>

<?php foreach ($success as $s): ?>
    <div class="ok">✓ <?php echo htmlspecialchars($s); ?></div>
<?php endforeach; ?>
<?php foreach ($errors as $e): ?>
    <div class="fail">✗ <?php echo htmlspecialchars($e); ?></div>
<?php endforeach; ?>

<?php if (empty($errors)): ?>
<div class="info">
    <strong>Setup concluído com sucesso!</strong><br><br>
    Próximos passos:<br>
    1. <strong>Apague ou renomeie este arquivo</strong> (setup/install.php)<br>
    2. Acesse o <a href="/admin/" style="color:#86efac">painel admin</a> (usuário: <code>distinto</code> / senha: <code>distinto@2025</code>)<br>
    3. Troque a senha no arquivo <code>admin/login.php</code><br>
    4. Cadastre seus projetos e configure os destaques
</div>
<?php else: ?>
<div class="warn">
    Alguns erros ocorreram. Verifique a conexão com o banco e tente novamente.
</div>
<?php endif; ?>

<div class="warn" style="margin-top:16px">
    ⚠️ <strong>ATENÇÃO:</strong> Apague este arquivo após o setup para evitar que seja executado novamente!
</div>
</body>
</html>
