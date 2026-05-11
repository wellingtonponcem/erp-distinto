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

// Buscar oportunidades
$stmtOportunidades = $db->query("SELECT id, nome, cliente_id FROM oportunidades ORDER BY previsao ASC");
$oportunidades = $stmtOportunidades->fetchAll();

// Buscar serviços (apenas ativos)
$stmtServicos = $db->query("SELECT id, nome, descricao, preco_venda, preco_venda_pontual, periodicidade, categoria, tipo, subtitulo, beneficios_json, condicoes_comerciais FROM servicos WHERE ativo = 1 ORDER BY nome ASC");
$servicos = $stmtServicos->fetchAll();
$servicosJson = json_encode($servicos);

// Separar serviços de casamento
$weddingPackages = array_filter($servicos, fn($s) => $s['categoria'] === 'wedding' && $s['tipo'] === 'plano');
$weddingUpgrades = array_filter($servicos, fn($s) => $s['categoria'] === 'wedding' && $s['tipo'] === 'servico');

$tituloPagina = 'Editar Proposta - ' . $proposta['cliente_nome'];
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
    .label-premium {
        display: block;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: rgba(255, 255, 255, 0.4);
        margin-bottom: 8px;
    }
    .section-header-premium {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 18px;
        font-weight: 900;
        color: white;
        margin-bottom: 24px;
    }
    .card-plan {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(255, 255, 255, 0.05);
        background: rgba(255, 255, 255, 0.02);
    }
    .card-plan-active {
        background: rgba(255, 255, 255, 0.05) !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
        transform: translateY(-2px);
        box-shadow: 0 20px 40px -20px rgba(0,0,0,0.5);
    }
    .upgrade-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
        transition: all 0.2s;
    }
    .upgrade-card:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.1);
    }
    .switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 22px;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: rgba(255, 255, 255, 0.1);
        transition: .4s;
        border-radius: 34px;
    }
    .slider:before {
        position: absolute;
        content: "";
        height: 16px; width: 16px;
        left: 3px; bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    input:checked + .slider { background-color: #10b981; }
    input:checked + .slider:before { transform: translateX(18px); }
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
                <!-- 1. Informações Gerais (Sempre Visível) -->
                <section class="card p-6">
                    <h3 class="section-header-premium">
                        <i data-lucide="info" class="w-5 h-5 text-blue-400"></i>
                        Informações Gerais
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="label">Cliente</label>
                            <select name="cliente_id" id="cliente_id" class="input">
                                <option value="">Selecione um cliente...</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= isset($proposta['cliente_id']) && $proposta['cliente_id'] === $c['id'] ? 'selected' : '' ?>><?= sanitizar($c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="label">Oportunidade vinculada</label>
                            <select name="oportunidade_id" class="input" id="oportunidade_id">
                                <option value="">Nenhuma</option>
                                <?php foreach ($oportunidades as $o): ?>
                                    <option value="<?= $o['id'] ?>" data-cliente-id="<?= sanitizar($o['cliente_id'] ?? '') ?>" <?= isset($proposta['oportunidade_id']) && $proposta['oportunidade_id'] === $o['id'] ? 'selected' : '' ?>><?= sanitizar($o['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-zinc-500 mt-2">A lista de oportunidades é filtrada pelo cliente selecionado.</p>
                        </div>
                        <div class="form-group">
                            <label class="label">Tipo de Serviço</label>
                            <select name="tipo" id="tipoProposta" class="input" required x-model="tipoProposta">
                                <option value="marketing">Marketing Digital</option>
                                <option value="casamento">Casamento</option>
                                <option value="15anos">15 Anos</option>
                                <option value="filmmaker">Filmmaker (Cinematic)</option>
                            </select>
                            <p class="text-[10px] text-zinc-500 mt-1">Alterar o tipo mudará os campos disponíveis abaixo.</p>
                        </div>
                        <div class="form-group">
                            <label class="label">Status da Proposta</label>
                            <select name="status" class="input" x-model="statusProposta">
                                <option value="rascunho">Rascunho</option>
                                <option value="pendente">Pendente</option>
                                <option value="aceita">Aceita</option>
                                <option value="recusada">Recusada</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- 2. FLUXO DE CASAMENTO -->
                <div x-show="tipoProposta === 'casamento'" class="space-y-6">
                    <section class="card p-6">
                        <h2 class="section-header-premium">
                            <i data-lucide="heart" class="w-5 h-5 text-rose-500"></i>
                            Dados do Casamento
                        </h2>

                        <!-- Campos principais -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 pb-8 border-b border-white/5">
                            <div class="form-group">
                                <label class="label-premium">WhatsApp do Cliente</label>
                                <input type="text" name="whatsapp_casamento" class="input" x-model="whatsapp" placeholder="Ex: 27999998888">
                                <input type="hidden" name="whatsapp" :value="whatsapp">
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
                                <input type="text" name="data_casamento" class="input js-datepicker" x-model="dataCasamento" placeholder="Selecione a data" x-init="flatpickr($el, { locale: 'pt', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y' })">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8 pb-8 border-b border-white/5">
                            <div class="form-group">
                                <label class="label-premium">Data Limite para Desconto</label>
                                <input type="text" name="data_limite_desconto" class="input js-datepicker" x-model="dataLimiteDesconto" placeholder="Selecione a data" x-init="flatpickr($el, { locale: 'pt', dateFormat: 'Y-m-d', altInput: true, altFormat: 'd/m/Y' })">
                            </div>
                            <div class="form-group">
                                <label class="label-premium">Condição Especial</label>
                                <input type="text" name="condicao_especial" class="input" x-model="condicaoEspecial" placeholder="Ex: Condição especial p/ amigos lagoinha">
                            </div>
                        </div>
                        
                        <!-- Pacotes de Casamento -->
                        <div class="space-y-6">
                            <?php foreach ($weddingPackages as $pkg): 
                                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $pkg['nome'])));
                                $flag = 'show' . (strpos($slug, 'heritage') !== false ? 'Heritage' : (strpos($slug, 'cinematic') !== false ? 'Cinematic' : 'Essencial'));
                                $valVar = 'valor' . (strpos($slug, 'heritage') !== false ? 'Heritage' : (strpos($slug, 'cinematic') !== false ? 'Cinematic' : 'Essencial'));
                                $itensVar = 'itens' . (strpos($slug, 'heritage') !== false ? 'Heritage' : (strpos($slug, 'cinematic') !== false ? 'Cinematic' : 'Essencial'));
                                $color = strpos($slug, 'heritage') !== false ? 'amber-500' : (strpos($slug, 'cinematic') !== false ? 'blue-500' : 'zinc-400');
                            ?>
                            <div class="card-plan p-5 rounded-2xl bg-zinc-50/30 border-zinc-100" :class="<?= $flag ?> ? 'card-plan-active' : 'opacity-60'">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-<?= $color ?>"></span> 
                                        <?= $pkg['nome'] ?>
                                    </h4>
                                    <label class="flex items-center gap-3 cursor-pointer bg-white/5 px-4 py-2 rounded-full border border-white/10 hover:border-white/20 transition-all">
                                        <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Mostrar</span>
                                        <div class="switch">
                                            <input type="checkbox" name="show_<?= strtolower(str_replace('show', '', $flag)) ?>" x-model="<?= $flag ?>">
                                            <span class="slider"></span>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4" x-show="<?= $flag ?>" x-collapse>
                                    <div class="form-group">
                                        <label class="label-premium">Valor do Pacote</label>
                                        <input type="number" name="valor_<?= strtolower(str_replace('show', '', $flag)) ?>" class="input font-bold" x-model="<?= $valVar ?>" placeholder="<?= number_format($pkg['preco_venda'], 2, ',', '.') ?>">
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="label-premium">Itens inclusos</label>
                                        <textarea name="itens_<?= strtolower(str_replace('show', '', $flag)) ?>" class="input text-xs leading-relaxed" x-model="<?= $itensVar ?>" rows="2"></textarea>
                                    </div>
                                </div>

                                <!-- Upgrades Dinâmicos -->
                                <div class="mt-6 pt-4 border-t border-white/5" x-show="<?= $flag ?>" x-collapse>
                                    <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-4">Adicionais Disponíveis</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <?php foreach ($weddingUpgrades as $upg): 
                                            $upgId = $upg['id'];
                                            $upgNomeLower = strtolower($upg['nome']);
                                            $suffix = (strpos($slug, 'heritage') !== false ? 'Heritage' : (strpos($slug, 'cinematic') !== false ? 'Cinematic' : 'Essencial'));
                                            $pkgId = strtolower($suffix);

                                            $isBoudoir = strpos($upgNomeLower, 'boudoir') !== false;
                                            $isPrewedding = strpos($upgNomeLower, 'pre-wedding') !== false || strpos($upgNomeLower, 'prewedding') !== false || strpos($upgNomeLower, 'wedding') !== false;
                                            
                                            if ($isBoudoir) {
                                                $upgFlag = 'includeBoudoir' . $suffix;
                                                $upgName = 'include_boudoir_' . $pkgId;
                                            } elseif ($isPrewedding) {
                                                $upgFlag = 'includePrewedding' . $suffix;
                                                $upgName = 'include_prewedding_' . $pkgId;
                                            } else {
                                                $upgFlag = "upgrades.{$pkgId}['{$upgId}']";
                                                $upgName = "upgrades[{$pkgId}][{$upgId}]";
                                            }
                                        ?>
                                        <label class="flex items-center justify-between p-4 rounded-2xl upgrade-card cursor-pointer">
                                            <div class="flex flex-col">
                                                <span class="text-[11px] font-bold text-zinc-100"><?= $upg['nome'] ?></span>
                                                <span class="text-[9px] text-zinc-500">Incluir neste pacote</span>
                                            </div>
                                            <div class="switch">
                                                <input type="checkbox" name="<?= $upgName ?>" x-model="<?= $upgFlag ?>">
                                                <span class="slider"></span>
                                            </div>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Condições Financeiras (Casamento) -->
                        <div class="border-t border-white/5 pt-8 mt-8">
                            <h4 class="text-[11px] font-black text-zinc-400 uppercase tracking-[0.2em] mb-6">Condições de Pagamento e Reserva</h4>
                            <div class="space-y-6">
                                <div class="form-group">
                                    <label class="label-premium">Texto Geral de Reserva (Contrato)</label>
                                    <div class="contract-block">
                                        <textarea name="condicoes_reserva" class="w-full bg-transparent border-0 focus:ring-0 p-0 text-xs leading-relaxed" x-model="condicoesReserva" rows="3"></textarea>
                                    </div>
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
                    </section>

                    <!-- Personalização Premium (Casamento) -->
                    <section class="card p-6">
                        <h3 class="text-sm font-bold text-white mb-6 flex items-center gap-2">
                            <i data-lucide="sparkles" class="w-4 h-4 text-amber-400"></i> Personalização Premium
                        </h3>
                        
                        <div class="space-y-6">
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

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="p-4 border border-white/5 rounded-2xl bg-white/5">
                                    <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">Cronograma de Entrega</h4>
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
                                <div class="p-4 border border-white/5 rounded-2xl bg-white/5">
                                    <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">Contatos da Proposta</h4>
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
                        </div>
                    </section>
                </div>

                <!-- 3. FLUXO DE MARKETING (Apenas se NÃO for Casamento) -->
                <div x-show="tipoProposta !== 'casamento'" class="space-y-6">
                    <section class="card p-6">
                        <h3 class="section-header-premium">
                            <i data-lucide="file-text" class="w-5 h-5 text-zinc-400"></i>
                            Informações do Contrato
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="label">Responsável(is)</label>
                                <input type="text" name="responsavel" class="input" x-model="responsavel" placeholder="Ex: João Silva ou João e Maria">
                            </div>
                            <div class="form-group">
                                <label class="label">WhatsApp do Cliente</label>
                                <input type="text" name="whatsapp" class="input" x-model="whatsapp" placeholder="Ex: 27999998888">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                            <div class="form-group">
                                <label class="label">Título da Proposta</label>
                                <input type="text" name="titulo" class="input" value="<?= sanitizar($proposta['titulo']) ?>" placeholder="Ex: Proposta Comercial">
                            </div>
                            <div class="form-group">
                                <label class="label">Subtítulo (Opcional)</label>
                                <input type="text" name="subtitulo" class="input" value="<?= sanitizar($proposta['subtitulo']) ?>" placeholder="Ex: Planejamento Estratégico Q3">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                            <div class="form-group">
                                <label class="label">Subtotal (R$)</label>
                                <input type="number" step="0.01" class="input bg-zinc-800/30 font-bold text-zinc-500" readonly :value="valorSubtotal">
                            </div>
                            <div class="form-group">
                                <label class="label">Desconto</label>
                                <div class="flex">
                                    <input type="number" step="0.01" name="desconto_valor" class="input rounded-r-none border-r-0" x-model="descontoValor" @input="recalcularTotal()">
                                    <select name="desconto_tipo" class="input rounded-l-none w-16 px-1 text-center font-bold bg-zinc-800/30" x-model="descontoTipo" @change="recalcularTotal()">
                                        <option value="porcentagem">%</option>
                                        <option value="fixo">R$</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="label">Valor Total</label>
                                <input type="number" step="0.01" name="valor_total" class="input font-bold" required x-model="valorTotal" @input="calcularDescontoDoTotal()">
                            </div>
                            <div class="form-group">
                                <label class="label">Meses</label>
                                <input type="number" name="meses_contrato" class="input" x-model="mesesContrato" @input="recalcularTotal()">
                            </div>
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
                                        <div class="md:col-span-4">
                                            <label class="text-[10px] font-bold text-zinc-500 uppercase mb-1 block">Serviço</label>
                                            <select :name="'servicos['+index+'][id]'" class="input py-2" x-model="item.id" @change="atualizarDadosServico(index)">
                                                <option value="">Selecione um serviço...</option>
                                                <?php foreach ($servicos as $s): ?>
                                                <option value="<?= $s['id'] ?>"><?= sanitizar($s['nome']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="text-[10px] font-bold text-zinc-500 uppercase mb-1 block">Modo de Cobrança</label>
                                            <select :name="'servicos['+index+'][tipo_cobranca]'" class="input py-2" x-model="item.tipo_cobranca" @change="recalcularTotal()">
                                                <option value="recorrente">Recorrente (Mensal)</option>
                                                <option value="pontual">Pontual (Única/Frequência)</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2" x-show="item.tipo_cobranca === 'pontual'">
                                            <label class="text-[10px] font-bold text-zinc-500 uppercase mb-1 block">Frequência/Mês</label>
                                            <input type="number" :name="'servicos['+index+'][frequencia]'" class="input py-2" x-model="item.frequencia" @input="recalcularTotal()" min="1" placeholder="Ex: 1">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="text-[10px] font-bold text-zinc-500 uppercase mb-1 block" x-text="item.tipo_cobranca === 'pontual' ? 'Valor Único' : 'Valor Mensal'"></label>
                                            <input type="number" step="0.01" :name="'servicos['+index+'][valor]'" class="input py-2 font-bold" x-model="item.valor" @input="recalcularTotal()">
                                        </div>
                                        <div class="md:col-span-1 flex items-end justify-end">
                                            <button type="button" @click="removerServico(index)" class="bg-red-50 text-red-500 p-2 rounded-lg hover:bg-red-100 transition-colors">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </section>

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
                </div>
            </div>

            <!-- Coluna Lateral: Ações -->
            <div class="space-y-6">
                <section class="card p-6 bg-zinc-900 text-white shadow-xl shadow-zinc-900/20 border-0 sticky top-6">
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
        instagramHandle: '',
        emailContato: '',
        whatsappNumero: '',

        initEdit() {
            // Carregar dados existentes
            const dados = <?= json_encode($dadosJson) ?>;
            this.secoes = dados.secoes || {};
            this.responsavel = dados.responsavel || '';
            this.whatsapp = dados.whatsapp || '';
            
            // Mapeia os serviços
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
            this.dataInicio = dados.data_inicio || '';

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
            
            this.condicoesReserva = dados.condicoes_reserva || 'A reserva da data é oficializada mediante a assinatura do contrato e o pagamento do sinal (entrada).';
            this.condicoesHeritageCinematic = dados.condicoes_heritage_cinematic || 'Entrada de 20% + Saldo parcelado';
            this.condicoesEssencial = dados.condicoes_essencial || 'Entrada de 25% + Saldo parcelado';
            
            this.mensagemPessoal = dados.mensagem_pessoal || 'Na Distinto, entendemos que o nosso papel vai muito além de apertar um botão.';
            this.prazoPrevias = dados.prazo_previas || '48 horas';
            this.prazoFinal = dados.prazo_final || '60 dias úteis';
            this.validadeProposta = dados.validade_proposta || '7';

            // Visibilidade de Pacotes
            this.showHeritage = (dados.show_heritage !== undefined) ? !!dados.show_heritage : true;
            this.showCinematic = (dados.show_cinematic !== undefined) ? !!dados.show_cinematic : true;
            this.showEssencial = (dados.show_essencial !== undefined) ? !!dados.show_essencial : true;
            
            // Flags de Upgrades
            this.includeBoudoirHeritage = !!(dados.include_boudoir_heritage || dados.include_boudoir);
            this.includePreweddingHeritage = !!(dados.include_prewedding_heritage || dados.include_prewedding);
            this.includeBoudoirCinematic = !!(dados.include_boudoir_cinematic || dados.include_boudoir);
            this.includePreweddingCinematic = !!(dados.include_prewedding_cinematic || dados.include_prewedding);
            this.includeBoudoirEssencial = !!(dados.include_boudoir_essencial || dados.include_boudoir);
            this.includePreweddingEssencial = !!(dados.include_prewedding_essencial || dados.include_prewedding);

            this.upgrades = dados.upgrades || { heritage: {}, cinematic: {}, essencial: {} };

            this.instagramHandle = dados.instagram_handle || '@distintowedding';
            this.emailContato = dados.email_contato || 'contato@wedistinto.com';
            this.whatsappNumero = dados.whatsapp_numero || '+55 27 9 8858-6935';

            this.$nextTick(() => {
                this.recalcularTotal();
                const valorTotalDB = <?= (float)$proposta['valor_total'] ?>;
                const meses = parseInt(this.mesesContrato) || 1;
                this.valorTotal = valorTotalDB;
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
                item.tipo_cobranca = servico.periodicidade === 'pontual' ? 'pontual' : 'recorrente';
                item.valor = parseFloat(servico.preco_venda || 0);
            }
            this.recalcularTotal();
        },

        detectarFases(forcar = false) {
            if (!forcar && this.fasesCronograma.length > 0) return;
            const nomes = this.servicosSelecionados.map(s => {
                const c = this.catalogoServicos.find(cat => String(cat.id) === String(s.id));
                return (c?.nome || '').toLowerCase();
            }).join(' ');
            const temEstrategia = /estrat[eé]g|planejamento/i.test(nomes);
            const temSocial     = /redes sociais|social media/i.test(nomes);
            const temCaptacao   = /filmmaker|capta[çc][aã]o/i.test(nomes);
            const fases = [];
            if (temEstrategia) fases.push({ nome: 'Planejamento Estratégico', dias: 15 });
            if (fases.length > 0) this.fasesCronograma = fases;
        },

        adicionarFase() {
            this.fasesCronograma.push({ nome: '', dias: 15, descricao: '' });
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        recalcularTotal() {
            const meses = parseInt(this.mesesContrato) || 1;
            this.servicosSelecionados.forEach(item => {
                const servico = this.catalogoServicos.find(s => s.id == item.id);
                const freq = parseInt(item.frequencia) || 1;
                if (item.tipo_cobranca === 'pontual') {
                    item.valor_mensal = (freq > 1) ? Math.round((parseFloat(item.valor) * freq) * 100) / 100 : Math.round((parseFloat(item.valor) / meses) * 100) / 100;
                } else {
                    item.valor_mensal = Math.round(parseFloat(item.valor) * 100) / 100;
                }
            });
            const subRaw = this.servicosSelecionados.reduce((acc, curr) => acc + (curr.valor_mensal || 0), 0);
            this.valorSubtotal = Math.round(subRaw * 100) / 100;
            let desconto = 0;
            if (this.descontoTipo === 'porcentagem') desconto = this.valorSubtotal * (parseFloat(this.descontoValor || 0) / 100);
            else desconto = parseFloat(this.descontoValor || 0);
            this.valorTotal = Math.round((this.valorSubtotal - desconto) * meses * 100) / 100;
        },

        calcularDescontoDoTotal() {
            const meses = parseInt(this.mesesContrato) || 1;
            const mensalImplicado = parseFloat(this.valorTotal || 0) / meses;
            if (this.valorSubtotal > 0 && mensalImplicado < this.valorSubtotal) {
                this.descontoValor = Math.round((this.valorSubtotal - mensalImplicado) * 100) / 100;
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
    const selectCliente = document.getElementById('cliente_id');
    const selectOportunidade = document.getElementById('oportunidade_id');

    function filterOportunidadesPorCliente(clienteId) {
        if (!selectOportunidade) return;
        selectOportunidade.querySelectorAll('option[data-cliente-id]').forEach(opt => {
            const optClient = opt.dataset.clienteId || '';
            if (!clienteId || clienteId === '' || opt.value === '' || optClient === '' || optClient === clienteId) {
                opt.hidden = false;
                opt.disabled = false;
            } else {
                opt.hidden = true;
                opt.disabled = true;
            }
        });
        if (selectOportunidade.value && selectOportunidade.selectedOptions[0].disabled) {
            selectOportunidade.value = '';
        }
    }

    if (selectCliente) {
        selectCliente.addEventListener('change', () => filterOportunidadesPorCliente(selectCliente.value));
        filterOportunidadesPorCliente(selectCliente.value);
    }

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
