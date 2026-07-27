<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
exigirAdmin();
$tituloPagina = 'Custos Fixos';
include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper" class="flex min-h-screen" x-data="custosFixos()">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet !bg-background !p-6 flex flex-col flex-1">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <!-- Topbar -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-8 mt-2">
            <div>
                <h1 class="font-display-lg text-on-surface mb-1">Custos Fixos</h1>
                <p class="text-body-md text-on-surface-variant">Despesas mensais recorrentes da agência</p>
            </div>
            <button class="bg-primary hover:bg-primary-container text-on-primary-container font-bold px-6 py-2.5 rounded-lg text-body-md transition-all active:scale-95 duration-150 shadow-lg flex items-center gap-2" @click="abrirModal()">
                <i data-lucide="plus" class="w-4 h-4"></i> Novo Custo
            </button>
        </div>

        <!-- Card Resumo -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-card-gap mb-8">
            <div class="glass-card p-6 rounded-xl flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-error/5 rounded-full blur-2xl group-hover:bg-error/10 transition-colors"></div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-label-caps font-label-caps text-on-surface-variant mb-1">Total Mensal</p>
                        <h3 class="text-3xl font-bold font-headline-md text-error tracking-tight font-data-tabular" x-text="formatarMoeda(totalMensal)"></h3>
                    </div>
                    <span class="w-8 h-8 rounded-lg bg-error/10 text-error flex items-center justify-center shrink-0">
                        <i data-lucide="trending-down" class="w-4 h-4"></i>
                    </span>
                </div>
                <p class="text-[9px] font-label-caps text-on-surface-variant mt-3">Custos mensais + anuais ÷ 12</p>
            </div>

            <div class="glass-card p-6 rounded-xl flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-tertiary/5 rounded-full blur-2xl group-hover:bg-tertiary/10 transition-colors"></div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-label-caps font-label-caps text-on-surface-variant mb-1">Custo Anual</p>
                        <h3 class="text-3xl font-bold font-headline-md text-tertiary tracking-tight font-data-tabular" x-text="formatarMoeda(totalMensal * 12)"></h3>
                    </div>
                    <span class="w-8 h-8 rounded-lg bg-tertiary/10 text-tertiary flex items-center justify-center shrink-0">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                    </span>
                </div>
                <p class="text-[9px] font-label-caps text-on-surface-variant mt-3">Projeção anual dos custos</p>
            </div>

            <div class="glass-card p-6 rounded-xl flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/5 rounded-full blur-2xl group-hover:bg-primary/10 transition-colors"></div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-label-caps font-label-caps text-on-surface-variant mb-1">Itens Ativos</p>
                        <h3 class="text-3xl font-bold font-headline-md text-primary tracking-tight font-data-tabular" x-text="lista.filter(c=>c.ativo=='1').length"></h3>
                    </div>
                    <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </span>
                </div>
                <p class="text-[9px] font-label-caps text-on-surface-variant mt-3">Custos sendo considerados</p>
            </div>
        </div>

        <!-- Tabela -->
        <div class="glass-card rounded-xl overflow-hidden mb-6">
            <div class="grid grid-cols-[2fr_1fr_1fr_1fr_80px] items-center px-6 py-4 bg-surface-container-low border-b border-outline-variant/20">
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Nome</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Categoria</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Recorrência</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Valor</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px] text-right">Ações</span>
            </div>

            <template x-if="carregando">
                <div class="p-10 text-center text-on-surface-variant">Carregando...</div>
            </template>

            <template x-if="!carregando && lista.length === 0">
                <div class="p-10 text-center text-on-surface-variant">
                    <i data-lucide="receipt" class="w-8 h-8 mx-auto mb-3 opacity-40"></i>
                    Nenhum custo fixo cadastrado
                </div>
            </template>

            <div class="divide-y divide-outline-variant/20">
                <template x-for="c in lista" :key="c.id">
                    <div class="grid grid-cols-[2fr_1fr_1fr_1fr_80px] items-center px-6 py-4 hover:bg-surface-container-high/20 transition-colors group" :class="c.ativo=='0' ? 'opacity-40' : ''">
                        <div class="min-w-0">
                            <span class="text-sm font-bold text-on-surface truncate" x-text="c.nome"></span>
                        </div>
                        <div>
                            <span class="px-2 py-0.5 rounded-full text-[9px] font-label-caps border bg-primary/10 text-primary border-primary/20 inline-block w-fit text-center" x-text="labelCategoria(c.categoria)"></span>
                        </div>
                        <div>
                            <span class="text-xs text-on-surface-variant font-label-caps" x-text="c.recorrencia === 'anual' ? 'Anual' : 'Mensal'"></span>
                        </div>
                        <div>
                            <span class="text-sm font-data-tabular font-bold text-on-surface" x-text="formatarMoeda(c.valor)"></span>
                        </div>
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="abrirModal(c)" class="p-1.5 text-on-surface-variant hover:text-on-surface hover:bg-surface-variant rounded transition-colors" title="Editar">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button @click="excluir(c.id)" class="p-1.5 text-error/70 hover:text-error hover:bg-error-container/10 rounded transition-colors" title="Excluir">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <p class="text-xs text-on-surface-variant mt-2">
            💡 Estes valores são usados automaticamente no cálculo de preço mínimo dos serviços e no prompt do Simulador IA.
        </p>
    </main>

    <!-- Modal -->
    <div class="modal-overlay" x-show="modalAberto" x-cloak @click.self="modalAberto=false">
        <div class="modal w-full max-w-lg p-6 bg-surface-container-low border border-outline-variant/30 rounded-2xl shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-title-sm font-headline-md font-bold text-on-surface" x-text="form.id ? 'Editar Custo Fixo' : 'Novo Custo Fixo'"></h2>
                <button @click="modalAberto=false" class="text-on-surface-variant hover:text-on-surface transition-colors">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form @submit.prevent="salvar()">
                <div class="mb-4">
                    <label class="label">Nome *</label>
                    <input class="input w-full" x-model="form.nome" required placeholder="Ex: Aluguel do escritório">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="label">Categoria *</label>
                        <select class="select w-full" x-model="form.categoria" required
                            @change="mostrarCampoCustom = form.categoria === '__custom__'; categoriaCustom = '';">
                            <option value="aluguel">Aluguel</option>
                            <option value="contabilidade">Contabilidade</option>
                            <option value="internet">Internet / Tel.</option>
                            <option value="impostos">Impostos</option>
                            <option value="folha">Folha de Pagamento</option>
                            <option value="outros">Outros</option>
                            <option value="__custom__">+ Nova categoria...</option>
                        </select>
                        <div x-show="mostrarCampoCustom" class="mt-2">
                            <input class="input w-full" x-model="categoriaCustom" placeholder="Digite o nome da categoria"
                                x-ref="inputCustom" @focus="$el.select()">
                        </div>
                    </div>
                    <div>
                        <label class="label">Recorrência *</label>
                        <select class="select w-full" x-model="form.recorrencia" required>
                            <option value="mensal">Mensal</option>
                            <option value="anual">Anual (÷12 no cálculo)</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="label">Valor (R$) *</label>
                        <input class="input w-full" type="number" step="0.01" min="0.01" x-model="form.valor" required placeholder="0,00">
                    </div>
                    <div>
                        <label class="label">Dia do vencimento *</label>
                        <input class="input w-full" type="number" min="1" max="28" x-model="form.dia_vencimento" required placeholder="Ex: 5">
                        <p class="text-[10px] text-on-surface-variant mt-1.5 font-label-caps">Todo mês neste dia</p>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="label">Forma de pagamento *</label>
                    <select class="select w-full" x-model="form.forma_pagamento" required>
                        <option value="pix">Pix</option>
                        <option value="boleto">Boleto</option>
                        <option value="debito_automatico">Débito Automático</option>
                        <option value="cartao_credito">Cartão de Crédito</option>
                        <option value="cartao_debito">Cartão de Débito</option>
                        <option value="transferencia">Transferência</option>
                        <option value="dinheiro">Dinheiro</option>
                    </select>
                </div>
                
                <div class="bg-primary/5 border border-primary/20 rounded-xl p-4 mb-6 text-xs text-primary flex items-start gap-2">
                    <i data-lucide="info" class="w-4 h-4 shrink-0 mt-0.5"></i>
                    <span>Ao salvar, os lançamentos em <strong>Contas a Pagar</strong> serão gerados automaticamente para os próximos 12 meses.</span>
                </div>
                
                <div class="flex gap-3 justify-end">
                    <button type="button" class="btn-secondary" @click="modalAberto=false">Cancelar</button>
                    <button type="submit" class="btn-primary" :disabled="salvando" x-text="salvando ? 'Salvando...' : (form.id ? 'Atualizar' : 'Criar')"></button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script>
function custosFixos() {
    return {
        lista: [],
        carregando: true,
        salvando: false,
        modalAberto: false,
        form: {},
        categoriaCustom: '',
        mostrarCampoCustom: false,

        get totalMensal() {
            return this.lista
                .filter(c => c.ativo == '1')
                .reduce((s, c) => {
                    const v = parseFloat(c.valor || 0);
                    return s + (c.recorrencia === 'anual' ? v / 12 : v);
                }, 0);
        },

        async init() { await this.carregar(); },

        async carregar() {
            this.carregando = true;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/custos-fixos.php') ?>');
                this.lista = await r.json();
            } catch(e) { toast('Erro ao carregar', 'erro'); }
            this.carregando = false;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        abrirModal(item = null) {
            const categoriasPadrao = ['aluguel','contabilidade','internet','impostos','folha','outros'];
            const cat = item?.categoria || 'outros';
            const ehPadrao = categoriasPadrao.includes(cat);
            this.form = item ? { ...item } : { nome: '', categoria: 'outros', recorrencia: 'mensal', valor: '', dia_vencimento: '5', forma_pagamento: 'pix', ativo: '1' };
            this.categoriaCustom = ehPadrao ? '' : cat;
            this.mostrarCampoCustom = !ehPadrao;
            if (!ehPadrao) this.form.categoria = '__custom__';
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async salvar() {
            this.salvando = true;
            // Resolver categoria: custom ou selecionada
            const payload = { ...this.form };
            if (this.mostrarCampoCustom) {
                if (!this.categoriaCustom.trim()) {
                    toast('Digite o nome da nova categoria', 'aviso');
                    this.salvando = false; return;
                }
                payload.categoria = this.categoriaCustom.trim().toLowerCase();
            }
            try {
                const metodo = payload.id ? 'PUT' : 'POST';
                const r = await fetch('<?= raizUrl('/api/financeiro/custos-fixos.php') ?>', {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await r.json();
                if (r.ok) {
                    toast('Custo salvo!', 'sucesso');
                    this.modalAberto = false;
                    await this.carregar();
                } else {
                    toast(res.erro || 'Erro ao salvar', 'erro');
                }
            } catch(e) { toast(e.message || 'Erro de conexão', 'erro'); }
            this.salvando = false;
        },

        async excluir(id) {
            if (!confirm('Excluir este custo fixo?')) return;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/custos-fixos.php') ?>?id=' + id, { method: 'DELETE' });
                if (r.ok) { toast('Custo excluído', 'sucesso'); await this.carregar(); }
                else { toast('Erro ao excluir', 'erro'); }
            } catch(e) { toast('Erro de conexão', 'erro'); }
        },

        labelCategoria(cat) {
            const map = { aluguel:'Aluguel', contabilidade:'Contabilidade', internet:'Internet/Tel.', impostos:'Impostos', folha:'Folha', outros:'Outros' };
            return map[cat] || (cat ? cat.charAt(0).toUpperCase() + cat.slice(1) : '—');
        },

        formatarMoeda(val) { return window.formatarMoeda(val); },
    };
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
