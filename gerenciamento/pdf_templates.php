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

<div id="app-wrapper" class="flex min-h-screen">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet !bg-background !p-6 flex flex-col flex-1">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <!-- Topbar -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-8 mt-2">
            <div>
                <h1 class="font-display-lg text-on-surface mb-1">Templates PDF</h1>
                <p class="text-body-md text-on-surface-variant">Modelos de propostas e contratos PDF customizados com a marca da agência.</p>
            </div>
            <a href="<?= raizUrl('/gerenciamento/pdf_template_editor.php') ?>" class="bg-primary hover:bg-primary-container text-on-primary-container font-bold px-6 py-2.5 rounded-lg text-body-md transition-all active:scale-95 duration-150 shadow-lg flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i> Novo Template
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-card-gap">
            <?php foreach ($templates as $tpl): ?>
                <a href="<?= raizUrl('/gerenciamento/pdf_template_editor.php?id=' . urlencode($tpl['id'])) ?>" class="glass-card p-6 rounded-xl flex flex-col justify-between h-40 relative overflow-hidden group hover:border-primary/40 transition-all border border-outline-variant/20 shadow-sm cursor-pointer">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/5 rounded-full blur-2xl group-hover:bg-primary/10 transition-colors"></div>
                    <div>
                        <div class="flex justify-between items-center">
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-label-caps border bg-primary/10 text-primary border-primary/20 inline-block w-fit text-center" x-text="'<?= strtolower(sanitizar($tpl['tipo'])) ?>'"></span>
                            <?php if (!empty($tpl['ativo'])): ?>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-label-caps border bg-emerald-500/10 text-emerald-400 border-emerald-500/20 inline-block w-fit text-center">Ativo</span>
                            <?php endif; ?>
                        </div>
                        <h2 class="text-sm font-bold text-on-surface group-hover:text-primary transition-colors mt-4"><?= sanitizar($tpl['nome']) ?></h2>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs text-on-surface-variant mt-3">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5 text-on-surface-variant"></i>
                        <span>Editar páginas e estilos</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
