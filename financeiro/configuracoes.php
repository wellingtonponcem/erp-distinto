<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
exigirAutenticacao();
$tituloPagina = 'Custos Fixos';
include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;" x-data="custosFixos()">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" style="flex:1; padding:28px 32px; overflow-y:auto; max-width:calc(100vw - 240px);">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:28px;">
            <div>
                <h1 style="font-size:22px; font-weight:700; color:#f1f5f9;">Custos Fixos</h1>
                <p style="font-size:14px; color:#6b7280; margin-top:2px;">Despesas mensais recorrentes da agência</p>
            </div>
            <button class="btn-primary" @click="abrirModal()">
                <i data-lucide="plus" style="width:15px;height:15px;"></i> Novo Custo
            </button>
        </div>

        <!-- Card Resumo -->
        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px;">
            <div class="card" style="padding:20px;">
                <div style="font-size:12px; color:#6b7280; margin-bottom:8px;">Total Mensal</div>
                <div style="font-size:24px; font-weight:700; color:#ef4444;" x-text="formatarMoeda(totalMensal)"></div>
                <div style="font-size:12px; color:#6b7280; margin-top:4px;">Custos mensais + anuais ÷ 12</div>
            </div>
            <div class="card" style="padding:20px;">
                <div style="font-size:12px; color:#6b7280; margin-bottom:8px;">Custo Anual</div>
                <div style="font-size:24px; font-weight:700; color:#f59e0b;" x-text="formatarMoeda(totalMensal * 12)"></div>
                <div style="font-size:12px; color:#6b7280; margin-top:4px;">Projeção anual dos custos</div>
            </div>
            <div class="card" style="padding:20px;">
                <div style="font-size:12px; color:#6b7280; margin-bottom:8px;">Itens Ativos</div>
                <div style="font-size:24px; font-weight:700; color:#a78bfa;" x-text="lista.filter(c=>c.ativo=='1').length"></div>
                <div style="font-size:12px; color:#6b7280; margin-top:4px;">Custos sendo considerados</div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="card" style="overflow:hidden;">
            <div class="table-header" style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr 80px;">
                <span>Nome</span><span>Categoria</span><span>Recorrência</span><span>Valor</span><span style="text-align:right;">Ações</span>
            </div>

            <template x-if="carregando">
                <div style="padding:40px; text-align:center; color:#4b5563;">Carregando...</div>
            </template>

            <template x-if="!carregando && lista.length === 0">
                <div style="padding:40px; text-align:center; color:#4b5563;">
                    <i data-lucide="receipt" style="width:36px;height:36px;margin:0 auto 12px;display:block;opacity:0.4;"></i>
                    Nenhum custo fixo cadastrado
                </div>
            </template>

            <template x-for="c in lista" :key="c.id">
                <div class="table-row" style="display:grid; grid-template-columns:2fr 1fr 1fr 1fr 80px; align-items:center;" :style="c.ativo=='0' ? 'opacity:0.5' : ''">
                    <div class="table-cell">
                        <span style="color:#e2e8f0; font-weight:500;" x-text="c.nome"></span>
                    </div>
                    <div class="table-cell">
                        <span class="badge" style="background:rgba(124,58,237,0.15); color:#a78bfa; border:1px solid rgba(124,58,237,0.3);" x-text="labelCategoria(c.categoria)"></span>
                    </div>
                    <div class="table-cell" style="color:#94a3b8;" x-text="c.recorrencia === 'anual' ? 'Anual' : 'Mensal'"></div>
                    <div class="table-cell" style="color:#f1f5f9; font-weight:600;" x-text="formatarMoeda(c.valor)"></div>
                    <div class="table-cell" style="display:flex; gap:6px; justify-content:flex-end;">
                        <button @click="abrirModal(c)" style="color:#6b7280; background:none; border:none; cursor:pointer; padding:4px;">
                            <i data-lucide="pencil" style="width:16px;height:16px;"></i>
                        </button>
                        <button @click="excluir(c.id)" style="color:#6b7280; background:none; border:none; cursor:pointer; padding:4px;">
                            <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <p style="font-size:12px; color:#4b5563; margin-top:12px;">
            💡 Estes valores são usados automaticamente no cálculo de preço mínimo dos serviços e no prompt do Simulador IA.
        </p>
    </main>

    <!-- Modal -->
    <div class="modal-overlay" x-show="modalAberto" x-cloak>
        <div class="modal" style="max-width:500px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <h2 style="font-size:17px; font-weight:600; color:#f1f5f9;" x-text="form.id ? 'Editar Custo Fixo' : 'Novo Custo Fixo'"></h2>
                <button @click="modalAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>
            <form @submit.prevent="salvar()">
                <div style="margin-bottom:16px;">
                    <label class="label">Nome *</label>
                    <input class="input" x-model="form.nome" required placeholder="Ex: Aluguel do escritório">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Categoria *</label>
                        <select class="select" x-model="form.categoria" required
                            @change="mostrarCampoCustom = form.categoria === '__custom__'; categoriaCustom = '';">
                            <option value="aluguel">Aluguel</option>
                            <option value="contabilidade">Contabilidade</option>
                            <option value="internet">Internet / Tel.</option>
                            <option value="impostos">Impostos</option>
                            <option value="folha">Folha de Pagamento</option>
                            <option value="outros">Outros</option>
                            <option value="__custom__">+ Nova categoria...</option>
                        </select>
                        <div x-show="mostrarCampoCustom" style="margin-top:8px;">
                            <input class="input" x-model="categoriaCustom" placeholder="Digite o nome da categoria"
                                x-ref="inputCustom" @focus="$el.select()">
                        </div>
                    </div>
                    <div>
                        <label class="label">Recorrência *</label>
                        <select class="select" x-model="form.recorrencia" required>
                            <option value="mensal">Mensal</option>
                            <option value="anual">Anual (÷12 no cálculo)</option>
                        </select>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Valor (R$) *</label>
                        <input class="input" type="number" step="0.01" min="0.01" x-model="form.valor" required placeholder="0,00">
                    </div>
                    <div>
                        <label class="label">Dia do vencimento *</label>
                        <input class="input" type="number" min="1" max="28" x-model="form.dia_vencimento" required placeholder="Ex: 5">
                        <p style="font-size:11px;color:#6b7280;margin-top:4px;">Todo mês neste dia</p>
                    </div>
                </div>
                <div style="margin-bottom:24px;">
                    <label class="label">Forma de pagamento *</label>
                    <select class="select" x-model="form.forma_pagamento" required>
                        <option value="pix">Pix</option>
                        <option value="boleto">Boleto</option>
                        <option value="debito_automatico">Débito Automático</option>
                        <option value="cartao_credito">Cartão de Crédito</option>
                        <option value="cartao_debito">Cartão de Débito</option>
                        <option value="transferencia">Transferência</option>
                        <option value="dinheiro">Dinheiro</option>
                    </select>
                </div>
                <div style="background:rgba(124,58,237,0.08); border:1px solid rgba(124,58,237,0.2); border-radius:8px; padding:12px 14px; margin-bottom:20px; font-size:13px; color:#a78bfa;">
                    <i data-lucide="info" style="width:13px;height:13px;vertical-align:middle;margin-right:6px;"></i>
                    Ao salvar, os lançamentos em <strong>Contas a Pagar</strong> serão gerados automaticamente para os próximos 12 meses.
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn-secondary" @click="modalAberto=false">Cancelar</button>
                    <button type="submit" class="btn-primary" :disabled="salvando" x-text="salvando ? 'Salvando...' : (form.id ? 'Atualizar' : 'Criar')"></button>
                </div>
            </form>
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
