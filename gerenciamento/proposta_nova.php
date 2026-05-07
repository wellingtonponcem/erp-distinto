<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();

$tituloPagina = 'Nova Proposta';
$db = Database::get();

// Buscar clientes
$stmtClientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
$clientes = $stmtClientes->fetchAll();

// Buscar serviços para o modelo Marketing (apenas ativos)
$stmtServicos = $db->query("SELECT id, nome, descricao, preco_venda, preco_venda_pontual, periodicidade FROM servicos WHERE ativo = 1 ORDER BY nome ASC");
$servicos = $stmtServicos->fetchAll();
$servicosJson = json_encode($servicos);

include __DIR__ . '/../includes/layout/head.php';
?>

<style>
    .is-modal-layout #main-content {
        margin-left: 0 !important;
        padding-top: 0 !important;
        background: transparent !important;
        color: white !important;
    }
    .is-modal-layout .page-title {
        font-size: 1.5rem;
        color: white !important;
    }
    .is-modal-layout .card {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        backdrop-filter: blur(10px);
    }
    .is-modal-layout .label {
        color: rgba(255, 255, 255, 0.5) !important;
    }
    .is-modal-layout .input {
        background: rgba(255, 255, 255, 0.05) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: white !important;
    }
    .is-modal-layout .input::placeholder {
        color: rgba(255, 255, 255, 0.2) !important;
    }
</style>

<?php 
$isModal = ($_GET['layout'] ?? '') === 'modal';
?>

<div id="app-wrapper" class="<?= $isModal ? 'is-modal-layout' : '' ?>">
    <?php if (!$isModal) include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet <?= $isModal ? 'p-0' : '' ?>" x-data="proposta">
        <?php if (!$isModal): ?>
        <div class="app-topbar">
            <div class="top-nav">
                <a href="<?= raizUrl('/dashboard.php') ?>">Visão Geral</a>
                <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>">Propostas</a>
                <a href="#" class="active">Nova Proposta</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="<?= $isModal ? 'px-8 pt-8 mb-6' : 'mb-8' ?>" x-show="passo === 2">
            <h1 class="page-title text-2xl">Criar Nova Proposta</h1>
            <p class="page-subtitle text-zinc-500">Preencha os dados abaixo para gerar uma proposta personalizada com IA.</p>
        </div>

        <form id="formGerarProposta" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-6 <?= $isModal ? 'px-8 pb-12' : '' ?>">
            
            <!-- PASSO 1: ESCOLHA DO TIPO -->
            <div x-show="passo === 1" class="lg:col-span-3 space-y-8 animate-fade-in py-10">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-black text-white mb-3 tracking-tight">Qual o tipo da nova proposta?</h2>
                    <p class="text-zinc-500 text-lg">Selecione o modelo base para começarmos a personalização.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-6xl mx-auto px-4">
                    <!-- Marketing -->
                    <div @click="tipoProposta = 'marketing'; passo = 2; $nextTick(() => lucide.createIcons())" 
                         class="group cursor-pointer bg-white/5 border border-white/5 hover:border-white/20 rounded-[2.5rem] p-8 transition-all hover:bg-white/[0.08] text-center flex flex-col items-center justify-center min-h-[320px] backdrop-blur-md">
                        <div class="w-24 h-24 bg-white/5 rounded-[2.3rem] flex items-center justify-center mb-6 group-hover:scale-110 transition-all duration-500 group-hover:bg-white group-hover:text-black">
                            <i data-lucide="megaphone" class="w-10 h-10"></i>
                        </div>
                        <h3 class="font-black text-xl text-white">Marketing Digital</h3>
                        <p class="text-sm text-zinc-500 mt-3 leading-relaxed">Gestão de tráfego, social media e estratégia digital.</p>
                    </div>

                    <!-- Filmmaker -->
                    <div @click="tipoProposta = 'filmmaker'; passo = 2; $nextTick(() => lucide.createIcons())" 
                         class="group cursor-pointer bg-white/5 border border-white/5 hover:border-white/20 rounded-[2.5rem] p-8 transition-all hover:bg-white/[0.08] text-center flex flex-col items-center justify-center min-h-[320px] backdrop-blur-md">
                        <div class="w-24 h-24 bg-white/5 rounded-[2.3rem] flex items-center justify-center mb-6 group-hover:scale-110 transition-all duration-500 group-hover:bg-white group-hover:text-black">
                            <i data-lucide="video" class="w-10 h-10"></i>
                        </div>
                        <h3 class="font-black text-xl text-white">Filmmaker</h3>
                        <p class="text-sm text-zinc-500 mt-3 leading-relaxed">Produção de vídeos, reels e conteúdo cinematic.</p>
                    </div>

                    <!-- Casamento -->
                    <div @click="tipoProposta = 'casamento'; passo = 2; $nextTick(() => lucide.createIcons())" 
                         class="group cursor-pointer bg-white/5 border border-white/5 hover:border-white/20 rounded-[2.5rem] p-8 transition-all hover:bg-white/[0.08] text-center flex flex-col items-center justify-center min-h-[320px] backdrop-blur-md">
                        <div class="w-24 h-24 bg-white/5 rounded-[2.3rem] flex items-center justify-center mb-6 group-hover:scale-110 transition-all duration-500 group-hover:bg-white group-hover:text-black">
                            <i data-lucide="heart" class="w-10 h-10"></i>
                        </div>
                        <h3 class="font-black text-xl text-white">Casamento</h3>
                        <p class="text-sm text-zinc-500 mt-3 leading-relaxed">Fotografia e vídeo premium para casamentos.</p>
                    </div>

                    <!-- 15 Anos -->
                    <div @click="tipoProposta = '15anos'; passo = 2; $nextTick(() => lucide.createIcons())" 
                         class="group cursor-pointer bg-white/5 border border-white/5 hover:border-white/20 rounded-[2.5rem] p-8 transition-all hover:bg-white/[0.08] text-center flex flex-col items-center justify-center min-h-[320px] backdrop-blur-md">
                        <div class="w-24 h-24 bg-white/5 rounded-[2.3rem] flex items-center justify-center mb-6 group-hover:scale-110 transition-all duration-500 group-hover:bg-white group-hover:text-black">
                            <i data-lucide="star" class="w-10 h-10"></i>
                        </div>
                        <h3 class="font-black text-xl text-white">15 Anos</h3>
                        <p class="text-sm text-zinc-500 mt-3 leading-relaxed">Cobertura completa de festas de debutante.</p>
                    </div>
                </div>
            </div>

            <!-- PASSO 2: DADOS DA PROPOSTA (COLUNA PRINCIPAL) -->
            <div x-show="passo === 2" class="lg:col-span-2 space-y-6 animate-fade-in" style="display: none;">
                <div class="flex items-center gap-2 mb-2">
                    <button type="button" @click="passo = 1" class="text-zinc-400 hover:text-zinc-900 transition-colors flex items-center gap-1 text-xs font-bold uppercase tracking-wider">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Voltar para escolha de tipo
                    </button>
                </div>
                <section class="card p-6">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Informações Básicas</h3>
                    
                    <div class="flex gap-6 mb-6 pb-6 border-b border-zinc-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="modo_cliente" value="cadastrado" checked class="w-4 h-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                            <span class="text-xs font-bold text-zinc-700">Cliente Cadastrado</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="modo_cliente" value="lead" class="w-4 h-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                            <span class="text-xs font-bold text-zinc-700">Novo Lead / Prospect</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <!-- Modo Cliente Cadastrado -->
                        <div id="wrapperClienteCadastrado" class="form-group">
                            <label class="label">Selecione o Cliente</label>
                            <select name="cliente_id" id="cliente_id" class="input">
                                <option value="">Selecione um cliente...</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= sanitizar($c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Modo Novo Lead -->
                        <div id="wrapperNovoLead" class="hidden animate-fade-in" x-show="tipoProposta !== 'casamento'">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="label">Nome da Empresa / Projeto</label>
                                    <input type="text" name="empresa_nome" class="input" placeholder="Ex: Innovare Solar" :required="modoCliente === 'lead' && tipoProposta !== 'casamento'">
                                </div>
                                <div class="form-group">
                                    <label class="label">Responsável(is)</label>
                                    <input type="text" name="responsavel" class="input" placeholder="Ex: João Silva, Maria Souza" :required="modoCliente === 'lead' && tipoProposta !== 'casamento'">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="label">WhatsApp do Cliente (Opcional)</label>
                                <input type="text" name="whatsapp" class="input" placeholder="Ex: 27999998888">
                                <p class="text-[10px] text-zinc-500 mt-1">Apenas números com DDD. Será usado para enviar a proposta.</p>
                            </div>
                            <div class="form-group">
                                <label class="label">Tipo de Serviço</label>
                                <select name="tipo" id="tipoPropostaSelect" class="input" required x-model="tipoProposta">
                                    <option value="marketing">Marketing Digital</option>
                                    <option value="casamento">Casamento</option>
                                    <option value="15anos">15 Anos</option>
                                    <option value="filmmaker">Filmmaker (Cinematic)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="tipoProposta !== 'casamento'">
                            <div class="form-group">
                                <label class="label">Título da Proposta</label>
                                <input type="text" name="titulo" class="input" placeholder="Ex: Gestão de Tráfego 2024" maxlength="60" :required="tipoProposta !== 'casamento'" :value="tipoProposta === 'casamento' ? (nomeNoivo + ' & ' + nomeNoiva) : ''">
                            </div>
                            <div class="form-group">
                                <label class="label">Subtítulo (Opcional)</label>
                                <input type="text" name="subtitulo" class="input" placeholder="Ex: Planejamento Estratégico Q3">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="form-group">
                                <label class="label">Subtotal (R$)</label>
                                <input type="number" step="0.01" class="input bg-zinc-50 font-bold text-zinc-500" placeholder="0,00" readonly :value="valorSubtotal">
                            </div>
                            <div class="form-group">
                                <label class="label">Desconto</label>
                                <div class="flex">
                                    <input type="number" step="0.01" name="desconto_valor" class="input rounded-r-none border-r-0" placeholder="0,00" x-model="descontoValor" @input="recalcularTotal()">
                                    <select name="desconto_tipo" class="input rounded-l-none w-16 px-1 text-center font-bold bg-zinc-50" x-model="descontoTipo" @change="recalcularTotal()">
                                        <option value="porcentagem">%</option>
                                        <option value="fixo">R$</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="label">Valor Total (Final)</label>
                                <input type="number" step="0.01" name="valor_total" class="input bg-zinc-100 font-bold text-zinc-900 border-zinc-300" placeholder="0,00" required readonly x-model="valorTotal">
                                <p class="text-[10px] text-zinc-500 mt-1">Valor final após desconto.</p>
                            </div>
                            <div class="form-group" x-show="tipoProposta !== 'casamento'">
                                <label class="label">Tempo de Contrato</label>
                                <input type="number" name="meses_contrato" class="input" placeholder="Meses" x-model="mesesContrato" @input="recalcularTotal()">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="label">Forma de Pagamento</label>
                            <select name="forma_pagamento" class="input">
                                <option value="boleto_pix">Boleto / PIX</option>
                                <option value="cartao">Cartão de Crédito (+2,13%)</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- CONFIGURAÇÕES DE CASAMENTO -->
                <section class="card p-6" x-show="tipoProposta === 'casamento'">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Dados do Casamento</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="form-group">
                            <label class="label">Nome do Noivo</label>
                            <input type="text" name="nome_noivo" class="input" x-model="nomeNoivo" placeholder="Ex: Rodolfo Elias">
                        </div>
                        <div class="form-group">
                            <label class="label">Nome da Noiva</label>
                            <input type="text" name="nome_noiva" class="input" x-model="nomeNoiva" placeholder="Ex: Rhuana Fonseca">
                        </div>
                        <div class="form-group">
                            <label class="label">Data do Casamento</label>
                            <input type="date" name="data_casamento" class="input" x-model="dataCasamento">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="form-group">
                            <label class="label">Data Limite para Desconto</label>
                            <input type="text" name="data_limite_desconto" class="input" x-model="dataLimiteDesconto" placeholder="Ex: 05/04/2026">
                        </div>
                        <div class="form-group">
                            <label class="label">Condição Especial</label>
                            <input type="text" name="condicao_especial" class="input" x-model="condicaoEspecial" placeholder="Ex: Condição especial p/ amigos lagoinha">
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="p-4 border border-zinc-100 rounded-2xl bg-zinc-50/50">
                            <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span> Plano Heritage (Completo)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-1">
                                    <label class="label">Valor (R$)</label>
                                    <input type="text" name="valor_heritage" class="input" x-model="valorHeritage" placeholder="7.900,00">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="label">Itens / Descrição</label>
                                    <textarea name="itens_heritage" class="input text-xs" x-model="itensHeritage" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 border border-zinc-100 rounded-2xl bg-zinc-50/50">
                            <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span> Plano Cinematic (Intermediário)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-1">
                                    <label class="label">Valor (R$)</label>
                                    <input type="text" name="valor_cinematic" class="input" x-model="valorCinematic" placeholder="5.900,00">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="label">Itens / Descrição</label>
                                    <textarea name="itens_cinematic" class="input text-xs" x-model="itensCinematic" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 border border-zinc-100 rounded-2xl bg-zinc-50/50">
                            <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-zinc-500"></span> Plano Essencial
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-1">
                                    <label class="label">Valor (R$)</label>
                                    <input type="text" name="valor_essencial" class="input" x-model="valorEssencial" placeholder="3.900,00">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="label">Itens / Descrição</label>
                                    <textarea name="itens_essencial" class="input text-xs" x-model="itensEssencial" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-4 border border-zinc-100 rounded-2xl bg-zinc-50/50">
                                <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-3">Upgrade Boudoir</h4>
                                <label class="label">Valor (R$)</label>
                                <input type="text" name="valor_boudoir" class="input" x-model="valorBoudoir" placeholder="800,00">
                            </div>
                            <div class="p-4 border border-zinc-100 rounded-2xl bg-zinc-50/50">
                                <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-3">Upgrade Pré-Wedding</h4>
                                <label class="label">Valor (R$)</label>
                                <input type="text" name="valor_prewedding" class="input" x-model="valorPrewedding" placeholder="1.200,00">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card p-6" id="sectionServicos" x-show="tipoProposta === 'marketing'">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-zinc-900">Serviços Inclusos</h3>
                        <button type="button" @click="adicionarServico()" class="text-[10px] bg-zinc-900 text-white px-3 py-1.5 rounded-lg font-bold hover:bg-zinc-800 transition-all flex items-center gap-1">
                            <i data-lucide="plus" class="w-3 h-3"></i> Adicionar Serviço
                        </button>
                    </div>

                    <div class="space-y-6">
                        <template x-for="(item, index) in servicosSelecionados" :key="index">
                            <div class="p-4 border border-zinc-100 rounded-xl bg-zinc-50/50 relative group animate-fade-in">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                    <!-- Seleção de Serviço -->
                                    <div class="md:col-span-4">
                                        <label class="text-[10px] font-bold text-zinc-500 uppercase mb-1 block">Serviço</label>
                                        <select :name="'servicos['+index+'][id]'" class="input py-2" x-model="item.id" @change="atualizarDadosServico(index)">
                                            <option value="">Selecione um serviço...</option>
                                            <?php foreach ($servicos as $s): ?>
                                            <option value="<?= $s['id'] ?>"><?= sanitizar($s['nome']) ?></option>
                                            <?php endforeach; ?>
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
                                        <input type="hidden" :name="'servicos['+index+'][valor_mensal]'" :value="item.valor_mensal">
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

                        <div x-show="servicosSelecionados.length === 0" class="text-center py-8 border-2 border-dashed border-zinc-100 rounded-xl">
                            <i data-lucide="layers" class="w-8 h-8 text-zinc-300 mx-auto mb-2"></i>
                            <p class="text-xs text-zinc-500">Nenhum serviço adicionado ainda.</p>
                            <button type="button" @click="adicionarServico()" class="text-xs text-zinc-900 font-bold mt-2 hover:underline">Clique para adicionar</button>
                        </div>
                    </div>
                </section>

                <section class="card p-6" x-show="tipoProposta !== 'casamento'">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Estratégia & Cronograma</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="form-group">
                            <label class="label">Data Prevista de Início</label>
                            <input type="date" name="data_inicio" class="input" value="<?= date('Y-m-d') ?>">
                            <p class="text-[10px] text-zinc-500 mt-1">Data que aparecerá no cronograma da proposta.</p>
                        </div>
                        <div class="form-group">
                            <label class="label">Validade da Proposta</label>
                            <input type="date" name="validade" class="input" value="<?= date('Y-m-d', strtotime('+15 days')) ?>">
                            <p class="text-[10px] text-zinc-500 mt-1">Até quando os valores e condições são garantidos.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="label">Objetivo do Projeto (Será refinado por IA)</label>
                        <textarea name="objetivo" class="input min-h-[100px]" maxlength="1020" placeholder="Ex: Fortalecer a marca Innovare Solar como referência em energia limpa no ES, aumentar captação de leads e fechar novos contratos..."></textarea>
                        <p class="text-[10px] text-zinc-500 mt-1">Máximo de 1020 caracteres. Este texto será reescrito pela IA para a Sessão 3 da proposta.</p>
                    </div>
                </section>

                <section class="card p-6" x-show="tipoProposta !== 'casamento'">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Opção Adicional (Upsell)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="form-group">
                            <label class="label">Título da Opção</label>
                            <input type="text" name="adicional_titulo" class="input" placeholder="Ex: VÍDEOS PARA REELS">
                        </div>
                        <div class="form-group">
                            <label class="label">Valor da Opção (R$/mês)</label>
                            <input type="number" step="0.01" name="adicional_valor" class="input" placeholder="0,00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="label">Descrição da Opção</label>
                        <textarea name="adicional_descricao" class="input min-h-[80px]" placeholder="Ex: Sessão mensal de até 3h de gravação com entrega de 8 vídeos para Reels..."></textarea>
                    </div>
                </section>

                <section class="card p-6" x-show="tipoProposta !== 'casamento'">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Briefing para IA</h3>
                    <div class="form-group">
                        <label class="label">Instruções Adicionais (Opcional)</label>
                        <textarea name="briefing" class="input min-h-[120px]" placeholder="Dê detalhes sobre o cliente ou o projeto para que a IA gere textos mais precisos..."></textarea>
                    </div>
                </section>
            </div>

            <!-- PASSO 2: SIDEBAR (COLUNA LATERAL) -->
            <div x-show="passo === 2" class="lg:col-span-1 space-y-6 animate-fade-in" style="display: none;">
                <section class="card p-6 bg-zinc-900 text-white shadow-xl shadow-zinc-900/20 border-0">
                    <h3 class="text-sm font-bold mb-4 opacity-80">Ações</h3>
                    <button type="submit" id="btnGerar" class="w-full h-12 rounded-xl font-bold bg-white text-black hover:bg-zinc-100 transition-all flex items-center justify-center gap-2 group !text-black">
                        <i data-lucide="sparkles" class="w-5 h-5 text-zinc-500 group-hover:text-black transition-colors !text-zinc-900"></i>
                        Gerar Proposta Web
                    </button>
                    <p class="mt-4 text-[11px] opacity-60 text-center">
                        Ao clicar em gerar, nossa IA irá processar os dados e criar uma página exclusiva para o cliente.
                    </p>
                </section>

                <div id="resultadoProposta" class="hidden animate-fade-in">
                    <section class="card p-6 border-2 border-emerald-500">
                        <div class="flex items-center gap-3 text-emerald-600 mb-4">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            <span class="font-bold text-sm">Gerada com Sucesso!</span>
                        </div>
                        <p class="text-xs text-zinc-600 mb-4">A proposta já está online. Você pode copiar o link ou visualizar agora.</p>
                        <div class="space-y-2">
                            <div class="grid grid-cols-1 gap-2">
                                <a href="#" id="linkVisualizar" target="_blank" class="btn-primary w-full justify-center py-3">
                                    <i data-lucide="external-link" class="w-4 h-4"></i>
                                    Visualizar Proposta
                                </a>
                                <button type="button" id="btnWhatsApp" class="w-full py-3 rounded-xl bg-[#25D366] text-white font-bold hover:bg-[#20ba59] transition-all flex items-center justify-center gap-2">
                                    <i class="fab fa-whatsapp text-lg"></i>
                                    Enviar via WhatsApp
                                </button>
                                <button type="button" id="btnCopiarLink" class="btn-secondary w-full justify-center gap-2 py-3">
                                    <i data-lucide="copy" class="w-4 h-4"></i>
                                    Copiar Link
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
// 1. Registro do componente Alpine.js (Escopo Global)
document.addEventListener('alpine:init', () => {
    Alpine.data('proposta', () => ({
        catalogoServicos: <?= $servicosJson ?>,
        servicosSelecionados: [],
        valorSubtotal: 0,
        descontoValor: 0,
        descontoTipo: 'porcentagem',
        valorTotal: 0,
        tipoProposta: 'marketing',
        passo: 1,
        mesesContrato: 12,
        // Campos de Casamento
        nomeNoivo: '',
        nomeNoiva: '',
        dataCasamento: '',
        dataLimiteDesconto: '',
        condicaoEspecial: '',
        valorHeritage: '',
        itensHeritage: 'Cobertura Documental, Álbum Heritage, Réplicas, Filme 4K, Drone, Ecossistema Digital',
        valorCinematic: '',
        itensCinematic: 'Fotografia 8h, Sessão Engagement, Short Film, Social Content, Making Of, Bônus',
        valorEssencial: '',
        itensEssencial: 'Cobertura Fotográfica Essencial',
        valorBoudoir: '',
        valorPrewedding: '',
        
        init() {
            if (this.tipoProposta === 'marketing') {
                this.adicionarServico();
            }
            
            this.$watch('tipoProposta', (value) => {
                const section = document.getElementById('sectionServicos');
                if (value === 'marketing') {
                    section.classList.remove('hidden');
                } else {
                    section.classList.add('hidden');
                }
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
                // Seta o tipo baseado no padrão do catálogo
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
                const freq = parseInt(item.frequencia) || 1;

                if (item.tipo_cobranca === 'pontual') {
                    if (freq > 1) {
                        item.valor_mensal = Math.round((precoRecorrente * freq) * 100) / 100;
                    } else {
                        item.valor_mensal = Math.round((parseFloat(item.valor) / meses) * 100) / 100;
                    }
                } else {
                    item.valor_mensal = Math.round(parseFloat(item.valor) * 100) / 100;
                }
            });

            const subRaw = this.servicosSelecionados.reduce((acc, curr) => acc + (curr.valor_mensal || 0), 0);
            this.valorSubtotal = Math.round(subRaw * 100) / 100;

            let desconto = 0;
            const sub = parseFloat(this.valorSubtotal || 0);
            const desc = parseFloat(this.descontoValor || 0);
            if (this.descontoTipo === 'porcentagem') desconto = sub * (desc / 100);
            else desconto = desc;

            const mensalFinal = Math.max(0, sub - desconto);
            // valor_total = total do contrato (mensal × meses) — template exibe ÷ meses para /mês
            this.valorTotal = Math.round(mensalFinal * meses * 100) / 100;
        }
    }));
});

// 2. Lógica de Envio e UI (DOM)
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formGerarProposta');
    const btnGerar = document.getElementById('btnGerar');
    const resultadoDiv = document.getElementById('resultadoProposta');
    const linkVisualizar = document.getElementById('linkVisualizar');
    const btnCopiarLink = document.getElementById('btnCopiarLink');
    let linkGerado = '';

    // Gestão de Clientes/Leads (Tradicional)
    const radiosModo = document.querySelectorAll('input[name="modo_cliente"]');
    const wrapperCadastrado = document.getElementById('wrapperClienteCadastrado');
    const wrapperLead = document.getElementById('wrapperNovoLead');
    const selectCliente = document.querySelector('select[name="cliente_id"]');
    const inputEmpresa = document.querySelector('input[name="empresa_nome"]');
    const inputResponsavel = document.querySelector('input[name="responsavel"]');

    radiosModo.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'cadastrado') {
                wrapperCadastrado.classList.remove('hidden');
                wrapperLead.classList.add('hidden');
                if(selectCliente) selectCliente.required = true;
                if(inputEmpresa) inputEmpresa.required = false;
                if(inputResponsavel) inputResponsavel.required = false;
            } else {
                wrapperCadastrado.classList.add('hidden');
                wrapperLead.classList.remove('hidden');
                if(selectCliente) selectCliente.required = false;
                if(inputEmpresa) inputEmpresa.required = true;
                if(inputResponsavel) inputResponsavel.required = true;
            }
        });
    });

    // Iniciar com o modo correto
    const checkedRadio = document.querySelector('input[name="modo_cliente"]:checked');
    if (checkedRadio) checkedRadio.dispatchEvent(new Event('change'));

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        btnGerar.disabled = true;
        btnGerar.innerHTML = '<i class="w-4 h-4 animate-spin"></i> Processando IA...';
        
        const formData = new FormData(form);
        
        try {
            const response = await fetch('<?= raizUrl('/api/propostas/gerar.php') ?>', {
                method: 'POST',
                body: formData
            });
            
            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                console.error('Erro ao processar JSON:', responseText);
                throw new Error('Resposta inválida do servidor.');
            }
            
            if (result.success) {
                const baseUrl = window.location.origin + window.location.pathname.split('/gerenciamento/')[0];
                linkGerado = `${baseUrl}/p/${result.slug}`;
                linkVisualizar.href = linkGerado;
                resultadoDiv.classList.remove('hidden');
                resultadoDiv.scrollIntoView({ behavior: 'smooth' });
            } else {
                alert('Erro: ' + (result.error || 'Falha ao gerar proposta.'));
            }
        } catch (error) {
            console.error(error);
            alert('Erro na comunicação com o servidor.');
        } finally {
            btnGerar.disabled = false;
            btnGerar.innerHTML = '<i data-lucide="sparkles" class="w-5 h-5 text-zinc-500 group-hover:text-zinc-900 transition-colors"></i> Gerar Proposta Web';
            if (window.lucide) lucide.createIcons();
        }
    });

    const btnWhatsApp = document.getElementById('btnWhatsApp');
    btnWhatsApp.addEventListener('click', function() {
        const whats = document.querySelector('input[name="whatsapp"]').value.replace(/\D/g, '');
        const responsavel = document.querySelector('input[name="responsavel"]')?.value || '';
        
        if (!whats) {
            alert('Por favor, preencha o número de WhatsApp do cliente.');
            return;
        }

        const primeiroNome = responsavel ? responsavel.split(' ')[0] : '';
        const saudacao = primeiroNome ? `Olá, ${primeiroNome}!` : 'Olá!';
        
        const texto = encodeURIComponent(`${saudacao} Preparei a sua proposta personalizada. Você pode acessar os detalhes por este link:\n\n${linkGerado}`);
        window.open(`https://wa.me/55${whats}?text=${texto}`, '_blank');
    });

    btnCopiarLink.addEventListener('click', function() {
        navigator.clipboard.writeText(linkGerado).then(() => {
            const originalText = this.innerHTML;
            this.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> Copiado!';
            setTimeout(() => {
                this.innerHTML = originalText;
                if (window.lucide) lucide.createIcons();
            }, 2000);
        });
    });
});
</script>

<?php if (!$isModal) include __DIR__ . '/../includes/layout/footer.php'; ?>
