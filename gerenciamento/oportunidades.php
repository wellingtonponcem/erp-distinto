<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();
$db = Database::get();

$statusMessage = '';
$errorMessage = '';
$editOportunidade = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = sanitizar($_POST['nome'] ?? '');
    $cliente_id = sanitizar($_POST['cliente_id'] ?? '');
    $valor_estimado = floatval($_POST['valor_estimado'] ?? 0);
    $etapa = sanitizar($_POST['etapa'] ?? 'novo');
    $previsao = sanitizar($_POST['previsao'] ?? '');
    $responsavel = sanitizar($_POST['responsavel'] ?? '');
    $descricao = sanitizar($_POST['descricao'] ?? '');

    if (!$nome) {
        $errorMessage = 'O nome da oportunidade é obrigatório.';
    } else {
        if ($id) {
            $stmt = $db->prepare("UPDATE oportunidades SET nome = ?, cliente_id = ?, valor_estimado = ?, etapa = ?, previsao = ?, responsavel = ?, descricao = ? WHERE id = ?");
            $stmt->execute([$nome, $cliente_id ?: null, $valor_estimado, $etapa, $previsao ?: null, $responsavel, $descricao, $id]);
            $statusMessage = 'Oportunidade atualizada com sucesso.';
        } else {
            $stmt = $db->prepare("INSERT INTO oportunidades (id, cliente_id, nome, valor_estimado, etapa, previsao, responsavel, descricao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([gerarId(), $cliente_id ?: null, $nome, $valor_estimado, $etapa, $previsao ?: null, $responsavel, $descricao]);
            $statusMessage = 'Oportunidade criada com sucesso.';
        }
    }
}

if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM oportunidades WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $editOportunidade = $stmt->fetch();
}

if (isset($_GET['deletar'])) {
    $stmt = $db->prepare("DELETE FROM oportunidades WHERE id = ?");
    $stmt->execute([$_GET['deletar']]);
    $statusMessage = 'Oportunidade excluída com sucesso.';
}

$clientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome ASC")->fetchAll();
$oportunidades = $db->query("SELECT o.*, c.nome AS cliente_nome FROM oportunidades o LEFT JOIN clientes c ON c.id = o.cliente_id ORDER BY CASE o.etapa WHEN 'novo' THEN 1 WHEN 'qualificado' THEN 2 WHEN 'proposta' THEN 3 WHEN 'negociacao' THEN 4 WHEN 'ganha' THEN 5 WHEN 'perdida' THEN 6 ELSE 7 END, o.previsao ASC")->fetchAll();
$tituloPagina = 'CRM • Oportunidades';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-zinc-900 dark:text-white flex items-center gap-3">
                    <i data-lucide="target" class="w-8 h-8 text-zinc-400"></i>
                    Oportunidades (CRM)
                </h1>
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mt-1">Acompanhe o pipeline comercial e saiba quais negociações estão mais próximas de fechar.</p>
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
                <i data-lucide="<?= $editOportunidade ? 'edit-3' : 'plus-circle' ?>" class="w-5 h-5 opacity-50"></i>
                <?= $editOportunidade ? 'Editar Oportunidade' : 'Nova Oportunidade' ?>
            </h2>
            
            <form method="post" action="<?= raizUrl('/gerenciamento/oportunidades.php') ?>">
                <input type="hidden" name="id" value="<?= $editOportunidade['id'] ?? '' ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="space-y-2 lg:col-span-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Nome da Oportunidade *</label>
                        <input class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                               type="text" name="nome" value="<?= sanitizar($editOportunidade['nome'] ?? '') ?>" required placeholder="Ex: Campanha de Inverno 2024">
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Cliente Vinculado</label>
                        <select class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" name="cliente_id">
                            <option value="">— Nenhum cliente —</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>" <?= isset($editOportunidade['cliente_id']) && $editOportunidade['cliente_id'] === $cliente['id'] ? 'selected' : '' ?>><?= sanitizar($cliente['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Valor Estimado (R$)</label>
                        <input class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                               type="number" step="0.01" name="valor_estimado" value="<?= sanitizar($editOportunidade['valor_estimado'] ?? '0.00') ?>">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Etapa do Pipeline</label>
                        <select class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" name="etapa">
                            <?php foreach (['novo'=>'Novo','qualificado'=>'Qualificado','proposta'=>'Proposta enviada','negociacao'=>'Negociação','ganha'=>'Ganha','perdida'=>'Perdida'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= (isset($editOportunidade['etapa']) && $editOportunidade['etapa'] === $value) ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Previsão de Fechamento</label>
                        <input class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                               type="date" name="previsao" value="<?= sanitizar($editOportunidade['previsao'] ?? '') ?>">
                    </div>

                    <div class="space-y-2 lg:col-span-3">
                        <label class="text-[11px] font-black uppercase tracking-widest text-zinc-500 ml-1">Descrição / Notas Comerciais</label>
                        <textarea class="w-full bg-zinc-100 dark:bg-black border border-zinc-200 dark:border-zinc-800 rounded-2xl px-5 py-3.5 text-sm focus:ring-2 ring-zinc-500/20 transition-all outline-none" 
                                  name="descricao" rows="2" placeholder="Histórico da negociação, pontos chave, necessidades do cliente..."><?= sanitizar($editOportunidade['descricao'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="flex justify-end items-center gap-4 mt-8 pt-6 border-t border-zinc-100 dark:border-zinc-800/50">
                    <?php if ($editOportunidade): ?>
                        <a href="<?= raizUrl('/gerenciamento/oportunidades.php') ?>" class="text-sm font-bold text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">Cancelar</a>
                    <?php endif; ?>
                    <button type="submit" class="bg-zinc-900 dark:bg-white text-white dark:text-black px-8 py-3.5 rounded-2xl text-sm font-black hover:scale-[1.02] active:scale-95 transition-all shadow-xl">
                        <?= $editOportunidade ? 'Salvar Alterações' : 'Cadastrar Oportunidade' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Lista de Oportunidades -->
        <div class="space-y-4">
            <h2 class="text-[11px] font-black uppercase tracking-[0.2em] text-zinc-500 ml-4 mb-4">Pipeline Comercial (<?= count($oportunidades) ?>)</h2>
            
            <div class="grid grid-cols-1 gap-3">
                <?php foreach ($oportunidades as $o): 
                    $corEtapa = match($o['etapa']) {
                        'novo' => 'bg-zinc-500',
                        'qualificado' => 'bg-blue-500',
                        'proposta' => 'bg-purple-500',
                        'negociacao' => 'bg-orange-500',
                        'ganha' => 'bg-emerald-500',
                        'perdida' => 'bg-red-500',
                        default => 'bg-zinc-500'
                    };
                ?>
                    <div class="bg-white/50 dark:bg-zinc-900/30 border border-zinc-200/60 dark:border-zinc-800/60 rounded-3xl p-5 flex flex-wrap items-center justify-between gap-4 group hover:border-zinc-400 dark:hover:border-zinc-600 transition-all">
                        <div class="flex items-center gap-5">
                            <div class="w-12 h-12 rounded-2xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors relative">
                                <i data-lucide="briefcase" class="w-6 h-6"></i>
                                <div class="absolute -top-1 -right-1 w-3 h-3 rounded-full border-2 border-white dark:border-zinc-900 <?= $corEtapa ?>"></div>
                            </div>
                            <div>
                                <h3 class="font-bold text-zinc-900 dark:text-white"><?= sanitizar($o['nome']) ?></h3>
                                <p class="text-[11px] font-medium text-zinc-500 uppercase tracking-widest mt-0.5"><?= sanitizar($o['cliente_nome'] ?: 'Sem cliente vinculado') ?></p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-8 items-center">
                            <div>
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Valor Estimado</p>
                                <p class="text-sm font-black text-zinc-900 dark:text-white mt-0.5"><?= formatarMoeda((float)$o['valor_estimado']) ?></p>
                            </div>
                            
                            <div class="hidden sm:block">
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Etapa</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-md text-white <?= $corEtapa ?>">
                                        <?= match($o['etapa']) {
                                            'novo' => 'Novo',
                                            'qualificado' => 'Qualificado',
                                            'proposta' => 'Proposta',
                                            'negociacao' => 'Negociação',
                                            'ganha' => 'Ganha',
                                            'perdida' => 'Perdida',
                                            default => $o['etapa']
                                        } ?>
                                    </span>
                                </div>
                            </div>

                            <div class="hidden md:block">
                                <p class="text-[10px] font-black text-zinc-400 uppercase tracking-tighter">Previsão</p>
                                <p class="text-xs font-bold text-zinc-600 dark:text-zinc-400 mt-0.5"><?= formatarData($o['previsao']) ?></p>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <a href="<?= raizUrl('/gerenciamento/oportunidades.php?editar=' . $o['id']) ?>" 
                                   class="p-2.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-900 dark:hover:bg-white hover:text-white dark:hover:text-black transition-all"
                                   title="Editar">
                                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                                </a>
                                <a href="<?= raizUrl('/gerenciamento/oportunidades.php?deletar=' . $o['id']) ?>" 
                                   onclick="return confirm('Excluir esta oportunidade?');"
                                   class="p-2.5 rounded-xl bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white transition-all"
                                   title="Excluir">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($oportunidades)): ?>
                    <div class="py-20 text-center">
                        <i data-lucide="target" class="w-12 h-12 mx-auto text-zinc-800 mb-4 opacity-20"></i>
                        <p class="text-sm font-bold text-zinc-500">Nenhuma oportunidade em aberto no momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
