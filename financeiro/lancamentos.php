<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
exigirAutenticacao();
$tituloPagina = 'Lançamentos';
$db = Database::get();
$clientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome ASC")->fetchAll();
$fornecedores = $db->query("SELECT id, nome FROM fornecedores ORDER BY nome ASC")->fetchAll();
include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;" x-data="lancamentos()" x-effect="lancamentosFiltrados; $nextTick(() => { if (window.lucide) lucide.createIcons(); })">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" style="flex:1; padding:28px 32px; overflow-y:auto; max-width:calc(100vw - 240px);">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <!-- Header -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:28px;">
            <div>
                <h1 style="font-size:22px; font-weight:700; color:#f1f5f9;">Lançamentos</h1>
                <p style="font-size:14px; color:#6b7280; margin-top:2px;">Contas a pagar e a receber</p>
            </div>
            <div style="display:flex; gap:10px;">
                <input type="file" x-ref="ofxInput" @change="uploadOfx($event)" style="opacity:0; position:absolute; width:1px; height:1px; z-index:-1;" accept=".ofx,.OFX">
                <button class="btn-secondary" @click="$refs.ofxInput.click()" style="color:#6366f1; border-color:rgba(99,102,241,0.3);" :disabled="uploadingOfx">
                    <span x-show="!uploadingOfx"><i data-lucide="file-up" style="width:15px;height:15px;"></i> Importar OFX</span>
                    <span x-show="uploadingOfx">⏳ Lendo arquivo...</span>
                </button>
                <div x-show="selecionados.length > 0" style="display:flex; gap:6px;" x-cloak>
                    <button class="btn-secondary" @click="alterarStatusSelecionados('pago')" style="color:#10b981; border-color:rgba(16,185,129,0.3);">
                        <i data-lucide="check-circle" style="width:15px;height:15px;"></i> Efetivar
                    </button>
                    <button class="btn-secondary" @click="alterarStatusSelecionados('pendente')" style="color:#f59e0b; border-color:rgba(245,158,11,0.3);">
                        <i data-lucide="clock" style="width:15px;height:15px;"></i> Pendente
                    </button>
                    <button class="btn-secondary" @click="abrirEdicaoMassa()" style="color:#6366f1; border-color:rgba(99,102,241,0.3);">
                        <i data-lucide="edit-3" style="width:15px;height:15px;"></i> Em Massa
                    </button>
                    <button class="btn-secondary" @click="excluirSelecionados()" style="color:#ef4444; border-color:rgba(239,68,68,0.3);">
                        <i data-lucide="trash-2" style="width:15px;height:15px;"></i> Excluir (<span x-text="selecionados.length"></span>)
                    </button>
                </div>
                <button class="btn-primary" @click="abrirModal()">
                    <i data-lucide="plus" style="width:15px;height:15px;"></i> Novo Lançamento
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card" style="padding:16px 20px; margin-bottom:20px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
            <div style="display:flex; gap:6px;">
                <button @click="filtros.tipo=''" :class="filtros.tipo==='' ? 'btn-primary' : 'btn-secondary'" style="padding:6px 14px; font-size:13px;">Todos</button>
                <button @click="filtros.tipo='receber'" :class="filtros.tipo==='receber' ? 'btn-primary' : 'btn-secondary'" style="padding:6px 14px; font-size:13px;">A Receber</button>
                <button @click="filtros.tipo='pagar'" :class="filtros.tipo==='pagar' ? 'btn-primary' : 'btn-secondary'" style="padding:6px 14px; font-size:13px;">A Pagar</button>
            </div>
            <div style="display:flex; gap:6px; flex-wrap:wrap; width:100%;">
                <input class="input" type="text" x-model="filtros.busca" placeholder="Buscar por descrição ou cliente..." style="flex:1; min-width:200px;">
                <div style="display:flex; gap:6px;">
                    <select class="select" x-model="filtros.categoria" style="width:auto; min-width:140px;">
                        <option value="">Todas as categorias</option>
                        <template x-for="cat in categoriasDisponiveis" :key="cat">
                            <option :value="cat" x-text="cat.charAt(0).toUpperCase() + cat.slice(1)"></option>
                        </template>
                    </select>
                    <button class="btn-secondary" @click="abrirGerenciarCategorias()" style="padding:6px 10px;" title="Gerenciar Categorias">
                        <i data-lucide="settings-2" style="width:15px;height:15px;color:#94a3b8;"></i>
                    </button>
                    <select class="select" x-model="filtros.conta" style="width:auto; min-width:140px;">
                        <option value="">Todas as contas</option>
                        <option value="__null__">Sem conta informada</option>
                        <template x-for="c in contas" :key="c.id">
                            <option :value="c.id" x-text="c.nome"></option>
                        </template>
                    </select>
                </div>
                <select class="select" x-model="filtros.status" style="width:auto; min-width:140px;">
                    <option value="">Todos os status</option>
                    <option value="pendente">Pendente</option>
                    <option value="pago_parcial">Pago Parcial</option>
                    <option value="pago">Pago</option>
                    <option value="atrasado">Atrasado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
            <div class="bg-slate-50 dark:bg-zinc-900/50 border border-slate-200 dark:border-zinc-800/50" style="display:flex; gap:6px; align-items:center; flex-wrap:wrap; width:100%; padding:10px; border-radius:8px;">
                <span class="text-slate-500 dark:text-zinc-400" style="font-size:13px; font-weight:600; margin-right:4px;">
                    <i data-lucide="calendar" style="width:14px;height:14px; display:inline-block; vertical-align:middle; margin-top:-2px;"></i> Período:
                </span>
                
                <select class="select" x-model="periodoAtivo" @change="mudarModoPeriodo()" style="width:auto; min-width:130px; font-size:13px; padding:6px 10px;">
                    <option value="mes">Mês</option>
                    <option value="dia">Dia</option>
                    <option value="semana">Semana / Específico</option>
                    <option value="ano">Ano</option>
                    <option value="tudo">Todo o histórico</option>
                </select>

                <template x-if="['dia', 'mes', 'ano'].includes(periodoAtivo)">
                    <div class="bg-white dark:bg-zinc-800 border border-slate-300 dark:border-zinc-700" style="display:flex; align-items:center; gap:4px; border-radius:6px; padding:2px;">
                        <button class="text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-zinc-200" @click="deslocarPeriodo(-1)" style="padding:4px 8px; border:none; background:transparent; box-shadow:none;">
                            <i data-lucide="chevron-left" style="width:16px;height:16px;"></i>
                        </button>
                        <span class="text-slate-900 dark:text-zinc-100" style="min-width:130px; text-align:center; font-weight:600; font-size:13px;" x-text="labelPeriodo()"></span>
                        <button class="text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-zinc-200" @click="deslocarPeriodo(1)" style="padding:4px 8px; border:none; background:transparent; box-shadow:none;">
                            <i data-lucide="chevron-right" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                </template>

                <template x-if="periodoAtivo === 'semana'">
                    <div style="display:flex; align-items:center; gap:6px;">
                        <input class="input js-datepicker" type="text" x-model="filtros.data_inicio" style="width:auto; padding:6px 10px; font-size:13px;" placeholder="Início" x-init="flatpickr($el, { locale: 'pt', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y' })">
                        <span style="color:#6b7280; font-size:13px;">até</span>
                        <input class="input js-datepicker" type="text" x-model="filtros.data_fim" style="width:auto; padding:6px 10px; font-size:13px;" placeholder="Fim" x-init="flatpickr($el, { locale: 'pt', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y' })">
                    </div>
                </template>
            </div>
        </div>

        <!-- Tabela -->
        <div class="card" style="overflow:hidden;">
            <div class="table-header" style="display:grid; grid-template-columns:30px 2fr 1fr 1fr 1fr 1fr 100px; align-items:center;">
                <input type="checkbox" style="accent-color:#111" :checked="todosSelecionados" @change="toggleTodos()">
                <span>Descrição</span><span>Vencimento</span><span>Valor</span><span>Pago</span><span>Status</span><span style="text-align:right;">Ações</span>
            </div>

            <template x-if="carregando">
                <div style="padding:40px; text-align:center; color:#4b5563;">Carregando...</div>
            </template>

            <template x-if="!carregando && lancamentosFiltrados.length === 0">
                <div style="padding:40px; text-align:center; color:#4b5563;">
                    <i data-lucide="inbox" style="width:36px;height:36px;margin:0 auto 12px;display:block;opacity:0.4;"></i>
                    Nenhum lançamento encontrado
                </div>
            </template>

            <template x-for="l in lancamentosFiltrados" :key="l.id">
                <div class="table-row" style="display:grid; grid-template-columns:30px 2fr 1fr 1fr 1fr 1fr 100px; align-items:center;">
                    <div class="table-cell">
                        <input type="checkbox" :value="l.id" x-model="selecionados" style="accent-color:#111">
                    </div>
                    <div class="table-cell">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span :style="l.tipo==='receber' ? 'color:#10b981' : 'color:#ef4444'">
                                <i :data-lucide="l.tipo==='receber' ? 'arrow-down-left' : 'arrow-up-right'" style="width:14px;height:14px;"></i>
                            </span>
                            <div>
                                <div style="color:#e2e8f0; font-weight:500; cursor:pointer;" @click="abrirModal(l)" x-text="l.descricao" class="hover-underline"></div>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <template x-if="l.conta_id">
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-800 text-gray-400 font-bold border border-gray-700" 
                                              :style="'border-color:' + (contas.find(c=>c.id===l.conta_id)?.cor || '#333')"
                                              x-text="contas.find(c=>c.id===l.conta_id)?.nome"></span>
                                    </template>
                                    <div style="color:#6b7280; font-size:12px;" x-text="clientes.find(c => c.id === l.cliente_id)?.nome || fornecedores.find(f => f.id === l.fornecedor_id)?.nome || l.cliente_fornecedor || l.categoria"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-cell" style="color:#94a3b8;" x-text="formatarData(l.vencimento)"></div>
                    <div class="table-cell" style="font-weight:600;" :style="l.tipo==='receber' ? 'color:#10b981' : 'color:#ef4444'" x-text="formatarMoeda(l.valor)"></div>
                    <div class="table-cell" style="color:#94a3b8;" x-text="formatarMoeda(l.valor_pago)"></div>
                    <div class="table-cell">
                        <span class="badge" :class="classeStatus(l.status)" x-text="labelStatus(l.status)"></span>
                    </div>
                    <div class="table-cell" style="display:flex; gap:6px; justify-content:flex-end;">
                        <button @click="abrirBaixa(l)" title="Registrar pagamento" style="color:#a78bfa; background:none; border:none; cursor:pointer; padding:4px;">
                            <i data-lucide="check-circle" style="width:16px;height:16px;"></i>
                        </button>
                        <button @click="abrirModal(l)" title="Editar" style="color:#6b7280; background:none; border:none; cursor:pointer; padding:4px;">
                            <i data-lucide="pencil" style="width:16px;height:16px;"></i>
                        </button>
                        <button @click="excluir(l.id)" title="Excluir" style="color:#6b7280; background:none; border:none; cursor:pointer; padding:4px;">
                            <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Resumo rodapé -->
        <div style="display:flex; gap:16px; margin-top:16px; flex-wrap:wrap;">
            <div style="font-size:13px; color:#6b7280;">
                Total a receber: <strong style="color:#10b981;" x-text="formatarMoeda(totalReceber)"></strong>
            </div>
            <div style="font-size:13px; color:#6b7280;">
                Total a pagar: <strong style="color:#ef4444;" x-text="formatarMoeda(totalPagar)"></strong>
            </div>
        </div>
    </main>

    <!-- Modal Novo/Editar Lançamento -->
    <div class="modal-overlay" x-show="modalAberto" x-cloak>
        <div class="modal">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <h2 style="font-size:17px; font-weight:600; color:#f1f5f9;" x-text="form.id ? 'Editar Lançamento' : 'Novo Lançamento'"></h2>
                <button @click="modalAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <form @submit.prevent="salvar()">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Tipo *</label>
                        <select class="select" x-model="form.tipo" required>
                            <option value="receber">A Receber</option>
                            <option value="pagar">A Pagar</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Modalidade *</label>
                        <select class="select" x-model="form.modalidade" required>
                            <option value="avista">À Vista</option>
                            <option value="parcelado">Parcelado</option>
                            <option value="recorrente">Recorrente</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Descrição *</label>
                    <input class="input" x-model="form.descricao" required maxlength="500" placeholder="Ex: Honorários cliente ABC">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Valor (R$) *</label>
                        <input class="input" type="number" step="0.01" min="0.01" x-model="form.valor" required>
                    </div>
                    <div>
                        <label class="label">Vencimento *</label>
                        <input class="input js-datepicker" type="text" x-model="form.vencimento" required placeholder="Selecione" x-init="flatpickr($el, { locale: 'pt', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y' })">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Categoria</label>
                        <select class="select" x-model="form.categoria" @change="mostrarCampoCustom = form.categoria === '__custom__'; categoriaCustom = '';">
                            <option value="">Selecione...</option>
                            <template x-for="cat in categoriasDisponiveis" :key="cat">
                                <option :value="cat" x-text="cat.charAt(0).toUpperCase() + cat.slice(1)"></option>
                            </template>
                            <option value="__custom__" style="font-weight:bold; color:#6366f1;">+ Nova categoria...</option>
                        </select>
                        <div x-show="mostrarCampoCustom" style="margin-top:8px;">
                            <input class="input" x-model="categoriaCustom" placeholder="Digite a categoria">
                        </div>
                    </div>
                    <div>
                        <label class="label">Cliente</label>
                        <select class="select" x-model="form.cliente_id">
                            <option value="">— Selecione um cliente —</option>
                            <template x-for="c in clientes" :key="c.id">
                                <option :value="c.id" x-text="c.nome"></option>
                            </template>
                        </select>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Fornecedor</label>
                        <select class="select" x-model="form.fornecedor_id">
                            <option value="">— Selecione um fornecedor —</option>
                            <template x-for="f in fornecedores" :key="f.id">
                                <option :value="f.id" x-text="f.nome"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="label">Cliente / Fornecedor (Texto)</label>
                        <input class="input" x-model="form.cliente_fornecedor" placeholder="Nome ou descrição">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Conta Bancária</label>
                    <select class="select" x-model="form.conta_id">
                        <option value="">— Selecione o Banco —</option>
                        <template x-for="c in contas" :key="c.id">
                            <option :value="c.id" x-text="c.nome"></option>
                        </template>
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Forma de Pagamento</label>
                    <select class="select" x-model="form.forma_pagamento">
                        <option value="">— Não informado —</option>
                        <option value="pix">Pix</option>
                        <option value="boleto">Boleto</option>
                        <option value="debito_automatico">Débito Automático</option>
                        <option value="cartao_credito">Cartão de Crédito</option>
                        <option value="cartao_debito">Cartão de Débito</option>
                        <option value="transferencia">Transferência</option>
                        <option value="dinheiro">Dinheiro</option>
                    </select>
                </div>

                <!-- Parcelado -->
                <div x-show="form.modalidade==='parcelado'" style="margin-bottom:16px;">
                    <label class="label">Número de Parcelas</label>
                    <input class="input" type="number" min="2" max="120" x-model="form.total_parcelas" placeholder="Ex: 12">
                </div>

                <!-- Recorrente -->
                <div x-show="form.modalidade==='recorrente'" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Frequência</label>
                        <select class="select" x-model="form.frequencia">
                            <option value="semanal">Semanal</option>
                            <option value="mensal">Mensal</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Até (opcional)</label>
                        <input class="input js-datepicker" type="text" x-model="form.data_termino" placeholder="Opcional" x-init="flatpickr($el, { locale: 'pt', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y' })">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Observação</label>
                    <textarea class="input" x-model="form.observacao" rows="2" placeholder="Opcional" style="resize:vertical;"></textarea>
                </div>

                <!-- Checkbox custo fixo — para contas a pagar -->
                <div x-show="form.tipo === 'pagar' && !form.custo_fixo_id" style="background:#f7f7f7; border:1px solid #e5e5e5; border-radius:10px; padding:14px 16px; margin-bottom:20px;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" x-model="form.e_custo_fixo" style="width:16px;height:16px;accent-color:#111111;">
                        <span style="font-size:13px; color:#c4b5fd; font-weight:500;">Salvar também como Custo Fixo</span>
                    </label>
                    <p x-show="form.e_custo_fixo" style="font-size:12px; color:#a78bfa; margin-top:6px; margin-left:26px;">
                        Este lancamento sera salvo em Custos Fixos e tambem gerara contas a pagar futuras, todo mes no dia <strong x-text="form.vencimento ? new Date(form.vencimento + 'T00:00:00').getDate() : '?'"></strong>.
                    </p>
                </div>

                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn-secondary" @click="modalAberto=false">Cancelar</button>
                    <button type="submit" class="btn-primary" :disabled="salvando" x-text="salvando ? 'Salvando...' : (form.id ? 'Atualizar' : 'Criar Lançamento')"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Baixa (pagamento) -->
    <div class="modal-overlay" x-show="modalBaixaAberto" x-cloak>
        <div class="modal" style="max-width:400px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="font-size:16px; font-weight:600; color:#f1f5f9;">Registrar Pagamento</h2>
                <button @click="modalBaixaAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>
            <template x-if="lancamentoBaixa">
                <div>
                    <p style="font-size:14px; color:#94a3b8; margin-bottom:4px;" x-text="lancamentoBaixa.descricao"></p>
                    <p style="font-size:20px; font-weight:700; color:#e2e8f0; margin-bottom:20px;">
                        Valor total: <span x-text="formatarMoeda(lancamentoBaixa.valor)"></span>
                    </p>
                    <p style="font-size:13px; color:#6b7280; margin-bottom:16px;">
                        Já pago: <strong style="color:#10b981;" x-text="formatarMoeda(lancamentoBaixa.valor_pago)"></strong>
                    </p>
                    <div style="margin-bottom:20px;">
                        <label class="label">Valor a pagar agora (R$) *</label>
                        <input class="input" type="number" step="0.01" min="0.01" x-model="valorBaixa" :max="lancamentoBaixa.valor - lancamentoBaixa.valor_pago">
                    </div>
                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                        <button class="btn-secondary" @click="modalBaixaAberto=false">Cancelar</button>
                        <button class="btn-primary" @click="confirmarBaixa()" :disabled="salvando" x-text="salvando ? 'Salvando...' : 'Confirmar'"></button>
                    </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Modal Conciliação OFX -->
    <div class="modal-overlay" x-show="modalOfxAberto" x-cloak>
        <div class="modal" style="max-width:1200px; width:90%; max-height:90vh; display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h2 style="font-size:17px; font-weight:600; color:#f1f5f9;">Conciliação Bancária (OFX)</h2>
                <button @click="modalOfxAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>
            <div style="font-size:13px; color:#94a3b8; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
                <span>Revise os lançamentos do seu extrato e escolha a conta destino.</span>
                <div style="display:flex; align-items:center; gap:10px;">
                    <label class="label" style="margin:0;">Conta Destino:</label>
                    <select class="select" x-model="ofxContaId" style="width:200px;">
                        <option value="">Selecione...</option>
                        <template x-for="c in contas" :key="c.id">
                            <option :value="c.id" x-text="c.nome"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div style="flex:1; overflow-y:auto; border:1px solid #1e293b; border-radius:8px; margin-bottom:20px;">
                <table style="width:100%; text-align:left; font-size:13px; border-collapse:collapse;">
                    <thead style="background:#0f172a; position:sticky; top:0; z-index:10;">
                        <tr>
                            <th style="padding:10px 12px; color:#94a3b8; font-weight:600;">Data</th>
                            <th style="padding:10px 12px; color:#94a3b8; font-weight:600;">Descrição (OFX)</th>
                            <th style="padding:10px 12px; color:#94a3b8; font-weight:600;">Valor</th>
                            <th style="padding:10px 12px; color:#94a3b8; font-weight:600;">Ação</th>
                            <th style="padding:10px 12px; color:#94a3b8; font-weight:600; width:160px;">Categoria</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(txn, i) in ofxTransacoes" :key="i">
                            <tr style="border-bottom:1px solid #1e293b;">
                                <td style="padding:10px 12px; color:#cbd5e1;" x-text="formatarData(txn.data)"></td>
                                <td style="padding:10px 12px; color:#cbd5e1;">
                                    <span x-text="txn.descricao"></span>
                                    <span x-show="txn.duplicado_hint" style="background:rgba(245,158,11,0.15); color:#f59e0b; border:1px solid rgba(245,158,11,0.3); font-size:10px; padding:2px 6px; border-radius:4px; margin-left:6px; display:inline-block;">Já Lançado</span>
                                </td>
                                <td style="padding:10px 12px; font-weight:600;" :style="txn.tipo==='receber'?'color:#10b981':'color:#ef4444'" x-text="formatarMoeda(txn.valor)"></td>
                                <td style="padding:10px 12px;">
                                    <select class="select" x-model="txn.acao_id" style="width:100%; padding:4px 8px; font-size:12px; height:auto; min-height:28px;">
                                        <option value="novo">✨ Criar como Novo (Pago)</option>
                                        <option value="ignorar">❌ Ignorar / Não Importar</option>
                                        <optgroup label="Vincular a Pendente:">
                                            <template x-for="l in buscarPendentesParaOfx(txn)">
                                                <option :value="l.id" x-text="formatarData(l.vencimento) + ' - ' + l.descricao + ' (' + formatarMoeda(l.valor) + ')'"></option>
                                            </template>
                                        </optgroup>
                                    </select>
                                </td>
                                <td style="padding:10px 12px;">
                                    <select class="select" x-model="txn.categoria" style="width:100%; padding:4px 8px; font-size:12px; height:auto; min-height:28px;" x-show="txn.acao_id === 'novo'">
                                        <option value="">(Sem categoria)</option>
                                        <template x-for="cat in categoriasDisponiveis" :key="cat">
                                            <option :value="cat" x-text="cat.charAt(0).toUpperCase() + cat.slice(1)"></option>
                                        </template>
                                        <option value="__custom__" style="font-weight:bold; color:#6366f1;">+ Nova categoria...</option>
                                    </select>
                                    <div x-show="txn.acao_id === 'novo' && txn.categoria === '__custom__'" style="margin-top:6px; display:flex; gap:4px;">
                                        <input class="input" type="text" x-model="txn.categoriaCustom" placeholder="Digite o nome..." style="flex:1; padding:4px 8px; font-size:12px;" @keyup.enter="adicionarCategoriaNoOfx(txn)">
                                        <button class="btn-primary" @click.prevent="adicionarCategoriaNoOfx(txn)" style="padding:4px 8px; height:28px;" title="Adicionar para todos"><i data-lucide="check" style="width:14px;height:14px;"></i></button>
                                    </div>
                                    <span x-show="txn.acao_id !== 'novo' && txn.acao_id !== 'ignorar'" style="font-size:11px; color:#6b7280;">Automático</span>
                                    <span x-show="txn.acao_id === 'ignorar'" style="font-size:11px; color:#6b7280;">-</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button class="btn-secondary" @click="modalOfxAberto=false">Cancelar</button>
                <button class="btn-primary" @click="processarOfx()">
                    Confirmar Importação
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Gerenciar Categorias -->
    <div class="modal-overlay" x-show="modalCategoriasAberto" x-cloak>
        <div class="modal" style="max-width:500px; width:100%; max-height:80vh; display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h2 style="font-size:17px; font-weight:600; color:#f1f5f9;">Gerenciar Categorias</h2>
                <button @click="modalCategoriasAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>
            
            <div style="font-size:13px; color:#94a3b8; margin-bottom:16px;">
                Aqui você pode renomear ou excluir as categorias. Excluir uma categoria moverá seus lançamentos para "outros".
            </div>

            <div style="flex:1; overflow-y:auto; margin-bottom:20px; border:1px solid #1e293b; border-radius:8px;">
                <template x-for="cat in categoriasDisponiveis" :key="cat">
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #1e293b;">
                        <template x-if="categoriaEditando !== cat">
                            <div style="display:flex; align-items:center; width:100%; justify-content:space-between;">
                                <span style="color:#e2e8f0; font-size:14px; text-transform:capitalize;" x-text="cat"></span>
                                <div style="display:flex; gap:8px;">
                                    <button @click="iniciarEdicaoCategoria(cat)" style="color:#6366f1; background:none; border:none; cursor:pointer; padding:4px;" title="Renomear">
                                        <i data-lucide="pencil" style="width:14px;height:14px;"></i>
                                    </button>
                                    <button @click="excluirCategoria(cat)" x-show="cat !== 'outros'" style="color:#ef4444; background:none; border:none; cursor:pointer; padding:4px;" title="Excluir">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <template x-if="categoriaEditando === cat">
                            <div style="display:flex; align-items:center; width:100%; gap:8px;">
                                <input class="input" type="text" x-model="novoNomeCategoria" style="flex:1; padding:4px 8px; font-size:13px; min-height:30px;" @keyup.enter="salvarEdicaoCategoria()">
                                <button class="btn-primary" @click="salvarEdicaoCategoria()" style="padding:4px 12px; font-size:12px;" :disabled="salvando">Salvar</button>
                                <button class="btn-secondary" @click="categoriaEditando=null" style="padding:4px 12px; font-size:12px;" :disabled="salvando">Cancelar</button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button class="btn-secondary" @click="modalCategoriasAberto=false">Fechar</button>
            </div>
        </div>
    </div>

    <!-- Modal Edição em Massa -->
    <div class="modal-overlay" x-show="modalMassaAberto" x-cloak>
        <div class="modal" style="max-width:400px; width:100%;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="font-size:18px; font-weight:700; color:#f1f5f9;">Editar <span x-text="selecionados.length"></span> lançamentos</h2>
                <button @click="modalMassaAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:20px;height:20px;"></i>
                </button>
            </div>
            
            <label class="label">Nova Categoria (Opcional)</label>
            <select class="select" x-model="formMassa.categoria" style="margin-bottom:12px;">
                <option value="">-- Não alterar --</option>
                <template x-for="cat in categoriasDisponiveis" :key="cat">
                    <option :value="cat" x-text="cat.charAt(0).toUpperCase() + cat.slice(1)"></option>
                </template>
            </select>

            <label class="label">Nova Conta Bancária (Opcional)</label>
            <select class="select" x-model="formMassa.conta_id" style="margin-bottom:12px;">
                <option value="">-- Não alterar --</option>
                <template x-for="c in contas" :key="c.id">
                    <option :value="c.id" x-text="c.nome"></option>
                </template>
            </select>

            <label class="label">Novo Status (Opcional)</label>
            <select class="select" x-model="formMassa.status" style="margin-bottom:20px;">
                <option value="">-- Não alterar --</option>
                <option value="pago">Pago / Efetivado</option>
                <option value="pendente">Pendente</option>
                <option value="cancelado">Cancelado</option>
            </select>

            <div style="display:flex; justify-content:flex-end; gap:12px;">
                <button class="btn-secondary" @click="modalMassaAberto=false">Cancelar</button>
                <button class="btn-primary" @click="salvarEdicaoMassa()" :disabled="salvando" x-text="salvando ? 'Salvando...' : 'Aplicar'"></button>
            </div>
        </div>
    </div>

</div>

<script>
function lancamentos() {
    return {
        lista: [],
        carregando: true,
        salvando: false,
        modalAberto: false,
        modalBaixaAberto: false,
        lancamentoBaixa: null,
        valorBaixa: '',
        filtros: { tipo: '', status: '', data_inicio: '', data_fim: '', busca: '', categoria: '', conta: '' },
        periodoAtivo: 'mes',
        referenciaData: new Date().toISOString().split('T')[0],
        selecionados: [],
        form: {},
        categoriaCustom: '',
        mostrarCampoCustom: false,
        modalOfxAberto: false,
        modalCategoriasAberto: false,
        modalMassaAberto: false,
        formMassa: { categoria: '', conta_id: '', status: '' },
        categoriaEditando: null,
        novoNomeCategoria: '',
        categoriasDinamicas: [],
        uploadingOfx: false,
        ofxTransacoes: [],
        ofxContaId: '',
        contas: [],
        clientes: <?= json_encode($clientes, JSON_UNESCAPED_UNICODE) ?>,
        fornecedores: <?= json_encode($fornecedores, JSON_UNESCAPED_UNICODE) ?>,

        get categoriasDisponiveis() {
            const padrao = ['serviços', 'produtos', 'aluguel', 'impostos', 'folha', 'marketing', 'outros'];
            let salvasLocal = [];
            try { salvasLocal = JSON.parse(localStorage.getItem('distinto_categorias') || '[]'); } catch(e) {}
            const doBanco = this.lista.map(l => l.categoria).filter(c => c && c.trim() !== '');
            const todas = [...padrao, ...salvasLocal, ...doBanco, ...this.categoriasDinamicas];
            return [...new Set(todas.map(c => c.toLowerCase()))].sort();
        },

        salvarCategoriaCustomizada(cat) {
            let salvas = [];
            try { salvas = JSON.parse(localStorage.getItem('distinto_categorias') || '[]'); } catch(e){}
            if (!salvas.includes(cat)) {
                salvas.push(cat);
                localStorage.setItem('distinto_categorias', JSON.stringify(salvas));
                if (!this.categoriasDinamicas.includes(cat)) this.categoriasDinamicas.push(cat);
            }
        },

        adicionarCategoriaNoOfx(txn) {
            if (!txn.categoriaCustom) return;
            const cat = txn.categoriaCustom.trim().toLowerCase();
            if (!cat) return;
            this.salvarCategoriaCustomizada(cat);
            txn.categoria = cat;
            txn.categoriaCustom = '';
            this.$nextTick(() => { if(window.lucide) lucide.createIcons(); });
        },

        get todosSelecionados() {
            return this.lancamentosFiltrados.length > 0 && this.selecionados.length === this.lancamentosFiltrados.length;
        },

        toggleTodos() {
            if (this.todosSelecionados) {
                this.selecionados = [];
            } else {
                this.selecionados = this.lancamentosFiltrados.map(l => l.id);
            }
        },

        get lancamentosFiltrados() {
            return this.lista.filter(l => {
                if (this.filtros.tipo   && l.tipo !== this.filtros.tipo) return false;
                if (this.filtros.status && l.status !== this.filtros.status) return false;
                if (this.filtros.categoria && l.categoria !== this.filtros.categoria) return false;
                if (this.filtros.conta) {
                    if (this.filtros.conta === '__null__') {
                        if (l.conta_id && l.conta_id !== '') return false;
                    } else {
                        if (l.conta_id !== this.filtros.conta) return false;
                    }
                }
                if (this.filtros.data_inicio && l.vencimento < this.filtros.data_inicio) return false;
                if (this.filtros.data_fim && l.vencimento > this.filtros.data_fim) return false;
                if (this.filtros.busca) {
                    const termo = this.filtros.busca.toLowerCase();
                    const matchDesc = (l.descricao || '').toLowerCase().includes(termo);
                    const matchCliFor = (l.cliente_fornecedor || '').toLowerCase().includes(termo);
                    if (!matchDesc && !matchCliFor) return false;
                }
                return true;
            });
        },

        get totalReceber() {
            return this.lancamentosFiltrados
                .filter(l => l.tipo === 'receber' && l.status !== 'cancelado')
                .reduce((s, l) => s + parseFloat(l.valor || 0), 0);
        },

        get totalPagar() {
            return this.lancamentosFiltrados
                .filter(l => l.tipo === 'pagar' && l.status !== 'cancelado')
                .reduce((s, l) => s + parseFloat(l.valor || 0), 0);
        },

        async init() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('filtro')) this.filtros.status = params.get('filtro');
            this.aplicarPeriodo();
            await Promise.all([
                this.carregarLancamentos(),
                this.carregarContas()
            ]);
        },

        async carregarContas() {
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/contas.php') ?>');
                this.contas = await r.json();
            } catch(e) {}
        },

        mudarModoPeriodo() {
            this.referenciaData = new Date().toISOString().split('T')[0];
            this.aplicarPeriodo();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        deslocarPeriodo(offset) {
            let [y, m, d] = this.referenciaData.split('-').map(Number);
            let date = new Date(y, m - 1, d);
            if (this.periodoAtivo === 'dia') date.setDate(date.getDate() + offset);
            else if (this.periodoAtivo === 'mes') date.setMonth(date.getMonth() + offset);
            else if (this.periodoAtivo === 'ano') date.setFullYear(date.getFullYear() + offset);
            
            const newY = date.getFullYear();
            const newM = String(date.getMonth() + 1).padStart(2, '0');
            const newD = String(date.getDate()).padStart(2, '0');
            this.referenciaData = `${newY}-${newM}-${newD}`;
            this.aplicarPeriodo();
        },

        aplicarPeriodo() {
            if (this.periodoAtivo === 'tudo') {
                this.filtros.data_inicio = '';
                this.filtros.data_fim = '';
                return;
            }
            if (this.periodoAtivo === 'semana') {
                const h = new Date();
                const dom = new Date(h.setDate(h.getDate() - h.getDay()));
                const sab = new Date(h.setDate(h.getDate() - h.getDay() + 6));
                this.filtros.data_inicio = dom.getFullYear() + '-' + String(dom.getMonth()+1).padStart(2,'0') + '-' + String(dom.getDate()).padStart(2,'0');
                this.filtros.data_fim = sab.getFullYear() + '-' + String(sab.getMonth()+1).padStart(2,'0') + '-' + String(sab.getDate()).padStart(2,'0');
                return;
            }
            
            let [y, m, d] = this.referenciaData.split('-').map(Number);
            if (this.periodoAtivo === 'dia') {
                this.filtros.data_inicio = this.referenciaData;
                this.filtros.data_fim = this.referenciaData;
            } else if (this.periodoAtivo === 'mes') {
                const ultimoDia = new Date(y, m, 0).getDate();
                this.filtros.data_inicio = `${y}-${String(m).padStart(2,'0')}-01`;
                this.filtros.data_fim = `${y}-${String(m).padStart(2,'0')}-${String(ultimoDia).padStart(2,'0')}`;
            } else if (this.periodoAtivo === 'ano') {
                this.filtros.data_inicio = `${y}-01-01`;
                this.filtros.data_fim = `${y}-12-31`;
            }
        },

        labelPeriodo() {
            let [y, m, d] = this.referenciaData.split('-').map(Number);
            if (this.periodoAtivo === 'dia') {
                return `${String(d).padStart(2,'0')}/${String(m).padStart(2,'0')}/${y}`;
            } else if (this.periodoAtivo === 'mes') {
                const meses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                return `${meses[m-1]} de ${y}`;
            } else if (this.periodoAtivo === 'ano') {
                return `${y}`;
            }
            return '';
        },

        async carregarLancamentos() {
            this.carregando = true;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>');
                const data = await r.json();
                if (!r.ok) {
                    throw new Error(data.erro || 'Erro HTTP ' + r.status);
                }
                this.lista = data;
            } catch(e) { 
                toast(e.message || 'Erro ao carregar lançamentos', 'erro'); 
            }
            this.carregando = false;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        abrirModal(lancamento = null) {
            const categoriasPadrao = ['servicos','produtos','aluguel','impostos','folha','marketing','outros'];
            const cat = lancamento?.categoria || 'servicos';
            const ehPadrao = categoriasPadrao.includes(cat);
            this.form = lancamento ? { ...lancamento } : {
                tipo: 'receber', modalidade: 'avista', descricao: '', valor: '',
                vencimento: '', categoria: 'servicos', cliente_fornecedor: '',
                cliente_id: '', fornecedor_id: '',
                forma_pagamento: '', conta_id: this.contas[0]?.id || '', total_parcelas: '', frequencia: 'mensal',
                data_termino: '', observacao: '', e_custo_fixo: false
            };
            this.categoriaCustom = ehPadrao ? '' : cat;
            this.mostrarCampoCustom = !ehPadrao;
            if (!ehPadrao) this.form.categoria = '__custom__';
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async salvar() {
            this.salvando = true;
            try {
                const payload = { ...this.form };
                if (this.mostrarCampoCustom) {
                    if (!this.categoriaCustom.trim()) {
                        toast('Digite a nova categoria', 'aviso');
                        this.salvando = false;
                        return;
                    }
                    payload.categoria = this.categoriaCustom.trim().toLowerCase();
                    this.salvarCategoriaCustomizada(payload.categoria);
                }
                const metodo = this.form.id ? 'PUT' : 'POST';
                const r = await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>', {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await r.json();
                if (r.ok) {
                    toast(this.form.id ? 'Lançamento atualizado!' : 'Lançamento(s) criado(s)!', 'sucesso');
                    this.modalAberto = false;
                    await this.carregarLancamentos();
                } else {
                    toast(res.erro || 'Erro ao salvar', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.salvando = false;
        },

        abrirBaixa(lancamento) {
            this.lancamentoBaixa = lancamento;
            this.valorBaixa = (parseFloat(lancamento.valor) - parseFloat(lancamento.valor_pago)).toFixed(2);
            this.modalBaixaAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async confirmarBaixa() {
            if (!this.valorBaixa || parseFloat(this.valorBaixa) <= 0) return;
            this.salvando = true;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/baixa.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: this.lancamentoBaixa.id, valor: parseFloat(this.valorBaixa) })
                });
                const res = await r.json();
                if (r.ok) {
                    toast('Pagamento registrado!', 'sucesso');
                    this.modalBaixaAberto = false;
                    await this.carregarLancamentos();
                } else {
                    toast(res.erro || 'Erro ao registrar baixa', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.salvando = false;
        },

        async excluir(id) {
            if (!confirm('Excluir este lançamento?')) return;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>', { 
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: [id] })
                });
                if (r.ok) {
                    toast('Lançamento excluído', 'sucesso');
                    this.selecionados = this.selecionados.filter(s => s !== id);
                    await this.carregarLancamentos();
                } else {
                    const res = await r.json();
                    toast(res.erro || 'Erro ao excluir', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
        },

        async alterarStatusSelecionados(novoStatus) {
            if (this.selecionados.length === 0) return;
            this.salvando = true;
            try {
                // Fazer isso de forma sequencial ou usando endpoint bulk se existir.
                // Como não temos endpoint bulk update status, iteramos:
                let concluidos = 0;
                for (const id of this.selecionados) {
                    const l = this.lista.find(i => i.id === id);
                    if (!l) continue;
                    
                    const payload = { ...l, status: novoStatus };
                    if (novoStatus === 'pago') payload.valor_pago = l.valor;
                    if (novoStatus === 'pendente') payload.valor_pago = 0;

                    await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    concluidos++;
                }
                toast(`${concluidos} lançamento(s) atualizado(s)!`, 'sucesso');
                this.selecionados = [];
                await this.carregarLancamentos();
            } catch(e) { toast('Erro ao atualizar status', 'erro'); }
            this.salvando = false;
        },

        async excluirSelecionados() {
            if (this.selecionados.length === 0) return;
            if (!confirm(`Excluir ${this.selecionados.length} lançamento(s)?`)) return;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>', { 
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: this.selecionados })
                });
                if (r.ok) {
                    toast(`${this.selecionados.length} lançamento(s) excluído(s)`, 'sucesso');
                    this.selecionados = [];
                    await this.carregarLancamentos();
                } else {
                    const res = await r.json();
                    toast(res.erro || 'Erro ao excluir', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
        },

        abrirEdicaoMassa() {
            this.formMassa = { categoria: '', conta_id: '', status: '' };
            this.modalMassaAberto = true;
        },

        async salvarEdicaoMassa() {
            if (!this.formMassa.categoria && !this.formMassa.conta_id && !this.formMassa.status) {
                toast('Selecione pelo menos um campo para alterar', 'erro');
                return;
            }
            this.salvando = true;
            try {
                const requests = this.selecionados.map(id => {
                    const l = this.lista.find(x => x.id === id);
                    if (!l) return Promise.resolve();
                    const payload = {
                        id: l.id,
                        tipo: l.tipo,
                        descricao: l.descricao,
                        valor: l.valor,
                        vencimento: l.vencimento,
                        modalidade: l.modalidade,
                        forma_pagamento: l.forma_pagamento,
                        observacao: l.observacao,
                        status: this.formMassa.status || l.status,
                        categoria: this.formMassa.categoria || l.categoria,
                        conta_id: this.formMassa.conta_id || l.conta_id
                    };
                    return fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                });
                
                await Promise.all(requests);
                toast(`${this.selecionados.length} lançamentos atualizados`, 'sucesso');
                this.modalMassaAberto = false;
                this.selecionados = [];
                await this.carregarLancamentos();
            } catch (e) {
                toast('Erro ao atualizar em massa', 'erro');
            }
            this.salvando = false;
        },

        classeStatus(status) {
            const map = {
                pago:         'badge bg-green-500/20 text-green-400 border border-green-500/30',
                pago_parcial: 'badge bg-yellow-500/20 text-yellow-400 border border-yellow-500/30',
                pendente:     'badge bg-blue-500/20 text-blue-400 border border-blue-500/30',
                atrasado:     'badge bg-red-500/20 text-red-400 border border-red-500/30',
                cancelado:    'badge bg-gray-500/20 text-gray-400 border border-gray-500/30',
            };
            return map[status] || 'badge bg-gray-500/20 text-gray-400';
        },

        labelStatus(status) {
            const map = { pago:'Pago', pago_parcial:'Parcial', pendente:'Pendente', atrasado:'Atrasado', cancelado:'Cancelado' };
            return map[status] || status;
        },

        async uploadOfx(e) {
            alert("Evento change disparado! Arquivo: " + (e.target?.files?.[0]?.name || 'Nenhum'));
            const file = e.target?.files ? e.target.files[0] : null;
            if (!file) {
                alert("Nenhum arquivo selecionado.");
                return;
            }
            const formData = new FormData();
            formData.append('arquivo', file);
            
            this.uploadingOfx = true;
            toast('Enviando arquivo OFX...', 'info');
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/upload-ofx.php') ?>', {
                    method: 'POST',
                    body: formData
                });
                
                let resText = await r.text();
                let res;
                try {
                    res = JSON.parse(resText);
                } catch (jsonErr) {
                    alert('Erro fatal do servidor. Resposta: ' + resText.substring(0, 100));
                    this.uploadingOfx = false;
                    return;
                }

                if (r.ok) {
                    if (!res.transacoes || res.transacoes.length === 0) {
                        alert("O arquivo não continha transações válidas.");
                        this.uploadingOfx = false;
                        return;
                    }
                    this.ofxTransacoes = res.transacoes.map(t => {
                        const matchPendente = this.buscarPendentesParaOfx(t)[0];
                        const matchPago = this.buscarPagosParaOfx(t)[0];
                        
                        let acao_id = 'novo';
                        let duplicado_hint = false;

                        if (matchPendente) {
                            acao_id = matchPendente.id;
                        } else if (matchPago) {
                            acao_id = 'ignorar';
                            duplicado_hint = true;
                        }

                        return { ...t, acao_id, duplicado_hint, categoria: 'outros', categoriaCustom: '' };
                    });
                    this.modalOfxAberto = true;
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } else {
                    alert(res.erro || 'Erro desconhecido ao processar OFX');
                }
            } catch (err) {
                alert('Erro de conexão: ' + err.message);
            }
            this.uploadingOfx = false;
            if (e.target) e.target.value = ''; 
        },

        buscarPendentesParaOfx(txn) {
            // Busca contas do mesmo tipo, que não estão pagas, com valor parecido (+- 10%)
            return this.lista.filter(l => 
                l.tipo === txn.tipo && 
                l.status !== 'pago' && 
                l.status !== 'cancelado' &&
                Math.abs(l.valor - txn.valor) <= (txn.valor * 0.10)
            );
        },

        buscarPagosParaOfx(txn) {
            // Busca contas já pagas com o MESMO valor e MESMA data
            return this.lista.filter(l => 
                l.tipo === txn.tipo && 
                l.status === 'pago' && 
                Math.abs(l.valor - txn.valor) < 0.05 &&
                l.vencimento === txn.data
            );
        },

        async processarOfx() {
            toast('Processando conciliação...', 'info');
            // Como podemos ter dezenas, vamos enviar para um endpoint de lote, 
            // ou iterar aqui. Vamos iterar aqui para simplificar e reaproveitar os endpoints existentes.
            let sucesso = 0;
            this.carregando = true;

            for (const txn of this.ofxTransacoes) {
                if (txn.acao_id === 'ignorar') continue;
                
                if (txn.acao_id === 'novo') {
                    let catFinal = txn.categoria || 'outros';
                    if (catFinal === '__custom__' && txn.categoriaCustom) {
                        catFinal = txn.categoriaCustom.trim().toLowerCase();
                        this.salvarCategoriaCustomizada(catFinal);
                    } else if (catFinal === '__custom__') {
                        catFinal = 'outros';
                    }

                    // Criar novo pagamento já baixado
                    await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            tipo: txn.tipo,
                            descricao: txn.descricao + ' (OFX)',
                            valor: txn.valor,
                            valor_pago: txn.valor,
                            status: 'pago',
                            vencimento: txn.data,
                            categoria: catFinal,
                            conta_id: this.ofxContaId,
                            observacao: 'Importado via OFX',
                            ofx_fitid: txn.fitid
                        })
                    });
                    // Opcionalmente poderíamos buscar o ID e dar baixa, ou o backend já assume.
                    // Para garantir a baixa, precisaríamos do ID.
                    // Como não pegamos o ID do POST, é melhor fazermos a requisição em lote no backend no futuro.
                    // Mas por hora vamos rodar carregarLancamentos() ao final e os novos vão ficar pendentes para baixar.
                    // Wait, se é pra entrar como pago, vamos alterar o backend de POST para aceitar 'valor_pago' e 'data_pagamento'.
                    // Por enquanto vamos cadastrar.
                    sucesso++;
                } else {
                    // Vincular (Baixar a conta existente)
                    await fetch('<?= raizUrl('/api/financeiro/baixa.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: txn.acao_id, valor: txn.valor })
                    });
                    sucesso++;
                }
            }

            this.carregando = false;
            this.modalOfxAberto = false;
            toast(`Conciliação concluída! ${sucesso} processados.`, 'sucesso');
            await this.carregarLancamentos();
        },

        formatarData(str) { return window.formatarData(str); },
        formatarMoeda(val) { return window.formatarMoeda(val); },
    };
}
</script>

<style>
.hover-underline:hover { text-decoration: underline; }
</style>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
