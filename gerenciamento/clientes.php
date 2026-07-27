<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();
$db = Database::get();

$statusMessage = '';
$errorMessage = '';
$editCliente = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = sanitizar($_POST['nome'] ?? '');
    $cpf_cnpj = sanitizar($_POST['cpf_cnpj'] ?? '');
    $contato = sanitizar($_POST['contato'] ?? '');
    $segmento = sanitizar($_POST['segmento'] ?? '');

    if (!$nome) {
        $errorMessage = 'O nome do cliente é obrigatório.';
    } else {
        if ($id) {
            $stmt = $db->prepare("UPDATE clientes SET nome = ?, cpf_cnpj = ?, contato = ?, segmento = ? WHERE id = ?");
            $stmt->execute([$nome, $cpf_cnpj, $contato, $segmento, $id]);
            $statusMessage = 'Cliente atualizado com sucesso.';
        } else {
            $stmt = $db->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, contato, segmento) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([gerarId(), $nome, $cpf_cnpj, $contato, $segmento]);
            $statusMessage = 'Cliente cadastrado com sucesso.';
        }
    }
}

if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $editCliente = $stmt->fetch();
}

if (isset($_GET['deletar'])) {
    $stmt = $db->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$_GET['deletar']]);
    $statusMessage = 'Cliente excluído com sucesso.';
}

$clientes = $db->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM oportunidades o WHERE o.cliente_id = c.id) as total_oportunidades,
           (SELECT COUNT(*) FROM propostas p WHERE p.cliente_id = c.id) as total_propostas
    FROM clientes c 
    ORDER BY c.nome ASC
")->fetchAll();
$tituloPagina = 'CRM • Clientes';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper" class="flex min-h-screen">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content" class="content-sheet !bg-background !p-6 flex flex-col flex-1">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <!-- Topbar -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-8 mt-2">
            <div>
                <h1 class="font-display-lg text-on-surface mb-1 flex items-center gap-2">
                    <i data-lucide="users" class="w-6 h-6 text-primary"></i>
                    Clientes
                </h1>
                <p class="text-body-md text-on-surface-variant">Gerencie a base de contatos que alimenta seu pipeline comercial</p>
            </div>
        </div>

        <?php if ($statusMessage): ?>
            <div class="bg-primary/10 border border-primary/20 rounded-xl p-4 mb-6 text-sm text-primary flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <span><?= sanitizar($statusMessage) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="bg-error/10 border border-error/20 rounded-xl p-4 mb-6 text-sm text-error flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                <span><?= sanitizar($errorMessage) ?></span>
            </div>
        <?php endif; ?>

        <!-- Formulário de Cadastro -->
        <div class="glass-card p-6 rounded-xl border border-outline-variant/20 mb-8 shadow-sm">
            <h2 class="text-title-sm font-headline-md font-bold text-on-surface mb-6 flex items-center gap-2">
                <i data-lucide="<?= $editCliente ? 'edit-3' : 'plus-circle' ?>" class="w-5 h-5 text-on-surface-variant"></i>
                <?= $editCliente ? 'Editar Cliente' : 'Novo Cliente' ?>
            </h2>
            
            <form method="post" action="<?= raizUrl('/gerenciamento/clientes.php') ?>">
                <input type="hidden" name="id" value="<?= $editCliente['id'] ?? '' ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="label">Nome Completo / Razão Social *</label>
                        <input class="input w-full" type="text" name="nome" value="<?= sanitizar($editCliente['nome'] ?? '') ?>" required placeholder="Ex: João Silva ou Empresa LTDA">
                    </div>
                    
                    <div>
                        <label class="label">CPF / CNPJ</label>
                        <input class="input w-full" type="text" name="cpf_cnpj" value="<?= sanitizar($editCliente['cpf_cnpj'] ?? '') ?>" placeholder="000.000.000-00">
                    </div>
                    
                    <div>
                        <label class="label">Contato (WhatsApp/E-mail)</label>
                        <input class="input w-full" type="text" name="contato" value="<?= sanitizar($editCliente['contato'] ?? '') ?>" placeholder="Ex: (27) 99999-0000">
                    </div>
                    
                    <div>
                        <label class="label">Segmento / Atividade</label>
                        <input class="input w-full" type="text" name="segmento" value="<?= sanitizar($editCliente['segmento'] ?? '') ?>" placeholder="Ex: Tecnologia, Varejo, Saúde...">
                    </div>
                </div>

                <div class="flex justify-end items-center gap-3 pt-4 border-t border-outline-variant/10">
                    <?php if ($editCliente): ?>
                        <a href="<?= raizUrl('/gerenciamento/clientes.php') ?>" class="btn-secondary">Cancelar</a>
                    <?php endif; ?>
                    <button type="submit" class="bg-primary hover:bg-primary-container text-on-primary-container font-bold px-6 py-2.5 rounded-lg text-body-md transition-all active:scale-95 duration-150 shadow-lg">
                        <?= $editCliente ? 'Salvar Alterações' : 'Cadastrar Cliente' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Clientes -->
        <div class="space-y-4">
            <h2 class="text-[10px] font-label-caps tracking-wider text-on-surface-variant ml-2 mb-4">Base de Clientes (<?= count($clientes) ?>)</h2>
            
            <div class="grid grid-cols-1 gap-3">
                <?php foreach ($clientes as $cliente): ?>
                    <div class="glass-card p-5 flex flex-wrap items-center justify-between gap-4 group hover:border-primary/40 transition-colors rounded-xl border border-outline-variant/20">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-surface-container flex items-center justify-center text-on-surface-variant group-hover:text-primary transition-colors">
                                <i data-lucide="user" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-on-surface"><?= sanitizar($cliente['nome']) ?></h3>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-label-caps border bg-primary/10 text-primary border-primary/20 inline-block w-fit text-center mt-1" x-text="'<?= strtolower(sanitizar($cliente['segmento'] ?: 'Sem segmento')) ?>'"></span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-6 items-center">
                            <div class="hidden sm:block">
                                <p class="text-[9px] font-label-caps text-on-surface-variant">Documento</p>
                                <p class="text-xs font-bold text-on-surface mt-0.5 font-data-tabular"><?= sanitizar($cliente['cpf_cnpj'] ?: '—') ?></p>
                            </div>
                            <div class="hidden md:block">
                                <p class="text-[9px] font-label-caps text-on-surface-variant">Contato</p>
                                <p class="text-xs font-bold text-on-surface mt-0.5"><?= sanitizar($cliente['contato'] ?: '—') ?></p>
                            </div>

                            <div class="flex items-center gap-4 border-l border-outline-variant/20 pl-6">
                                <div class="text-center">
                                    <p class="text-[9px] font-label-caps text-on-surface-variant">Oportunidades</p>
                                    <p class="text-sm font-bold text-on-surface font-data-tabular"><?= $cliente['total_oportunidades'] ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[9px] font-label-caps text-on-surface-variant">Propostas</p>
                                    <p class="text-sm font-bold text-on-surface font-data-tabular"><?= $cliente['total_propostas'] ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="<?= raizUrl('/gerenciamento/clientes.php?editar=' . $cliente['id']) ?>" 
                                   class="p-1.5 text-on-surface-variant hover:text-on-surface hover:bg-surface-variant rounded transition-colors"
                                   title="Editar">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <a href="<?= raizUrl('/gerenciamento/clientes.php?deletar=' . $cliente['id']) ?>" 
                                   onclick="return confirm('Excluir este cliente?');"
                                   class="p-1.5 text-error/70 hover:text-error hover:bg-error-container/10 rounded transition-colors"
                                   title="Excluir">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($clientes)): ?>
                    <div class="py-20 text-center text-on-surface-variant italic">
                        <i data-lucide="users" class="w-8 h-8 mx-auto mb-3 opacity-40"></i>
                        Nenhum cliente cadastrado ainda.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
