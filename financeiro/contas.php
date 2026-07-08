<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirDistinto();

$tituloPagina = "Contas Bancárias";
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper" x-data="contas">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content">
        <div class="app-topbar">
            <div>
                <h1 class="page-title">Contas Bancárias</h1>
                <p class="page-subtitle">Gerencie suas contas e saldos.</p>
            </div>
            <button @click="abrirModal()" class="btn-primary">
                <i data-lucide="plus" style="width:16px;height:16px;"></i>
                Nova Conta
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <template x-for="conta in lista" :key="conta.id">
                <div class="card p-6 relative overflow-hidden">
                    <div :style="'position:absolute; top:0; left:0; width:4px; height:100%; background:' + (conta.cor || '#2a2a2a')"></div>
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-bold" x-text="conta.nome"></h3>
                        </div>
                        <div class="flex gap-2">
                            <button @click="abrirModal(conta)" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg">
                                <i data-lucide="edit-2" style="width:14px;height:14px;"></i>
                            </button>
                            <button @click="excluir(conta.id)" class="p-2 hover:bg-red-50 text-red-500 rounded-lg">
                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-bold mb-1">
                                Saldo Atual
                                <span x-show="conta.saldo_asaas_api" class="ml-2 px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider bg-purple-500/10 text-purple-400 border border-purple-500/20">Via API</span>
                            </p>
                            <div class="text-2xl font-black flex items-center gap-3" :class="conta.saldo_atual < 0 ? 'text-red-500' : 'text-distinto-ink dark:text-white'">
                                <span x-text="formatarMoeda(conta.saldo_atual)"></span>
                                <template x-if="conta.id === 'asaas'">
                                    <button @click="sincronizarAsaas()" :disabled="sincronizandoAsaas"
                                            class="px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5"
                                            :class="sincronizandoAsaas ? 'bg-zinc-800 text-zinc-500 cursor-wait' : 'bg-purple-500/10 text-purple-400 hover:bg-purple-500 hover:text-white cursor-pointer'"
                                            title="Sincronizar todas as cobranças do Asaas">
                                        <svg x-show="sincronizandoAsaas" class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4" stroke-dashoffset="10"/>
                                        </svg>
                                        <span x-text="sincronizandoAsaas ? 'Sincronizando...' : 'Sincronizar'"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <div class="pt-4 border-top border-gray-100 dark:border-gray-800">
                            <p class="text-xs text-gray-500 mb-1">Saldo Inicial: <span x-text="formatarMoeda(conta.saldo_inicial)"></span></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Modal -->
        <div class="modal-overlay" x-show="modalAberto" x-cloak @click.self="modalAberto=false">
            <div class="modal" style="max-width:450px;">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold" x-text="form.id ? 'Editar Conta' : 'Nova Conta'"></h2>
                    <button @click="modalAberto=false"><i data-lucide="x"></i></button>
                </div>

                <form @submit.prevent="salvar()">
                    <div class="mb-4">
                        <label class="label">Nome do Banco / Conta</label>
                        <input class="input" x-model="form.nome" required placeholder="Ex: C6 Bank, Itaú PJ...">
                    </div>
                    
                    <div x-show="form.id" class="mb-6 p-4 rounded-xl border border-blue-500/20 bg-blue-500/5">
                        <label class="label text-blue-400">Ajustar Saldo Atual</label>
                        <div class="flex gap-2 items-center mb-3">
                            <input class="input" type="number" step="0.01" x-model.number="novoSaldoAtual" placeholder="Ex: 39.98">
                        </div>
                        
                        <div x-show="diferencaAjuste() !== 0" class="space-y-3 mt-3 p-3 bg-black/20 rounded-lg border border-white/5">
                            <p class="text-xs text-gray-400">
                                O saldo atual é <b>R$ <span x-text="form.saldo_atual"></span></b> e o novo será <b>R$ <span x-text="novoSaldoAtual"></span></b>.
                                A diferença é de <b :class="diferencaAjuste() > 0 ? 'text-green-400' : 'text-red-400'">R$ <span x-text="Math.abs(diferencaAjuste()).toFixed(2)"></span></b>.
                            </p>
                            <label class="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
                                <input type="radio" x-model="tipoAjuste" value="lancamento" class="accent-blue-500">
                                <span x-text="diferencaAjuste() > 0 ? 'Lançar como nova Receita (Ajuste)' : 'Lançar como nova Despesa (Ajuste)'"></span>
                            </label>
                            <label class="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
                                <input type="radio" x-model="tipoAjuste" value="inicial" class="accent-blue-500">
                                <span>Alterar Saldo Inicial (Retroativo)</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="label">Cor de Identificação</label>
                        <div class="flex gap-2">
                            <template x-for="c in cores">
                                <button type="button" @click="form.cor = c" 
                                    class="w-8 h-8 rounded-full border-2 transition-all"
                                    :style="'background:' + c"
                                    :class="form.cor === c ? 'border-white scale-110 shadow-lg' : 'border-transparent opacity-50'"></button>
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
