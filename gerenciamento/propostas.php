<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();

$tituloPagina = 'Propostas';
$db = Database::get();

// Buscar propostas
$stmt = $db->query("SELECT * FROM propostas ORDER BY criada_em DESC");
$propostas = $stmt->fetchAll();

include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet">
        <div class="app-topbar">
            <div class="top-nav">
                <a href="<?= raizUrl('/dashboard.php') ?>">Visão Geral</a>
                <a href="#" class="active">Propostas</a>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5 mb-8">
            <div>
                <h1 class="page-title">Propostas Web</h1>
                <p class="page-subtitle">Gerencie e acompanhe o status das propostas enviadas aos clientes.</p>
            </div>
            <a href="<?= raizUrl('/gerenciamento/proposta_nova.php') ?>" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nova Proposta
            </a>
        </div>

        <section class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-zinc-50 border-b border-zinc-100">
                            <th class="px-6 py-4 text-[11px] font-bold text-zinc-500 uppercase tracking-wider">Cliente / Título</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-zinc-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-zinc-500 uppercase tracking-wider">Valor</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-zinc-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-zinc-500 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-zinc-500 uppercase tracking-wider text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        <?php if (empty($propostas)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-zinc-400">
                                    <i data-lucide="file-search" class="w-10 h-10 mx-auto mb-3 opacity-20"></i>
                                    <p class="font-bold">Nenhuma proposta gerada ainda.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($propostas as $p): ?>
                                <tr class="hover:bg-zinc-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-zinc-900"><?= sanitizar($p['cliente_nome']) ?></div>
                                        <div class="text-[11px] text-zinc-500"><?= sanitizar($p['titulo']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded bg-zinc-100 text-zinc-600">
                                            <?= $p['tipo'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-zinc-900">
                                        <?= formatarMoeda($p['valor_total']) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $statusClass = [
                                            'rascunho' => 'bg-amber-100 text-amber-700',
                                            'aceita' => 'bg-emerald-100 text-emerald-700',
                                            'recusada' => 'bg-red-100 text-red-700'
                                        ];
                                        ?>
                                        <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-full <?= $statusClass[$p['status']] ?? 'bg-zinc-100' ?>">
                                            <?= $p['status'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-zinc-500">
                                        <?= date('d/m/Y', strtotime($p['criada_em'])) ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="<?= APP_URL ?>/p/<?= $p['slug'] ?>" target="_blank" class="p-2 hover:bg-zinc-200 rounded-lg transition-colors" title="Ver Proposta">
                                                <i data-lucide="external-link" class="w-4 h-4 text-zinc-600"></i>
                                            </a>
                                            <button onclick="copiarLink('<?= $p['slug'] ?>')" class="p-2 hover:bg-zinc-200 rounded-lg transition-colors" title="Copiar Link">
                                                <i data-lucide="copy" class="w-4 h-4 text-zinc-600"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
function copiarLink(slug) {
    const link = `https://wedistinto.com/p/${slug}`;
    navigator.clipboard.writeText(link).then(() => {
        alert('Link copiado com sucesso!');
    });
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
