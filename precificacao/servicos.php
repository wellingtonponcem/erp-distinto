<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
exigirAutenticacao();
$tituloPagina = 'Serviços';
include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;" x-data="servicos()">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet !bg-background !p-6 flex flex-col flex-1">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <!-- Topbar -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-8 mt-2">
            <div>
                <h1 class="font-display-lg text-on-surface mb-1">Tabela de Serviços</h1>
                <p class="text-body-md text-on-surface-variant">Catálogo com preço mínimo calculado automaticamente</p>
            </div>
            <button class="bg-primary hover:bg-primary-container text-on-primary-container font-bold px-6 py-2.5 rounded-lg text-body-md transition-all active:scale-95 duration-150 shadow-lg flex items-center gap-2" @click="abrirModal()">
                <i data-lucide="plus" class="w-4 h-4"></i> Novo Serviço
            </button>
        </div>
        
        <!-- Filtro de Categoria (Abas) -->
        <div class="glass-card p-1 rounded-xl flex gap-1.5 mb-6 max-w-md">
            <button @click="categoriaAtiva = 'marketing'" 
                    :class="categoriaAtiva === 'marketing' ? 'bg-primary text-on-primary shadow-lg' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/40'"
                    class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs flex items-center justify-center gap-2 transition-all duration-200">
                <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                Marketing
            </button>
            <button @click="categoriaAtiva = 'wedding'" 
                    :class="categoriaAtiva === 'wedding' ? 'bg-primary text-on-primary shadow-lg' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/40'"
                    class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs flex items-center justify-center gap-2 transition-all duration-200">
                <i data-lucide="camera" class="w-3.5 h-3.5"></i>
                Wedding
            </button>
            <button @click="categoriaAtiva = '15anos'" 
                    :class="categoriaAtiva === '15anos' ? 'bg-primary text-on-primary shadow-lg' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high/40'"
                    class="flex-1 px-4 py-2.5 rounded-lg font-bold text-xs flex items-center justify-center gap-2 transition-all duration-200">
                <i data-lucide="party-popper" class="w-3.5 h-3.5"></i>
                15 Anos
            </button>
        </div>

        <!-- Configuração de horas mensais -->
        <div class="glass-card p-4 rounded-xl flex flex-wrap items-center justify-between gap-4 mb-6 text-xs text-on-surface-variant">
            <div class="flex items-center gap-4 flex-wrap">
                <div class="flex items-center gap-1.5">
                    <i data-lucide="info" class="w-4 h-4 text-primary shrink-0"></i>
                    <span>Horas mensais de capacidade da agência:</span>
                </div>
                <div class="flex items-center gap-2">
                    <input class="input w-24 !py-1 !px-2.5" type="number" min="1" x-model="horasMensais" placeholder="160">
                    <button class="btn-secondary !py-1 !px-3 flex items-center gap-1" @click="abrirPlanejadorIA()">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-primary"></i> Planejar com IA
                    </button>
                </div>
            </div>
            <div class="font-bold">
                Custo fixo total mensal:
                <span class="text-error font-data-tabular ml-1" x-text="formatarMoeda(totalCustosFixos)"></span>
            </div>
        </div>

        <!-- Tabela de Serviços -->
        <div class="glass-card rounded-xl overflow-hidden mb-6">
            <div class="grid grid-cols-[2.5fr_100px_1.5fr_1.5fr_1.5fr_100px_100px] items-center px-6 py-4 bg-surface-container-low border-b border-outline-variant/20">
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Serviço</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Horas</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Preço Mínimo</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">P. Recorrente</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">P. Pontual</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px]">Markup</span>
                <span class="text-label-caps font-label-caps text-on-surface-variant text-[10px] text-right">Ações</span>
            </div>

            <template x-if="carregando">
                <div class="p-10 text-center text-on-surface-variant">Carregando...</div>
            </template>

            <template x-if="!carregando && lista.length === 0">
                <div class="p-10 text-center text-on-surface-variant">
                    <i data-lucide="briefcase" class="w-8 h-8 mx-auto mb-3 opacity-40"></i>
                    Nenhum serviço cadastrado
                </div>
            </template>

            <div class="divide-y divide-outline-variant/20">
                <template x-for="s in listaFiltrada" :key="s.id">
                    <div class="grid grid-cols-[2.5fr_100px_1.5fr_1.5fr_1.5fr_100px_100px] items-center px-6 py-4 hover:bg-surface-container-high/20 transition-colors group">
                        <div class="min-w-0 pr-4">
                            <div class="text-sm font-bold text-on-surface truncate" x-text="s.nome"></div>
                            <div class="text-xs text-on-surface-variant truncate mt-0.5" :title="s.descricao" x-text="s.descricao || ''"></div>
                        </div>
                        <div class="text-xs text-on-surface-variant font-data-tabular" x-text="s.horas_estimadas + 'h'"></div>
                        <div class="text-xs text-on-surface-variant font-data-tabular" x-text="formatarMoeda(calcularPrecoMinimo(s))"></div>
                        <div>
                            <span class="text-sm font-bold font-data-tabular" :class="s.periodicidade === 'mensal' ? 'text-primary' : 'text-on-surface-variant/40'" x-text="s.preco_venda > 0 ? formatarMoeda(s.preco_venda) : '—'"></span>
                        </div>
                        <div>
                            <span class="text-sm font-bold font-data-tabular" :class="s.periodicidade === 'pontual' ? 'text-primary' : 'text-on-surface-variant/40'" x-text="s.preco_venda_pontual > 0 ? formatarMoeda(s.preco_venda_pontual) : '—'"></span>
                        </div>
                        <div>
                            <span class="text-xs font-bold text-on-surface-variant font-data-tabular" x-text="s.markup + '%'"></span>
                        </div>
                        <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="abrirModal(s)" class="p-1.5 text-on-surface-variant hover:text-on-surface hover:bg-surface-variant rounded transition-colors" title="Editar">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button @click="excluir(s.id)" class="p-1.5 text-error/70 hover:text-error hover:bg-error-container/10 rounded transition-colors" title="Excluir">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <p class="text-xs text-on-surface-variant mt-2">
            💡 Preço mínimo = (horas / capacidade mensal × custos fixos) + custo variável + markup aplicado.
        </p>
    </main>

    <!-- Modal Serviço -->
    <div class="modal-overlay" x-show="modalAberto" x-cloak @click.self="modalAberto=false">
        <div class="modal w-full max-w-2xl p-6 bg-surface-container-low border border-outline-variant/30 rounded-2xl shadow-2xl relative animate-in fade-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto">
            <button @click="modalAberto=false" class="absolute top-6 right-6 text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            
            <div class="flex items-center gap-3 mb-6 pr-8">
                <h2 class="text-title-sm font-headline-md font-bold text-on-surface" x-text="form.id ? 'Editar Serviço' : 'Novo Serviço'"></h2>
                <button type="button" @click="melhorarServicoIA()" :disabled="melhorandoIA" class="bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 px-3 py-1 rounded-md text-[10px] font-label-caps transition-all flex items-center gap-1 cursor-pointer">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span x-text="melhorandoIA ? 'Melhorando...' : 'Editar com IA'"></span>
                </button>
            </div>

            <form @submit.prevent="salvar()">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="label">Categoria</label>
                        <select class="select w-full" x-model="form.categoria">
                            <option value="marketing">Marketing</option>
                            <option value="wedding">Wedding</option>
                            <option value="15anos">15 Anos</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Tipo de Item</label>
                        <select class="select w-full" x-model="form.tipo">
                            <option value="servico">Serviço / Upgrade</option>
                            <option value="plano">Plano Base (Pacote)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Configuração de Itens (Apenas para Planos que não sejam de Eventos) -->
                <div x-show="form.tipo === 'plano' && form.categoria === 'marketing'" class="bg-primary/5 p-4 rounded-xl border border-primary/10 mb-4">
                    <label class="label flex items-center gap-1.5 text-primary">
                        <i data-lucide="list-checks" class="w-4 h-4"></i>
                        Configuração de Itens (JSON)
                    </label>
                    <textarea class="input w-full font-mono text-[11px] mt-1" x-model="form.itens_json" rows="3" placeholder='Ex: {"servico_id": "incluso", "outro_id": "opcional"}'></textarea>
                    <p class="text-[10px] text-on-surface-variant mt-1.5">
                        💡 Liste os IDs dos serviços e defina se são "incluso" ou "opcional".
                    </p>
                </div>

                <div class="mb-4">
                    <label class="label">Nome do Serviço *</label>
                    <input class="input w-full" x-model="form.nome" required placeholder="Ex: Registro Essencial">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" x-show="form.categoria === 'marketing'">
                    <div>
                        <label class="label">Periodicidade</label>
                        <select class="select w-full" x-model="form.periodicidade">
                            <option value="mensal">Mensal (Recorrente)</option>
                            <option value="pontual">Pontual (Projeto Único)</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Prazo Mínimo (Meses)</label>
                        <input class="input w-full" type="number" min="0" x-model="form.prazo_minimo" placeholder="Ex: 6 (0 para s/ mínimo)">
                    </div>
                </div>

                <div class="mb-4" x-show="form.categoria === 'marketing'">
                    <label class="label">Descrição Básica</label>
                    <textarea class="textarea w-full" x-model="form.descricao" rows="2" placeholder="O que é o serviço de forma resumida..."></textarea>
                </div>

                <!-- Campos Específicos para Eventos (Wedding / 15 Anos) -->
                <div x-show="form.categoria === 'wedding' || form.categoria === '15anos'" class="bg-tertiary/5 p-4 rounded-xl border border-tertiary/10 mb-4">
                    <div class="mb-3">
                        <label class="label text-tertiary">Subtítulo (Tagline)</label>
                        <input class="input w-full" x-model="form.subtitulo" placeholder="Ex: O plano definitivo para casais...">
                    </div>
                    
                    <div>
                        <label class="label text-tertiary flex justify-between items-center mb-2">
                            Benefícios / Diferenciais (Itens)
                            <button type="button" @click="adicionarBeneficio()" class="bg-tertiary text-on-tertiary px-2 py-0.5 rounded text-[10px] font-bold">
                                + Adicionar Item
                            </button>
                        </label>
                        <div class="flex flex-col gap-2">
                            <template x-for="(b, index) in form.beneficios_lista" :key="index">
                                <div class="flex gap-2">
                                    <input class="input w-full text-xs" x-model="form.beneficios_lista[index]" placeholder="Descreva o benefício...">
                                    <button type="button" @click="removerBeneficio(index)" class="text-error/70 hover:text-error p-1">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                
                <!-- Vínculo de Upgrades / Adicionais (Apenas para Planos de Eventos) -->
                <div x-show="form.tipo === 'plano' && (form.categoria === 'wedding' || form.categoria === '15anos')" class="bg-primary/5 p-4 rounded-xl border border-primary/10 mb-4">
                    <label class="label flex items-center gap-1.5 text-primary mb-3">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                        Upgrades e Adicionais Sugeridos
                    </label>
                    <div class="flex flex-col gap-2">
                        <template x-for="u in upgradesDisponiveis" :key="u.id">
                            <div class="flex items-center justify-between bg-surface-container-high/40 p-2.5 rounded-lg border border-outline-variant/10">
                                <span class="text-xs font-bold text-on-surface" x-text="u.nome"></span>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-1.5 text-[11px] text-on-surface-variant cursor-pointer">
                                        <input type="checkbox" :checked="isUpgradeVinculado(u.id, 'opcional')" @change="toggleUpgrade(u.id, 'opcional')">
                                        Disponível
                                    </label>
                                    <label class="flex items-center gap-1.5 text-[11px] text-on-surface-variant cursor-pointer">
                                        <input type="checkbox" :checked="isUpgradeVinculado(u.id, 'incluso')" @change="toggleUpgrade(u.id, 'incluso')">
                                        Incluso
                                    </label>
                                </div>
                            </div>
                        </template>
                        <div x-show="upgradesDisponiveis.length === 0" class="text-xs text-on-surface-variant text-center py-2 italic">
                            Nenhum upgrade cadastrado nesta categoria.
                        </div>
                    </div>
                </div>

                <div class="mb-4" x-show="form.categoria === 'marketing'">
                    <label class="label">Entregáveis (Escopo)</label>
                    <textarea class="textarea w-full" x-model="form.entregaveis" rows="2" placeholder="Ex: 4 posts semanais, 1 relatório mensal, etc..."></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" x-show="form.categoria === 'marketing'">
                    <div>
                        <label class="label">Ferramentas Adicionais</label>
                        <input class="input w-full" x-model="form.ferramentas" placeholder="Ex: Canva Pro, RD Station...">
                    </div>
                    <div>
                        <label class="label">Terceirização (Custo/O que)</label>
                        <input class="input w-full" x-model="form.terceirizacao" placeholder="Ex: R$ 200,00 - Designer Freelancer">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" x-show="form.categoria === 'marketing'">
                    <div>
                        <label class="label">Horas/Dia (Dedicadas) *</label>
                        <input class="input w-full" type="number" step="0.1" min="0" x-model="form.horas_dia" @input="calcularHorasMensaisServico()" :required="form.categoria === 'marketing'" placeholder="Ex: 0.5">
                    </div>
                    <div>
                        <label class="label">Horas Estimadas (Mês) *</label>
                        <input class="input w-full" type="number" step="0.5" min="0.5" x-model="form.horas_estimadas" :required="form.categoria === 'marketing'" placeholder="Ex: 20">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4" x-show="form.categoria === 'marketing'">
                    <div>
                        <label class="label">Custo de Produção (R$) *</label>
                        <input class="input w-full" type="number" step="0.01" min="0" x-model="form.custo_producao" :required="form.categoria === 'marketing'" placeholder="0,00">
                    </div>
                    <div>
                        <label class="label">Custos Variáveis (R$)</label>
                        <input class="input w-full" type="number" step="0.01" min="0" x-model="form.custos_variaveis" placeholder="Ferramentas, etc.">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-2">
                    <div class="bg-primary/5 p-4 rounded-xl border border-primary/20">
                        <div class="flex justify-between items-center mb-2">
                            <label class="label !mb-0" x-text="form.categoria === 'marketing' ? 'Preço Recorrente (R$)' : 'Investimento (R$)'"></label>
                            <button type="button" class="bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 px-2 py-0.5 rounded text-[10px] font-bold transition-all flex items-center gap-1" @click="sugerirPrecoIA('recorrente')" :disabled="sugerindoPreco" x-show="form.categoria === 'marketing'">
                                <i data-lucide="sparkles" class="w-3 h-3"></i> Sugerir
                            </button>
                        </div>
                        <input class="input w-full !text-base !font-bold text-primary font-data-tabular bg-transparent border-none focus:ring-0 p-0" type="number" step="0.01" x-model="form.preco_venda" placeholder="0,00">
                    </div>

                    <div class="bg-primary/5 p-4 rounded-xl border border-primary/20" x-show="form.categoria === 'marketing'">
                        <div class="flex justify-between items-center mb-2">
                            <label class="label !mb-0">Preço Pontual (R$)</label>
                            <button type="button" class="bg-primary/10 hover:bg-primary/20 text-primary border border-primary/20 px-2 py-0.5 rounded text-[10px] font-bold transition-all flex items-center gap-1" @click="sugerirPrecoIA('pontual')" :disabled="sugerindoPreco">
                                <i data-lucide="sparkles" class="w-3 h-3"></i> Sugerir
                            </button>
                        </div>
                        <input class="input w-full !text-base !font-bold text-primary font-data-tabular bg-transparent border-none focus:ring-0 p-0" type="number" step="0.01" x-model="form.preco_venda_pontual" placeholder="0,00">
                    </div>
                </div>

                <div class="text-[10px] text-on-surface-variant font-label-caps mb-4 pl-1" x-show="form.categoria === 'marketing'">
                    💡 Piso mínimo (custo + rateio): <span class="font-bold font-data-tabular" x-text="formatarMoeda(calcularPrecoMinimo(form))"></span>
                </div>
                
                <div class="mb-6" x-show="form.categoria === 'marketing'">
                    <label class="label">Markup Manual (%)</label>
                    <input class="input w-full" type="number" step="0.5" min="0" x-model="form.markup" @input="recalcularPrecoPeloMarkup()" placeholder="Ex: 30">
                </div>

                <div class="flex gap-3 justify-end">
                    <button type="button" class="btn-secondary" @click="modalAberto=false">Cancelar</button>
                    <button type="submit" class="btn-primary" :disabled="salvando" x-text="salvando ? 'Salvando...' : (form.id ? 'Atualizar' : 'Criar Serviço')"></button>
                    <!-- Modal Planejador IA -->
    <div class="modal-overlay" x-show="modalPlanejadorAberto" x-cloak @click.self="modalPlanejadorAberto=false">
        <div class="modal w-full max-w-md p-6 bg-surface-container-low border border-outline-variant/30 rounded-2xl shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
            <button @click="modalPlanejadorAberto=false" class="absolute top-6 right-6 text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>

            <div class="mb-6">
                <h2 class="text-title-sm font-headline-md font-bold text-on-surface flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-5 h-5 text-primary"></i>
                    Planejador de Capacidade IA
                </h2>
                <p class="text-body-md text-on-surface-variant mt-1">Responda brevemente para a IA calcular sua capacidade mensal real.</p>
            </div>

            <div class="space-y-4 mb-6">
                <div>
                    <label class="label">Quantas pessoas trabalham na produção?</label>
                    <input class="input w-full" x-model="planejador.equipe" placeholder="Ex: 2 pessoas + eu">
                </div>
                <div>
                    <label class="label">Qual a jornada diária e dias por semana?</label>
                    <input class="input w-full" x-model="planejador.jornada" placeholder="Ex: 8h por dia, segunda a sexta">
                </div>
                <div>
                    <label class="label">Alguma observação sobre produtividade?</label>
                    <textarea class="textarea w-full" x-model="planejador.obs" rows="2" placeholder="Ex: Perdemos 20% do tempo em reuniões..."></textarea>
                </div>
            </div>

            <div class="flex gap-3 justify-end">
                <button class="btn-secondary" @click="modalPlanejadorAberto=false">Cancelar</button>
                <button class="btn-primary" @click="calcularCapacidadeIA()" :disabled="planejando">
                    <span x-show="!planejando">Calcular Capacidade</span>
                    <span x-show="planejando">Analisando...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Painel de Chat IA -->
    <div class="chat-ai-panel bg-surface-container-low border-l border-outline-variant/30 shadow-2xl" :class="chatAberto ? 'active' : ''" x-cloak>
        <div class="flex flex-col h-full">
            <div class="p-5 border-b border-outline-variant/20 flex justify-between items-center bg-surface-container-low/60 backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <div class="font-bold text-on-surface text-xs font-label-caps truncate max-w-[200px]" x-text="form.nome || 'Novo Serviço'"></div>
                        <div class="text-[9px] font-label-caps text-primary tracking-wider mt-0.5">ESTRUTURAÇÃO & PRECIFICAÇÃO</div>
                    </div>
                </div>
                <button @click="chatAberto=false" class="text-on-surface-variant hover:text-on-surface transition-colors">
                    <i data-lucide="chevron-right" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Corpo do Chat -->
            <div id="chat-body" class="flex-1 overflow-y-auto p-5 flex flex-col gap-4 scroll-smooth">
                <template x-for="msg in chatHistorico">
                    <div class="max-w-[85%] flex flex-col" :class="msg.role === 'user' ? 'self-end' : 'self-start'">
                        <div class="p-3.5 rounded-2xl text-xs line-height-relaxed"
                             :class="msg.role === 'user' 
                                ? 'bg-primary text-on-primary rounded-tr-none' 
                                : 'bg-surface-container-high border border-outline-variant/20 text-on-surface rounded-tl-none'"
                             x-text="msg.content">
                        </div>
                        <span class="text-[9px] text-on-surface-variant font-label-caps mt-1"
                              :class="msg.role === 'user' ? 'text-right' : 'text-left'"
                              x-text="msg.role === 'user' ? 'Você' : 'Assistente IA'"></span>
                    </div>
                </template>
                
                <div x-show="melhorandoIA" class="self-start bg-surface-container-high/40 p-3 rounded-2xl border border-outline-variant/10 flex gap-1 items-center">
                    <span class="dot-loading"></span>
                    <span class="dot-loading" style="animation-delay:0.2s"></span>
                    <span class="dot-loading" style="animation-delay:0.4s"></span>
                </div>
            </div>

            <!-- Input do Chat -->
            <div class="p-5 bg-surface-container-low border-t border-outline-variant/20">
                <div class="relative">
                    <textarea 
                        x-model="chatMensagem" 
                        @keydown.enter.prevent="enviarMensagemChatIA()" 
                        placeholder="Diga o que deseja alterar..." 
                        rows="1" 
                        class="w-full input pr-12 text-xs focus:ring-1 focus:ring-primary/40 resize-none py-3"
                        :disabled="melhorandoIA"
                    ></textarea>
                    <button 
                        @click="enviarMensagemChatIA()" 
                        :disabled="!chatMensagem.trim() || melhorandoIA"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-primary text-on-primary hover:bg-primary-container hover:text-on-primary-container transition-all flex items-center justify-center disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer"
                    >
                        <i data-lucide="send" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="text-center mt-2.5 text-[9px] text-on-surface-variant font-label-caps">
                    Pressione Enter para enviar
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .chat-ai-panel {
        position: fixed;
        right: -400px;
        top: 0;
        bottom: 0;
        width: 380px;
        z-index: 10001;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
    }
    .chat-ai-panel.active {
        right: 0;
    }
    .chat-ai-panel * {
        position: relative;
        z-index: 2;
    }
    .dot-loading {
        width: 6px;
        height: 6px;
        background: var(--color-on-surface-variant, #6b7280);
        border-radius: 50%;
        display: inline-block;
        animation: dot-pulse 1.4s infinite ease-in-out;
    }
    @keyframes dot-pulse {
        0%, 80%, 100% { transform: scale(0); opacity: 0.3; }
        40% { transform: scale(1); opacity: 1; }
    .modal-overlay {
        z-index: 10000;
    }
</style>

<script>
function servicos() {
    return {
        lista: [],
        carregando: true,
        salvando: false,
        modalAberto: false,
        form: {},
        totalCustosFixos: 0,
        horasMensais: parseInt(localStorage.getItem('cap_horas_mensais') || 160),
        categoriaAtiva: 'marketing', // Nova variável para abas
        
        // Sugestão de Preço IA
        sugerindoPreco: false,
        melhorandoIA: false,
        chatAberto: false,
        chatMensagem: '',
        chatHistorico: [],
        planejador: {
            equipe: '',
            jornada: '',
            obs: ''
        },

        get listaFiltrada() {
            return this.lista.filter(s => (s.categoria || 'marketing') === this.categoriaAtiva);
        },

        get upgradesDisponiveis() {
            return this.lista.filter(s => 
                (s.categoria || 'marketing') === this.form.categoria && 
                s.tipo === 'servico' && 
                s.id !== this.form.id
            );
        },

        async init() {
            await Promise.all([this.carregar(), this.carregarCustosFixos()]);
            this.$watch('categoriaAtiva', () => this.$nextTick(() => lucide.createIcons()));
        },

        async carregar() {
            this.carregando = true;
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/servicos.php') ?>');
                this.lista = await r.json();
            } catch(e) { toast('Erro ao carregar serviços', 'erro'); }
            this.carregando = false;
            this.$nextTick(() => lucide.createIcons());
        },

        async carregarCustosFixos() {
            try {
                const r = await fetch('<?= raizUrl('/api/financeiro/custos-fixos.php') ?>');
                const custos = await r.json();
                this.totalCustosFixos = custos
                    .filter(c => c.ativo == '1')
                    .reduce((s, c) => s + (c.recorrencia === 'anual' ? parseFloat(c.valor)/12 : parseFloat(c.valor)), 0);
            } catch(e) {}
        },

        calcularPrecoMinimo(s) {
            const horas = parseFloat(s.horas_estimadas || 0);
            const custo = parseFloat(s.custo_producao || 0) + parseFloat(s.custos_variaveis || 0);
            const rateio = this.horasMensais > 0 ? (horas / this.horasMensais) * this.totalCustosFixos : 0;
            const markup = parseFloat(s.markup || 0) / 100;
            return (rateio + custo) * (1 + markup);
        },
        abrirModal(item = null) {
            this.form = item ? { ...item } : { 
                nome:'', 
                categoria: this.categoriaAtiva,
                tipo: 'servico',
                itens_json: '',
                descricao:'', 
                entregaveis: '',
                ferramentas: '',
                terceirizacao: '',
                periodicidade: 'mensal',
                prazo_minimo: 0,
                horas_dia: '',
                horas_estimadas:'', 
                custo_producao:'', 
                custos_variaveis:'0', 
                preco_venda: 0,
                preco_venda_pontual: 0,
                markup:'30',
                subtitulo: '',
                beneficios_lista: []
            };
            if (item && item.beneficios_json) {
                try {
                    this.form.beneficios_lista = JSON.parse(item.beneficios_json);
                } catch(e) { this.form.beneficios_lista = []; }
            } else if (!item) {
                this.form.beneficios_lista = [];
            }
            if (this.form.id && this.form.horas_estimadas) {
                this.form.horas_dia = (parseFloat(this.form.horas_estimadas) / 22).toFixed(1);
            }
            if (!this.form.preco_venda) {
                this.form.preco_venda = this.calcularPrecoMinimo(this.form);
            }
            this.chatAberto = false;
            this.chatHistorico = [];
            this.chatMensagem = '';
            this.modalAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        adicionarBeneficio() {
            if (!this.form.beneficios_lista) this.form.beneficios_lista = [];
            this.form.beneficios_lista.push('');
        },

        removerBeneficio(index) {
            this.form.beneficios_lista.splice(index, 1);
        },

        isUpgradeVinculado(id, status) {
            if (!this.form.itens_json) return false;
            try {
                const itens = JSON.parse(this.form.itens_json);
                return itens[id] === status;
            } catch(e) { return false; }
        },

        toggleUpgrade(id, status) {
            let itens = {};
            try {
                if (this.form.itens_json) itens = JSON.parse(this.form.itens_json);
            } catch(e) { itens = {}; }

            if (itens[id] === status) {
                delete itens[id];
            } else {
                itens[id] = status;
            }
            this.form.itens_json = JSON.stringify(itens);
        },

        recalcularPrecoPeloMarkup() {
            this.form.preco_venda = this.calcularPrecoMinimo(this.form);
            this.form.preco_venda_pontual = this.form.preco_venda * 1.5; // Sugestão padrão pontual 50% mais caro
        },

        async melhorarServicoIA() {
            if (!this.form.nome) {
                toast('Dê um nome ou descrição básica primeiro', 'aviso');
                return;
            }
            this.chatAberto = true;
            if (this.chatHistorico.length === 0) {
                this.chatHistorico.push({ role: 'assistant', content: `Olá! Sou seu assistente de estruturação. Como posso ajudar a melhorar o serviço "${this.form.nome}"?` });
            }
            this.$nextTick(() => {
                const body = document.getElementById('chat-body');
                if (body) body.scrollTop = body.scrollHeight;
                if (window.lucide) lucide.createIcons();
            });
        },

        async enviarMensagemChatIA() {
            if (!this.chatMensagem.trim() || this.melhorandoIA) return;

            const userMsg = this.chatMensagem.trim();
            this.chatHistorico.push({ role: 'user', content: userMsg });
            this.chatMensagem = '';
            this.melhorandoIA = true;

            this.$nextTick(() => {
                const body = document.getElementById('chat-body');
                if (body) body.scrollTop = body.scrollHeight;
            });

            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/editar-servico-ia.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        servico: this.form,
                        mensagens: this.chatHistorico
                    })
                });
                const res = await r.json();
                if (r.ok) {
                    this.chatHistorico.push({ role: 'assistant', content: res.mensagem });
                    this.form.nome = res.nome;
                    this.form.descricao = res.descricao;
                    this.form.entregaveis = res.entregaveis;
                    
                    // Atualizar preços e markup sugeridos se retornados
                    if (res.preco_venda) this.form.preco_venda = res.preco_venda;
                    if (res.preco_venda_pontual) this.form.preco_venda_pontual = res.preco_venda_pontual;
                    if (res.markup) this.form.markup = res.markup;
                    
                    this.$nextTick(() => {
                        const body = document.getElementById('chat-body');
                        if (body) body.scrollTop = body.scrollHeight;
                    });
                } else { 
                    toast(res.erro || 'Erro na IA', 'erro'); 
                    this.chatHistorico.push({ role: 'assistant', content: 'Desculpe, tive um problema ao processar sua solicitação.' });
                }
            } catch(e) { 
                toast('Erro de conexão', 'erro'); 
            }
            this.melhorandoIA = false;
        },

        async sugerirPrecoIA(tipo = 'recorrente') {
            if (!this.form.nome || !this.form.horas_estimadas) {
                toast('Preencha o nome e as horas primeiro', 'aviso');
                return;
            }
            this.sugerindoPreco = true;
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/sugerir-preco-servico.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        servico: this.form,
                        tipo: tipo,
                        totalCustosFixos: this.totalCustosFixos,
                        horasMensais: this.horasMensais,
                        precoMinimo: this.calcularPrecoMinimo(this.form)
                    })
                });
                const res = await r.json();
                if (r.ok) {
                    if (tipo === 'recorrente') {
                        this.form.preco_venda = res.preco;
                        this.form.markup = res.markup_sugerido;
                    } else {
                        this.form.preco_venda_pontual = res.preco;
                    }
                    toast('Preço sugerido pela IA!', 'sucesso');
                } else { toast(res.erro || 'Erro na IA', 'erro'); }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.sugerindoPreco = false;
        },

        calcularHorasMensaisServico() {
            const hDia = parseFloat(this.form.horas_dia || 0);
            this.form.horas_estimadas = (hDia * 22).toFixed(1); // Média de 22 dias úteis
        },

        abrirPlanejadorIA() {
            this.modalPlanejadorAberto = true;
            this.$nextTick(() => lucide.createIcons());
        },

        async calcularCapacidadeIA() {
            if (!this.planejador.equipe || !this.planejador.jornada) return;
            this.planejando = true;
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/planejar-capacidade.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.planejador)
                });
                const res = await r.json();
                if (r.ok) {
                    const horasResult = parseInt(res.horas);
                    if (isNaN(horasResult) || horasResult <= 0) {
                        toast('IA retornou um valor inválido', 'erro');
                        return;
                    }
                    this.horasMensais = horasResult;
                    localStorage.setItem('cap_horas_mensais', horasResult);
                    toast(`Sucesso! Capacidade ajustada para ${horasResult}h`, 'sucesso');
                    this.modalPlanejadorAberto = false;
                } else {
                    toast(res.erro || 'Erro ao calcular', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.planejando = false;
        },

        async salvar() {
            this.salvando = true;
            try {
                const metodo = this.form.id ? 'PUT' : 'POST';
                const payload = { ...this.form };
                if (payload.beneficios_lista) {
                    payload.beneficios_json = JSON.stringify(payload.beneficios_lista.filter(b => b.trim() !== ''));
                    delete payload.beneficios_lista;
                }
                const r = await fetch('<?= raizUrl('/api/precificacao/servicos.php') ?>', {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (r.ok) {
                    toast('Serviço salvo!', 'sucesso');
                    this.modalAberto = false;
                    await this.carregar();
                } else {
                    const res = await r.json();
                    toast(res.erro || 'Erro ao salvar', 'erro');
                }
            } catch(e) { toast('Erro de conexão', 'erro'); }
            this.salvando = false;
        },

        async excluir(id) {
            if (!confirm('Excluir este serviço?')) return;
            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/servicos.php') ?>?id=' + id, { method: 'DELETE' });
                if (r.ok) { toast('Serviço excluído', 'sucesso'); await this.carregar(); }
                else { toast('Erro ao excluir', 'erro'); }
            } catch(e) { toast('Erro de conexão', 'erro'); }
        },

        formatarMoeda(val) { return window.formatarMoeda(val); },
    };
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
