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

    <main id="main-content" style="flex:1; padding:28px 32px; overflow-y:auto; max-width:calc(100vw - 240px);">

        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:28px;">
            <div>
                <h1 style="font-size:22px; font-weight:700; color:#f1f5f9;">Tabela de Serviços</h1>
                <p style="font-size:14px; color:#6b7280; margin-top:2px;">Catálogo com preço mínimo calculado automaticamente</p>
            </div>
            <button class="btn-primary" @click="abrirModal()">
                <i data-lucide="plus" style="width:15px;height:15px;"></i> Novo Serviço
            </button>
        </div>
        
        <!-- Abas de Categoria -->
        <div style="display:flex; gap:8px; margin-bottom:20px; border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:12px;">
            <button @click="categoriaAtiva = 'marketing'" 
                    :class="categoriaAtiva === 'marketing' ? 'btn-primary' : 'btn-secondary'"
                    style="padding:8px 20px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                <i data-lucide="trending-up" style="width:14px;height:14px;"></i> Marketing
            </button>
            <button @click="categoriaAtiva = 'wedding'" 
                    :class="categoriaAtiva === 'wedding' ? 'btn-primary' : 'btn-secondary'"
                    style="padding:8px 20px; font-size:13px; font-weight:600; display:flex; align-items:center; gap:8px;">
                <i data-lucide="camera" style="width:14px;height:14px;"></i> Wedding / 15 Anos
            </button>
        </div>

        <!-- Configuração de horas mensais -->
        <div class="card" style="padding:16px 20px; margin-bottom:20px; display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
            <div style="font-size:13px; color:#94a3b8;">
                <i data-lucide="info" style="width:14px;height:14px; vertical-align:middle; margin-right:4px;"></i>
                Horas mensais de capacidade da agência (para rateio dos custos fixos):
            </div>
            <div style="display:flex; align-items:center; gap:8px;">
                <input class="input" type="number" min="1" x-model="horasMensais" style="width:100px;" placeholder="160">
                <button class="btn-secondary" @click="abrirPlanejadorIA()" style="padding:6px 10px; border-color:#a78bfa; color:#a78bfa;">
                    <i data-lucide="sparkles" style="width:14px;height:14px;"></i> Planejar com IA
                </button>
            </div>
            <div style="font-size:13px; color:#6b7280;">
                Custo fixo total mensal:
                <strong style="color:#ef4444;" x-text="formatarMoeda(totalCustosFixos)"></strong>
            </div>
        </div>

        <!-- Tabela de Serviços -->
        <div class="card" style="overflow:hidden;">
            <div class="table-header" style="display:grid; grid-template-columns:2fr 80px 1fr 1fr 1fr 80px 100px;">
                <span>Serviço</span><span>Horas</span><span>Preço Mínimo</span><span>P. Recorrente</span><span>P. Pontual</span><span>Markup</span><span style="text-align:right;">Ações</span>
            </div>

            <template x-if="carregando">
                <div style="padding:40px; text-align:center; color:#4b5563;">Carregando...</div>
            </template>

            <template x-if="!carregando && lista.length === 0">
                <div style="padding:40px; text-align:center; color:#4b5563;">
                    <i data-lucide="briefcase" style="width:36px;height:36px;margin:0 auto 12px;display:block;opacity:0.4;"></i>
                    Nenhum serviço cadastrado
                </div>
            </template>

            <template x-for="s in listaFiltrada" :key="s.id">
                    <div class="table-row" style="display:grid; grid-template-columns:2fr 80px 1fr 1fr 1fr 80px 100px; align-items:center;">
                        <div class="table-cell">
                            <!-- Nome do Serviço (Título Principal) -->
                            <div style="color:#f8fafc; font-weight:800; font-size:12px; text-transform:uppercase; letter-spacing:0.8px; line-height:1.1; margin-bottom:2px;" x-text="(s.nome || 'SEM NOME').toUpperCase()"></div>
                            
                            <!-- Descrição do Serviço (Detalhe Sutil) -->
                            <div style="color:#64748b; font-size:10px; font-weight:400; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; opacity:0.7;" :title="s.descricao" x-text="s.descricao || ''"></div>
                        </div>
                        <div class="table-cell" style="color:#94a3b8;" x-text="s.horas_estimadas + 'h'"></div>
                        <div class="table-cell" style="color:#94a3b8;" x-text="formatarMoeda(calcularPrecoMinimo(s))"></div>
                        <div class="table-cell" style="font-weight:700;" :style="s.periodicidade === 'mensal' ? 'color:#10b981;' : 'color:#4b5563; opacity:0.6;'" x-text="s.preco_venda > 0 ? formatarMoeda(s.preco_venda) : '—'"></div>
                        <div class="table-cell" style="font-weight:700;" :style="s.periodicidade === 'pontual' ? 'color:#10b981;' : 'color:#4b5563; opacity:0.6;'" x-text="s.preco_venda_pontual > 0 ? formatarMoeda(s.preco_venda_pontual) : '—'"></div>
                        <div class="table-cell">
                            <span style="color:#94a3b8; font-weight:600;" x-text="s.markup + '%'"></span>
                        </div>
                    <div class="table-cell" style="display:flex; gap:6px; justify-content:flex-end;">
                        <button @click="abrirModal(s)" style="color:#6b7280; background:none; border:none; cursor:pointer; padding:4px;">
                            <i data-lucide="pencil" style="width:16px;height:16px;"></i>
                        </button>
                        <button @click="excluir(s.id)" style="color:#6b7280; background:none; border:none; cursor:pointer; padding:4px;">
                            <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <p style="font-size:12px; color:#4b5563; margin-top:12px;">
            💡 Preço mínimo = (horas / capacidade mensal × custos fixos) + custo variável + markup aplicado.
        </p>

    </main>

    <!-- Modal -->
    <div class="modal-overlay" x-show="modalAberto" x-cloak @click.self="modalAberto=false">
        <div class="modal">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <h2 style="font-size:17px; font-weight:600; color:#f1f5f9;" x-text="form.id ? 'Editar Serviço' : 'Novo Serviço'"></h2>
                    <button type="button" @click="melhorarServicoIA()" :disabled="melhorandoIA" class="btn-secondary" style="padding:4px 10px; font-size:11px; border-color:#a78bfa; color:#a78bfa; display:flex; align-items:center; gap:6px;">
                        <i data-lucide="sparkles" style="width:12px;height:12px;"></i>
                        <span x-text="melhorandoIA ? 'Melhorando...' : 'Editar com IA'"></span>
                    </button>
                </div>
                <button @click="modalAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>
            <form @submit.prevent="salvar()">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div>
                        <label class="label">Categoria</label>
                        <select class="select" x-model="form.categoria">
                            <option value="marketing">Marketing</option>
                            <option value="wedding">Wedding / 15 Anos</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Tipo de Item</label>
                        <select class="select" x-model="form.tipo">
                            <option value="servico">Serviço / Upgrade</option>
                            <option value="plano">Plano Base (Pacote)</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="label">Nome do Serviço *</label>
                    <input class="input" x-model="form.nome" required placeholder="Ex: Registro Essencial">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div>
                        <label class="label">Periodicidade</label>
                        <select class="select" x-model="form.periodicidade">
                            <option value="mensal">Mensal (Recorrente)</option>
                            <option value="pontual">Pontual (Projeto Único)</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Prazo Mínimo (Meses)</label>
                        <input class="input" type="number" min="0" x-model="form.prazo_minimo" placeholder="Ex: 6 (0 para s/ mínimo)">
                    </div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="label">Descrição Básica</label>
                    <textarea class="input" x-model="form.descricao" rows="2" placeholder="O que é o serviço de forma resumida..." style="resize:vertical;"></textarea>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="label">Entregáveis (Escopo)</label>
                    <textarea class="input" x-model="form.entregaveis" rows="3" placeholder="Ex: 4 posts semanais, 1 relatório mensal, etc..." style="resize:vertical;"></textarea>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div>
                        <label class="label">Ferramentas Adicionais</label>
                        <input class="input" x-model="form.ferramentas" placeholder="Ex: Canva Pro, RD Station...">
                    </div>
                    <div>
                        <label class="label">Terceirização (Custo/O que)</label>
                        <input class="input" x-model="form.terceirizacao" placeholder="Ex: R$ 200,00 - Designer Freelancer">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div>
                        <label class="label">Horas/Dia (Dedicadas) *</label>
                        <input class="input" type="number" step="0.1" min="0" x-model="form.horas_dia" @input="calcularHorasMensaisServico()" required placeholder="Ex: 0.5">
                    </div>
                    <div>
                        <label class="label">Horas Estimadas (Mês) *</label>
                        <input class="input" type="number" step="0.5" min="0.5" x-model="form.horas_estimadas" required placeholder="Ex: 20">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div>
                        <label class="label">Custo de Produção (R$) *</label>
                        <input class="input" type="number" step="0.01" min="0" x-model="form.custo_producao" required placeholder="0,00">
                    </div>
                    <div>
                        <label class="label">Custos Variáveis (R$)</label>
                        <input class="input" type="number" step="0.01" min="0" x-model="form.custos_variaveis" placeholder="Ferramentas, etc.">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:16px;">
                    <div style="background:rgba(167,139,250,0.05); padding:16px; border-radius:8px; border:1px solid rgba(167,139,250,0.2);">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                            <label class="label" style="margin-bottom:0;">Preço Recorrente (R$)</label>
                            <button type="button" class="btn-secondary" @click="sugerirPrecoIA('recorrente')" :disabled="sugerindoPreco" style="padding:2px 6px; font-size:10px; border-color:#a78bfa; color:#a78bfa;">
                                <i data-lucide="sparkles" style="width:10px;height:10px;"></i> Sugerir
                            </button>
                        </div>
                        <input class="input" type="number" step="0.01" x-model="form.preco_venda" style="font-size:16px; font-weight:800; color:#10b981; background:transparent; border:none; padding:0;" placeholder="0,00">
                    </div>

                    <div style="background:rgba(167,139,250,0.05); padding:16px; border-radius:8px; border:1px solid rgba(167,139,250,0.2);">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                            <label class="label" style="margin-bottom:0;">Preço Pontual (R$)</label>
                            <button type="button" class="btn-secondary" @click="sugerirPrecoIA('pontual')" :disabled="sugerindoPreco" style="padding:2px 6px; font-size:10px; border-color:#a78bfa; color:#a78bfa;">
                                <i data-lucide="sparkles" style="width:10px;height:10px;"></i> Sugerir
                            </button>
                        </div>
                        <input class="input" type="number" step="0.01" x-model="form.preco_venda_pontual" style="font-size:16px; font-weight:800; color:#10b981; background:transparent; border:none; padding:0;" placeholder="0,00">
                    </div>
                </div>

                <div style="font-size:11px; color:#6b7280; margin-bottom:16px; padding-left:4px;">
                    💡 Piso mínimo (custo + rateio): <span x-text="formatarMoeda(calcularPrecoMinimo(form))"></span>
                </div>
                
                <div style="margin-bottom:16px;">
                    <label class="label">Markup Manual (%)</label>
                    <input class="input" type="number" step="0.5" min="0" x-model="form.markup" @input="recalcularPrecoPeloMarkup()" placeholder="Ex: 30">
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn-secondary" @click="modalAberto=false">Cancelar</button>
                    <button type="submit" class="btn-primary" :disabled="salvando" x-text="salvando ? 'Salvando...' : (form.id ? 'Atualizar' : 'Criar Serviço')"></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Planejador IA -->
    <div class="modal-overlay" x-show="modalPlanejadorAberto" x-cloak @click.self="modalPlanejadorAberto=false">
        <div class="modal" style="max-width:500px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="font-size:17px; font-weight:600; color:#f1f5f9; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="sparkles" style="width:18px;height:18px;color:#a78bfa;"></i>
                    Planejador de Capacidade IA
                </h2>
                <button @click="modalPlanejadorAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>
            
            <div style="background:rgba(124,58,237,0.1); border:1px solid rgba(124,58,237,0.2); padding:12px; border-radius:8px; margin-bottom:20px; font-size:13px; color:#cbd5e1;">
                Responda brevemente para a IA calcular sua capacidade mensal real.
            </div>

            <div style="margin-bottom:16px;">
                <label class="label">Quantas pessoas trabalham na produção?</label>
                <input class="input" x-model="planejador.equipe" placeholder="Ex: 2 pessoas + eu">
            </div>
            <div style="margin-bottom:16px;">
                <label class="label">Qual a jornada diária e dias por semana?</label>
                <input class="input" x-model="planejador.jornada" placeholder="Ex: 8h por dia, segunda a sexta">
            </div>
            <div style="margin-bottom:20px;">
                <label class="label">Alguma observação sobre produtividade?</label>
                <textarea class="input" x-model="planejador.obs" rows="2" placeholder="Ex: Perdemos 20% do tempo em reuniões..."></textarea>
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button class="btn-secondary" @click="modalPlanejadorAberto=false">Cancelar</button>
                <button class="btn-primary" @click="calcularCapacidadeIA()" :disabled="planejando">
                    <span x-show="!planejando">Calcular Capacidade</span>
                    <span x-show="planejando">Analisando...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Painel de Chat IA -->
    <div class="chat-ai-panel" :class="chatAberto ? 'active' : ''" x-cloak>
        <div style="display:flex; flex-direction:column; h-100; height:100%;">
            <div style="padding:20px; border-bottom:1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center; background:rgba(0,0,0,0.2);">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; border-radius:8px; background:linear-gradient(135deg, #a78bfa 0%, #7c3aed 100%); display:flex; align-items:center; justify-content:center;">
                        <i data-lucide="sparkles" style="width:16px;height:16px;color:#fff;"></i>
                    </div>
                    <div>
                        <div style="font-weight:700; color:#f1f5f9; font-size:13px; text-transform:uppercase; letter-spacing:0.5px;" x-text="form.nome || 'Novo Serviço'"></div>
                        <div style="font-size:10px; color:#a78bfa; font-weight:600; letter-spacing:0.3px;">ESTRUTURAÇÃO & PRECIFICAÇÃO</div>
                    </div>
                </div>
                <button @click="chatAberto=false" style="color:#6b7280; background:none; border:none; cursor:pointer;">
                    <i data-lucide="chevron-right" style="width:20px;height:20px;"></i>
                </button>
            </div>

            <!-- Corpo do Chat -->
            <div id="chat-body" style="flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:16px; scroll-behavior:smooth;">
                <template x-for="msg in chatHistorico">
                    <div :style="msg.role === 'user' ? 'align-self:flex-end; max-width:85%;' : 'align-self:flex-start; max-width:85%;'">
                        <div :style="msg.role === 'user' 
                            ? 'background:#a78bfa; color:#fff; padding:12px 16px; border-radius:16px 16px 4px 16px; font-size:13px; line-height:1.5;' 
                            : 'background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); color:#e2e8f0; padding:12px 16px; border-radius:16px 16px 16px 4px; font-size:13px; line-height:1.5;'"
                            x-text="msg.content">
                        </div>
                        <div :style="msg.role === 'user' ? 'text-align:right;' : 'text-align:left;'" style="font-size:10px; color:#4b5563; margin-top:4px;" x-text="msg.role === 'user' ? 'Você' : 'Assistente IA'"></div>
                    </div>
                </template>
                <div x-show="melhorandoIA" style="align-self:flex-start; background:rgba(255,255,255,0.03); padding:10px 16px; border-radius:16px; display:flex; gap:4px; align-items:center;">
                    <span class="dot-loading"></span>
                    <span class="dot-loading" style="animation-delay:0.2s"></span>
                    <span class="dot-loading" style="animation-delay:0.4s"></span>
                </div>
            </div>

            <!-- Input do Chat -->
            <div style="padding:20px; background:rgba(0,0,0,0.2); border-top:1px solid rgba(255,255,255,0.05);">
                <div style="position:relative;">
                    <textarea 
                        x-model="chatMensagem" 
                        @keydown.enter.prevent="enviarMensagemChatIA()" 
                        placeholder="Diga o que deseja alterar..." 
                        rows="1" 
                        style="width:100%; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:12px; padding:12px 45px 12px 16px; color:#fff; font-size:13px; outline:none; resize:none; transition:all 0.2s; focus:border-color:#a78bfa;"
                        :disabled="melhorandoIA"
                    ></textarea>
                    <button 
                        @click="enviarMensagemChatIA()" 
                        :disabled="!chatMensagem.trim() || melhorandoIA"
                        style="position:absolute; right:8px; top:50%; transform:translateY(-50%); width:32px; height:32px; border-radius:8px; background:#a78bfa; border:none; color:#fff; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s;"
                        :style="(!chatMensagem.trim() || melhorandoIA) ? 'opacity:0.5; cursor:not-allowed;' : ''"
                    >
                        <i data-lucide="send" style="width:16px;height:16px;"></i>
                    </button>
                </div>
                <div style="text-align:center; margin-top:10px; font-size:10px; color:#4b5563;">
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
        background: #0f172a; /* Azul muito escuro e sólido para bloquear o fundo */
        border-left: 1px solid rgba(255,255,255,0.1);
        z-index: 10001;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: -15px 0 40px rgba(0,0,0,0.7);
        display: flex;
        flex-direction: column;
    }
    .chat-ai-panel.active {
        right: 0;
    }
    /* Impedir que as linhas do HUD ou outros elementos apareçam dentro do painel */
    .chat-ai-panel * {
        position: relative;
        z-index: 2;
    }
    .dot-loading {
        width: 6px;
        height: 6px;
        background: #6b7280;
        border-radius: 50%;
        display: inline-block;
        animation: dot-pulse 1.4s infinite ease-in-out;
    }
    @keyframes dot-pulse {
        0%, 80%, 100% { transform: scale(0); opacity: 0.3; }
        40% { transform: scale(1); opacity: 1; }
    }
    /* Estilo para garantir que o modal não fique por cima do chat se necessário */
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

        async init() {
            await Promise.all([this.carregar(), this.carregarCustosFixos()]);
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
                markup:'30' 
            };
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
                const r = await fetch('<?= raizUrl('/api/precificacao/servicos.php') ?>', {
                    method: metodo,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
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
