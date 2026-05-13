<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    header('Location: ' . url('admin/login.php'));
    exit;
}
require_once dirname(__DIR__) . '/includes/db.php';

$saved = false;
$tab = $_GET['tab'] ?? 'settings';

// ── SAVE SETTINGS ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'settings') {
    $fields = [
        'hero_headline', 'hero_serif', 'hero_video_url',
        'service_capturar_sub', 'service_capturar_img', 'service_capturar_vid',
        'service_narrar_sub',   'service_narrar_img',   'service_narrar_vid',
        'service_construir_sub','service_construir_img','service_construir_vid',
        'cta_title', 'cta_subtitle',
        'slot_1', 'slot_2', 'slot_3', 'slot_4',
    ];
    foreach ($fields as $f) {
        set_setting($f, trim($_POST[$f] ?? ''));
    }
    $saved = true;
}

// ── SAVE / DELETE PROJECT ───────────────────────────────────────────────────
$proj_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'projects') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete' && !empty($_POST['project_id'])) {
        $pid = (int)$_POST['project_id'];
        db()->prepare('DELETE FROM project_gallery WHERE project_id = :id')->execute([':id' => $pid]);
        db()->prepare('DELETE FROM project_category_relations WHERE project_id = :id')->execute([':id' => $pid]);
        db()->prepare('DELETE FROM projects WHERE id = :id')->execute([':id' => $pid]);
        $proj_msg = 'Projeto removido.';
    }

    if (in_array($action, ['create', 'update'])) {
        $pid           = (int)($_POST['project_id'] ?? 0);
        $title         = trim($_POST['title'] ?? '');
        $thumbnail_url = trim($_POST['thumbnail_url'] ?? '');
        $media_type    = $_POST['media_type'] ?? 'image';
        $video_url     = trim($_POST['video_url'] ?? '');
        $overlay_title = trim($_POST['overlay_title'] ?? '');
        $storytelling  = trim($_POST['storytelling'] ?? '');
        $impact_title  = trim($_POST['impact_title'] ?? '');
        $narrative_text= trim($_POST['narrative_text'] ?? '');
        $story_quote   = trim($_POST['storytelling_quote'] ?? '');
        $menu_order    = (int)($_POST['menu_order'] ?? 0);
        $category_ids  = $_POST['categories'] ?? [];

        if ($action === 'create') {
            $stmt = db()->prepare(
                'INSERT INTO projects (title, thumbnail_url, media_type, video_url, overlay_title, storytelling, impact_title, narrative_text, storytelling_quote, menu_order, status)
                 VALUES (:t,:thu,:mt,:vu,:ot,:st,:it,:nt,:sq,:mo,\'published\') RETURNING id'
            );
        } else {
            $stmt = db()->prepare(
                'UPDATE projects SET title=:t, thumbnail_url=:thu, media_type=:mt, video_url=:vu,
                 overlay_title=:ot, storytelling=:st, impact_title=:it, narrative_text=:nt,
                 storytelling_quote=:sq, menu_order=:mo WHERE id=:pid RETURNING id'
            );
        }
        $params = [':t'=>$title,':thu'=>$thumbnail_url,':mt'=>$media_type,':vu'=>$video_url,
                   ':ot'=>$overlay_title,':st'=>$storytelling,':it'=>$impact_title,
                   ':nt'=>$narrative_text,':sq'=>$story_quote,':mo'=>$menu_order];
        if ($action === 'update') $params[':pid'] = $pid;
        $stmt->execute($params);
        $pid = $stmt->fetchColumn() ?: $pid;

        // Categorias
        db()->prepare('DELETE FROM project_category_relations WHERE project_id = :id')->execute([':id' => $pid]);
        foreach ($category_ids as $cid) {
            $cid = (int)$cid;
            if ($cid > 0) {
                db()->prepare('INSERT INTO project_category_relations (project_id, category_id) VALUES (:p,:c) ON CONFLICT DO NOTHING')
                    ->execute([':p' => $pid, ':c' => $cid]);
            }
        }

        // Galeria (URLs separadas por linha)
        $gallery_urls = array_filter(array_map('trim', explode("\n", $_POST['gallery_urls'] ?? '')));
        db()->prepare('DELETE FROM project_gallery WHERE project_id = :id')->execute([':id' => $pid]);
        foreach ($gallery_urls as $idx => $url) {
            db()->prepare('INSERT INTO project_gallery (project_id, image_url, sort_order) VALUES (:p,:u,:s)')
                ->execute([':p' => $pid, ':u' => $url, ':s' => $idx]);
        }

        $proj_msg = $action === 'create' ? 'Projeto criado com sucesso.' : 'Projeto atualizado.';
    }
}

// ── SAVE MESSAGE DELETE ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'messages' && !empty($_POST['delete_msg'])) {
    db()->prepare('DELETE FROM contact_messages WHERE id = :id')->execute([':id' => (int)$_POST['delete_msg']]);
}

// ── LOAD DATA ────────────────────────────────────────────────────────────────
$opts       = get_settings_all();
$all_projects = [];
$categories = [];
$messages   = [];
try {
    $stmt = db()->query('SELECT p.*, STRING_AGG(r.category_id::text, \',\') AS cat_ids, STRING_AGG(pc.slug,\' \') AS cat_slugs
                         FROM projects p
                         LEFT JOIN project_category_relations r ON p.id = r.project_id
                         LEFT JOIN project_categories pc ON r.category_id = pc.id
                         GROUP BY p.id ORDER BY p.menu_order ASC, p.id ASC');
    $all_projects = $stmt->fetchAll();

    $categories = db()->query('SELECT * FROM project_categories ORDER BY name ASC')->fetchAll();
    $messages   = db()->query('SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 50')->fetchAll();
} catch (PDOException) {}

// Projeto em edição
$editing = null;
$edit_gallery = [];
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $editing = get_project($eid);
    if ($editing) $edit_gallery = get_project_gallery($eid);
}

function opt(array $opts, string $k, string $d = ''): string {
    return htmlspecialchars($opts[$k] ?? $d, ENT_QUOTES, 'UTF-8');
}

$s = fn(string $k, string $d = '') => opt($opts, $k, $d);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Distinto Studio</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700;900&family=Inter:wght@400;700&display=swap">
<style>
body { background:#0e0e0e; color:#e2e2e2; font-family:'Inter',sans-serif; }
.tab-active { border-bottom: 2px solid white; color:white; }
input,textarea,select { background:#1f1f1f!important; border:1px solid #333!important; color:#e2e2e2!important; border-radius:4px; padding:8px 12px; width:100%; outline:none; }
input:focus,textarea:focus,select:focus { border-color:#666!important; }
label { font-size:11px; text-transform:uppercase; letter-spacing:.1em; color:#888; display:block; margin-bottom:6px; }
.section-title { font-family:'Space Grotesk'; font-weight:900; font-size:11px; text-transform:uppercase; letter-spacing:.2em; color:#555; margin-bottom:16px; margin-top:32px; padding-top:16px; border-top:1px solid #222; }
.btn-primary { background:white; color:black; font-family:'Space Grotesk'; font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:.15em; padding:10px 24px; border:none; cursor:pointer; }
.btn-primary:hover { background:#ddd; }
.btn-danger { background:#7f1d1d; color:#fca5a5; font-size:11px; padding:6px 14px; border:none; cursor:pointer; font-family:'Space Grotesk'; font-weight:700; letter-spacing:.1em; }
.btn-secondary { background:#1f1f1f; color:#e2e2e2; border:1px solid #333; font-size:11px; padding:8px 16px; cursor:pointer; font-family:'Space Grotesk'; font-weight:700; letter-spacing:.1em; }
.btn-secondary:hover { border-color:#666; }
table { width:100%; border-collapse:collapse; font-size:13px; }
th { text-align:left; padding:10px 12px; font-size:10px; text-transform:uppercase; letter-spacing:.1em; color:#555; border-bottom:1px solid #222; }
td { padding:10px 12px; border-bottom:1px solid #1a1a1a; vertical-align:top; }
tr:hover td { background:#161616; }
.alert-success { background:#14532d44; border:1px solid #16a34a44; color:#86efac; padding:12px 16px; font-size:12px; margin-bottom:20px; border-radius:4px; }
</style>
</head>
<body>

<!-- Top bar -->
<div style="background:#131313;border-bottom:1px solid #222" class="flex items-center justify-between px-8 py-4">
    <div class="flex items-center gap-6">
        <img src="/assets/imgs/distinto_footes.png" alt="Distinto" style="height:24px;opacity:.7">
        <span style="font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.2em">ADMIN</span>
    </div>
    <div class="flex items-center gap-4">
        <a href="<?php echo url(); ?>" target="_blank" style="font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.1em;text-decoration:none">Ver site ↗</a>
        <a href="<?php echo url('admin/logout.php'); ?>" style="font-size:10px;color:#555;text-transform:uppercase;letter-spacing:.1em;text-decoration:none">Sair</a>
    </div>
</div>

<!-- Tabs -->
<div style="background:#131313;border-bottom:1px solid #222" class="flex gap-8 px-8">
    <?php foreach (['settings' => 'Configurações', 'projects' => 'Projetos', 'messages' => 'Mensagens'] as $t => $label): ?>
    <a href="<?php echo url('admin/'); ?>?tab=<?php echo $t; ?>" style="font-size:10px;text-transform:uppercase;letter-spacing:.15em;padding:14px 0;text-decoration:none;color:<?php echo $tab===$t?'white':'#555'; ?>"
       class="<?php echo $tab===$t?'tab-active':''; ?>"><?php echo $label; ?></a>
    <?php endforeach; ?>
</div>

<div class="px-8 py-8 max-w-5xl mx-auto">

<!-- ──────────── SETTINGS ──────────── -->
<?php if ($tab === 'settings'): ?>
    <?php if ($saved): ?><div class="alert-success">✓ Configurações salvas com sucesso!</div><?php endif; ?>

    <form method="POST" action="?tab=settings">
        <div class="section-title">Seção Hero</div>
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div><label>Frase Principal</label><input name="hero_headline" value="<?php echo $s('hero_headline','NARRATIVAS VISUAIS QUE CONECTAM'); ?>"></div>
            <div><label>Complemento Serif</label><input name="hero_serif" value="<?php echo $s('hero_serif','estratégia, estética e emoção'); ?>"></div>
        </div>
        <div class="mb-6"><label>URL do Vídeo Hero (Vimeo)</label><input name="hero_video_url" value="<?php echo $s('hero_video_url'); ?>"></div>

        <div class="section-title">Destaques da Home (Slots 1-4)</div>
        <div class="grid grid-cols-4 gap-4 mb-6">
            <?php for ($i = 1; $i <= 4; $i++): ?>
            <div>
                <label>Slot <?php echo $i; ?></label>
                <select name="slot_<?php echo $i; ?>">
                    <option value="">— Nenhum —</option>
                    <?php foreach ($all_projects as $pj): ?>
                        <option value="<?php echo $pj['id']; ?>" <?php echo ($opts["slot_{$i}"] ?? '') == $pj['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($pj['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endfor; ?>
        </div>

        <?php foreach ([
            ['key' => 'capturar',  'label' => 'CAPTURAR'],
            ['key' => 'narrar',    'label' => 'NARRAR'],
            ['key' => 'construir', 'label' => 'CONSTRUIR'],
        ] as $srv): ?>
        <div class="section-title">Serviço — <?php echo $srv['label']; ?></div>
        <div class="grid grid-cols-1 gap-4 mb-4">
            <div><label>Subtexto</label><input name="service_<?php echo $srv['key']; ?>_sub" value="<?php echo $s('service_'.$srv['key'].'_sub'); ?>"></div>
            <div><label>URL Imagem</label><input name="service_<?php echo $srv['key']; ?>_img" value="<?php echo $s('service_'.$srv['key'].'_img'); ?>"></div>
            <div><label>URL Vídeo (hover)</label><input name="service_<?php echo $srv['key']; ?>_vid" value="<?php echo $s('service_'.$srv['key'].'_vid'); ?>"></div>
        </div>
        <?php endforeach; ?>

        <div class="section-title">CTA Final</div>
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div><label>Título</label><input name="cta_title" value="<?php echo $s('cta_title','Para marcas que querem ser percebidas.'); ?>"></div>
            <div><label>Subtítulo</label><input name="cta_subtitle" value="<?php echo $s('cta_subtitle','Para histórias que merecem ser eternas.'); ?>"></div>
        </div>

        <div class="mt-8"><button type="submit" class="btn-primary">SALVAR CONFIGURAÇÕES</button></div>
    </form>

<!-- ──────────── PROJECTS ──────────── -->
<?php elseif ($tab === 'projects'): ?>
    <?php if ($proj_msg): ?><div class="alert-success">✓ <?php echo htmlspecialchars($proj_msg); ?></div><?php endif; ?>

    <!-- Form novo / edição -->
    <div style="background:#131313;border:1px solid #222;padding:24px;margin-bottom:40px">
        <h2 style="font-family:'Space Grotesk';font-weight:900;font-size:14px;text-transform:uppercase;letter-spacing:.15em;margin-bottom:24px">
            <?php echo $editing ? 'Editar: '.htmlspecialchars($editing['title']) : 'Novo Projeto'; ?>
        </h2>

        <form method="POST" action="?tab=projects">
            <input type="hidden" name="action" value="<?php echo $editing ? 'update' : 'create'; ?>">
            <?php if ($editing): ?><input type="hidden" name="project_id" value="<?php echo $editing['id']; ?>"><?php endif; ?>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div><label>Título do Projeto</label><input name="title" value="<?php echo htmlspecialchars($editing['title'] ?? ''); ?>" required></div>
                <div><label>Ordem no Menu</label><input name="menu_order" type="number" value="<?php echo $editing['menu_order'] ?? 0; ?>"></div>
            </div>
            <div class="mb-6"><label>URL da Thumbnail</label><input name="thumbnail_url" value="<?php echo htmlspecialchars($editing['thumbnail_url'] ?? ''); ?>"></div>
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label>Tipo de Mídia</label>
                    <select name="media_type" id="media_type_sel">
                        <option value="image" <?php echo ($editing['media_type'] ?? '') === 'image' ? 'selected' : ''; ?>>Imagem</option>
                        <option value="video" <?php echo ($editing['media_type'] ?? '') === 'video' ? 'selected' : ''; ?>>Vídeo</option>
                    </select>
                </div>
                <div id="video_url_row" style="<?php echo ($editing['media_type'] ?? 'image') !== 'video' ? 'opacity:.3' : ''; ?>">
                    <label>URL do Vídeo</label><input name="video_url" value="<?php echo htmlspecialchars($editing['video_url'] ?? ''); ?>">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div><label>Overlay Title</label><input name="overlay_title" value="<?php echo htmlspecialchars($editing['overlay_title'] ?? ''); ?>"></div>
                <div><label>Storytelling (sub)</label><input name="storytelling" value="<?php echo htmlspecialchars($editing['storytelling'] ?? ''); ?>"></div>
            </div>

            <div style="margin-bottom:16px">
                <label>Categorias</label>
                <div class="flex gap-4 mt-2 flex-wrap">
                    <?php
                    $editing_cat_ids = array_map('intval', explode(',', $editing['cat_ids'] ?? ''));
                    foreach ($categories as $cat):
                    ?>
                    <label style="font-size:13px;text-transform:none;letter-spacing:0;color:#e2e2e2;cursor:pointer;display:flex;align-items:center;gap:6px">
                        <input type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>"
                               <?php echo in_array($cat['id'], $editing_cat_ids) ? 'checked' : ''; ?>
                               style="width:auto;border:1px solid #555">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section-title">Narrativa de Casamento (opcional)</div>
            <div class="grid grid-cols-2 gap-6 mb-4">
                <div><label>Título de Impacto</label><input name="impact_title" value="<?php echo htmlspecialchars($editing['impact_title'] ?? ''); ?>"></div>
                <div><label>Texto de Narrativa</label><input name="narrative_text" value="<?php echo htmlspecialchars($editing['narrative_text'] ?? ''); ?>"></div>
            </div>
            <div class="mb-6"><label>Citação (Quote)</label><textarea name="storytelling_quote" rows="2"><?php echo htmlspecialchars($editing['storytelling_quote'] ?? ''); ?></textarea></div>

            <div class="section-title">Galeria (URLs das fotos, uma por linha)</div>
            <div class="mb-6">
                <?php
                $gallery_text = implode("\n", array_column($edit_gallery, 'image_url'));
                ?>
                <textarea name="gallery_urls" rows="6" placeholder="https://exemplo.com/foto1.jpg&#10;https://exemplo.com/foto2.jpg"><?php echo htmlspecialchars($gallery_text); ?></textarea>
                <p style="font-size:10px;color:#555;margin-top:6px">Para casamentos: as 5 primeiras imagens compõem o mosaico.</p>
            </div>

            <div class="flex gap-4 mt-8">
                <button type="submit" class="btn-primary"><?php echo $editing ? 'ATUALIZAR PROJETO' : 'CRIAR PROJETO'; ?></button>
                <?php if ($editing): ?>
                <a href="<?php echo url('admin/'); ?>?tab=projects" class="btn-secondary" style="text-decoration:none;display:inline-block">CANCELAR</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <script>
    document.getElementById('media_type_sel')?.addEventListener('change', function() {
        document.getElementById('video_url_row').style.opacity = this.value === 'video' ? '1' : '0.3';
    });
    </script>

    <!-- Lista de projetos -->
    <table>
        <thead>
            <tr><th>Ord.</th><th>Título</th><th>Categorias</th><th>Mídia</th><th>Ações</th></tr>
        </thead>
        <tbody>
        <?php foreach ($all_projects as $pj): ?>
        <tr>
            <td style="color:#555"><?php echo $pj['menu_order']; ?></td>
            <td><?php echo htmlspecialchars($pj['title']); ?></td>
            <td><span style="font-size:10px;color:#777"><?php echo htmlspecialchars($pj['cat_slugs'] ?? '—'); ?></span></td>
            <td><span style="font-size:10px;color:#777"><?php echo $pj['media_type']; ?></span></td>
            <td>
                <div class="flex gap-2">
                    <a href="<?php echo url('admin/'); ?>?tab=projects&edit=<?php echo $pj['id']; ?>" class="btn-secondary" style="text-decoration:none">Editar</a>
                    <form method="POST" action="<?php echo url('admin/'); ?>?tab=projects" style="display:inline" onsubmit="return confirm('Remover este projeto?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="project_id" value="<?php echo $pj['id']; ?>">
                        <button type="submit" class="btn-danger">Remover</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($all_projects)): ?>
        <tr><td colspan="5" style="text-align:center;color:#555;padding:32px">Nenhum projeto cadastrado ainda.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

<!-- ──────────── MESSAGES ──────────── -->
<?php elseif ($tab === 'messages'): ?>
    <table>
        <thead>
            <tr><th>Data</th><th>Nome</th><th>E-mail</th><th>Tipo</th><th>Mensagem</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($messages as $msg): ?>
        <tr>
            <td style="color:#555;white-space:nowrap;font-size:11px"><?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?></td>
            <td><?php echo htmlspecialchars($msg['name']); ?></td>
            <td><a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" style="color:#aaa"><?php echo htmlspecialchars($msg['email']); ?></a></td>
            <td style="font-size:11px;color:#777"><?php echo htmlspecialchars($msg['project_type'] ?? '—'); ?></td>
            <td style="max-width:300px;font-size:12px;color:#aaa"><?php echo htmlspecialchars(substr($msg['message'] ?? '', 0, 120)); ?></td>
            <td>
                <form method="POST" action="<?php echo url('admin/'); ?>?tab=messages" onsubmit="return confirm('Excluir mensagem?')">
                    <input type="hidden" name="delete_msg" value="<?php echo $msg['id']; ?>">
                    <button type="submit" class="btn-danger">Excluir</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($messages)): ?>
        <tr><td colspan="6" style="text-align:center;color:#555;padding:32px">Nenhuma mensagem ainda.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>

</div>
</body>
</html>
