<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
exigirAutenticacao();
$tituloPagina = 'Lançamentos';
$clientes = [];
$fornecedores = [];
try {
    $db = Database::get();
    $clientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome ASC")->fetchAll();
    $fornecedores = $db->query("SELECT id, nome FROM fornecedores ORDER BY nome ASC")->fetchAll();
} catch (Exception $e) {
    error_log("Erro ao carregar clientes/fornecedores na inicialização de lançamentos: " . $e->getMessage());
}
include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper" class="flex min-h-screen" x-data="lancamentos()" x-effect="lancamentosFiltrados; $nextTick(() => { if (window.lucide) lucide.createIcons(); })" @keyup.escape.window="periodoTiposAberto = false">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet !bg-background !p-6 flex flex-col flex-1">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <!-- Header Actions -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-6">
            <div>
                <h1 class="font-display-lg text-on-surface mb-1" style="font-size: 2rem; font-weight: 800; line-height: 1.2;">Fluxo de Caixa</h1>
                <p class="text-body-md text-on-surface-variant">Gestão completa de lançamentos e conciliação</p>
            </div>
            <div class="flex items-center gap-3">
                <input type="file" x-ref="ofxInput" @change="uploadOfx($event)" class="hidden" accept=".ofx,.OFX">
                <input type="file" x-ref="iaInput" @change="lerComprovante($event)" class="hidden" accept="image/*">
                
                <div class="flex items-center gap-1.5 p-1 bg-surface-container border border-outline-variant/30 rounded-lg">
                    <button @click="modalIaAberto = true" 
                            class="flex items-center gap-2 px-4 py-2 rounded text-emerald-500 hover:bg-emerald-500/10 transition-all font-label-caps text-[10px] group"
                            :disabled="processandoIA">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 group-hover:rotate-12 transition-transform"></i>
                        <span x-show="!processandoIA">Scanner IA</span>
                        <span x-show="processandoIA">Analisando...</span>
                    </button>
                    <div class="w-px h-4 bg-outline-variant/30"></div>
                    <button @click="abrirModalExtratoAsaas()" 
                            class="flex items-center gap-2 px-4 py-2 rounded text-purple-500 hover:bg-purple-500/10 transition-all font-label-caps text-[10px]"
                            :disabled="consultandoAsaas">
                        <i data-lucide="landmark" class="w-3.5 h-3.5"></i>
                        <span x-show="!consultandoAsaas">Extrato Asaas</span>
                        <span x-show="consultandoAsaas">Consultando...</span>
                    </button>
                    <div class="w-px h-4 bg-outline-variant/30"></div>
                    <button @click="$refs.ofxInput.click()" 
                            class="flex items-center gap-2 px-4 py-2 rounded text-blue-500 hover:bg-blue-500/10 transition-all font-label-caps text-[10px]"
                            :disabled="uploadingOfx">
                        <i data-lucide="file-up" class="w-3.5 h-3.5"></i>
                        <span x-show="!uploadingOfx">Importar OFX</span>
                        <span x-show="uploadingOfx">Lendo...</span>
                    </button>
                </div>

                <!-- Ações em Massa -->
                <div x-show="selecionados.length > 0" class="flex items-center gap-1.5 p-1 bg-surface-container border border-outline-variant/30 rounded-lg" x-cloak x-transition>
                    <button @click="alterarStatusSelecionados('pago')" class="px-3 py-1.5 text-emerald-500 hover:bg-emerald-500/10 rounded font-label-caps text-[10px]">Efetivar</button>
                    <button @click="alterarStatusSelecionados('pendente')" class="px-3 py-1.5 text-amber-500 hover:bg-amber-500/10 rounded font-label-caps text-[10px]">Pendente</button>
                    <div class="w-px h-4 bg-outline-variant/30"></div>
                    <button @click="abrirEdicaoMassa()" class="px-3 py-1.5 text-blue-500 hover:bg-blue-500/10 rounded font-label-caps text-[10px]">Conta/Cat</button>
                    <div class="w-px h-4 bg-outline-variant/30"></div>
                    <button @click="excluirSelecionados()" class="px-3 py-1.5 text-red-500 hover:bg-red-500/10 rounded font-label-caps text-[10px]">Excluir</button>
                </div>
            </div>
        </div>

        <!-- Executive Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-card-gap mb-8 items-stretch">
            <!-- Summary Card: Total a Receber -->
            <div class="glass-card p-6 rounded-xl flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/5 rounded-full blur-2xl group-hover:bg-primary/10 transition-colors"></div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-label-caps font-label-caps text-on-surface-variant mb-1">TOTAL A RECEBER</p>
                        <h3 class="text-3xl font-bold font-headline-md text-primary tracking-tight" x-text="formatarMoeda(totalReceber)"></h3>
                    </div>
                    <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-4 flex items-center gap-2 text-on-surface-variant text-[11px] font-label-caps">
                    <span class="text-primary font-bold">Saldo</span>
                    <span>previsto em caixa</span>
                </div>
            </div>

            <!-- Summary Card: Total a Pagar -->
            <div class="glass-card p-6 rounded-xl flex flex-col justify-between h-32 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-error/5 rounded-full blur-2xl group-hover:bg-error/10 transition-colors"></div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-label-caps font-label-caps text-on-surface-variant mb-1">TOTAL A PAGAR</p>
                        <h3 class="text-3xl font-bold font-headline-md text-error tracking-tight" x-text="formatarMoeda(totalPagar)"></h3>
                    </div>
                    <span class="w-8 h-8 rounded-lg bg-error/10 text-error flex items-center justify-center shrink-0">
                        <i data-lucide="trending-down" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-4 flex items-center gap-2 text-on-surface-variant text-[11px] font-label-caps">
                    <span class="text-error font-bold">Compromissos</span>
                    <span>pendentes</span>
                </div>
            </div>

            <!-- Action Card: Novo Lançamento -->
            <div class="glass-card p-6 rounded-xl border-primary/20 bg-primary/5 flex flex-col justify-between h-32 relative overflow-hidden group hover:scale-[1.02] transition-all cursor-pointer" @click="abrirModal()">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/20 rounded-full blur-2xl group-hover:bg-primary/30 transition-colors"></div>
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-label-caps font-label-caps text-primary mb-1">AÇÕES RÁPIDAS</p>
                        <h3 class="text-xl font-bold font-headline-md leading-tight text-on-surface">Novo Lançamento</h3>
                    </div>
                    <span class="w-8 h-8 rounded-lg bg-primary text-on-primary flex items-center justify-center shrink-0 shadow-lg shadow-primary/20">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-4 flex items-center gap-2 text-on-surface-variant text-[11px] font-label-caps">
                    <span>Registrar entrada ou saída</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="glass-card p-4 rounded-xl mb-8 flex flex-col gap-4">
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                <!-- Segmented Control para Tipo -->
                <div class="flex p-1 bg-surface-container-lowest/80 rounded-lg border border-outline-variant/30">
                    <button @click="filtros.tipo=''" 
                            :class="filtros.tipo==='' ? 'bg-surface-variant text-on-surface shadow' : 'text-on-surface-variant hover:text-on-surface'"
                            class="px-4 py-1.5 rounded font-label-caps text-[10px] transition-all">Todos</button>
                    <button @click="filtros.tipo='receber'" 
                            :class="filtros.tipo==='receber' ? 'bg-emerald-500/20 text-emerald-400 shadow' : 'text-on-surface-variant hover:text-on-surface'"
                            class="px-4 py-1.5 rounded font-label-caps text-[10px] transition-all">A Receber</button>
                    <button @click="filtros.tipo='pagar'" 
                            :class="filtros.tipo==='pagar' ? 'bg-red-500/20 text-red-400 shadow' : 'text-on-surface-variant hover:text-on-surface'"
                            class="px-4 py-1.5 rounded font-label-caps text-[10px] transition-all">A Pagar</button>
                </div>

                <!-- Search Bar -->
                <div class="flex-1 relative group">
                    <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-on-surface-variant group-focus-within:text-primary transition-colors"></i>
                    <input type="text" x-model="filtros.busca" placeholder="Buscar por descrição, cliente ou categoria..." 
                           class="w-full bg-surface-container border border-outline-variant/30 rounded-lg py-2.5 pl-12 pr-6 text-body-md text-on-surface placeholder-on-surface-variant outline-none focus:border-primary/50 focus:bg-surface-container-low transition-all">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <select class="bg-surface-container border border-outline-variant/30 rounded-lg py-2 px-4 text-label-caps font-label-caps text-on-surface-variant outline-none focus:border-primary/50 transition-all appearance-none cursor-pointer" x-model="filtros.categoria">
                    <option value="">Todas as categorias</option>
                    <template x-for="cat in categoriasDisponiveis" :key="cat">
                        <option :value="cat" x-text="cat.toUpperCase()"></option>
                    </template>
                </select>
                
                <select class="bg-surface-container border border-outline-variant/30 rounded-lg py-2 px-4 text-label-caps font-label-caps text-on-surface-variant outline-none focus:border-primary/50 transition-all appearance-none cursor-pointer" x-model="filtros.conta">
                    <option value="">Todas as contas</option>
                    <template x-for="c in contas" :key="c.id">
                        <option :value="c.nome" x-text="c.nome.toUpperCase()"></option>
                    </template>
                </select>

                <select class="bg-surface-container border border-outline-variant/30 rounded-lg py-2 px-4 text-label-caps font-label-caps text-on-surface-variant outline-none focus:border-primary/50 transition-all appearance-none cursor-pointer" x-model="filtros.status">
                    <option value="">Todos os status</option>
                    <option value="pendente">PENDENTE</option>
                    <option value="pago">PAGO</option>
                    <option value="atrasado">ATRASADO</option>
                </select>

                <select class="bg-surface-container border border-outline-variant/30 rounded-lg py-2 px-4 text-label-caps font-label-caps text-on-surface-variant outline-none focus:border-primary/50 transition-all appearance-none cursor-pointer" x-model="filtros.conciliado">
                    <option value="">Conciliação: Todos</option>
                    <option value="1">Conciliado</option>
                    <option value="0">Não Conciliado</option>
                </select>

                <div class="h-4 w-px bg-outline-variant/30 mx-1"></div>

                <!-- Seletor de Período -->
                <select class="bg-surface-container border border-outline-variant/30 rounded-lg py-2 px-3 text-label-caps font-label-caps text-on-surface-variant outline-none focus:border-primary/50 transition-all cursor-pointer"
                        x-model="periodoAtivo"
                        @change="mudarModoPeriodo()">
                    <option value="mes">MÊS</option>
                    <option value="dia">DIA</option>
                    <option value="semana">SEMANA</option>
                    <option value="ano">ANO</option>
                    <option value="personalizado">PERSONALIZADO</option>
                    <option value="tudo">TUDO</option>
                </select>

                <template x-if="periodoAtivo !== 'personalizado' && periodoAtivo !== 'tudo'">
                    <div class="flex items-center gap-1 p-1 bg-surface-container rounded-lg border border-outline-variant/30">
                        <button @click="deslocarPeriodo(-1)" class="p-1.5 hover:bg-surface-variant rounded text-on-surface-variant transition-colors">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        </button>
                        <span class="px-3 text-label-caps font-label-caps text-on-surface min-w-[120px] text-center" x-text="labelPeriodo()"></span>
                        <button @click="deslocarPeriodo(1)" class="p-1.5 hover:bg-surface-variant rounded text-on-surface-variant transition-colors">
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </template>

                <template x-if="periodoAtivo === 'personalizado'">
                    <div class="flex items-center gap-2 p-1 bg-surface-container rounded-lg border border-outline-variant/30 px-3">
                        <input type="date" x-model="customDataInicio" @change="aplicarPeriodoPersonalizado()"
                               class="bg-transparent border-0 text-label-caps font-label-caps text-on-surface outline-none w-[130px]">
                        <span class="text-on-surface-variant text-label-caps">até</span>
                        <input type="date" x-model="customDataFim" @change="aplicarPeriodoPersonalizado()"
                               class="bg-transparent border-0 text-label-caps font-label-caps text-on-surface outline-none w-[130px]">
                    </div>
                </template>
            </div>
        </div>

        <!-- Tabela -->
        <div class="glass-card rounded-xl overflow-hidden mb-6 relative z-10">
            <div class="grid grid-cols-[40px_2.5fr_1fr_1fr_1fr_1fr_1fr_120px] items-center px-6 py-4 bg-surface-container-low border-b border-outline-variant/20">
                <input type="checkbox" class="rounded border-outline-variant/30 bg-surface-container text-primary focus:ring-primary focus:ring-offset-background" :checked="todosSelecionados" @change="toggleTodos()">
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Descrição / Cliente</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px] text-center">Vencimento</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px] text-center">Pagamento</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px] text-right pr-4">Valor</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px] text-right pr-4">Valor Pago</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px] text-center">Status</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px] text-right">Ações</span>
            </div>

            <div class="divide-y divide-outline-variant/20">
                <template x-if="carregando">
                    <div class="divide-y divide-outline-variant/20">
                        <template x-for="i in 6" :key="i">
                            <div class="grid grid-cols-[40px_2.5fr_1fr_1fr_1fr_1fr_1fr_120px] items-center px-6 py-4 animate-pulse">
                                <div class="w-4 h-4 bg-outline-variant/30 rounded"></div>
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded bg-outline-variant/30"></div>
                                    <div class="flex-1 space-y-2">
                                        <div class="h-3 bg-outline-variant/30 rounded w-3/4"></div>
                                        <div class="h-2 bg-outline-variant/30 rounded w-1/2"></div>
                                    </div>
                                </div>
                                <div class="h-3 bg-outline-variant/30 rounded w-16 mx-auto"></div>
                                <div class="h-3 bg-outline-variant/30 rounded w-16 mx-auto"></div>
                                <div class="h-3 bg-outline-variant/30 rounded w-20 ml-auto"></div>
                                <div class="h-3 bg-outline-variant/30 rounded w-20 ml-auto"></div>
                                <div class="h-6 bg-outline-variant/30 rounded-full w-24 mx-auto"></div>
                                <div class="flex justify-end gap-2">
                                    <div class="w-8 h-8 bg-outline-variant/30 rounded-lg"></div>
                                    <div class="w-8 h-8 bg-outline-variant/30 rounded-lg"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!carregando && lancamentosFiltrados.length === 0">
                    <div class="py-24 text-center">
                        <div class="w-16 h-16 bg-surface-container rounded-full flex items-center justify-center mx-auto mb-4 border border-outline-variant/30 text-on-surface-variant">
                            <i data-lucide="search-x" class="w-8 h-8"></i>
                        </div>
                        <h3 class="text-on-surface font-headline-md text-title-sm mb-1">Sem resultados</h3>
                        <p class="text-on-surface-variant text-body-md mb-6 max-w-xs mx-auto">Não encontramos nada com os filtros aplicados. Tente ajustar sua busca ou limpe os filtros para ver tudo.</p>
                        <button @click="limparFiltros()" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-on-primary rounded font-bold transition-all hover:scale-105 active:scale-95 text-body-md shadow-lg shadow-primary/20">
                            <i data-lucide="filter-x" class="w-4 h-4"></i>
                            Limpar Filtros
                        </button>
                    </div>
                </template>

                <template x-for="l in lancamentosFiltrados" :key="l.id">
                    <div class="grid grid-cols-[40px_2.5fr_1fr_1fr_1fr_1fr_1fr_120px] items-center px-6 py-4 hover:bg-surface-container-high/20 transition-colors group">
                        <div>
                            <input type="checkbox" :value="l.id" x-model="selecionados" class="rounded border-outline-variant/30 bg-surface-container text-primary focus:ring-primary focus:ring-offset-background">
                        </div>
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                                 :class="l.tipo==='receber' ? 'bg-[#10b981]/10 text-[#10b981]' : 'bg-error/10 text-error'">
                                <i :data-lucide="l.tipo==='receber' ? 'arrow-down-left' : 'arrow-up-right'" class="w-4 h-4"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold text-on-surface truncate cursor-pointer hover:text-primary transition-colors" @click="abrirModal(l)" x-text="l.descricao"></div>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <template x-if="l.conta_id">
                                        <span class="text-[8px] px-1.5 py-0.5 rounded bg-surface-variant text-on-surface-variant font-label-caps border border-outline-variant/10" 
                                              x-text="contas.find(c=>c.id===l.conta_id)?.nome"></span>
                                    </template>
                                    <template x-if="parseInt(l.conciliado) === 1">
                                        <span class="inline-flex items-center gap-1 text-[8px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-400 font-label-caps border border-emerald-500/20" title="Conciliado. Edição de conta, valor e data bloqueada.">
                                            <i data-lucide="lock" style="width:8px;height:8px;"></i> CONCILIADO
                                        </span>
                                    </template>
                                    <span class="text-xs text-on-surface-variant truncate" x-text="clientes.find(c => c.id === l.cliente_id)?.nome || fornecedores.find(f => f.id === l.fornecedor_id)?.nome || l.cliente_fornecedor || l.categoria"></span>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <span class="text-xs font-data-tabular text-on-surface-variant" x-text="formatarData(l.vencimento)"></span>
                        </div>
                        <div class="text-center">
                            <span class="text-xs font-data-tabular" 
                                  :class="l.data_pagamento ? 'text-emerald-400 font-bold' : 'text-on-surface-variant'" 
                                  x-text="l.data_pagamento ? formatarData(l.data_pagamento) : '—'"></span>
                        </div>
                        <div class="text-right pr-4">
                            <span class="text-sm font-data-tabular font-bold" :class="l.tipo==='receber' ? 'text-primary' : 'text-on-surface'" x-text="formatarMoeda(l.valor)"></span>
                        </div>
                        <div class="text-right pr-4">
                            <span class="text-sm font-data-tabular font-bold text-on-surface-variant" x-text="formatarMoeda(l.valor_pago)"></span>
                        </div>
                        <div class="flex justify-center">
                            <span class="status-pill" 
                                  :class="{
                                      'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': l.status === 'pago',
                                      'bg-amber-500/10 text-amber-400 border border-amber-500/20': l.status === 'pendente',
                                      'bg-red-500/10 text-red-400 border border-red-500/20': l.status === 'atrasado',
                                      'bg-surface-variant text-on-surface-variant border border-outline-variant/20': !['pago', 'pendente', 'atrasado'].includes(l.status)
                                  }" x-text="labelStatus(l.status)"></span>
                        </div>
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="abrirBaixa(l)" class="p-1.5 hover:bg-emerald-500/10 text-emerald-400 rounded transition-colors" title="Baixar">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                            </button>
                            <button @click="abrirModal(l)" class="p-1.5 hover:bg-surface-variant text-on-surface-variant hover:text-on-surface rounded transition-colors" title="Editar">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                            <button @click="excluir(l.id)" class="p-1.5 hover:bg-red-500/10 text-red-400 rounded transition-colors" title="Excluir">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Resumo rodapé (Removido pois agora está no topo) -->
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

            <template x-if="form.conciliado == 1">
                <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.2); border-radius:12px; padding:12px 16px; margin-bottom:16px; font-size:12px; color:#34d399; font-weight:500; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="lock" style="width:16px; height:16px;"></i>
                    Este lançamento foi conciliado. Alterações de tipo, valor, vencimento e conta estão bloqueadas.
                </div>
            </template>

            <form @submit.prevent="salvar()">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Tipo *</label>
                        <select class="select" x-model="form.tipo" required :disabled="form.conciliado == 1">
                            <option value="receber">A Receber</option>
                            <option value="pagar">A Pagar</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Modalidade *</label>
                        <select class="select" x-model="form.modalidade" required :disabled="form.conciliado == 1">
                            <option value="avista">À Vista</option>
                            <option value="parcelado">Parcelado</option>
                            <option value="recorrente">Recorrente</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Descrição *</label>
                    <input class="input" x-model="form.descricao" required maxlength="500" placeholder="Ex: Honorários cliente ABC" :disabled="form.conciliado == 1">
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Valor (R$) *</label>
                        <input class="input" type="number" step="0.01" min="0.01" x-model="form.valor" required :disabled="form.conciliado == 1">
                    </div>
                    <div>
                        <label class="label">Vencimento *</label>
                        <input class="input js-datepicker" type="text" x-model="form.vencimento" required placeholder="Selecione" x-init="flatpickr($el, { locale: 'pt', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y' })" :disabled="form.conciliado == 1">
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
                        <select class="select" x-model="form.cliente_id" :disabled="form.conciliado == 1">
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
                        <select class="select" x-model="form.fornecedor_id" :disabled="form.conciliado == 1">
                            <option value="">— Selecione um fornecedor —</option>
                            <template x-for="f in fornecedores" :key="f.id">
                                <option :value="f.id" x-text="f.nome"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="label" x-text="form.tipo === 'receber' ? 'Cliente (Texto/Novo)' : 'Fornecedor (Texto/Novo)'"></label>
                        <input class="input" x-model="form.cliente_fornecedor" placeholder="Nome ou descrição" :disabled="form.conciliado == 1">
                    </div>
                </div>

                <div style="margin-bottom:16px;" x-show="!form.cliente_id && !form.fornecedor_id && form.entidade_documento">
                    <label class="label">CPF/CNPJ Identificado (Será cadastrado)</label>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <input class="input" x-model="form.entidade_documento" placeholder="00.000.000/0001-00" style="flex:1;" :disabled="form.conciliado == 1">
                        <div x-show="form.entidade_endereco" class="text-[11px] text-zinc-500 italic">Endereço capturado ✅</div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Conta Bancária</label>
                    <select class="select" x-model="form.conta_id" :disabled="form.conciliado == 1">
                        <option value="">— Selecione o Banco —</option>
                        <template x-for="c in contas" :key="c.id">
                            <option :value="c.id" x-text="c.nome"></option>
                        </template>
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Forma de Pagamento</label>
                    <select class="select" x-model="form.forma_pagamento" :disabled="form.conciliado == 1">
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
                    <input class="input" type="number" min="2" max="120" x-model="form.total_parcelas" placeholder="Ex: 12" :disabled="form.conciliado == 1">
                </div>

                <!-- Recorrente -->
                <div x-show="form.modalidade==='recorrente'" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">Frequência</label>
                        <select class="select" x-model="form.frequencia" :disabled="form.conciliado == 1">
                            <option value="semanal">Semanal</option>
                            <option value="mensal">Mensal</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Até (opcional)</label>
                        <input class="input js-datepicker" type="text" x-model="form.data_termino" placeholder="Opcional" x-init="flatpickr($el, { locale: 'pt', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y' })" :disabled="form.conciliado == 1">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Observação</label>
                    <textarea class="input" x-model="form.observacao" rows="2" placeholder="Opcional" style="resize:vertical;"></textarea>
                </div>

                <!-- Checkbox custo fixo — para contas a pagar -->
                <div x-show="form.tipo === 'pagar' && !form.custo_fixo_id" style="background:#f7f7f7; border:1px solid #e5e5e5; border-radius:10px; padding:14px 16px; margin-bottom:20px;">
                    <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                        <input type="checkbox" x-model="form.e_custo_fixo" style="width:16px;height:16px;accent-color:#111111;" :disabled="form.conciliado == 1">
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
                <button class="btn-primary" @click="processarOfx()" :disabled="carregando">
                    <span x-show="!carregando">Confirmar Importação</span>
                    <span x-show="carregando">Processando...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Extrato Asaas -->
    <div class="modal-overlay" x-show="modalAsaasAberto" x-cloak>
        <div class="modal" style="max-width:1200px; width:90%; max-height:90vh; display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h2 style="font-size:17px; font-weight:600; color:#f1f5f9;">
                    <i data-lucide="landmark" style="width:18px;height:18px;color:#a855f7;margin-right:8px;"></i>
                    Extrato Financeiro Asaas
                </h2>
                <button @click="modalAsaasAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <div style="font-size:13px; color:#94a3b8; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                <span>Consulte o extrato financeiro da conta Asaas e concilie com os lançamentos.</span>
                <div style="display:flex; align-items:center; gap:8px;">
                    <label class="label" style="margin:0;">Conta:</label>
                    <select class="select" x-model="ofxContaId" style="width:160px; padding:4px 10px; font-size:12px; height:auto;">
                        <option value="">Selecione...</option>
                        <template x-for="c in contas" :key="c.id">
                            <option :value="c.id" x-text="c.nome"></option>
                        </template>
                    </select>
                    <label class="label" style="margin:0;">Período:</label>
                    <input type="date" x-model="asaasDataInicio" class="input" style="padding:4px 10px; font-size:12px; width:auto;">
                    <span style="color:#6b7280;">até</span>
                    <input type="date" x-model="asaasDataFim" class="input" style="padding:4px 10px; font-size:12px; width:auto;">
                    <button class="btn-primary" @click="consultarExtratoAsaas()" :disabled="consultandoAsaas" style="white-space:nowrap;">
                        <span x-show="!consultandoAsaas">Consultar</span>
                        <span x-show="consultandoAsaas">Buscando...</span>
                    </button>
                </div>
            </div>

            <div x-show="asaasErro" style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); border-radius:8px; padding:12px 16px; margin-bottom:16px; color:#ef4444; font-size:13px;">
                <span x-text="asaasErro"></span>
            </div>

            <div x-show="asaasTransacoes.length > 0" style="flex:1; overflow-y:auto; border:1px solid #1e293b; border-radius:8px; margin-bottom:20px;">
                <table style="width:100%; text-align:left; font-size:13px; border-collapse:collapse;">
                    <thead style="background:#0f172a; position:sticky; top:0; z-index:10;">
                        <tr>
                            <th style="padding:10px 12px; color:#94a3b8; font-weight:600;">Data</th>
                            <th style="padding:10px 12px; color:#94a3b8; font-weight:600;">Tipo Asaas</th>
                            <th style="padding:10px 12px; color:#94a3b8; font-weight:600; text-align:right;">Valor</th>
                            <th style="padding:10px 12px; color:#94a3b8; font-weight:600;">Descrição</th>
                            <th style="padding:10px 12px; color:#94a3b8; font-weight:600; width:200px;">Ação</th>
                            <th style="padding:10px 12px; color:#94a3b8; font-weight:600; width:160px;">Categoria</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="txn in asaasTransacoes" :key="txn.fitid">
                            <tr style="border-bottom:1px solid #1e293b;" :style="txn.duplicado ? 'opacity:0.4;' : ''">
                                <td style="padding:10px 12px; color:#cbd5e1;" x-text="formatarData(txn.data)"></td>
                                <td style="padding:10px 12px; color:#cbd5e1;">
                                    <span style="font-size:10px; font-weight:600; padding:2px 6px; border-radius:4px; display:inline-block; text-transform:uppercase;"
                                          :style="txn.tipo==='receber' ? 'background:rgba(16,185,129,0.15); color:#10b981;' : 'background:rgba(239,68,68,0.15); color:#ef4444;'"
                                          x-text="txn.asaas_tipo"></span>
                                </td>
                                <td style="padding:10px 12px; font-weight:600; text-align:right;" :style="txn.tipo==='receber'?'color:#10b981':'color:#ef4444'" x-text="formatarMoeda(txn.valor)"></td>
                                <td style="padding:10px 12px; color:#cbd5e1;">
                                    <span x-text="txn.descricao"></span>
                                    <span x-show="txn.duplicado" style="background:rgba(245,158,11,0.15); color:#f59e0b; border:1px solid rgba(245,158,11,0.3); font-size:10px; padding:2px 6px; border-radius:4px; margin-left:6px; display:inline-block;">Duplicado</span>
                                </td>
                                <td style="padding:10px 12px;">
                                    <select class="select" x-model="txn.acao_id" style="width:100%; padding:4px 8px; font-size:12px; height:auto; min-height:28px;" :disabled="txn.duplicado">
                                        <option value="novo">✨ Criar como Novo (Pago)</option>
                                        <option value="ignorar">❌ Ignorar / Não Importar</option>
                                        <optgroup label="Vincular a Pendente:">
                                            <template x-for="l in buscarPendentesParaAsaas(txn)">
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

            <div x-show="!asaasTransacoes.length && !consultandoAsaas && !asaasErro" style="text-align:center; padding:48px 24px; color:#6b7280; font-size:14px;">
                <i data-lucide="landmark" style="width:48px;height:48px;margin:0 auto 16px;display:block;opacity:0.3;"></i>
                Selecione um período e clique em Consultar para importar o extrato do Asaas.
            </div>

            <div x-show="asaasTransacoes.length > 0" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                <span style="font-size:12px; color:#6b7280;" x-text="`Total: ${asaasTransacoes.length} transações`"></span>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button class="btn-secondary" @click="modalAsaasAberto=false">Cancelar</button>
                    <button class="btn-primary" @click="processarExtratoAsaas()" :disabled="processandoAsaas">
                        <span x-show="!processandoAsaas">Processar Conciliação</span>
                        <span x-show="processandoAsaas">Processando...</span>
                    </button>
                </div>
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

    <!-- Modal Resultados IA (Conciliação) -->
    <div class="modal-overlay" x-show="modalIaResultadosAberto" x-cloak>
        <div class="modal" style="max-width:1000px; width:95%; height:90vh; display:flex; flex-direction:column;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div>
                    <h2 style="font-size:18px; font-weight:700; color:#f1f5f9; display:flex; align-items:center; gap:10px;">
                        <i data-lucide="sparkles" style="width:20px;height:20px;color:#10b981;"></i>
                        Revisar Lançamentos da IA
                    </h2>
                    <p style="font-size:12px; color:#6b7280; margin-top:4px;">Confirme os dados extraídos antes de salvar no sistema.</p>
                </div>
                <button @click="modalIaResultadosAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:20px;height:20px;"></i>
                </button>
            </div>

            <div style="flex:1; overflow-y:auto; margin-bottom:20px; padding-right:10px;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead style="position:sticky; top:0; background:#18181b; z-index:10;">
                        <tr style="border-bottom:1px solid #27272a;">
                            <th style="padding:12px; text-align:left; width:40px;"></th>
                            <th style="padding:12px; text-align:left; font-size:12px; color:#a1a1aa; font-weight:600;">TIPO</th>
                            <th style="padding:12px; text-align:left; font-size:12px; color:#a1a1aa; font-weight:600;">DESCRIÇÃO / ENTIDADE</th>
                            <th style="padding:12px; text-align:left; font-size:12px; color:#a1a1aa; font-weight:600;">VALOR</th>
                            <th style="padding:12px; text-align:left; font-size:12px; color:#a1a1aa; font-weight:600;">VENCIMENTO</th>
                            <th style="padding:12px; text-align:left; font-size:12px; color:#a1a1aa; font-weight:600;">CATEGORIA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in iaResultados" :key="index">
                            <tr style="border-bottom:1px solid #27272a;" :style="item.processar ? '' : 'opacity:0.5'">
                                <td style="padding:12px;">
                                    <input type="checkbox" x-model="item.processar" class="rounded border-zinc-700 bg-zinc-800 text-green-500 focus:ring-green-500">
                                </td>
                                <td style="padding:12px;">
                                    <select x-model="item.tipo" class="select" style="padding:4px 8px; font-size:12px; min-height:30px;">
                                        <option value="receber">Entrada</option>
                                        <option value="pagar">Saída</option>
                                    </select>
                                </td>
                                <td style="padding:12px;">
                                    <input type="text" x-model="item.descricao" class="input" style="padding:4px 8px; font-size:12px; min-height:30px; margin-bottom:4px;" placeholder="Descrição">
                                    <div style="display:flex; gap:6px; align-items:center;">
                                        <input type="text" x-model="item.entidade_nome" class="input" style="padding:2px 6px; font-size:11px; min-height:24px; flex:1;" placeholder="Cliente/Fornecedor">
                                        <span x-show="item.entidade_id" class="badge bg-green-500/10 text-green-500 border-green-500/20" style="font-size:9px;">✓ Existe</span>
                                    </div>
                                </td>
                                <td style="padding:12px;">
                                    <input type="number" x-model="item.valor" class="input" style="padding:4px 8px; font-size:12px; min-height:30px; width:100px;" step="0.01">
                                </td>
                                <td style="padding:12px;">
                                    <input type="date" x-model="item.vencimento" class="input" style="padding:4px 8px; font-size:12px; min-height:30px; width:130px;">
                                </td>
                                <td style="padding:12px;">
                                    <select x-model="item.categoria" class="select" style="padding:4px 8px; font-size:12px; min-height:30px;">
                                        <template x-for="cat in categoriasDisponiveis" :key="cat">
                                            <option :value="cat" x-text="cat.charAt(0).toUpperCase() + cat.slice(1)"></option>
                                        </template>
                                    </select>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; padding-top:20px; border-top:1px solid #27272a;">
                <button class="btn-secondary" @click="modalIaResultadosAberto=false">Cancelar</button>
                <button class="btn-primary" @click="processarLoteIA()" :disabled="salvando || iaResultados.filter(i => i.processar).length === 0">
                    <span x-show="!salvando">Salvar <span x-text="iaResultados.filter(i => i.processar).length"></span> Lançamento(s)</span>
                    <span x-show="salvando">Processando...</span>
                </button>
            </div>
        </div>
    </div>
    <div class="modal-overlay" x-show="modalIaAberto" x-cloak>
        <div class="modal" style="max-width:450px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="font-size:17px; font-weight:700; color:#f1f5f9; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="sparkles" style="width:18px;height:18px;color:#10b981;"></i>
                    Leitura Inteligente
                </h2>
                <button @click="modalIaAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>
            
            <div 
                @click="$refs.iaInput.click()"
                class="ia-capture-zone"
                :class="processandoIA ? 'is-processing' : ''"
            >
                <div x-show="!processandoIA" style="text-align:center;">
                    <div class="ia-icon-circle">
                        <i data-lucide="image-plus" style="width:32px; height:32px;"></i>
                    </div>
                    <p style="font-weight:700; color:#e2e8f0; margin-bottom:6px; font-size:15px;">Cole (Ctrl+V) ou clique aqui</p>
                    <p style="font-size:12px; color:#6b7280; line-height:1.5;">O Gemini identificará valores, datas e fornecedores automaticamente.</p>
                </div>

                <div x-show="processandoIA" style="text-align:center;">
                    <div class="ia-loading-animation"></div>
                    <p style="font-weight:700; color:#10b981; margin-bottom:6px; font-size:15px;">Analisando comprovante...</p>
                    <p style="font-size:12px; color:#6b7280;">Aguarde enquanto extraímos os dados.</p>
                </div>
            </div>

            <div style="margin-top:20px; display:flex; justify-content:flex-end;">
                <button class="btn-secondary" @click="modalIaAberto=false">Cancelar</button>
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
        filtros: { tipo: '', status: '', data_inicio: '', data_fim: '', busca: '', categoria: '', conta: '', conciliado: '' },
        periodoAtivo: 'mes',
        referenciaData: new Date().toISOString().split('T')[0],
        periodoTiposAberto: false,
        customDataInicio: '',
        customDataFim: '',
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
        processandoIA: false,
        modalIaAberto: false,
        modalIaResultadosAberto: false,
        iaResultados: [],
        ofxTransacoes: [],
        ofxContaId: '',
        contas: [],
        consultandoAsaas: false,
        asaasErro: '',
        modalAsaasAberto: false,
        asaasDataInicio: '',
        asaasDataFim: '',
        asaasTransacoes: [],
        processandoAsaas: false,
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
                if (l.status === 'cancelado') return false;
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
                const dataRef = (parseFloat(l.valor_pago) >= parseFloat(l.valor) && l.data_pagamento) ? l.data_pagamento : l.vencimento;
                if (this.filtros.data_inicio && dataRef < this.filtros.data_inicio) return false;
                if (this.filtros.data_fim && dataRef > this.filtros.data_fim) return false;
                if (this.filtros.conciliado !== '') {
                    const isConc = parseInt(l.conciliado) === 1;
                    const filtConc = parseInt(this.filtros.conciliado) === 1;
                    if (isConc !== filtConc) return false;
                }
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
            
            // Listener de colagem (Paste)
            window.addEventListener('paste', (e) => this.handlePaste(e));

            await Promise.all([
                this.carregarLancamentos(),
                this.carregarContas()
            ]);
        },

        async handlePaste(e) {
            const items = e.clipboardData.items;
            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    const blob = items[i].getAsFile();
                    const reader = new FileReader();
                    reader.onload = async (event) => {
                        await this.processarIA(event.target.result);
                    };
                    reader.readAsDataURL(blob);
                }
            }
        },

        async lerComprovante(e) {
            const file = e.target?.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = async (event) => {
                await this.processarIA(event.target.result);
            };
            reader.readAsDataURL(file);
            e.target.value = '';
        },

        async processarIA(base64) {
            this.processandoIA = true;
            toast('IA analisando comprovante...', 'info');
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/ia-processar.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ imagem: base64 })
                });
                const res = await r.json();
                if (r.ok) {
                    this.modalIaAberto = false;
                    this.abrirModalComIA(res);
                } else {
                    toast(res.erro || 'Erro na análise da IA', 'erro');
                }
            } catch(e) { toast('Erro de conexão com a IA', 'erro'); }
            this.processandoIA = false;
        },

        abrirModalComIA(lista) {
            this.iaResultados = lista.map(item => {
                // Tenta encontrar entidade existente
                let entidade_id = null;
                const docLimpo = (item.entidade_documento || '').replace(/\D/g, '');
                
                if (docLimpo) {
                    if (item.tipo === 'receber') {
                        const cli = this.clientes.find(c => (c.cpf_cnpj || '').replace(/\D/g, '') === docLimpo);
                        if (cli) entidade_id = cli.id;
                    } else {
                        const forn = this.fornecedores.find(f => (f.cpf_cnpj || f.documento || '').replace(/\D/g, '') === docLimpo);
                        if (forn) entidade_id = forn.id;
                    }
                }

                if (item.receita_federal) {
                    item.entidade_nome = item.receita_federal.razao_social || item.entidade_nome;
                }

                return {
                    ...item,
                    entidade_id: entidade_id,
                    processar: true,
                    categoria: item.categoria || 'outros'
                };
            });
            
            this.modalIaResultadosAberto = true;
            toast('IA identificou ' + lista.length + ' item(ns)!', 'sucesso');
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async processarLoteIA() {
            const itensParaProcessar = this.iaResultados.filter(i => i.processar);
            if (itensParaProcessar.length === 0) return;

            this.salvando = true;
            toast('Salvando lançamentos...', 'info');
            
            let sucesso = 0;
            try {
                for (const item of itensParaProcessar) {
                    const payload = {
                        tipo: item.tipo,
                        descricao: item.descricao,
                        valor: item.valor,
                        vencimento: item.vencimento,
                        categoria: item.categoria,
                        cliente_fornecedor: item.entidade_nome,
                        entidade_documento: item.entidade_documento,
                        status: 'pendente'
                    };

                    if (item.tipo === 'receber') payload.cliente_id = item.entidade_id;
                    else payload.fornecedor_id = item.entidade_id;

                    const r = await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (r.ok) sucesso++;
                }

                toast(`${sucesso} lançamentos criados com sucesso!`, 'sucesso');
                this.modalIaResultadosAberto = false;
                await this.carregarLancamentos();
            } catch (e) {
                toast('Erro ao processar lote', 'erro');
            }
            this.salvando = false;
        },

        async carregarContas() {
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/contas.php') ?>');
                if (!r.ok) {
                    console.error('Erro ao carregar contas:', r.status);
                    return;
                }
                this.contas = await r.json();
            } catch(e) {
                console.error('Erro ao carregar contas:', e);
                toast('Erro ao carregar contas bancárias', 'erro');
            }
        },

        mudarModoPeriodo() {
            if (this.periodoAtivo === 'personalizado') {
                if (!this.customDataInicio) {
                    const hoje = new Date();
                    this.customDataInicio = hoje.toISOString().split('T')[0];
                    const fim = new Date(hoje);
                    fim.setDate(fim.getDate() + 30);
                    this.customDataFim = fim.toISOString().split('T')[0];
                }
                this.aplicarPeriodoPersonalizado();
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                return;
            }
            if (this.periodoAtivo === 'tudo') {
                this.aplicarPeriodo();
                this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                return;
            }
            this.referenciaData = new Date().toISOString().split('T')[0];
            this.aplicarPeriodo();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        aplicarPeriodoPersonalizado() {
            this.filtros.data_inicio = this.customDataInicio || '';
            this.filtros.data_fim = this.customDataFim || '';
        },

        deslocarPeriodo(offset) {
            if (this.periodoAtivo === 'personalizado' || this.periodoAtivo === 'tudo') return;
            let [y, m, d] = this.referenciaData.split('-').map(Number);
            let date = new Date(y, m - 1, d);
            if (this.periodoAtivo === 'dia') date.setDate(date.getDate() + offset);
            else if (this.periodoAtivo === 'semana') date.setDate(date.getDate() + offset * 7);
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
            if (this.periodoAtivo === 'personalizado') {
                this.aplicarPeriodoPersonalizado();
                return;
            }
            if (this.periodoAtivo === 'semana') {
                let [y, m, d] = this.referenciaData.split('-').map(Number);
                const ref = new Date(y, m - 1, d);
                const diaSemana = ref.getDay();
                const dom = new Date(ref);
                dom.setDate(dom.getDate() - diaSemana);
                const sab = new Date(dom);
                sab.setDate(dom.getDate() + 6);
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
            } else if (this.periodoAtivo === 'semana') {
                const ref = new Date(y, m - 1, d);
                const diaSemana = ref.getDay();
                const dom = new Date(ref);
                dom.setDate(dom.getDate() - diaSemana);
                const sab = new Date(dom);
                sab.setDate(dom.getDate() + 6);
                const fmt = (dt) => `${String(dt.getDate()).padStart(2,'0')}/${String(dt.getMonth()+1).padStart(2,'0')}`;
                return `${fmt(dom)} - ${fmt(sab)}`;
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
            const item = this.lista.find(l => l.id === id);
            const ehCustoFixo = item && item.custo_fixo_id;
            if (!confirm(ehCustoFixo ? 'Cancelar este lançamento de custo fixo? Ele não será mais gerado automaticamente para este mês.' : 'Excluir este lançamento?')) return;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>', { 
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: [id] })
                });
                if (r.ok) {
                    toast(ehCustoFixo ? 'Lançamento cancelado. Para evitar novos lançamentos, desative o custo fixo.' : 'Lançamento excluído', 'sucesso');
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
            const temCustoFixo = this.selecionados.some(id => this.lista.find(l => l.id === id)?.custo_fixo_id);
            if (!confirm(temCustoFixo ? `Processar ${this.selecionados.length} lançamento(s)? Itens de custos fixos serão cancelados.` : `Excluir ${this.selecionados.length} lançamento(s)?`)) return;
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>', { 
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids: this.selecionados })
                });
                if (r.ok) {
                    toast(`${this.selecionados.length} lançamento(s) processado(s)`, 'sucesso');
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
                    const resp = await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>', {
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
                            ofx_fitid: txn.fitid,
                            conciliado: 1
                        })
                    });
                    if (!resp.ok) {
                        const err = await resp.json();
                        if (resp.status === 409) {
                            toast(err.erro, 'aviso');
                            continue;
                        }
                    }
                    sucesso++;
                } else {
                    // Vincular (Baixar a conta existente)
                    await fetch('<?= raizUrl('/api/financeiro/baixa.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: txn.acao_id, valor: txn.valor, conciliado: 1, data_pagamento: txn.data })
                    });
                    sucesso++;
                }
            }

            this.carregando = false;
            this.modalOfxAberto = false;
            toast(`Conciliação concluída! ${sucesso} processados.`, 'sucesso');
            await this.carregarLancamentos();
        },

        abrirModalExtratoAsaas() {
            const hoje = new Date();
            const inicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
            this.asaasDataFim = hoje.toISOString().split('T')[0];
            this.asaasDataInicio = inicio.toISOString().split('T')[0];
            this.asaasTransacoes = [];
            this.asaasErro = '';
            this.modalAsaasAberto = true;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async consultarExtratoAsaas() {
            if (!this.asaasDataInicio || !this.asaasDataFim) {
                toast('Selecione o período', 'erro');
                return;
            }
            this.consultandoAsaas = true;
            this.asaasErro = '';
            toast('Consultando extrato Asaas...', 'info');
            try {
                const params = new URLSearchParams();
                params.append('data_inicio', this.asaasDataInicio);
                params.append('data_fim', this.asaasDataFim);
                
                const r = await fetch('<?= raizUrl('/api/financeiro/extrato_asaas.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params
                });
                const res = await r.json();
                if (res.ok) {
                    this.asaasTransacoes = res.transacoes.map(t => {
                        const matchPendente = this.buscarPendentesParaAsaas(t)[0];
                        const matchPago = this.buscarPagosParaAsaas(t)[0];
                        let acao_id = 'novo';
                        let duplicado_hint = false;
                        if (matchPendente) {
                            acao_id = matchPendente.id;
                        } else if (matchPago) {
                            acao_id = 'ignorar';
                            duplicado_hint = true;
                        }
                        return { ...t, acao_id, duplicado: duplicado_hint, categoria: 'outros', categoriaCustom: '' };
                    });
                    toast(`${this.asaasTransacoes.length} transações encontradas`, 'sucesso');
                } else {
                    this.asaasErro = res.erro || 'Erro ao consultar extrato';
                    toast(this.asaasErro, 'erro');
                }
            } catch (err) {
                this.asaasErro = 'Erro de conexão: ' + err.message;
                toast(this.asaasErro, 'erro');
            }
            this.consultandoAsaas = false;
        },

        buscarPendentesParaAsaas(txn) {
            return this.lista.filter(l => 
                l.tipo === txn.tipo && 
                l.status !== 'pago' && 
                l.status !== 'cancelado' &&
                Math.abs(l.valor - txn.valor) <= (txn.valor * 0.10)
            );
        },

        buscarPagosParaAsaas(txn) {
            return this.lista.filter(l => 
                l.tipo === txn.tipo && 
                l.status === 'pago' && 
                Math.abs(l.valor - txn.valor) < 0.05 &&
                l.vencimento === txn.data
            );
        },

        async processarExtratoAsaas() {
            toast('Processando conciliação Asaas...', 'info');
            let sucesso = 0;
            this.processandoAsaas = true;

            for (const txn of this.asaasTransacoes) {
                if (txn.acao_id === 'ignorar') continue;
                if (txn.duplicado) continue;
                
                if (txn.acao_id === 'novo') {
                    let catFinal = txn.categoria || 'outros';
                    if (catFinal === '__custom__' && txn.categoriaCustom) {
                        catFinal = txn.categoriaCustom.trim().toLowerCase();
                        this.salvarCategoriaCustomizada(catFinal);
                    } else if (catFinal === '__custom__') {
                        catFinal = 'outros';
                    }

                    const descFinal = txn.descricao + ' (Asaas)';
                    const observacao = '[ASAAS:' + txn.fitid + '] ' + descFinal;
                    
                    const resp = await fetch('<?= raizUrl('/api/financeiro/lancamentos.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            tipo: txn.tipo,
                            descricao: descFinal,
                            valor: txn.valor,
                            valor_pago: txn.valor,
                            status: 'pago',
                            vencimento: txn.data,
                            categoria: catFinal,
                            conta_id: this.ofxContaId,
                            observacao: observacao,
                            conciliado: 1
                        })
                    });
                    if (!resp.ok) {
                        const err = await resp.json();
                        toast(err.erro || 'Erro ao salvar', 'erro');
                        continue;
                    }
                    sucesso++;
                } else {
                    await fetch('<?= raizUrl('/api/financeiro/baixa.php') ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: txn.acao_id, valor: txn.valor, conciliado: 1, data_pagamento: txn.data })
                    });
                    sucesso++;
                }
            }

            this.processandoAsaas = false;
            this.modalAsaasAberto = false;
            toast(`Conciliação Asaas concluída! ${sucesso} processados.`, 'sucesso');
            await this.carregarLancamentos();
        },

        limparFiltros() {
            this.filtros = {
                tipo: '',
                busca: '',
                categoria: '',
                conta: '',
                status: '',
                data_inicio: '',
                data_fim: '',
                conciliado: ''
            };
            this.periodoAtivo = 'mes';
            this.referenciaData = new Date().toISOString().split('T')[0];
            this.customDataInicio = '';
            this.customDataFim = '';
            this.aplicarPeriodo();
            toast('Filtros limpos', 'info');
        },

        formatarData(str) { return window.formatarData(str); },
        formatarMoeda(val) { return window.formatarMoeda(val); },
    };
}
</script>

<style>
.hover-underline:hover { text-decoration: underline; }

.ia-capture-zone {
    border: 2px dashed #334155;
    border-radius: 24px;
    padding: 48px 24px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: rgba(15, 23, 42, 0.3);
    position: relative;
    overflow: hidden;
}
.ia-capture-zone:hover {
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.05);
    transform: translateY(-2px);
}
.ia-capture-zone.is-processing {
    border-color: #10b981;
    cursor: default;
    pointer-events: none;
}
.ia-icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}
.ia-loading-animation {
    width: 50px;
    height: 50px;
    border: 3px solid rgba(16, 185, 129, 0.1);
    border-top-color: #10b981;
    border-radius: 50%;
    animation: ia-spin 1s linear infinite;
    margin: 0 auto 20px;
}
@keyframes ia-spin {
    to { transform: rotate(360deg); }
}
</style>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
