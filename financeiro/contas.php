<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();

$tituloPagina = "Contas Bancárias";
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper" class="flex min-h-screen" x-data="contas">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet !bg-background !p-6 flex flex-col flex-1">
        <!-- Topbar -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-8 mt-2">
            <div>
                <h1 class="font-display-lg text-on-surface mb-1">Contas Bancárias</h1>
                <p class="text-body-md text-on-surface-variant">Gerencie suas contas e saldos.</p>
            </div>
            <button @click="abrirModal()" class="bg-primary hover:bg-primary-container text-on-primary-container font-bold px-6 py-2.5 rounded-lg text-body-md transition-all active:scale-95 duration-150 shadow-lg flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Nova Conta
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-card-gap mb-6">
            <template x-for="conta in lista" :key="conta.id">
                <div class="glass-card p-6 rounded-xl relative overflow-hidden flex flex-col justify-between h-48 group">
                    <!-- Color strip indicator on top or side -->
                    <div :style="'position:absolute; top:0; left:0; width:4px; height:100%; background:' + (conta.cor || '#484555')"></div>
                    
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-title-sm font-headline-md text-on-surface font-bold" x-text="conta.nome"></h3>
                        </div>
                        <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="abrirModal(conta)" class="p-1.5 text-on-surface-variant hover:text-on-surface hover:bg-surface-variant rounded transition-colors" title="Editar">
                                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                            </button>
                            <button @click="excluir(conta.id)" class="p-1.5 text-error/70 hover:text-error hover:bg-error-container/10 rounded transition-colors" title="Excluir">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-label-caps font-label-caps text-on-surface-variant text-[9px]">Saldo Atual</span>
                                <span x-show="conta.saldo_asaas_api" class="px-1.5 py-0.5 rounded text-[8px] font-label-caps bg-purple-500/10 text-purple-400 border border-purple-500/20">Via API</span>
                            </div>
                            <div class="text-2xl font-bold font-data-tabular flex items-baseline gap-3" :class="conta.saldo_atual < 0 ? 'text-error' : 'text-on-surface'">
                                <span x-text="formatarMoeda(conta.saldo_atual)"></span>
                                <template x-if="conta.id === 'asaas'">
                                    <button @click="sincronizarAsaas()" :disabled="sincronizandoAsaas"
                                            class="px-2 py-1 rounded bg-purple-500/10 text-purple-400 hover:bg-purple-500/20 font-label-caps text-[8px] flex items-center gap-1.5 transition-all active:scale-95"
                                            title="Sincronizar todas as cobranças do Asaas">
                                        <svg x-show="sincronizandoAsaas" class="w-3 h-3 animate-spin text-purple-400" viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/>
                                        </svg>
                                        <span x-text="sincronizandoAsaas ? 'Sincronizando...' : 'Sincronizar'"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-outline-variant/10 text-body-md text-on-surface-variant flex justify-between text-xs">
                            <span>Saldo Inicial:</span>
                            <span class="font-data-tabular font-bold" x-text="formatarMoeda(conta.saldo_inicial)"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Modal -->
        <div class="modal-overlay" x-show="modalAberto" x-cloak @click.self="modalAberto=false">
            <div class="modal w-full max-w-md p-6 bg-surface-container-low border border-outline-variant/30 rounded-2xl shadow-2xl">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-title-sm font-headline-md font-bold text-on-surface" x-text="form.id ? 'Editar Conta' : 'Nova Conta'"></h2>
                    <button @click="modalAberto=false" class="text-on-surface-variant hover:text-on-surface transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form @submit.prevent="salvar()">
                    <div class="mb-4">
                        <label class="label">Nome do Banco / Conta</label>
                        <input class="input w-full" x-model="form.nome" required placeholder="Ex: C6 Bank, Itaú PJ...">
                    </div>
                    
                    <div x-show="form.id" class="mb-6 p-4 rounded-xl border border-primary/20 bg-primary/5">
                        <label class="label text-primary">Ajustar Saldo Atual</label>
                        <div class="flex gap-2 items-center mb-3">
                            <input class="input w-full" type="number" step="0.01" x-model.number="novoSaldoAtual" placeholder="Ex: 39.98">
                        </div>
                        
                        <div x-show="diferencaAjuste() !== 0" class="space-y-3 mt-3 p-3 bg-surface-container-lowest/50 rounded-lg border border-outline-variant/10">
                            <p class="text-xs text-on-surface-variant">
                                O saldo atual é <b class="text-on-surface font-data-tabular">R$ <span x-text="form.saldo_atual"></span></b> e o novo será <b class="text-on-surface font-data-tabular">R$ <span x-text="novoSaldoAtual"></span></b>.
                                A diferença é de <b class="font-data-tabular" :class="diferencaAjuste() > 0 ? 'text-primary' : 'text-error'">R$ <span x-text="Math.abs(diferencaAjuste()).toFixed(2)"></span></b>.
                            </p>
                            <label class="flex items-center gap-2 text-xs text-on-surface-variant cursor-pointer">
                                <input type="radio" x-model="tipoAjuste" value="lancamento" class="rounded border-outline-variant/30 text-primary focus:ring-primary focus:ring-offset-background">
                                <span x-text="diferencaAjuste() > 0 ? 'Lançar como nova Receita (Ajuste)' : 'Lançar como nova Despesa (Ajuste)'"></span>
                            </label>
                            <label class="flex items-center gap-2 text-xs text-on-surface-variant cursor-pointer">
                                <input type="radio" x-model="tipoAjuste" value="inicial" class="rounded border-outline-variant/30 text-primary focus:ring-primary focus:ring-offset-background">
                                <span>Alterar Saldo Inicial (Retroativo)</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="label">Cor de Identificação</label>
                        <div class="flex gap-2 flex-wrap">
                            <template x-for="c in cores">
                                <button type="button" @click="form.cor = c" 
                                    class="w-8 h-8 rounded-full border-2 transition-all cursor-pointer"
                                    :style="'background:' + c"
                                    :class="form.cor === c ? 'border-on-surface scale-110 shadow-lg' : 'border-transparent opacity-60 hover:opacity-100'"></button>
                            </template>
                        </div>
                    </div>

                    <div class="flex gap-3 justify-end">
                        <button type="button" class="btn-secondary" @click="modalAberto=false">Cancelar</button>
                        <button type="submit" class="btn-primary" :disabled="salvando" x-text="salvando ? 'Salvando...' : 'Salvar Conta'"></button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('contas', () => ({
        lista: [],
        modalAberto: false,
        salvando: false,
        sincronizandoAsaas: false,
        form: {},
        novoSaldoAtual: 0,
        tipoAjuste: 'lancamento',
        cores: ['#2a2a2a', '#2563eb', '#16a34a', '#d97706', '#dc2626', '#7c3aed', '#db2777'],

        async init() {
            await this.carregar();
        },

        async carregar() {
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/contas.php') ?>');
                this.lista = await r.json();
                this.$nextTick(() => lucide.createIcons());
            } catch(e) { toast('Erro ao carregar contas', 'erro'); }
        },

        async sincronizarAsaas() {
            if (this.sincronizandoAsaas) return;
            this.sincronizandoAsaas = true;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/sincronizar_asaas_completo.php') ?>', { method: 'POST' });
                const data = await r.json();
                if (data.success) {
                    toast(data.mensagem, 'sucesso');
                } else {
                    toast(data.erro || 'Erro ao sincronizar Asaas', 'erro');
                }
            } catch(e) {
                toast('Erro de conexão ao sincronizar Asaas', 'erro');
            }
            this.sincronizandoAsaas = false;
            await this.carregar();
        },

        abrirModal(conta = null) {
            this.form = conta ? { ...conta } : { nome: '', saldo_inicial: 0, cor: '#2a2a2a', saldo_atual: 0 };
            this.novoSaldoAtual = conta ? conta.saldo_atual : 0;
            this.tipoAjuste = 'lancamento';
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async salvar() {
            this.salvando = true;
            try {
                const metodo = this.form.id ? 'PUT' : 'POST';
                const payload = { ...this.form };
                if (this.form.id && this.diferencaAjuste() !== 0) {
                    payload.ajuste_saldo = this.diferencaAjuste();
                    payload.tipo_ajuste = this.tipoAjuste;
                }

                const r = await fetch('<?= raizUrl('/api/financeiro/contas.php') ?>', {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (r.ok) {
                    await this.carregar();
                    this.modalAberto = false;
                    toast('Conta salva com sucesso!', 'sucesso');
                } else {
                    const res = await r.json();
                    toast(res.erro || 'Erro ao salvar', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.salvando = false;
        },

        async excluir(id) {
            if (!confirm('Deseja realmente excluir esta conta?')) return;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/contas.php?id=') ?>' + id, { method: 'DELETE' });
                if (r.ok) {
                    await this.carregar();
                    toast('Conta excluída', 'sucesso');
                }
            } catch(e) { toast('Erro ao excluir', 'erro'); }
        },

        formatarMoeda(v) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v || 0);
        },

        diferencaAjuste() {
            if (!this.form.id) return 0;
            const atual = parseFloat(this.form.saldo_atual) || 0;
            const novo = parseFloat(this.novoSaldoAtual) || 0;
            return novo - atual;
        }
    }));
});
</script>

<?php require_once __DIR__ . '/../includes/layout/footer.php'; ?>
