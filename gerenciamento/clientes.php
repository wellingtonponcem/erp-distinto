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
<div id="app-wrapper">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-zinc-900 dark:text-white flex items-center gap-3">
                    <i data-lucide="users" class="w-8 h-8 text-zinc-400"></i>
                    Clientes
                </h1>
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mt-1">Gerencie a base de contatos que alimenta seu pipeline comercial.</p>
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

        <!-- Formulário de Cadastro -->
        <div class="bg-white/80 dark:bg-zinc-900/50 backdrop-blur-xl border border-zinc-200 dark:border-zinc-800 rounded-[32px] p-8 mb-10 shadow-sm">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white mb-6 flex items-center gap-2">
                <i data-lucide="<?= $editCliente ? 'edit-3' : 'plus-circle' ?>" class="w-5 h-5 opacity-50"></i>
                <?= $editCliente ? 'Editar Cliente' : 'Novo Cliente' ?>
            </h2>
            
            <form method="post" action="<?= raizUrl('/gerenciamento/clientes.php') ?>">
                <input type="hidden" name="id" value="<?= $editCliente['id'] ?? '' ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Nome Completo / Razão Social *</label>
                        <input class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                               type="text" name="nome" value="<?= sanitizar($editCliente['nome'] ?? '') ?>" required placeholder="Ex: João Silva ou Empresa LTDA">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">CPF / CNPJ</label>
                        <input class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                               type="text" name="cpf_cnpj" value="<?= sanitizar($editCliente['cpf_cnpj'] ?? '') ?>" placeholder="000.000.000-00">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Contato (WhatsApp/E-mail)</label>
                        <input class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                               type="text" name="contato" value="<?= sanitizar($editCliente['contato'] ?? '') ?>" placeholder="Ex: (27) 99999-0000">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Segmento / Atividade</label>
                        <input class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                               type="text" name="segmento" value="<?= sanitizar($editCliente['segmento'] ?? '') ?>" placeholder="Ex: Tecnologia, Varejo, Saúde...">
                    </div>
                </div>

                <div class="flex justify-end items-center gap-4 mt-8 pt-6 border-t border-zinc-100 dark:border-zinc-800/50">
                    <?php if ($editCliente): ?>
                        <a href="<?= raizUrl('/gerenciamento/clientes.php') ?>" class="text-sm font-bold text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">Cancelar</a>
                    <?php endif; ?>
                    <button type="submit" class="bg-zinc-900 dark:bg-white text-white dark:text-black px-8 py-3.5 rounded-2xl text-sm font-black hover:scale-[1.02] active:scale-95 transition-all shadow-xl">
                        <?= $editCliente ? 'Salvar Alterações' : 'Cadastrar Cliente' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Clientes -->
        <div class="space-y-4">
            <h2 class="text-[11px] font-black uppercase tracking-[0.2em] text-zinc-500 ml-4 mb-4">Base de Clientes (<?= count($clientes) ?>)</h2>
            
            <div class="grid grid-cols-1 gap-3">
                <?php foreach ($clientes as $cliente): ?>
                    <div class="bg-white/50 dark:bg-zinc-900/30 border border-zinc-200/60 dark:border-zinc-800/60 rounded-3xl p-5 flex flex-wrap items-center justify-between gap-4 group hover:border-zinc-400 dark:hover:border-zinc-600 transition-all">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 rounded-2xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors">
                                <i data-lucide="user" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-zinc-900 dark:text-white"><?= sanitizar($cliente['nome']) ?></h3>
                                <p class="text-[11px] font-medium text-zinc-500 uppercase tracking-widest mt-0.5"><?= sanitizar($cliente['segmento'] ?: 'Sem segmento') ?></p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-8 items-center">
                            <div class="hidden sm:block">
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Documento</p>
                                <p class="text-xs font-bold text-zinc-600 dark:text-zinc-400 mt-0.5"><?= sanitizar($cliente['cpf_cnpj'] ?: '—') ?></p>
                            </div>
                            <div class="hidden md:block">
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Contato</p>
                                <p class="text-xs font-bold text-zinc-600 dark:text-zinc-400 mt-0.5"><?= sanitizar($cliente['contato'] ?: '—') ?></p>
                            </div>

                            <div class="flex items-center gap-4 border-l border-zinc-100 dark:border-zinc-800/50 pl-6">
                                <div class="text-center">
                                    <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Oportunidades</p>
                                    <p class="text-sm font-black text-zinc-900 dark:text-white"><?= $cliente['total_oportunidades'] ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Propostas</p>
                                    <p class="text-sm font-black text-zinc-900 dark:text-white"><?= $cliente['total_propostas'] ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <a href="<?= raizUrl('/gerenciamento/clientes.php?editar=' . $cliente['id']) ?>" 
                                   class="p-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-900 dark:hover:bg-white hover:text-white dark:hover:text-black transition-all"
                                   title="Editar">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </a>
                                <a href="<?= raizUrl('/gerenciamento/clientes.php?deletar=' . $cliente['id']) ?>" 
                                   onclick="return confirm('Excluir este cliente?');"
                                   class="p-2.5 rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all"
                                   title="Excluir">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($clientes)): ?>
                    <div class="py-20 text-center">
                        <i data-lucide="users" class="w-12 h-12 mx-auto text-zinc-800 mb-4 opacity-20"></i>
                        <p class="text-sm font-bold text-zinc-500">Nenhum cliente cadastrado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
