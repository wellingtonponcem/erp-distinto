<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirDistinto();
$db = Database::get();

$statusMessage = '';
$errorMessage = '';
$editFornecedor = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = sanitizar($_POST['nome'] ?? '');
    $contato = sanitizar($_POST['contato'] ?? '');
    $telefone = sanitizar($_POST['telefone'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $categoria = sanitizar($_POST['categoria'] ?? '');
    $observacao = sanitizar($_POST['observacao'] ?? '');

    if (!$nome) {
        $errorMessage = 'O nome do fornecedor é obrigatório.';
    } else {
        if ($id) {
            $stmt = $db->prepare("UPDATE fornecedores SET nome = ?, contato = ?, telefone = ?, email = ?, categoria = ?, observacao = ? WHERE id = ?");
            $stmt->execute([$nome, $contato, $telefone, $email, $categoria, $observacao, $id]);
            $statusMessage = 'Fornecedor atualizado com sucesso.';
        } else {
            $stmt = $db->prepare("INSERT INTO fornecedores (id, nome, contato, telefone, email, categoria, observacao) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([gerarId(), $nome, $contato, $telefone, $email, $categoria, $observacao]);
            $statusMessage = 'Fornecedor cadastrado com sucesso.';
        }
    }
}

if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM fornecedores WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $editFornecedor = $stmt->fetch();
}

if (isset($_GET['deletar'])) {
    $stmt = $db->prepare("DELETE FROM fornecedores WHERE id = ?");
    $stmt->execute([$_GET['deletar']]);
    $statusMessage = 'Fornecedor excluído com sucesso.';
}

$fornecedores = $db->query("
    SELECT f.*, 
           (SELECT COUNT(*) FROM propostas p WHERE p.dados_json->'adicional'->>'fornecedor_id' = f.id) as total_vinculos
    FROM fornecedores f 
    ORDER BY f.nome ASC
")->fetchAll();
$tituloPagina = 'CRM • Fornecedores';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-zinc-900 dark:text-white flex items-center gap-3">
                    <i data-lucide="truck" class="w-8 h-8 text-zinc-400"></i>
                    Fornecedores
                </h1>
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mt-1">Gerencie seus parceiros e mantenha o controle de compras e serviços contratados.</p>
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
                <i data-lucide="<?= $editFornecedor ? 'edit-3' : 'plus-circle' ?>" class="w-5 h-5 opacity-50"></i>
                <?= $editFornecedor ? 'Editar Fornecedor' : 'Novo Fornecedor' ?>
            </h2>
            
            <form method="post" action="<?= raizUrl('/gerenciamento/fornecedores.php') ?>">
                <input type="hidden" name="id" value="<?= $editFornecedor['id'] ?? '' ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Nome / Razão Social *</label>
                        <input class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                               type="text" name="nome" value="<?= sanitizar($editFornecedor['nome'] ?? '') ?>" required placeholder="Nome do Fornecedor">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Pessoa de Contato</label>
                        <input class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                               type="text" name="contato" value="<?= sanitizar($editFornecedor['contato'] ?? '') ?>" placeholder="Nome do representante">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Telefone</label>
                        <input class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                               type="text" name="telefone" value="<?= sanitizar($editFornecedor['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">E-mail</label>
                        <input class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                               type="email" name="email" value="<?= sanitizar($editFornecedor['email'] ?? '') ?>" placeholder="email@fornecedor.com">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Categoria</label>
                        <input class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                               type="text" name="categoria" value="<?= sanitizar($editFornecedor['categoria'] ?? '') ?>" placeholder="Ex: Equipamentos, Software, Marketing...">
                    </div>

                    <div class="space-y-2 md:col-span-3">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Observações Adicionais</label>
                        <textarea class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                                  name="observacao" rows="2" placeholder="Detalhes do contrato, prazos, etc."><?= sanitizar($editFornecedor['observacao'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="flex justify-end items-center gap-4 mt-8 pt-6 border-t border-zinc-100 dark:border-zinc-800/50">
                    <?php if ($editFornecedor): ?>
                        <a href="<?= raizUrl('/gerenciamento/fornecedores.php') ?>" class="text-sm font-bold text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">Cancelar</a>
                    <?php endif; ?>
                    <button type="submit" class="bg-zinc-900 dark:bg-white text-white dark:text-black px-8 py-3.5 rounded-2xl text-sm font-black hover:scale-[1.02] active:scale-95 transition-all shadow-xl">
                        <?= $editFornecedor ? 'Salvar Alterações' : 'Cadastrar Fornecedor' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Fornecedores -->
        <div class="space-y-4">
            <h2 class="text-[11px] font-black uppercase tracking-[0.2em] text-zinc-500 ml-4 mb-4">Parceiros e Fornecedores (<?= count($fornecedores) ?>)</h2>
            
            <div class="grid grid-cols-1 gap-3">
                <?php foreach ($fornecedores as $f): ?>
                    <div class="bg-white/50 dark:bg-zinc-900/30 border border-zinc-200/60 dark:border-zinc-800/60 rounded-3xl p-5 flex flex-wrap items-center justify-between gap-4 group hover:border-zinc-400 dark:hover:border-zinc-600 transition-all">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 rounded-2xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors">
                                <i data-lucide="package" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-zinc-900 dark:text-white"><?= sanitizar($f['nome']) ?></h3>
                                <p class="text-[11px] font-medium text-zinc-500 uppercase tracking-widest mt-0.5"><?= sanitizar($f['categoria'] ?: 'Sem categoria') ?></p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-8 items-center">
                            <div class="hidden sm:block">
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Contato</p>
                                <p class="text-xs font-bold text-zinc-600 dark:text-zinc-400 mt-0.5"><?= sanitizar($f['contato'] ?: '—') ?></p>
                            </div>
                            <div class="hidden md:block">
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Telefone</p>
                                <p class="text-xs font-bold text-zinc-600 dark:text-zinc-400 mt-0.5"><?= sanitizar($f['telefone'] ?: '—') ?></p>
                            </div>

                            <div class="flex items-center gap-4 border-l border-zinc-100 dark:border-zinc-800/50 pl-6">
                                <div class="text-center">
                                    <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Propostas</p>
                                    <p class="text-sm font-black text-zinc-900 dark:text-white"><?= $f['total_vinculos'] ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <a href="<?= raizUrl('/gerenciamento/fornecedores.php?editar=' . $f['id']) ?>" 
                                   class="p-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-900 dark:hover:bg-white hover:text-white dark:hover:text-black transition-all"
                                   title="Editar">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </a>
                                <a href="<?= raizUrl('/gerenciamento/fornecedores.php?deletar=' . $f['id']) ?>" 
                                   onclick="return confirm('Excluir este fornecedor?');"
                                   class="p-2.5 rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all"
                                   title="Excluir">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($fornecedores)): ?>
                    <div class="py-20 text-center">
                        <i data-lucide="truck" class="w-12 h-12 mx-auto text-zinc-800 mb-4 opacity-20"></i>
                        <p class="text-sm font-bold text-zinc-500">Nenhum fornecedor cadastrado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
