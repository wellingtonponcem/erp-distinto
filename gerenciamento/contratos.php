<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirDistinto();
$db = Database::get();

$statusMessage = '';
$errorMessage = '';

if (isset($_GET['deletar'])) {
    $id = $_GET['deletar'];
    try {
        $stmt = $db->prepare("DELETE FROM contratos WHERE id = ?");
        $stmt->execute([$id]);
        $statusMessage = 'Contrato excluído com sucesso.';
    } catch (Exception $e) {
        $errorMessage = 'Erro ao excluir contrato: ' . $e->getMessage();
    }
}

// Fetch all contracts with their proposals
$contratos = $db->query("
    SELECT c.*, p.slug as proposta_slug
    FROM contratos c
    LEFT JOIN propostas p ON c.proposta_id = p.id
    ORDER BY c.created_at DESC
")->fetchAll();

$tituloPagina = 'Contratos Comerciais';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content" class="content-sheet flex flex-col min-h-screen">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-zinc-900 dark:text-white flex items-center gap-3">
                    <i data-lucide="scroll" class="w-8 h-8 text-zinc-400"></i>
                    Contratos
                </h1>
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mt-1">Gerencie, envie para assinatura eletrônica e acompanhe o status de formalização das propostas.</p>
            </div>
        </div>

        <?php if ($statusMessage): ?>
            <div class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-sm font-bold flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <?= sanitizar($statusMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/20 text-red-600 dark:text-red-400 text-sm font-bold flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
                <?= sanitizar($errorMessage) ?>
            </div>
        <?php endif; ?>

        <!-- Lista de Contratos -->
        <div class="space-y-4">
            <div class="flex items-center justify-between ml-4 mb-4">
                <h2 class="text-[11px] font-black uppercase tracking-[0.2em] text-zinc-500">Histórico de Contratos (<?= count($contratos) ?>)</h2>
                <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>" class="text-xs font-bold text-zinc-600 dark:text-zinc-400 hover:text-zinc-950 dark:hover:text-white transition-colors flex items-center gap-1">
                    <i data-lucide="plus" class="w-3.5 h-3.5"></i> Gerar a partir de Proposta
                </a>
            </div>
            
            <div class="grid grid-cols-1 gap-3">
                <?php foreach ($contratos as $contrato): ?>
                    <?php
                    $status = $contrato['status'] ?? 'rascunho';
                    $statusLabel = 'Rascunho';
                    $statusClass = 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400';
                    if ($status === 'pendente') {
                        $statusLabel = 'Pendente Assinatura';
                        $statusClass = 'bg-blue-500/10 text-blue-500';
                    } elseif ($status === 'assinado') {
                        $statusLabel = 'Assinado';
                        $statusClass = 'bg-emerald-500/10 text-emerald-500';
                    } elseif ($status === 'cancelado') {
                        $statusLabel = 'Cancelado';
                        $statusClass = 'bg-red-500/10 text-red-500';
                    }
                    ?>
                    <div class="bg-white/50 dark:bg-zinc-900/30 border border-zinc-200/60 dark:border-zinc-800/60 rounded-3xl p-5 flex flex-wrap items-center justify-between gap-4 group hover:border-zinc-400 dark:hover:border-zinc-600 transition-all">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 rounded-2xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors">
                                <i data-lucide="scroll" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-bold text-zinc-900 dark:text-white"><?= sanitizar($contrato['cliente_nome']) ?></h3>
                                    <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider <?= $statusClass ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </div>
                                <p class="text-[11px] font-medium text-zinc-500 uppercase tracking-widest"><?= sanitizar($contrato['titulo']) ?></p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-8 items-center">
                            <div>
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Valor Total</p>
                                <p class="text-xs font-bold text-zinc-600 dark:text-zinc-400 mt-0.5">R$ <?= number_format($contrato['valor_total'], 2, ',', '.') ?></p>
                            </div>
                            
                            <div class="hidden sm:block">
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Data do Contrato</p>
                                <p class="text-xs font-bold text-zinc-600 dark:text-zinc-400 mt-0.5">
                                    <?= $contrato['data_contrato'] ? date('d/m/Y', strtotime($contrato['data_contrato'])) : '—' ?>
                                </p>
                            </div>

                            <?php if ($contrato['link_assinatura']): ?>
                                <div class="hidden md:block">
                                    <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Assinatura Eletrônica</p>
                                    <a href="<?= sanitizar($contrato['link_assinatura']) ?>" target="_blank" class="text-xs font-bold text-blue-500 hover:underline flex items-center gap-1 mt-0.5">
                                        <i data-lucide="signature" class="w-3.5 h-3.5"></i> Link Assinafy
                                    </a>
                                </div>
                            <?php endif; ?>

                            <div class="flex items-center gap-2 border-l border-zinc-100 dark:border-zinc-800/50 pl-6">
                                <a href="<?= raizUrl('/gerenciamento/contrato_visualizar.php?id=' . $contrato['id']) ?>" 
                                   class="p-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-900 dark:hover:bg-white hover:text-white dark:hover:text-black transition-all"
                                   title="Visualizar PDF / Enviar">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                
                                <?php if ($status === 'rascunho'): ?>
                                    <a href="<?= raizUrl('/gerenciamento/contrato_gerar.php?id=' . $contrato['id']) ?>" 
                                       class="p-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-900 dark:hover:bg-white hover:text-white dark:hover:text-black transition-all"
                                       title="Editar Conteúdo">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?= raizUrl('/gerenciamento/contratos.php?deletar=' . $contrato['id']) ?>" 
                                   onclick="return confirm('Tem certeza que deseja excluir este contrato? Essa ação não pode ser desfeita.');"
                                   class="p-2.5 rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all"
                                   title="Excluir Contrato">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($contratos)): ?>
                    <div class="py-20 text-center bg-white/50 dark:bg-zinc-900/10 rounded-[32px] border border-dashed border-zinc-200 dark:border-zinc-800">
                        <i data-lucide="scroll" class="w-12 h-12 mx-auto text-zinc-800 mb-4 opacity-20"></i>
                        <p class="text-sm font-bold text-zinc-500 mb-2">Nenhum contrato gerado ainda.</p>
                        <p class="text-xs text-zinc-400 max-w-sm mx-auto">Vá para a seção de <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>" class="text-zinc-600 dark:text-zinc-300 font-bold hover:underline">Propostas Web</a> e clique com o botão direito em uma proposta para gerar seu contrato.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
