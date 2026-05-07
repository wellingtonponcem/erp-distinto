<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID da proposta não informado.");
}

$db = Database::get();

// Buscar proposta
$stmt = $db->prepare("SELECT * FROM propostas WHERE id = ?");
$stmt->execute([$id]);
$proposta = $stmt->fetch();

if (!$proposta) {
    die("Proposta não encontrada.");
}

$dadosJson = json_decode($proposta['dados_json'], true);

// Buscar clientes
$stmtClientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
$clientes = $stmtClientes->fetchAll();

// Buscar serviços (incluindo periodicidade e preço pontual)
$stmtServicos = $db->query("SELECT id, nome, descricao, preco_venda, preco_venda_pontual, periodicidade FROM servicos ORDER BY nome ASC");
$servicos = $stmtServicos->fetchAll();
$servicosJson = json_encode($servicos);

$tituloPagina = 'Editar Proposta - ' . $proposta['cliente_nome'];
include __DIR__ . '/../includes/layout/head.php';
?>

<style>
    .is-modal-layout #main-content {
        margin-left: 0 !important;
        padding-top: 0 !important;
        background: transparent !important;
    }
    .is-modal-layout .page-title {
        font-size: 1.5rem;
    }
</style>

<?php 
$isModal = ($_GET['layout'] ?? '') === 'modal';
?>

<div id="app-wrapper" class="<?= $isModal ? 'is-modal-layout' : '' ?>">
    <?php if (!$isModal) include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet <?= $isModal ? 'p-0' : '' ?>">
        <?php if (!$isModal): ?>
        <div class="app-topbar">
            <div class="top-nav">
                <a href="<?= raizUrl('/dashboard.php') ?>">Visão Geral</a>
                <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>">Propostas</a>
                <a href="#" class="active">Editar Proposta</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="<?= $isModal ? 'px-8 pt-8 mb-6' : 'mb-8' ?>">
            <h1 class="page-title text-2xl">Editar Proposta</h1>
            <p class="page-subtitle text-zinc-500">Ajuste os dados da proposta gerada para <?= sanitizar($proposta['cliente_nome']) ?>.</p>
        </div>

        <form id="formAtualizarProposta" x-data="proposta" x-init="initEdit()" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6 <?= $isModal ? 'px-8 pb-12' : '' ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            
            <div class="lg:col-span-2 space-y-6">
                <section class="card p-6">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Informações Básicas</h3>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div class="form-group">
                            <label class="label">Cliente</label>
                            <input type="text" class="input bg-zinc-50 text-zinc-500" value="<?= sanitizar($proposta['cliente_nome']) ?>" readonly>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="label">Responsável(is)</label>
                                <input type="text" name="responsavel" class="input" x-model="responsavel" placeholder="Ex: João Silva ou João e Maria">
                            </div>
                            <div class="form-group">
                                <label class="label">WhatsApp do Cliente</label>
                                <input type="text" name="whatsapp" class="input" x-model="whatsapp" placeholder="Ex: 27999998888">
                            </div>
                            <div class="form-group">
                                <label class="label">Tipo de Serviço</label>
                                <select name="tipo" id="tipoProposta" class="input" required x-model="tipoProposta" disabled>
                                    <option value="marketing">Marketing Digital</option>
                                    <option value="casamento">Casamento</option>
                                    <option value="15anos">15 Anos</option>
                                    <option value="filmmaker">Filmmaker (Cinematic)</option>
                                </select>
                                <p class="text-[10px] text-zinc-400 mt-1">O tipo não pode ser alterado após a criação.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="label">Título da Proposta</label>
                                <input type="text" name="titulo" class="input" value="<?= sanitizar($proposta['titulo']) ?>" maxlength="100" required>
                            </div>
                            <div class="form-group">
                                <label class="label">Subtítulo (Opcional)</label>
                                <input type="text" name="subtitulo" class="input" value="<?= sanitizar($proposta['subtitulo']) ?>" placeholder="Ex: Planejamento Estratégico Q3">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="form-group">
                                <label class="label">Subtotal (R$)</label>
                                <input type="number" step="0.01" class="input bg-zinc-50 font-bold text-zinc-500" readonly :value="valorSubtotal">
                            </div>
                            <div class="form-group">
                                <label class="label">Desconto</label>
                                <div class="flex">
                                    <input type="number" step="0.01" name="desconto_valor" class="input rounded-r-none border-r-0" x-model="descontoValor" @input="recalcularTotal()">
                                    <select name="desconto_tipo" class="input rounded-l-none w-16 px-1 text-center font-bold bg-zinc-50" x-model="descontoTipo" @change="recalcularTotal()">
                                        <option value="porcentagem">%</option>
                                        <option value="fixo">R$</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="label">Valor Total (Final)</label>
                                <input type="number" step="0.01" name="valor_total" class="input bg-zinc-100 font-bold text-zinc-900 border-zinc-300" required readonly x-model="valorTotal">
                            </div>
                            <div class="form-group">
                                <label class="label">Tempo de Contrato</label>
                                <input type="number" name="meses_contrato" class="input" x-model="mesesContrato">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="label">Forma de Pagamento</label>
                                <select name="forma_pagamento" class="input" x-model="formaPagamento">
                                    <option value="boleto_pix">Boleto / PIX</option>
                                    <option value="cartao">Cartão de Crédito (+2,13%)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="label">Status</label>
                                <select name="status" class="input" x-model="statusProposta">
                                    <option value="rascunho">Rascunho</option>
                                    <option value="pendente">Pendente</option>
                                    <option value="aceita">Aceita</option>
                                    <option value="recusada">Recusada</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Conteúdo Gerado pela IA (Editável) -->
                <section class="card p-6 border-zinc-200">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Conteúdo Gerado (IA)</h3>
                    <div class="space-y-6">
                        <template x-for="(content, key) in secoes" :key="key">
                            <div class="form-group">
                                <label class="label uppercase tracking-wider text-[10px]" x-text="key"></label>
                                <textarea :name="'secoes['+key+']'" class="input min-h-[150px] text-sm leading-relaxed" x-model="secoes[key]"></textarea>
                            </div>
                        </template>
                    </div>
                </section>

                <section class="card p-6" id="sectionServicos">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-zinc-900">Serviços Inclusos</h3>
                        <button type="button" @click="adicionarServico()" class="text-[10px] bg-zinc-900 text-white px-3 py-1.5 rounded-lg font-bold hover:bg-zinc-800 transition-all flex items-center gap-1">
                            <i data-lucide="plus" class="w-3 h-3"></i> Adicionar Serviço
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(item, index) in servicosSelecionados" :key="index">
                            <div class="p-4 border border-zinc-100 rounded-xl bg-zinc-50/50 relative group animate-fade-in">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                    <!-- Seleção de Serviço -->
                                    <div class="md:col-span-4">
                                        <label class="text-[10px] font-bold text-zinc-500 uppercase mb-1 block">Serviço</label>
                                        <select :name="'servicos['+index+'][id]'" class="input py-2" x-model="item.id" @change="atualizarDadosServico(index)">
                                            <option value="">Selecione um serviço...</option>
                                            <template x-for="s in catalogoServicos" :key="s.id">
                                                <option :value="s.id" x-text="s.nome"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <!-- Tipo de Cobrança -->
                                    <div class="md:col-span-3">
                                        <label class="text-[10px] font-bold text-zinc-500 uppercase mb-1 block">Modo de Cobrança</label>
                                        <select :name="'servicos['+index+'][tipo_cobranca]'" class="input py-2" x-model="item.tipo_cobranca" @change="recalcularTotal()">
                                            <option value="recorrente">Recorrente (Mensal)</option>
                                            <option value="pontual">Pontual (Única/Frequência)</option>
                                        </select>
                                    </div>

                                    <!-- Frequência (Se Pontual) -->
                                    <div class="md:col-span-2" x-show="item.tipo_cobranca === 'pontual'">
                                        <label class="text-[10px] font-bold text-zinc-500 uppercase mb-1 block">Frequência/Mês</label>
                                        <input type="number" :name="'servicos['+index+'][frequencia]'" class="input py-2" x-model="item.frequencia" @input="recalcularTotal()" min="1" placeholder="Ex: 1">
                                    </div>

                                    <!-- Valor Base -->
                                    <div class="md:col-span-2">
                                        <label class="text-[10px] font-bold text-zinc-500 uppercase mb-1 block" x-text="item.tipo_cobranca === 'pontual' ? 'Valor Único' : 'Valor Mensal'"></label>
                                        <input type="number" step="0.01" :name="'servicos['+index+'][valor]'" class="input py-2 font-bold" x-model="item.valor" @input="recalcularTotal()">
                                    </div>

                                    <!-- Botão Remover -->
                                    <div class="md:col-span-1 flex items-end justify-end">
                                        <button type="button" @click="removerServico(index)" class="bg-red-50 text-red-500 p-2 rounded-lg hover:bg-red-100 transition-colors">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Resumo do Cálculo -->
                                <div class="mt-3 pt-3 border-t border-zinc-200/50 flex items-center justify-between">
                                    <div class="flex gap-4">
                                        <p class="text-[10px] text-zinc-400 italic" x-show="item.tipo_cobranca === 'pontual' && item.frequencia <= 1">
                                            * Valor único diluído em <span x-text="mesesContrato"></span> meses de contrato.
                                        </p>
                                        <p class="text-[10px] text-zinc-400 italic" x-show="item.tipo_cobranca === 'pontual' && item.frequencia > 1">
                                            * Frequência mensal detectada. Usando valor de contrato.
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] font-bold text-zinc-400 uppercase">Total Mensal:</span>
                                        <span class="text-xs font-bold text-zinc-900" x-text="new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(item.valor_mensal || 0)"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <section class="card p-6">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Estratégia & Cronograma</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="form-group">
                            <label class="label">Data Prevista de Início</label>
                            <input type="date" name="data_inicio" class="input" x-model="dataInicio">
                        </div>
                        <div class="form-group">
                            <label class="label">Validade da Proposta</label>
                            <input type="date" name="validade" class="input" value="<?= $proposta['validade'] ?>">
                        </div>
                    </div>

                    <div class="border-t border-zinc-100 pt-4" x-show="tipoProposta === 'marketing'">
                        <label class="label mb-3 block">Etapas Visíveis no Cronograma</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <template x-for="etapa in etapasDisponiveis" :key="etapa.id">
                                <label class="flex items-center gap-3 p-3 border border-zinc-100 rounded-xl cursor-pointer hover:bg-zinc-50 transition-all" :class="etapasAtivas.includes(etapa.id) ? 'bg-emerald-50/50 border-emerald-100' : ''">
                                    <input type="checkbox" name="etapas_ativas[]" :value="etapa.id" x-model="etapasAtivas" class="w-4 h-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                    <span class="text-xs font-semibold text-zinc-700" x-text="etapa.label"></span>
                                </label>
                            </template>
                        </div>
                        <p class="text-[10px] text-zinc-400 mt-3 italic">* Desmarque as etapas que o cliente já possui ou que não fazem parte deste escopo.</p>
                    </div>
                </section>

                <section class="card p-6">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Opção Adicional (Upsell)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="form-group">
                            <label class="label">Título da Opção</label>
                            <input type="text" name="adicional_titulo" class="input" x-model="adicional.titulo">
                        </div>
                        <div class="form-group">
                            <label class="label">Valor da Opção (R$/mês)</label>
                            <input type="number" step="0.01" name="adicional_valor" class="input" x-model="adicional.valor">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="label">Descrição da Opção</label>
                        <textarea name="adicional_descricao" class="input min-h-[80px]" x-model="adicional.descricao"></textarea>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section class="card p-6 bg-zinc-900 text-white shadow-xl shadow-zinc-900/20 border-0">
                    <h3 class="text-sm font-bold mb-4 opacity-80">Ações</h3>
                    <button type="submit" id="btnSalvar" class="w-full h-12 rounded-xl font-bold bg-white text-black hover:bg-zinc-100 transition-all flex items-center justify-center gap-2 group !text-black">
                        <i data-lucide="save" class="w-5 h-5 text-zinc-500 group-hover:text-black transition-colors !text-zinc-900"></i>
                        Salvar Alterações
                    </button>
                    <a href="<?= raizUrl('/p/' . $proposta['slug']) ?>" target="_blank" class="w-full mt-3 h-12 rounded-xl font-bold bg-zinc-800 text-white hover:bg-zinc-700 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="external-link" class="w-5 h-5"></i>
                        Visualizar Atual
                    </a>
                </section>

                <div id="statusSalvar" class="hidden animate-fade-in">
                    <div class="p-4 bg-emerald-500/10 border border-emerald-500 text-emerald-500 rounded-xl text-center text-sm font-bold">
                        Alterações salvas com sucesso!
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('proposta', () => ({
        catalogoServicos: <?= $servicosJson ?>,
        servicosSelecionados: [],
        valorSubtotal: 0,
        descontoValor: 0,
        descontoTipo: 'porcentagem',
        valorTotal: 0,
        tipoProposta: '<?= $proposta['tipo'] ?>',
        mesesContrato: 12,
        formaPagamento: 'boleto_pix',
        statusProposta: '<?= $proposta['status'] ?>',
        dataInicio: '',
        responsavel: '',
        whatsapp: '',
        adicional: { titulo: '', valor: 0, descricao: '' },
        secoes: {},
        etapasDisponiveis: [
            { id: 'imersao', label: 'Imersão' },
            { id: 'diagnostico', label: 'Diagnóstico' },
            { id: 'planejamento', label: 'Planejamento' },
            { id: 'linguagem_visual', label: 'Linguagem Visual' },
            { id: 'entrega', label: 'Entrega' },
            { id: 'gestao', label: 'Gestão' }
        ],
        etapasAtivas: [],

        initEdit() {
            // Carregar dados existentes
            const dados = <?= json_encode($dadosJson) ?>;
            this.secoes = dados.secoes || {};
            this.responsavel = dados.responsavel || '';
            this.whatsapp = dados.whatsapp || '';
            
            // Mapeia os serviços garantindo que o ID seja encontrado
            this.servicosSelecionados = dados.servicos ? dados.servicos.map(s => {
                const servicoEncontrado = this.catalogoServicos.find(cat => 
                    (s.id && cat.id == s.id) || 
                    (cat.nome && s.nome && cat.nome.trim().toUpperCase() === s.nome.trim().toUpperCase())
                );
                
                return { 
                    id: servicoEncontrado ? servicoEncontrado.id : (s.id || ''), 
                    valor: parseFloat(s.valor_individual || 0),
                    tipo_cobranca: s.tipo_cobranca || (servicoEncontrado && servicoEncontrado.periodicidade === 'pontual' ? 'pontual' : 'recorrente'),
                    frequencia: parseInt(s.frequencia) || 1,
                    valor_mensal: 0
                };
            }) : [];

            this.mesesContrato = parseInt(dados.meses_contrato) || 12;
            this.formaPagamento = dados.forma_pagamento || 'boleto_pix';
            this.dataInicio = dados.data_inicio || '';
            this.adicional = dados.adicional || { titulo: '', valor: 0, descricao: '' };
            
            // Força o Alpine a processar os dados e depois recalcula
            this.$nextTick(() => {
                this.recalcularTotal();
                if (window.lucide) lucide.createIcons();
            });
            
            // Carregar etapas ativas ou padrão (todas ativas se for nova ou antiga sem esse campo)
            if (dados.etapas_ativas && Array.isArray(dados.etapas_ativas) && dados.etapas_ativas.length > 0) {
                this.etapasAtivas = dados.etapas_ativas;
            } else {
                this.etapasAtivas = this.etapasDisponiveis.map(e => e.id);
            }

            this.valorTotal = <?= (float)$proposta['valor_total'] ?>;
            
            // Recalcular subtotal
            this.recalcularTotal();
            
            // Tentar inferir desconto se o total for diferente do subtotal
            const sub = this.valorSubtotal;
            if (sub > 0 && this.valorTotal < sub) {
                this.descontoValor = sub - this.valorTotal;
                this.descontoTipo = 'fixo';
            }

            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        adicionarServico() {
            this.servicosSelecionados.push({ id: '', valor: 0, tipo_cobranca: 'recorrente', frequencia: 1, valor_mensal: 0 });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        removerServico(index) {
            this.servicosSelecionados.splice(index, 1);
            this.recalcularTotal();
        },

        atualizarDadosServico(index) {
            const item = this.servicosSelecionados[index];
            const servico = this.catalogoServicos.find(s => s.id == item.id);
            if (servico) {
                // Seta o tipo baseado no padrão do catálogo se for novo
                item.tipo_cobranca = servico.periodicidade === 'pontual' ? 'pontual' : 'recorrente';
                
                if (item.tipo_cobranca === 'pontual') {
                    item.valor = parseFloat(servico.preco_venda_pontual || servico.preco_venda || 0);
                } else {
                    item.valor = parseFloat(servico.preco_venda || 0);
                }
            }
            this.recalcularTotal();
        },

        recalcularTotal() {
            const meses = parseInt(this.mesesContrato) || 1;
            
            this.servicosSelecionados.forEach(item => {
                const servico = this.catalogoServicos.find(s => s.id == item.id);
                const precoRecorrente = servico ? parseFloat(servico.preco_venda || 0) : 0;
                const precoPontual = servico ? parseFloat(servico.preco_venda_pontual || 0) : item.valor;
                const freq = parseInt(item.frequencia) || 1;

                if (item.tipo_cobranca === 'pontual') {
                    if (freq > 1) {
                        // Se tem recorrência (ex: 2x/mês), usa o preço de contrato * frequência
                        item.valor_mensal = precoRecorrente * freq;
                    } else {
                        // Se é pontual único, divide o valor total pelos meses de contrato
                        item.valor_mensal = parseFloat(item.valor) / meses;
                    }
                } else {
                    // Recorrente padrão
                    item.valor_mensal = parseFloat(item.valor);
                }
            });

            this.valorSubtotal = this.servicosSelecionados.reduce((acc, curr) => acc + (curr.valor_mensal || 0), 0);
            let desconto = 0;
            const sub = parseFloat(this.valorSubtotal || 0);
            const desc = parseFloat(this.descontoValor || 0);
            if (this.descontoTipo === 'porcentagem') desconto = sub * (desc / 100);
            else desconto = desc;
            this.valorTotal = Math.max(0, sub - desconto);
        }
    }));
});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formAtualizarProposta');
    const btnSalvar = document.getElementById('btnSalvar');
    const statusDiv = document.getElementById('statusSalvar');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        btnSalvar.disabled = true;
        btnSalvar.innerHTML = '<i class="w-4 h-4 animate-spin"></i> Salvando...';
        
        const formData = new FormData(form);
        
        try {
            const response = await fetch('<?= raizUrl('/api/propostas/atualizar.php') ?>', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                statusDiv.classList.remove('hidden');
                setTimeout(() => statusDiv.classList.add('hidden'), 3000);
            } else {
                alert('Erro: ' + (result.erro || 'Falha ao salvar.'));
            }
        } catch (error) {
            alert('Erro na comunicação com o servidor.');
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = '<i data-lucide="save" class="w-5 h-5 text-zinc-500"></i> Salvar Alterações';
            if (window.lucide) lucide.createIcons();
        }
    });
});
</script>

<?php if (!$isModal) include __DIR__ . '/../includes/layout/footer.php'; ?>
