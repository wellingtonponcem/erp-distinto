<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pdf_templates.php';

exigirAdmin();

$tituloPagina = 'Templates PDF';
$db = Database::get();
garantirTabelasPdfTemplates($db);

$templates = $db->query("SELECT * FROM pdf_templates ORDER BY tipo ASC, atualizado_em DESC")->fetchAll();

include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet">
        <div class="app-topbar">
            <div class="top-nav">
                <a href="<?= raizUrl('/dashboard.php') ?>">Visao Geral</a>
                <a href="#" class="active">Templates PDF</a>
            </div>
        </div>

        <div class="mb-8 flex items-center justify-between gap-4">
            <div>
                <h1 class="page-title text-2xl">Templates PDF</h1>
                <p class="page-subtitle text-zinc-500">Modelos usados automaticamente ao exportar propostas.</p>
            </div>
            <a href="<?= raizUrl('/gerenciamento/pdf_template_editor.php') ?>" class="btn btn-primary inline-flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Novo Template
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <?php foreach ($templates as $tpl): ?>
                <a href="<?= raizUrl('/gerenciamento/pdf_template_editor.php?id=' . urlencode($tpl['id'])) ?>" class="card p-5 hover:border-zinc-400 transition-all">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-[10px] uppercase tracking-widest font-black text-zinc-500"><?= sanitizar($tpl['tipo']) ?></span>
                        <?php if (!empty($tpl['ativo'])): ?>
                            <span class="text-[10px] uppercase tracking-widest font-black text-emerald-500">Ativo</span>
                        <?php endif; ?>
                    </div>
                    <h2 class="text-base font-black text-zinc-900 dark:text-white"><?= sanitizar($tpl['nome']) ?></h2>
                    <p class="text-xs text-zinc-500 mt-2">Editar paginas, campos e estilos.</p>
                </a>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
