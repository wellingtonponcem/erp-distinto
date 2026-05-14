<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirDistinto();

$tituloPagina = 'Depoimentos';
include __DIR__ . '/../includes/layout/head.php';

$categorias = [
    'casamento'  => ['label' => 'Casamento',          'icon' => 'heart',      'cor' => '#f59e0b'],
    'filmmaker'  => ['label' => 'Filmmaker',           'icon' => 'clapperboard','cor' => '#8b5cf6'],
    '15anos'     => ['label' => '15 Anos',             'icon' => 'sparkles',   'cor' => '#ec4899'],
    'marketing'  => ['label' => 'Marketing Digital',   'icon' => 'megaphone',  'cor' => '#10b981'],
];
$categoriasJson = json_encode($categorias);
?>

<div id="app-wrapper" x-data="depoimentos()" x-init="carregar()">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet">
        <div class="app-topbar">
            <div class="top-nav">
                <a href="<?= raizUrl('/dashboard.php') ?>">Dashboard</a>
                <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>">Propostas</a>
                <a href="<?= raizUrl('/gerenciamento/depoimentos.php') ?>" style="font-weight:800; color:#111;">Depoimentos</a>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5 mb-6">
            <div>
                <h1 class="page-title">Depoimentos</h1>
                <p class="page-subtitle">Gerencie os depoimentos exibidos nas propostas por categoria de serviço.</p>
            </div>
            <button @click="abrirNovo()" class="btn-primary">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Novo Depoimento
            </button>
        </div>

        <!-- Filtro por categoria -->
        <div class="flex flex-wrap gap-2 mb-6">
            <button
                @click="filtro = null"
                :class="filtro === null ? 'btn-primary' : 'btn-secondary'"
                style="min-height:32px; padding:6px 14px; font-size:12px;">
                Todos <span class="ml-1 opacity-60" x-text="'(' + lista.length + ')'"></span>
            </button>
            <?php foreach ($categorias as $chave => $cat): ?>
            <button
                @click="filtro = '<?= $chave ?>'"
                :class="filtro === '<?= $chave ?>' ? 'btn-primary' : 'btn-secondary'"
                style="min-height:32px; padding:6px 14px; font-size:12px;">
                <i data-lucide="<?= $cat['icon'] ?>" class="w-3 h-3"></i>
                <?= $cat['label'] ?>
                <span class="ml-1 opacity-60" x-text="'(' + lista.filter(d => d.categoria === '<?= $chave ?>').length + ')'"></span>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Estado vazio -->
        <template x-if="!carregando && listaFiltrada.length === 0">
            <div class="card p-12 flex flex-col items-center text-center text-zinc-400">
                <i data-lucide="message-square-quote" class="w-10 h-10 mb-3 opacity-30"></i>
                <p class="text-sm font-bold">Nenhum depoimento cadastrado</p>
                <p class="text-xs mt-1">Clique em "Novo Depoimento" para começar.</p>
            </div>
        </template>

        <!-- Loading -->
        <template x-if="carregando">
            <div class="card p-12 flex items-center justify-center text-zinc-400">
                <i data-lucide="loader-2" class="w-5 h-5 animate-spin mr-2"></i>
                <span class="text-sm">Carregando...</span>
            </div>
        </template>

        <!-- Grid de depoimentos agrupados por categoria -->
        <?php foreach ($categorias as $chave => $cat): ?>
        <template x-if="!carregando && (filtro === null || filtro === '<?= $chave ?>') && lista.filter(d => d.categoria === '<?= $chave ?>').length > 0">
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-2 h-2 rounded-full" style="background: <?= $cat['cor'] ?>;"></div>
                    <h2 class="text-xs font-bold uppercase tracking-widest text-zinc-500"><?= $cat['label'] ?></h2>
                    <span class="text-xs text-zinc-400" x-text="'(' + lista.filter(d => d.categoria === '<?= $chave ?>').length + ')'"></span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    <template x-for="d in lista.filter(d => d.categoria === '<?= $chave ?>')" :key="d.id">
                        <article class="card p-5 flex flex-col gap-4 transition-all"
                            :class="d.ativo == 1 ? '' : 'opacity-50'">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="text-[13px] leading-relaxed text-zinc-700 italic line-clamp-4"
                                        x-text="'\"' + d.texto + '\"'"></p>
                                </div>
                                <div class="flex flex-col gap-1 flex-shrink-0">
                                    <button @click="editar(d)" title="Editar"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-zinc-400 hover:text-zinc-900 hover:bg-zinc-100 transition-all">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </button>
                                    <button @click="toggleAtivo(d)" :title="d.ativo == 1 ? 'Desativar' : 'Ativar'"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg transition-all"
                                        :class="d.ativo == 1 ? 'text-emerald-500 hover:bg-emerald-50' : 'text-zinc-300 hover:bg-zinc-100'">
                                        <i data-lucide="toggle-right" class="w-3.5 h-3.5" x-show="d.ativo == 1"></i>
                                        <i data-lucide="toggle-left" class="w-3.5 h-3.5" x-show="d.ativo != 1"></i>
                                    </button>
                                    <button @click="excluir(d.id)" title="Excluir"
                                        class="w-7 h-7 flex items-center justify-center rounded-lg text-zinc-300 hover:text-red-500 hover:bg-red-50 transition-all">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between border-t border-zinc-100 pt-3">
                                <div>
                                    <p class="text-xs font-bold text-zinc-900" x-text="'— ' + d.autor"></p>
                                </div>
                                <span x-show="d.ativo != 1"
                                    class="text-[10px] font-bold uppercase tracking-wider text-zinc-400 bg-zinc-100 px-2 py-0.5 rounded-full">
                                    Inativo
                                </span>
                                <span x-show="d.ativo == 1"
                                    class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                    Ativo
                                </span>
                            </div>
                        </article>
                    </template>
                </div>
            </div>
        </template>
        <?php endforeach; ?>

    </main>

    <!-- Modal -->
    <div class="modal-overlay" x-show="modalAberto" x-cloak @click.self="fecharModal()">
        <div class="modal" style="max-width:540px; width:100%;">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-base font-bold text-zinc-900" x-text="form.id ? 'Editar Depoimento' : 'Novo Depoimento'"></h2>
                <button @click="fecharModal()" class="text-zinc-400 hover:text-zinc-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <div class="space-y-4">
                <div class="form-group">
                    <label class="label">Categoria</label>
                    <select x-model="form.categoria" class="input">
                        <option value="">Selecione...</option>
                        <?php foreach ($categorias as $chave => $cat): ?>
                        <option value="<?= $chave ?>"><?= $cat['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label">Depoimento</label>
                    <textarea x-model="form.texto" class="input" rows="4"
                        placeholder="O texto do depoimento..."></textarea>
                </div>

                <div class="form-group">
                    <label class="label">Autor / Cliente</label>
                    <input type="text" x-model="form.autor" class="input"
                        placeholder="Ex: Fernanda & Thiago">
                </div>

                <div class="form-group">
                    <label class="label">Ordem de exibição</label>
                    <input type="number" x-model="form.ordem" class="input" min="0" placeholder="0">
                </div>

                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="form.ativo" :true-value="1" :false-value="0"
                            class="w-4 h-4 rounded">
                        <span class="text-sm font-medium text-zinc-700">Ativo (exibido nas propostas)</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button @click="salvar()" :disabled="salvando"
                    class="btn-primary flex-1" x-text="salvando ? 'Salvando...' : 'Salvar'"></button>
                <button @click="fecharModal()" class="btn-secondary">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
function depoimentos() {
    return {
        lista: [],
        filtro: null,
        carregando: true,
        modalAberto: false,
        salvando: false,
        form: {},

        get listaFiltrada() {
            if (!this.filtro) return this.lista;
            return this.lista.filter(d => d.categoria === this.filtro);
        },

        async carregar() {
            this.carregando = true;
            try {
                const r = await fetch('<?= raizUrl('/api/depoimentos/index.php') ?>');
                this.lista = await r.json();
            } catch (e) {
                console.error(e);
            } finally {
                this.carregando = false;
                this.$nextTick(() => lucide.createIcons());
            }
        },

        abrirNovo() {
            this.form = { id: null, texto: '', autor: '', categoria: this.filtro || '', ordem: 0, ativo: 1 };
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        editar(d) {
            this.form = { ...d };
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        fecharModal() {
            this.modalAberto = false;
        },

        async salvar() {
            if (!this.form.texto || !this.form.autor || !this.form.categoria) {
                alert('Preencha texto, autor e categoria.');
                return;
            }
            this.salvando = true;
            try {
                const metodo = this.form.id ? 'PUT' : 'POST';
                const r = await fetch('<?= raizUrl('/api/depoimentos/index.php') ?>', {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form),
                });
                const res = await r.json();
                if (res.erro) { alert(res.erro); return; }
                this.fecharModal();
                await this.carregar();
            } finally {
                this.salvando = false;
            }
        },

        async toggleAtivo(d) {
            const novoAtivo = d.ativo == 1 ? 0 : 1;
            await fetch('<?= raizUrl('/api/depoimentos/index.php') ?>', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: d.id, ativo: novoAtivo }),
            });
            d.ativo = novoAtivo;
        },

        async excluir(id) {
            if (!confirm('Remover este depoimento?')) return;
            await fetch('<?= raizUrl('/api/depoimentos/index.php') ?>?id=' + id, { method: 'DELETE' });
            await this.carregar();
        },
    };
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
