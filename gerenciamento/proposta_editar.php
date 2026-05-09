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

// Buscar serviços (apenas ativos, incluindo periodicidade e preço pontual)
$stmtServicos = $db->query("SELECT id, nome, descricao, preco_venda, preco_venda_pontual, periodicidade FROM servicos WHERE ativo = 1 ORDER BY nome ASC");
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
                <!-- Informações Básicas (Oculto para Casamento) -->
                <section class="card p-6" x-show="tipoProposta !== 'casamento'">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Informações Básicas</h3>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div class="form-group">
                            <label class="label">Cliente</label>
                            <input type="text" class="input bg-zinc-50 text-zinc-500" value="<?= sanitizar($proposta['cliente_nome']) ?>" readonly>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group" x-show="tipoProposta !== 'casamento'">
                                <label class="label">Responsável(is)</label>
                                <input type="text" name="responsavel" class="input" x-model="responsavel" placeholder="Ex: João Silva ou João e Maria">
                            </div>
                            <div class="form-group">
                                <label class="label">WhatsApp do Cliente</label>
                                <input type="text" name="whatsapp" class="input" x-model="whatsapp" placeholder="Ex: 27999998888" :disabled="tipoProposta === 'casamento'">
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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="tipoProposta !== 'casamento'">
                            <div class="form-group">
                                <label class="label">Título da Proposta</label>
                                <input type="text" name="titulo" class="input" :value="tipoProposta === 'casamento' ? (nomeNoivo + ' & ' + nomeNoiva) : '<?= sanitizar($proposta['titulo']) ?>'" maxlength="100" required>
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
                                <label class="label">Valor Total do Contrato</label>
                                <input type="number" step="0.01" name="valor_total" class="input font-bold" required x-model="valorTotal" @input="calcularDescontoDoTotal()">
                                <p class="text-[10px] text-zinc-400 mt-1" x-text="'R$ ' + (valorTotal / (parseInt(mesesContrato)||1)).toFixed(2).replace('.',',') + '/mês × ' + (parseInt(mesesContrato)||1) + ' meses'"></p>
                            </div>
                            <div class="form-group" x-show="tipoProposta !== 'casamento'">
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
                                <select name="status" class="input" x-model="statusProposta" :disabled="tipoProposta === 'casamento'">
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
                <section class="card p-6 border-zinc-200" x-show="tipoProposta !== 'casamento'">
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

                <!-- CONFIGURAÇÕES DE CASAMENTO -->
                <section class="card p-6" x-show="tipoProposta === 'casamento'">
                    <h2 class="section-header-premium">
                        <i data-lucide="heart" class="w-5 h-5 text-rose-500"></i>
                        Dados do Casamento
                    </h2>

                    <!-- Campos movidos de Informações Básicas -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 pb-8 border-b border-zinc-100/50">
                        <div class="form-group">
                            <label class="label-premium">WhatsApp do Cliente</label>
                            <input type="text" name="whatsapp" class="input" x-model="whatsapp" placeholder="Ex: 27999998888" :disabled="tipoProposta !== 'casamento'">
                        </div>
                        <div class="form-group">
                            <label class="label-premium">Tipo de Serviço</label>
                            <input type="text" class="input bg-zinc-50/50 text-zinc-500" value="Casamento" readonly>
                            <input type="hidden" name="tipo" value="casamento" :disabled="tipoProposta !== 'casamento'">
                        </div>
                        <div class="form-group">
                            <label class="label-premium">Status da Proposta</label>
                            <select name="status" class="input" x-model="statusProposta" :disabled="tipoProposta !== 'casamento'">
                                <option value="rascunho">Rascunho</option>
                                <option value="pendente">Pendente</option>
                                <option value="aceita">Aceita</option>
                                <option value="recusada">Recusada</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="form-group">
                            <label class="label-premium">Nome do Noivo</label>
                            <input type="text" name="nome_noivo" class="input" x-model="nomeNoivo" placeholder="Ex: Rodolfo Elias">
                        </div>
                        <div class="form-group">
                            <label class="label-premium">Nome da Noiva</label>
                            <input type="text" name="nome_noiva" class="input" x-model="nomeNoiva" placeholder="Ex: Rhuana Fonseca">
                        </div>
                        <div class="form-group">
                            <label class="label-premium">Data do Casamento</label>
                            <input type="date" name="data_casamento" class="input" x-model="dataCasamento">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 pb-8 border-b border-zinc-100/50">
                        <div class="form-group">
                            <label class="label-premium">Data Limite para Desconto</label>
                            <input type="text" name="data_limite_desconto" class="input" x-model="dataLimiteDesconto" placeholder="Ex: 05/04/2026">
                        </div>
                        <div class="form-group">
                            <label class="label-premium">Condição Especial</label>
                            <input type="text" name="condicao_especial" class="input" x-model="condicaoEspecial" placeholder="Ex: Condição especial p/ amigos lagoinha">
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        <!-- PLANO HERITAGE -->
                        <div class="card-plan p-5 rounded-2xl bg-zinc-50/30 border-zinc-100" :class="showHeritage ? 'card-plan-active' : 'opacity-60'">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-widest flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.5)]"></span> 
                                    Experiência Heritage
                                </h4>
                                <label class="flex items-center gap-2 cursor-pointer bg-white/50 px-3 py-1 rounded-full border border-zinc-200">
                                    <input type="checkbox" name="show_heritage" x-model="showHeritage" class="w-4 h-4 rounded border-zinc-300 text-amber-500 focus:ring-amber-500">
                                    <span class="text-[10px] font-black uppercase text-zinc-600">Exibir na Proposta</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4" x-show="showHeritage" x-collapse>
                                <div class="md:col-span-1">
                                    <label class="label-premium">Valor (R$)</label>
                                    <input type="text" name="valor_heritage" class="input font-bold text-zinc-900" x-model="valorHeritage" placeholder="7.900,00">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="label-premium">Itens inclusos</label>
                                    <textarea name="itens_heritage" class="input text-xs leading-relaxed" x-model="itensHeritage" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- PLANO CINEMATIC -->
                        <div class="card-plan p-5 rounded-2xl bg-zinc-50/30 border-zinc-100" :class="showCinematic ? 'card-plan-active' : 'opacity-60'">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-widest flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></span> 
                                    Experiência Cinematic
                                </h4>
                                <label class="flex items-center gap-2 cursor-pointer bg-white/50 px-3 py-1 rounded-full border border-zinc-200">
                                    <input type="checkbox" name="show_cinematic" x-model="showCinematic" class="w-4 h-4 rounded border-zinc-300 text-blue-500 focus:ring-blue-500">
                                    <span class="text-[10px] font-black uppercase text-zinc-600">Exibir na Proposta</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4" x-show="showCinematic" x-collapse>
                                <div class="md:col-span-1">
                                    <label class="label-premium">Valor (R$)</label>
                                    <input type="text" name="valor_cinematic" class="input font-bold text-zinc-900" x-model="valorCinematic" placeholder="5.900,00">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="label-premium">Itens inclusos</label>
                                    <textarea name="itens_cinematic" class="input text-xs leading-relaxed" x-model="itensCinematic" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- PLANO ESSENCIAL -->
                        <div class="card-plan p-5 rounded-2xl bg-zinc-50/30 border-zinc-100" :class="showEssencial ? 'card-plan-active' : 'opacity-60'">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-widest flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-zinc-400 shadow-[0_0_8px_rgba(156,163,175,0.5)]"></span> 
                                    Registro Essencial
                                </h4>
                                <label class="flex items-center gap-2 cursor-pointer bg-white/50 px-3 py-1 rounded-full border border-zinc-200">
                                    <input type="checkbox" name="show_essencial" x-model="showEssencial" class="w-4 h-4 rounded border-zinc-300 text-zinc-500 focus:ring-zinc-500">
                                    <span class="text-[10px] font-black uppercase text-zinc-600">Exibir na Proposta</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4" x-show="showEssencial" x-collapse>
                                <div class="md:col-span-1">
                                    <label class="label-premium">Valor (R$)</label>
                                    <input type="text" name="valor_essencial" class="input font-bold text-zinc-900" x-model="valorEssencial" placeholder="3.900,00">
                                </div>
                                <div class="md:col-span-3">
                                    <label class="label-premium">Itens inclusos</label>
                                    <textarea name="itens_essencial" class="input text-xs leading-relaxed" x-model="itensEssencial" rows="2"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-zinc-100/50 pt-8 mt-8">
                            <h4 class="text-[11px] font-black text-zinc-400 uppercase tracking-[0.2em] mb-6">Condições de Pagamento e Reserva</h4>
                            <div class="space-y-6">
                                <div class="form-group">
                                    <label class="label-premium">Texto Geral de Reserva (Contrato)</label>
                                    <div class="contract-block">
                                        <textarea name="condicoes_reserva" class="w-full bg-transparent border-0 focus:ring-0 p-0 text-xs leading-relaxed" x-model="condicoesReserva" rows="3"></textarea>
                                    </div>
                                    <p class="text-[10px] text-zinc-400 mt-2 italic">Este texto aparece no final da proposta como cláusula legal.</p>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="form-group">
                                        <label class="label-premium">Heritage & Cinematic (Parcelamento)</label>
                                        <input type="text" name="condicoes_heritage_cinematic" class="input text-xs" x-model="condicoesHeritageCinematic">
                                    </div>
                                    <div class="form-group">
                                        <label class="label-premium">Registro Essencial (Parcelamento)</label>
                                        <input type="text" name="condicoes_essencial" class="input text-xs" x-model="condicoesEssencial">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                            <div class="card-plan p-5 rounded-2xl bg-zinc-50/30 border-zinc-100" :class="includeBoudoir ? 'card-plan-active' : 'opacity-60'">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-[10px] font-black text-zinc-900 uppercase tracking-widest">Upgrade Boudoir</h4>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="include_boudoir" x-model="includeBoudoir" class="sr-only peer">
                                        <div class="w-9 h-5 bg-zinc-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500"></div>
                                    </label>
                                </div>
                                <label class="label-premium">Valor Adicional</label>
                                <input type="text" name="valor_boudoir" class="input font-bold" x-model="valorBoudoir" placeholder="800,00" :disabled="!includeBoudoir">
                            </div>
                            <div class="card-plan p-5 rounded-2xl bg-zinc-50/30 border-zinc-100" :class="includePrewedding ? 'card-plan-active' : 'opacity-60'">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-[10px] font-black text-zinc-900 uppercase tracking-widest">Upgrade Pré-Wedding</h4>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="include_prewedding" x-model="includePrewedding" class="sr-only peer">
                                        <div class="w-9 h-5 bg-zinc-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500"></div>
                                    </label>
                                </div>
                                <label class="label-premium">Valor Adicional</label>
                                <input type="text" name="valor_prewedding" class="input font-bold" x-model="valorPrewedding" placeholder="1.200,00" :disabled="!includePrewedding">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card p-6" x-show="tipoProposta === 'marketing'">
                    <h3 class="text-sm font-bold text-zinc-900 mb-6 flex items-center gap-2">
                        <i data-lucide="heart" class="w-4 h-4 text-zinc-400"></i> Personalização Premium (Casamento)
                    </h3>
                    
                    <div class="space-y-6">
                        <!-- Mensagem e Validade -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="md:col-span-2">
                                <label class="label-premium">Mensagem Pessoal (Página 02)</label>
                                <textarea name="mensagem_pessoal" class="input text-xs" x-model="mensagemPessoal" rows="3"></textarea>
                            </div>
                            <div>
                                <label class="label-premium">Validade da Proposta (Dias)</label>
                                <input type="number" name="validade_proposta" class="input" x-model="validadeProposta">
                            </div>
                        </div>

                        <!-- Prazos e Contatos -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-4 border border-zinc-100 rounded-2xl bg-zinc-50/50">
                                <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">Cronograma de Entrega</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="label-premium">Prazo Prévias</label>
                                        <input type="text" name="prazo_previas" class="input" x-model="prazoPrevias">
                                    </div>
                                    <div>
                                        <label class="label-premium">Prazo Material Final</label>
                                        <input type="text" name="prazo_final" class="input" x-model="prazoFinal">
                                    </div>
                                </div>
                            </div>
                            <div class="p-4 border border-zinc-100 rounded-2xl bg-zinc-50/50">
                                <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider mb-4">Contatos da Proposta</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="col-span-2">
                                        <label class="label-premium">Instagram</label>
                                        <input type="text" name="instagram_handle" class="input" x-model="instagramHandle">
                                    </div>
                                    <div>
                                        <label class="label-premium">E-mail</label>
                                        <input type="text" name="email_contato" class="input" x-model="emailContato">
                                    </div>
                                    <div>
                                        <label class="label-premium">WhatsApp</label>
                                        <input type="text" name="whatsapp_numero" class="input" x-model="whatsappNumero">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Depoimentos gerenciados centralmente -->
                        <div class="p-4 border border-zinc-100 rounded-2xl bg-zinc-50/50 flex items-center justify-between gap-4">
                            <div>
                                <h4 class="text-xs font-bold text-zinc-900 uppercase tracking-wider">Depoimentos (Prova Social)</h4>
                                <p class="text-[11px] text-zinc-400 mt-1">Os depoimentos são escolhidos automaticamente do banco central de depoimentos da categoria Casamento.</p>
                            </div>
                            <a href="<?= raizUrl('/gerenciamento/depoimentos.php') ?>" target="_blank"
                                class="text-[11px] font-bold text-zinc-600 whitespace-nowrap underline underline-offset-2 hover:text-zinc-900 transition-colors">
                                Gerenciar depoimentos →
                            </a>
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

                    <div class="space-y-3">
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
                                        <!-- valor_mensal calculado (enviado oculto para a API) -->
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
                    </div>
                </section>

                <!-- CRONOGRAMA DE ENTREGA -->
                <section class="card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900">Cronograma de Entrega</h3>
                            <p class="text-[11px] text-zinc-400 mt-0.5">Fases detectadas automaticamente pelos serviços. Edite os prazos conforme o projeto.</p>
                        </div>
                        <button type="button" @click="detectarFases(true)" class="text-[10px] bg-zinc-100 text-zinc-700 px-3 py-1.5 rounded-lg font-bold hover:bg-zinc-200 transition-all flex items-center gap-1">
                            <i data-lucide="refresh-cw" class="w-3 h-3"></i> Redetectar
                        </button>
                    </div>

                    <div class="space-y-3" x-show="fasesCronograma.length > 0">
                        <template x-for="(fase, idx) in fasesCronograma" :key="idx">
                            <div class="flex items-start gap-3 p-3 border border-zinc-100 rounded-xl bg-zinc-50/50">
                                <!-- Número da fase -->
                                <div class="w-7 h-7 rounded-full bg-zinc-900 text-white text-[10px] font-bold flex items-center justify-center flex-shrink-0 mt-1" x-text="idx + 1"></div>
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
                                    <div class="md:col-span-2">
                                        <label class="text-[10px] font-bold text-zinc-500 uppercase mb-1 block">Nome da Fase</label>
                                        <input type="text" :name="'fases['+idx+'][nome]'" class="input py-1.5 text-sm" x-model="fase.nome">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-zinc-500 uppercase mb-1 block" x-text="fase.dias === 0 ? 'Duração' : 'Dias'"></label>
                                        <input type="number" :name="'fases['+idx+'][dias]'" class="input py-1.5 text-sm" x-model="fase.dias" min="0" placeholder="0 = simultâneo">
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="text-[10px] font-bold text-zinc-500 uppercase mb-1 block">Descrição (opcional)</label>
                                        <input type="text" :name="'fases['+idx+'][descricao]'" class="input py-1.5 text-sm" x-model="fase.descricao">
                                    </div>
                                </div>
                                <button type="button" @click="fasesCronograma.splice(idx,1)" class="text-zinc-300 hover:text-red-400 transition-colors mt-1 flex-shrink-0">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div x-show="fasesCronograma.length === 0" class="text-center py-6 border-2 border-dashed border-zinc-100 rounded-xl">
                        <p class="text-xs text-zinc-400">Nenhuma fase detectada. Adicione serviços ou clique em Redetectar.</p>
                    </div>

                    <div class="flex gap-2 mt-3">
                        <button type="button" @click="adicionarFase()" class="text-[10px] text-zinc-600 font-bold hover:text-zinc-900 flex items-center gap-1">
                            <i data-lucide="plus" class="w-3 h-3"></i> Adicionar fase manualmente
                        </button>
                    </div>
                </section>

                <section class="card p-6" x-show="tipoProposta !== 'casamento'">
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
                                <div class="flex flex-col gap-2 p-3 border border-zinc-100 rounded-xl transition-all" :class="etapasAtivas.includes(etapa.id) ? 'bg-emerald-50/50 border-emerald-100' : ''">
                                    <label class="flex items-center gap-3 cursor-pointer">
                                        <input type="checkbox" name="etapas_ativas[]" :value="etapa.id" x-model="etapasAtivas" class="w-4 h-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                        <span class="text-xs font-semibold text-zinc-700" x-text="etapa.label"></span>
                                    </label>
                                    <div x-show="etapasAtivas.includes(etapa.id)" class="flex items-center gap-2 mt-1 animate-fade-in">
                                        <span class="text-[10px] text-zinc-400 uppercase font-bold">Duração (dias):</span>
                                        <input type="number" :name="'etapas_dias['+etapa.id+']'" x-model="etapasDias[etapa.id]" class="input py-1 px-2 text-xs w-16" min="0">
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p class="text-[10px] text-zinc-400 mt-3 italic">* Desmarque as etapas que o cliente já possui ou que não fazem parte deste escopo.</p>
                    </div>
                </section>

                <section class="card p-6" x-show="tipoProposta !== 'casamento'">
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
        fasesCronograma: [],
        etapasDias: {},
        secoes: {},
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
        condicoesReserva: '',
        condicoesHeritageCinematic: '',
        condicoesEssencial: '',
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
            
            // Mapeia os serviços garantindo que o ID seja encontrado (sempre como string)
            this.servicosSelecionados = dados.servicos ? dados.servicos.map(s => {
                const sid = String(s.id || '');
                const servicoEncontrado = this.catalogoServicos.find(cat =>
                    (sid && String(cat.id) === sid) ||
                    (cat.nome && s.nome && cat.nome.trim().toUpperCase() === s.nome.trim().toUpperCase())
                );

                return {
                    id: servicoEncontrado ? String(servicoEncontrado.id) : sid,
                    valor: parseFloat(s.valor_individual || s.valor || 0),
                    tipo_cobranca: s.tipo_cobranca || (servicoEncontrado && servicoEncontrado.periodicidade === 'pontual' ? 'pontual' : 'recorrente'),
                    frequencia: parseInt(s.frequencia) || 1,
                    valor_mensal: 0
                };
            }) : [];

            this.mesesContrato = parseInt(dados.meses_contrato) || 12;
            this.formaPagamento = dados.forma_pagamento || 'boleto_pix';
            this.dataInicio = dados.data_inicio || '';
            this.adicional = dados.adicional || { titulo: '', valor: 0, descricao: '' };

            // Carregar fases do cronograma (ou auto-detectar se não existirem)
            if (dados.fases_cronograma && dados.fases_cronograma.length > 0) {
                this.fasesCronograma = dados.fases_cronograma;
            } else {
                this.$nextTick(() => this.detectarFases());
            }

            // Carregar etapas ativas ou padrão (todas ativas se for nova ou antiga sem esse campo)
            if (dados.etapas_ativas && Array.isArray(dados.etapas_ativas) && dados.etapas_ativas.length > 0) {
                this.etapasAtivas = dados.etapas_ativas;
            } else {
                this.etapasAtivas = this.etapasDisponiveis.map(e => e.id);
            }

            // Carregar durações das etapas (etapas_dias)
            this.etapasDias = dados.etapas_dias || {
                imersao: 5,
                diagnostico: 7,
                planejamento: 14,
                linguagem_visual: 7,
                entrega: 2,
                gestao: 0
            };

            // Carregar dados de casamento
            this.nomeNoivo = dados.nome_noivo || '';
            this.nomeNoiva = dados.nome_noiva || '';
            this.dataCasamento = dados.data_casamento || '';
            this.dataLimiteDesconto = dados.data_limite_desconto || '';
            this.condicaoEspecial = dados.condicao_especial || '';
            this.valorHeritage = dados.valor_heritage || '';
            this.itensHeritage = dados.itens_heritage || 'Cobertura Documental, Álbum Heritage, Réplicas, Filme 4K, Drone, Ecossistema Digital';
            this.valorCinematic = dados.valor_cinematic || '';
            this.itensCinematic = dados.itens_cinematic || 'Fotografia 8h, Sessão Engagement, Short Film, Social Content, Making Of, Bônus';
            this.valorEssencial = dados.valor_essencial || '';
            this.itensEssencial = dados.itens_essencial || 'Cobertura Fotográfica Essencial';
            this.valorBoudoir = dados.valor_boudoir || '';
            this.valorPrewedding = dados.valor_prewedding || '';
            this.condicoesReserva = dados.condicoes_reserva || 'A reserva da data é oficializada mediante a assinatura do contrato e o pagamento do sinal (entrada), que pode ser de 20% ou 25% do valor do pacote escolhido.\nOpções de Parcelamento: Oferecemos flexibilidade para que o saldo seja quitado de forma equilibrada até a data do evento:';
            this.condicoesHeritageCinematic = dados.condicoes_heritage_cinematic || 'Entrada de 20% + Saldo parcelado em até 6x (dependendo do pacote selecionado)';
            this.condicoesEssencial = dados.condicoes_essencial || 'Entrada de 25% + Saldo parcelado em até 5x (dependendo do pacote selecionado)';
            
            // Novos campos dinâmicos para Proposta de Casamento
            this.mensagemPessoal = dados.mensagem_pessoal || 'Na Distinto, entendemos que o nosso papel vai muito além de apertar um botão: nossa missão é registrar histórias de amor com autenticidade e emoção.';
            this.prazoPrevias = dados.prazo_previas || '48 horas';
            this.prazoFinal = dados.prazo_final || '60 dias úteis';
            this.validadeProposta = dados.validade_proposta || '7';
            // Visibilidade de Pacotes (Casamento) - Retrocompatibilidade
            this.showHeritage = (dados.show_heritage !== undefined) ? !!dados.show_heritage : true;
            this.showCinematic = (dados.show_cinematic !== undefined) ? !!dados.show_cinematic : true;
            this.showEssencial = (dados.show_essencial !== undefined) ? !!dados.show_essencial : true;
            this.includeBoudoir = !!dados.include_boudoir;
            this.includePrewedding = !!dados.include_prewedding;

            this.instagramHandle = dados.instagram_handle || '@distintowedding';
            this.emailContato = dados.email_contato || 'contato@wedistinto.com';
            this.whatsappNumero = dados.whatsapp_numero || '+55 27 9 8858-6935';

            // Força o Alpine a processar os dados e depois recalcula
            this.$nextTick(() => {
                // Calcular subtotal dos serviços
                this.recalcularTotal();

                const valorTotalDB = <?= (float)$proposta['valor_total'] ?>;
                const meses = parseInt(this.mesesContrato) || 1;

                // Detectar formato antigo (valor_total era mensal, não contrato total)
                const isFormatoAntigo = dados.servicos?.some(s => 'tipo_cobranca' in s)
                    && valorTotalDB < this.valorSubtotal * 1.5; // heurística: se menor que 1.5× mensal, era mensal

                const valorContrato = isFormatoAntigo ? valorTotalDB * meses : valorTotalDB;
                this.valorTotal = Math.round(valorContrato * 100) / 100;

                // Inferir desconto do valor editado
                const mensalImplicado = this.valorTotal / meses;
                if (this.valorSubtotal > 0 && mensalImplicado < this.valorSubtotal - 0.01) {
                    this.descontoValor = Math.round((this.valorSubtotal - mensalImplicado) * 100) / 100;
                    this.descontoTipo = 'fixo';
                }

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

        // Detecta fases do cronograma com base nos serviços selecionados
        detectarFases(forcar = false) {
            if (!forcar && this.fasesCronograma.length > 0) return; // não sobrescreve se já tem fases

            const nomes = this.servicosSelecionados.map(s => {
                const c = this.catalogoServicos.find(cat => String(cat.id) === String(s.id));
                return (c?.nome || '').toLowerCase();
            }).join(' ');

            const temEstrategia = /estrat[eé]g|planejamento/i.test(nomes);
            const temSocial     = /redes sociais|social media|gestão.*social|gestao.*social/i.test(nomes);
            const temCaptacao   = /filmmaker|capta[çc][aã]o|audiovisual|filmagem|di[aá]ria film/i.test(nomes);

            const fases = [];

            if (temEstrategia) {
                fases.push({ nome: 'Planejamento Estratégico', dias: 15, descricao: 'Desenvolvimento do plano estratégico, calendário editorial e aprovação com o cliente' });
            }
            if (temCaptacao) {
                fases.push({ nome: 'Captação Audiovisual', dias: 0, descricao: 'Agendada imediatamente após a aprovação da estratégia — necessária para produção dos conteúdos' });
            }
            if (temEstrategia || temSocial) {
                fases.push({ nome: 'Produção e Aprovação do 1º Mês', dias: 15, descricao: 'Criação do primeiro mês de conteúdo, envio para aprovação e ajustes finais' });
            }
            if (temSocial) {
                fases.push({ nome: 'Início das Publicações', dias: 0, descricao: 'Publicações iniciadas após aprovação completa do material' });
            }

            if (fases.length > 0) this.fasesCronograma = fases;
        },

        adicionarFase() {
            this.fasesCronograma.push({ nome: '', dias: 15, descricao: '' });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        recalcularTotal() {
            const meses = parseInt(this.mesesContrato) || 1;

            // Calcular valor_mensal de cada serviço
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

            // Subtotal mensal
            const subRaw = this.servicosSelecionados.reduce((acc, curr) => acc + (curr.valor_mensal || 0), 0);
            this.valorSubtotal = Math.round(subRaw * 100) / 100;

            // Desconto sobre o mensal
            let desconto = 0;
            const sub = parseFloat(this.valorSubtotal || 0);
            const desc = parseFloat(this.descontoValor || 0);
            if (this.descontoTipo === 'porcentagem') desconto = sub * (desc / 100);
            else desconto = desc;

            const mensalFinal = Math.max(0, sub - desconto);

            // valorTotal = valor total do contrato (mensal × meses)
            this.valorTotal = Math.round(mensalFinal * meses * 100) / 100;
        },

        // Quando o usuário edita o valor total diretamente, calcula o desconto implícito
        calcularDescontoDoTotal() {
            const meses = parseInt(this.mesesContrato) || 1;
            const sub = parseFloat(this.valorSubtotal || 0);
            const total = parseFloat(this.valorTotal || 0);
            const mensalImplicado = total / meses;

            if (sub > 0 && mensalImplicado < sub) {
                this.descontoValor = Math.round((sub - mensalImplicado) * 100) / 100;
                this.descontoTipo = 'fixo';
            } else {
                this.descontoValor = 0;
            }
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
