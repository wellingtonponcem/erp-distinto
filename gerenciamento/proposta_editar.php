<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAdmin();

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
if (!is_array($dadosJson)) {
    $dadosJson = [];
}
$pacoteDadoAndamento = $dadosJson['pacote_dado_andamento'] ?? '';
if ($pacoteDadoAndamento === '' && !empty($dadosJson['cliente_escolha']['plano_id'])) {
    $pacoteDadoAndamento = $dadosJson['cliente_escolha']['plano_id'];
}
$responsavelForm = $dadosJson['responsavel_manual'] ?? ($dadosJson['responsavel'] ?? '');
$contatoTipoForm = $dadosJson['contato_tipo'] ?? 'noiva';
$nomeNoivoForm = $dadosJson['nome_noivo'] ?? '';
$nomeNoivaForm = $dadosJson['nome_noiva'] ?? '';
$dataCasamentoForm = $dadosJson['data_casamento'] ?? ($dadosJson['data_evento'] ?? '');
$whatsappForm = $dadosJson['whatsapp'] ?? '';
$dataLimiteDescontoForm = $dadosJson['data_limite_desconto'] ?? '';
if (($nomeNoivoForm === '' || $nomeNoivaForm === '') && !empty($proposta['cliente_nome']) && strpos($proposta['cliente_nome'], '&') !== false) {
    [$nomeNoivoFallback, $nomeNoivaFallback] = array_map('trim', explode('&', $proposta['cliente_nome'], 2));
    if ($nomeNoivoForm === '') {
        $nomeNoivoForm = $nomeNoivoFallback;
    }
    if ($nomeNoivaForm === '') {
        $nomeNoivaForm = $nomeNoivaFallback;
    }
}
$responsavelComparacao = strtolower(trim($responsavelForm));
if ($responsavelComparacao !== '') {
    if ($nomeNoivaForm !== '' && $responsavelComparacao === strtolower(trim($nomeNoivaForm))) {
        $contatoTipoForm = 'noiva';
    } elseif ($nomeNoivoForm !== '' && $responsavelComparacao === strtolower(trim($nomeNoivoForm))) {
        $contatoTipoForm = 'noivo';
    }
}
if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dataCasamentoForm, $m)) {
    $dataCasamentoDisplay = "{$m[3]}/{$m[2]}/{$m[1]}";
} else {
    $dataCasamentoDisplay = $dataCasamentoForm;
}
if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dataLimiteDescontoForm, $m)) {
    $dataLimiteDescontoDisplay = "{$m[3]}/{$m[2]}/{$m[1]}";
} else {
    $dataLimiteDescontoDisplay = $dataLimiteDescontoForm;
}
$tipoPropostaInicial = ($proposta['tipo'] ?? '');
$isCasamento = $tipoPropostaInicial === 'casamento'
    || !empty($dadosJson['nome_noivo'])
    || !empty($dadosJson['nome_noiva'])
    || !empty($dadosJson['data_casamento'])
    || !empty($dadosJson['show_heritage'])
    || !empty($dadosJson['show_cinematic'])
    || !empty($dadosJson['show_essencial'])
    || !empty($pacoteDadoAndamento);
if ($isCasamento) {
    $tipoPropostaInicial = 'casamento';
}

if (!function_exists('jsonParaJs')) {
    function jsonParaJs($valor): string {
        $json = json_encode(
            $valor,
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );

        return $json === false ? 'null' : $json;
    }
}

// Buscar clientes
$stmtClientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
$clientes = $stmtClientes->fetchAll();

// Buscar oportunidades
$stmtOportunidades = $db->query("SELECT id, nome, cliente_id FROM oportunidades ORDER BY previsao ASC");
$oportunidades = $stmtOportunidades->fetchAll();

// Buscar fornecedores
$stmtFornecedores = $db->query("SELECT id, nome, categoria FROM fornecedores ORDER BY nome ASC");
$fornecedores = $stmtFornecedores->fetchAll();

// Buscar serviços (apenas ativos)
$stmtServicos = $db->query("SELECT id, nome, descricao, preco_venda, preco_venda_pontual, periodicidade, categoria, tipo, subtitulo, beneficios_json, condicoes_comerciais FROM servicos WHERE ativo = 1 ORDER BY nome ASC");
$servicos = $stmtServicos->fetchAll();
$servicosJson = jsonParaJs($servicos);

// Separar serviços de casamento
$weddingPackages = array_filter($servicos, fn($s) => $s['categoria'] === 'wedding' && $s['tipo'] === 'plano');
$weddingUpgrades = array_filter($servicos, fn($s) => $s['categoria'] === 'wedding' && $s['tipo'] === 'servico');

$heritagePkg = array_values(array_filter($weddingPackages, fn($s) => strpos(strtolower($s['nome']), 'heritage') !== false))[0] ?? null;
$cinematicPkg = array_values(array_filter($weddingPackages, fn($s) => strpos(strtolower($s['nome']), 'cinematic') !== false))[0] ?? null;
$essencialPkg = array_values(array_filter($weddingPackages, fn($s) => strpos(strtolower($s['nome']), 'essencial') !== false))[0] ?? null;

if (!function_exists('obterBeneficiosTexto')) {
    function obterBeneficiosTexto($pkg) {
        if (!$pkg || empty($pkg['beneficios_json'])) {
            return '';
        }
        $arr = json_decode($pkg['beneficios_json'], true);
        if (is_array($arr)) {
            return implode("\n", $arr);
        }
        return $pkg['beneficios_json'];
    }
}

$beneficiosH = obterBeneficiosTexto($heritagePkg);
$beneficiosC = obterBeneficiosTexto($cinematicPkg);
$beneficiosE = obterBeneficiosTexto($essencialPkg);

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

    /* Stepper Styles */
    .stepper-item {
        position: relative;
        flex: 1;
        text-align: center;
    }
    .stepper-item:not(:last-child):after {
        content: '';
        position: absolute;
        top: 15px;
        left: 50%;
        width: 100%;
        height: 2px;
        background: rgba(255,255,255,0.05);
        z-index: 0;
    }
    .stepper-item.active:after {
        background: #10b981;
    }
    .stepper-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
        border: 2px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        position: relative;
        z-index: 1;
        font-size: 12px;
        font-weight: 700;
        transition: all 0.3s;
    }
    .stepper-item.active .stepper-circle {
        background: #10b981;
        border-color: #10b981;
        color: white;
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.3);
    }
    .stepper-item.completed .stepper-circle {
        background: #10b981;
        border-color: #10b981;
        color: white;
    }
    .stepper-label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255,255,255,0.4);
    }
    .stepper-item.active .stepper-label {
        color: white;
    }
    .proposal-step {
        display: none;
    }
    .proposal-step.is-active {
        display: block;
    }
    .is-wedding-editor [data-non-wedding-field],
    .is-non-wedding-editor [data-wedding-field] {
        display: none !important;
    }

</style>

<?php
$isModal = ($_GET['layout'] ?? '') === 'modal';
?>

<div id="app-wrapper" class="<?= $isModal ? 'is-modal-layout' : '' ?>">
    <?php if (!$isModal) include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet <?= $isModal ? 'p-0' : '' ?>" x-data="proposta()" x-init="initEdit()">
        <?php if (!$isModal): ?>
        <div class="app-topbar">
            <div class="top-nav">
                <a href="<?= raizUrl('/dashboard.php') ?>">Visão Geral</a>
                <a href="<?= raizUrl('/gerenciamento/propostas.php' . (!empty($proposta['pasta_id']) ? '?folder=' . $proposta['pasta_id'] : '')) ?>">Propostas</a>
                <a href="#" class="active">Editar Proposta</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="<?= $isModal ? 'px-8 pt-8 mb-6' : 'mb-8' ?>">
            <h1 class="page-title text-2xl">Edição de Proposta: <span class="text-emerald-400"><?= sanitizar($proposta['cliente_nome']) ?></span></h1>
            <p class="page-subtitle text-zinc-500">Ajuste os detalhes para enviar ao cliente utilizando o fluxo guiado.</p>
        </div>

        <form id="formAtualizarProposta" class="grid grid-cols-1 lg:grid-cols-3 gap-8 <?= $isModal ? 'px-8 pb-12' : '' ?> <?= $isCasamento ? 'is-wedding-editor' : 'is-non-wedding-editor' ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" data-db-field="responsavel" value="<?= sanitizar($responsavelForm) ?>">
            <input type="hidden" data-db-field="contato_tipo" value="<?= sanitizar($contatoTipoForm) ?>">
            <input type="hidden" data-db-field="tipo" value="<?= sanitizar($tipoPropostaInicial) ?>">
            <input type="hidden" data-db-field="nome_noivo" value="<?= sanitizar($nomeNoivoForm) ?>">
            <input type="hidden" data-db-field="nome_noiva" value="<?= sanitizar($nomeNoivaForm) ?>">
            <input type="hidden" data-db-field="data_casamento" value="<?= sanitizar($dataCasamentoDisplay) ?>">
            <input type="hidden" data-db-field="whatsapp" value="<?= sanitizar($whatsappForm) ?>">
            <input type="hidden" data-db-field="data_limite_desconto" value="<?= sanitizar($dataLimiteDescontoDisplay) ?>">
            <input type="hidden" data-db-field="pacote_dado_andamento" value="<?= sanitizar($pacoteDadoAndamento) ?>">

            <div class="lg:col-span-2 space-y-8">
                <!-- STEPPER UI -->
                <div class="flex items-center justify-between mb-10 bg-white/5 p-6 rounded-2xl border border-white/5">
                    <div class="stepper-item active" data-step-indicator="1">
                        <div class="stepper-circle">1</div>
                        <div class="stepper-label">Cliente & Evento</div>
                    </div>
                    <div class="stepper-item" data-step-indicator="2">
                        <div class="stepper-circle">2</div>
                        <div class="stepper-label">Escopo</div>
                    </div>
                    <div class="stepper-item" data-step-indicator="3">
                        <div class="stepper-circle">3</div>
                        <div class="stepper-label">Financeiro</div>
                    </div>
                    <div class="stepper-item" data-step-indicator="4">
                        <div class="stepper-circle">4</div>
                        <div class="stepper-label">Contrato</div>
                    </div>
                </div>

                <!-- PASSO 1: CLIENTE E EVENTO -->
                <div data-editor-step="1" class="proposal-step is-active space-y-6">
                    <section class="card p-8">
                        <h3 class="section-header-premium">
                            <i data-lucide="user" class="w-6 h-6 text-blue-400"></i>
                            Informações do Cliente & Evento
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group md:col-span-2" data-wedding-field>
                                <label class="label-premium">Nome do cliente / contratante</label>
                                <input type="text" name="responsavel" id="responsavelInput" class="input h-12" value="<?= sanitizar($responsavelForm) ?>" placeholder="Quem está negociando e será responsável pelo contrato">
                                <p class="text-[10px] text-zinc-500 mt-1">Na proposta de casamento, o cliente pode ser a noiva, o noivo ou outra pessoa. No contrato, este nome será o responsável pelo pagamento.</p>
                            </div>
                            <div class="form-group" data-wedding-field>
                                <label class="label-premium">Esse cliente é</label>
                                <select name="contato_tipo" id="contatoTipoSelect" class="input h-12">
                                    <option value="noiva" <?= $contatoTipoForm === 'noiva' ? 'selected' : '' ?>>Noiva</option>
                                    <option value="noivo" <?= $contatoTipoForm === 'noivo' ? 'selected' : '' ?>>Noivo</option>
                                    <option value="outro" <?= $contatoTipoForm === 'outro' ? 'selected' : '' ?>>Responsável financeiro / familiar</option>
                                </select>
                            </div>
                            <div class="form-group" data-non-wedding-field>
                                <label class="label-premium">Cliente cadastrado</label>
                                <select name="cliente_id" id="cliente_id" class="input h-12">
                                    <option value="">Selecione um cliente...</option>
                                    <?php foreach ($clientes as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= isset($proposta['cliente_id']) && $proposta['cliente_id'] === $c['id'] ? 'selected' : '' ?>><?= sanitizar($c['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="label-premium">Tipo de Evento</label>
                                <select name="tipo" id="tipoProposta" class="input h-12" required>
                                    <option value="casamento" <?= $tipoPropostaInicial === 'casamento' ? 'selected' : '' ?>>Casamento</option>
                                    <option value="15anos" <?= $tipoPropostaInicial === '15anos' ? 'selected' : '' ?>>15 Anos</option>
                                    <option value="corporativo" <?= $tipoPropostaInicial === 'corporativo' ? 'selected' : '' ?>>Corporativo</option>
                                    <option value="marketing" <?= $tipoPropostaInicial === 'marketing' ? 'selected' : '' ?>>Marketing Digital</option>
                                </select>
                            </div>
                            <div class="form-group" data-wedding-field>
                                <label class="label-premium">Nome do Noivo</label>
                                <input type="text" name="nome_noivo" id="nomeNoivoInput" class="input h-12" value="<?= sanitizar($nomeNoivoForm) ?>" placeholder="Ex: Rodolfo Elias">
                            </div>
                            <div class="form-group" data-wedding-field>
                                <label class="label-premium">Nome da Noiva</label>
                                <input type="text" name="nome_noiva" id="nomeNoivaInput" class="input h-12" value="<?= sanitizar($nomeNoivaForm) ?>" placeholder="Ex: Rhuana Fonseca">
                            </div>
                            <div class="form-group" data-non-wedding-field>
                                <label class="label-premium">Empresa / Responsável</label>
                                <input type="text" name="nome_noivo" class="input h-12" value="<?= sanitizar($nomeNoivoForm) ?>" placeholder="Ex: Tech Solutions">
                            </div>
                            <div class="form-group">
                                <label class="label-premium">Data do Evento</label>
                                <input type="text" name="data_casamento" class="input h-12 js-datepicker" value="<?= sanitizar($dataCasamentoDisplay) ?>" placeholder="dd/mm/aaaa" inputmode="numeric" autocomplete="off" data-ptbr-calendar>
                            </div>
                            <div class="form-group">
                                <label class="label-premium">WhatsApp de Contato</label>
                                <input type="text" name="whatsapp" class="input h-12" value="<?= sanitizar($whatsappForm) ?>" placeholder="Ex: 27999998888">
                            </div>
                        </div>

                        <div class="mt-8 pt-8 border-t border-white/5 grid grid-cols-1 md:grid-cols-2 gap-6" data-wedding-field>
                            <div class="form-group">
                                <label class="label-premium">Data Limite para Desconto</label>
                                <input type="text" name="data_limite_desconto" class="input h-12 js-datepicker" value="<?= sanitizar($dataLimiteDescontoDisplay) ?>" placeholder="dd/mm/aaaa" inputmode="numeric" autocomplete="off" data-ptbr-calendar>
                            </div>
                            <div class="form-group">
                                <label class="label-premium">Condição Especial (Opcional)</label>
                                <input type="text" name="condicao_especial" class="input h-12" value="<?= sanitizar($dadosJson['condicao_especial'] ?? '') ?>" placeholder="Ex: Valor p/ pagamento à vista">
                            </div>
                        </div>
                    </section>

                    <div class="flex justify-end pt-4">
                        <button type="button" data-go-step="2" class="px-8 h-14 rounded-xl bg-white text-black font-bold hover:bg-zinc-100 transition-all flex items-center gap-3">
                            Próximo Passo: Escopo
                            <i data-lucide="arrow-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- PASSO 2: ESCOPO E SERVIÇOS -->
                <div data-editor-step="2" class="proposal-step space-y-6">
                    
                    <!-- Fluxo de Casamento (Planos de Casamento) -->
                    <div data-wedding-field class="space-y-6">
                        <section class="card p-8">
                            <h3 class="section-header-premium mb-8">
                                <i data-lucide="heart" class="w-6 h-6 text-rose-500"></i>
                                Planos de Casamento & Adicionais
                            </h3>
                            
                            <div class="space-y-6">
                                <?php foreach ($weddingPackages as $pkg):
                                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $pkg['nome'])));
                                    $flag = 'show' . (strpos($slug, 'heritage') !== false ? 'Heritage' : (strpos($slug, 'cinematic') !== false ? 'Cinematic' : 'Essencial'));
                                    $valVar = 'valor' . (strpos($slug, 'heritage') !== false ? 'Heritage' : (strpos($slug, 'cinematic') !== false ? 'Cinematic' : 'Essencial'));
                                    $baseVar = 'base' . (strpos($slug, 'heritage') !== false ? 'Heritage' : (strpos($slug, 'cinematic') !== false ? 'Cinematic' : 'Essencial'));
                                    $itensVar = 'itens' . (strpos($slug, 'heritage') !== false ? 'Heritage' : (strpos($slug, 'cinematic') !== false ? 'Cinematic' : 'Essencial'));
                                    $color = strpos($slug, 'heritage') !== false ? 'amber-500' : (strpos($slug, 'cinematic') !== false ? 'blue-500' : 'zinc-400');
                                    $pkgId = strpos($slug, 'heritage') !== false ? 'heritage' : (strpos($slug, 'cinematic') !== false ? 'cinematic' : 'essencial');
                                    $cleanName = strtolower(str_replace('show', '', $flag));
                                    $showCampo = 'show_' . $cleanName;
                                    $valorCampo = 'valor_' . $cleanName;
                                    $itensCampo = 'itens_' . $cleanName;
                                    $planoVisivel = array_key_exists($showCampo, $dadosJson) ? (bool)$dadosJson[$showCampo] : true;
                                    $valorPlano = $dadosJson[$valorCampo] ?? ($pkg['preco_venda'] ?? '');
                                    $itensPlano = $dadosJson[$itensCampo] ?? ($pkgId === 'heritage' ? $beneficiosH : ($pkgId === 'cinematic' ? $beneficiosC : $beneficiosE));
                                    if (is_array($itensPlano)) {
                                        $itensPlano = implode("\n", $itensPlano);
                                    }
                                ?>
                                <div class="card-plan p-6 rounded-2xl bg-white/5 border border-white/5" :class="<?= $flag ?> ? 'card-plan-active' : 'opacity-60'">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full bg-<?= $color ?>"></span>
                                            <?= $pkg['nome'] ?>
                                        </h4>
                                        <label class="flex items-center gap-3 cursor-pointer bg-white/5 px-4 py-2 rounded-full border border-white/10 hover:border-white/20 transition-all">
                                            <span class="text-[10px] font-bold text-zinc-400 uppercase tracking-widest">Mostrar na Proposta</span>
                                            <div class="switch">
                                                <input type="checkbox" name="show_<?= $cleanName ?>" x-model="<?= $flag ?>" @change="recalcularTotal()" <?= $planoVisivel ? 'checked' : '' ?>>
                                                <span class="slider"></span>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4" x-show="<?= $flag ?>" x-collapse>
                                        <div class="form-group">
                                            <label class="label-premium">Valor base (R$)</label>
                                            <input type="number" step="0.01" class="input font-bold" x-model="<?= $baseVar ?>" value="<?= sanitizar((string)$valorPlano) ?>" @input="recalcularPacote('<?= $pkgId ?>'); recalcularTotal()">
                                            <input type="hidden" name="valor_<?= $cleanName ?>" value="<?= sanitizar((string)$valorPlano) ?>" :value="<?= $valVar ?>">
                                            <p class="text-[10px] text-zinc-500 mt-1">Final: <span x-text="formatCurrency(<?= $valVar ?>)"></span></p>
                                        </div>
                                        <div class="md:col-span-3">
                                            <label class="label-premium">Itens inclusos</label>
                                            <textarea name="itens_<?= $cleanName ?>" class="input text-xs leading-relaxed animate-fade-in" x-model="<?= $itensVar ?>" rows="2"><?= sanitizar((string)$itensPlano) ?></textarea>
                                        </div>
                                    </div>

                                    <div class="mt-6 pt-4 border-t border-white/5" x-show="<?= $flag ?>" x-collapse>
                                        <div class="flex items-center justify-between mb-4">
                                            <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest">Itens editáveis do pacote</p>
                                            <button type="button" @click="adicionarItemPersonalizado('<?= $pkgId ?>')" class="text-[10px] bg-white/10 text-white px-3 py-1.5 rounded-lg font-bold hover:bg-white/20 transition-all flex items-center gap-1">
                                                <i data-lucide="plus" class="w-3 h-3"></i> Adicionar item
                                            </button>
                                        </div>
                                        <template x-for="(item, idx) in itensPersonalizados.<?= $pkgId ?>" :key="idx">
                                            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 p-4 rounded-2xl upgrade-card mb-3">
                                                <input type="hidden" :name="'itens_personalizados[<?= $pkgId ?>]['+idx+'][incluido]'" :value="item.incluido ? '1' : '0'">
                                                <div class="md:col-span-3">
                                                    <label class="label-premium">Item</label>
                                                    <input type="text" :name="'itens_personalizados[<?= $pkgId ?>]['+idx+'][nome]'" class="input text-xs" x-model="item.nome">
                                                </div>
                                                <div class="md:col-span-4">
                                                    <label class="label-premium">Descrição</label>
                                                    <input type="text" :name="'itens_personalizados[<?= $pkgId ?>]['+idx+'][descricao]'" class="input text-xs" x-model="item.descricao">
                                                </div>
                                                <div class="md:col-span-2">
                                                    <label class="label-premium">Preço (R$)</label>
                                                    <input type="number" step="0.01" :name="'itens_personalizados[<?= $pkgId ?>]['+idx+'][valor]'" class="input text-xs font-bold" x-model="item.valor" @input="recalcularPacote('<?= $pkgId ?>'); recalcularTotal()">
                                                </div>
                                                <div class="md:col-span-2 flex items-end">
                                                    <label class="flex items-center gap-2 text-[10px] font-bold text-zinc-400 uppercase tracking-wider">
                                                        <input type="checkbox" x-model="item.incluido" @change="recalcularPacote('<?= $pkgId ?>'); recalcularTotal()" class="w-4 h-4 rounded border-zinc-300">
                                                        Incluso
                                                    </label>
                                                </div>
                                                <div class="md:col-span-1 flex items-end justify-end">
                                                    <button type="button" @click="removerItemPersonalizado('<?= $pkgId ?>', idx)" class="bg-red-500/10 text-red-400 p-2 rounded-lg hover:bg-red-500/20 transition-colors">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Upgrades Dinâmicos -->
                                    <div class="mt-6 pt-4 border-t border-white/5" x-show="<?= $flag ?>" x-collapse>
                                        <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-4">Adicionais Disponíveis no Pacote</p>
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
                                                    $upgradeMarcado = !empty($dadosJson[$upgName]) || !empty($dadosJson['include_boudoir']);
                                                } elseif ($isPrewedding) {
                                                    $upgFlag = 'includePrewedding' . $suffix;
                                                    $upgName = 'include_prewedding_' . $pkgId;
                                                    $upgradeMarcado = !empty($dadosJson[$upgName]) || !empty($dadosJson['include_prewedding']);
                                                } else {
                                                    $upgFlag = "upgrades.{$pkgId}['{$upgId}']";
                                                    $upgName = "upgrades[{$pkgId}][{$upgId}]";
                                                    $upgradeMarcado = !empty($dadosJson['upgrades'][$pkgId][$upgId]);
                                                }
                                            ?>
                                            <label class="flex items-center justify-between p-4 rounded-2xl upgrade-card cursor-pointer">
                                                <div class="flex flex-col">
                                                    <span class="text-[11px] font-bold text-zinc-100"><?= $upg['nome'] ?></span>
                                                    <span class="text-[9px] text-zinc-500">Incluir neste pacote</span>
                                                </div>
                                                <div class="switch">
                                                    <input type="checkbox" name="<?= $upgName ?>" x-model="<?= $upgFlag ?>" @change="recalcularTotal()" <?= $upgradeMarcado ? 'checked' : '' ?>>
                                                    <span class="slider"></span>
                                                </div>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-8 pt-8 border-t border-white/5">
                                <div class="form-group">
                                    <label class="label-premium">Plano escolhido pelo casal / para contrato</label>
                                    <select name="pacote_dado_andamento" class="input h-12">
                                        <option value="" <?= $pacoteDadoAndamento === '' ? 'selected' : '' ?>>Ainda não definido</option>
                                        <option value="heritage" <?= $pacoteDadoAndamento === 'heritage' ? 'selected' : '' ?>>Experiência Heritage</option>
                                        <option value="cinematic" <?= $pacoteDadoAndamento === 'cinematic' ? 'selected' : '' ?>>Experiência Cinematic</option>
                                        <option value="essencial" <?= $pacoteDadoAndamento === 'essencial' ? 'selected' : '' ?>>Registro Essencial</option>
                                    </select>
                                    <p class="text-[10px] text-zinc-500 mt-2">Os botões “Mostrar na Proposta” definem quais planos o cliente vê. Este campo define qual plano será usado para gerar contrato e próximos passos.</p>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Fluxo Geral / Marketing (Adicionar itens individuais) -->
                    <div data-non-wedding-field class="space-y-6">
                        <section class="card p-8">
                            <div class="flex items-center justify-between mb-8">
                                <h3 class="section-header-premium !mb-0">
                                    <i data-lucide="layers" class="w-6 h-6 text-emerald-400"></i>
                                    Montagem do Pacote & Escopo
                                </h3>
                                <button type="button" data-add-servico-editor class="px-4 py-2 bg-emerald-500/10 text-emerald-400 rounded-lg text-xs font-bold hover:bg-emerald-500/20 transition-all flex items-center gap-2">
                                    <i data-lucide="plus" class="w-4 h-4"></i> Adicionar Cobertura / Produto
                                </button>
                            </div>

                            <div class="space-y-4">
                                <!-- Cards de Serviços Dinâmicos -->
                                <template x-for="(item, index) in servicosSelecionados" :key="index">
                                    <div class="p-6 rounded-2xl bg-white/5 border border-white/5 relative group hover:border-white/10 transition-all">
                                        <button type="button" @click="removerServico(index)" class="absolute top-4 right-4 text-zinc-600 hover:text-red-400 transition-colors p-2">
                                            <i data-lucide="x" class="w-4 h-4"></i>
                                        </button>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                                            <div class="md:col-span-5">
                                                <label class="label-premium">Serviço / Item</label>
                                                <select :name="'servicos['+index+'][id]'" class="input h-12" x-model="item.id" @change="atualizarDadosServico(index)">
                                                    <option value="">Selecione...</option>
                                                    <?php foreach ($servicos as $s): ?>
                                                    <option value="<?= $s['id'] ?>"><?= sanitizar($s['nome']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="label-premium">Cobrança</label>
                                                <select :name="'servicos['+index+'][tipo_cobranca]'" class="input h-12" x-model="item.tipo_cobranca" @change="recalcularTotal()">
                                                    <option value="recorrente">Recorrente</option>
                                                    <option value="pontual">Valor Único</option>
                                                </select>
                                            </div>
                                            <div class="md:col-span-4">
                                                <label class="label-premium">Valor Bruto</label>
                                                <div class="relative">
                                                    <span class="absolute left-4 top-3.5 text-zinc-500 text-xs font-bold">R$</span>
                                                    <input type="number" step="0.01" :name="'servicos['+index+'][valor]'" class="input h-12 pl-12 font-bold" x-model="item.valor" @input="recalcularTotal()">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="servicosSelecionados.length === 0" class="p-12 border-2 border-dashed border-white/5 rounded-2xl text-center">
                                    <i data-lucide="package-search" class="w-12 h-12 text-zinc-700 mx-auto mb-4"></i>
                                    <p class="text-zinc-500 font-medium">Nenhum serviço adicionado ao escopo ainda.</p>
                                    <button type="button" data-add-servico-editor class="mt-4 text-emerald-400 text-sm font-bold hover:underline">Clique para adicionar o pacote base</button>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="flex justify-between pt-4">
                        <button type="button" data-go-step="1" class="px-8 h-14 rounded-xl bg-white/5 text-white font-bold hover:bg-white/10 transition-all flex items-center gap-3">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            Voltar
                        </button>
                        <button type="button" data-go-step="3" class="px-8 h-14 rounded-xl bg-white text-black font-bold hover:bg-zinc-100 transition-all flex items-center gap-3">
                            Próximo Passo: Financeiro
                            <i data-lucide="arrow-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- PASSO 3: FINANCEIRO -->
                <div data-editor-step="3" class="proposal-step space-y-6">
                    
                    <!-- Escolha do Casal e Fechamento (Se for Casamento) -->
                    <div x-show="tipoProposta === 'casamento'" class="space-y-6">
                        <section class="card p-8 bg-zinc-950/20 border-emerald-500/20">
                            <h3 class="section-header-premium mb-8">
                                <i data-lucide="check-circle-2" class="w-6 h-6 text-emerald-400"></i>
                                Escolha do Casal & Fechamento
                            </h3>

                            <div class="space-y-6">
                                <div class="form-group">
                                    <label class="label-premium">Plano Fechado pelo Casal</label>
                                    <select name="pacote_dado_andamento" class="input h-12" x-model="pacoteDadoAndamento" @change="onPlanoEscolhidoChange(); recalcularTotal()">
                                        <option value="">Ainda não definido (Decisão via Proposta Web)</option>
                                        <option value="heritage">Experiência Heritage</option>
                                        <option value="cinematic">Experiência Cinematic</option>
                                        <option value="essencial">Registro Essencial</option>
                                    </select>
                                    <p class="text-xs text-zinc-500 mt-2">Escolha aqui o plano que vai entrar no contrato.</p>
                                </div>

                                <div x-show="pacoteDadoAndamento" class="space-y-4 p-5 rounded-2xl bg-white/5 border border-white/5" x-collapse>
                                    <p class="text-[10px] font-black text-zinc-400 uppercase tracking-widest mb-2">Opcionais do Fechamento</p>

                                    <!-- Inputs ocultos dinâmicos para o POST -->
                                    <input type="hidden" name="escolha_boudoir" :value="escolhaBoudoir ? '1' : '0'">
                                    <input type="hidden" name="escolha_prewedding" :value="escolhaPrewedding ? '1' : '0'">
                                    <template x-for="(valor, id) in escolhaUpgrades" :key="id">
                                        <input type="hidden" :name="'escolha_upgrades[' + id + ']'" :value="valor ? '1' : '0'">
                                    </template>

                                    <!-- Boudoir -->
                                    <label class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-all cursor-pointer">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-zinc-200">Boudoir da Noiva</span>
                                            <span class="text-[10px] text-zinc-500">+ <span x-text="formatCurrency(valorBoudoir || 500)"></span></span>
                                        </div>
                                        <div class="switch">
                                            <input type="checkbox" x-model="escolhaBoudoir" @change="recalcularEscolha(); recalcularTotal()">
                                            <span class="slider"></span>
                                        </div>
                                    </label>

                                    <!-- Prewedding -->
                                    <label class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-all cursor-pointer">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-zinc-200">Ensaio Pré-Wedding</span>
                                            <span class="text-[10px] text-zinc-500">+ <span x-text="formatCurrency(valorPrewedding || 1100)"></span></span>
                                        </div>
                                        <div class="switch">
                                            <input type="checkbox" x-model="escolhaPrewedding" @change="recalcularEscolha(); recalcularTotal()">
                                            <span class="slider"></span>
                                        </div>
                                    </label>

                                    <!-- Upgrades Dinâmicos -->
                                    <p class="text-[10px] font-black text-zinc-400 uppercase tracking-widest mt-4 mb-2">Upgrades e Adicionais</p>
                                    <div class="space-y-2">
                                        <template x-for="upg in catalogoServicos.filter(s => s.categoria === 'wedding' && s.tipo === 'servico')" :key="upg.id">
                                            <label class="flex items-center justify-between p-3 rounded-xl hover:bg-white/5 transition-all cursor-pointer">
                                                <div class="flex flex-col">
                                                    <span class="text-xs font-bold text-zinc-200" x-text="upg.nome"></span>
                                                    <span class="text-[10px] text-zinc-500">+ <span x-text="formatCurrency(upg.preco_venda)"></span></span>
                                                </div>
                                                <div class="switch">
                                                    <input type="checkbox" x-model="escolhaUpgrades[upg.id]" @change="recalcularEscolha(); recalcularTotal()">
                                                    <span class="slider"></span>
                                                </div>
                                            </label>
                                        </template>
                                    </div>

                                    <!-- Valor Final e Condições de Pagamento -->
                                    <div class="mt-6 pt-5 border-t border-white/5 space-y-4">
                                        <div class="form-group">
                                            <label class="label-premium">Valor Final do Fechamento (R$)</label>
                                            <input type="number" step="0.01" name="escolha_valor_total" class="input font-bold text-emerald-400 h-12" x-model="escolhaValorTotal" @input="recalcularTotal()">
                                            <p class="text-[9px] text-zinc-500 mt-1">Sugerido (Plano + Extras): <span x-text="formatCurrency(escolhaValorSugerido)"></span>. Edite o valor se aplicar desconto especial.</p>
                                        </div>

                                        <div class="form-group">
                                            <label class="label-premium">Condições de Pagamento Combinadas</label>
                                            <textarea name="escolha_condicoes" class="input text-xs leading-relaxed animate-fade-in" x-model="escolhaCondicoes" rows="3" placeholder="Ex: Entrada de 20% via Pix e o restante parcelado até 15 dias antes do casamento."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Gestão de Valores Comum (Se NÃO for Casamento) -->
                    <div x-show="tipoProposta !== 'casamento'" class="space-y-6">
                        <section class="card p-8">
                            <h3 class="section-header-premium mb-8">
                                <i data-lucide="banknote" class="w-6 h-6 text-emerald-400"></i>
                                Gestão de Valores e Pagamentos
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                                <div class="p-6 rounded-2xl bg-white/5 border border-white/5">
                                    <label class="label-premium">Subtotal Bruto</label>
                                    <div class="text-2xl font-black text-white" x-text="formatCurrency(valorSubtotal)"></div>
                                </div>
                                <div class="p-6 rounded-2xl bg-white/5 border border-white/5">
                                    <label class="label-premium">Total de Descontos</label>
                                    <div class="text-2xl font-black text-rose-400" x-text="'- ' + formatCurrency(valorTotalDesconto)"></div>
                                </div>
                                <div class="p-6 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                                    <label class="label-premium !text-emerald-400">Valor Final (Líquido)</label>
                                    <div class="text-2xl font-black text-emerald-400" x-text="formatCurrency(valorTotal)"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <div class="space-y-6">
                                    <h4 class="text-xs font-bold text-white uppercase tracking-widest border-b border-white/5 pb-4">Ajustes de Valor</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="form-group">
                                            <label class="label-premium">Desconto Valor</label>
                                            <input type="number" step="0.01" name="desconto_valor" class="input h-12" x-model="descontoValor" @input="recalcularTotal()">
                                        </div>
                                        <div class="form-group">
                                            <label class="label-premium">Tipo de Desconto</label>
                                            <select name="desconto_tipo" class="input h-12" x-model="descontoTipo" @change="recalcularTotal()">
                                                <option value="porcentagem">% Porcentagem</option>
                                                <option value="fixo">R$ Valor Fixo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <h4 class="text-xs font-bold text-white uppercase tracking-widest border-b border-white/5 pb-4">Condições de Pagamento</h4>
                                    <div class="form-group">
                                        <label class="label-premium">Parcelamento e Reserva</label>
                                        <textarea name="condicoes_reserva" class="input min-h-[100px] text-xs leading-relaxed" x-model="condicoesReserva"></textarea>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="flex justify-between pt-4">
                        <button type="button" data-go-step="2" class="px-8 h-14 rounded-xl bg-white/5 text-white font-bold hover:bg-white/10 transition-all flex items-center gap-3">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            Voltar
                        </button>
                        <button type="button" data-go-step="4" class="px-8 h-14 rounded-xl bg-white text-black font-bold hover:bg-zinc-100 transition-all flex items-center gap-3">
                            Próximo Passo: Contrato
                            <i data-lucide="arrow-right" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- PASSO 4: CONTRATO E PRAZOS -->
                <div data-editor-step="4" class="proposal-step space-y-6">
                    <section class="card p-8">
                        <h3 class="section-header-premium">
                            <i data-lucide="file-check" class="w-6 h-6 text-blue-400"></i>
                            Contrato e Prazos de Entrega
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div class="form-group" data-non-wedding-field>
                                    <label class="label-premium">Modelo de Contrato Sugerido</label>
                                    <select name="pacote_dado_andamento" class="input h-12">
                                        <option value="">Selecione o template...</option>
                                        <option value="corporativo">Template Corporativo</option>
                                        <option value="marketing">Template Marketing</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="label-premium">Status Inicial</label>
                                    <select name="status" class="input h-12" x-model="statusProposta">
                                        <option value="rascunho">Rascunho</option>
                                        <option value="pendente">Pendente (Enviar para cliente)</option>
                                        <option value="aceita">Aceita (Gerar contrato agora)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-6 p-6 rounded-2xl bg-white/5 border border-white/5">
                                <h4 class="text-[10px] font-black text-white uppercase tracking-widest mb-4">Prazos de Entrega (Estimados)</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="label-premium">Prévias</label>
                                        <input type="text" name="prazo_previas" class="input h-10" x-model="prazoPrevias" placeholder="Ex: 48h">
                                    </div>
                                    <div>
                                        <label class="label-premium">Material Final</label>
                                        <input type="text" name="prazo_final" class="input h-10" x-model="prazoFinal" placeholder="Ex: 60 dias">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="flex justify-between pt-4">
                        <button type="button" data-go-step="3" class="px-8 h-14 rounded-xl bg-white/5 text-white font-bold hover:bg-white/10 transition-all flex items-center gap-3">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            Voltar
                        </button>
                        <div class="flex gap-4">
                            <button type="submit" id="btnSalvar" class="px-8 h-14 rounded-xl bg-white/10 text-white font-bold hover:bg-white/20 transition-all flex items-center gap-3">
                                <i data-lucide="save" class="w-5 h-5"></i>
                                Salvar Rascunho
                            </button>
                            <button type="submit" id="btnSalvarGerarContrato" data-action="gerar-contrato" class="px-8 h-14 rounded-xl bg-emerald-500 text-white font-bold hover:bg-emerald-400 transition-all flex items-center gap-3 shadow-lg shadow-emerald-500/20">
                                <i data-lucide="send" class="w-5 h-5"></i>
                                Finalizar e Gerar Contrato
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna Lateral: RESUMO STICKY -->
            <div class="space-y-6">
                <div class="sticky top-6 space-y-6">
                    <section class="card p-6 bg-zinc-900 text-white shadow-2xl border-0">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center">
                                <i data-lucide="clipboard-check" class="w-5 h-5 text-emerald-400"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold">Resumo da Edição</h3>
                                <p class="text-[10px] text-zinc-500 uppercase tracking-widest font-bold">Checklist de Prontidão</p>
                            </div>
                        </div>

                        <div class="space-y-4 mb-8">
                            <template x-for="item in checklistContrato()" :key="item.label">
                                <div class="flex items-center justify-between p-3 rounded-xl transition-all" :class="item.ok ? 'bg-emerald-500/10 border border-emerald-500/20' : 'bg-white/5 border border-white/5 opacity-60'">
                                    <div class="flex items-center gap-3">
                                        <i :data-lucide="item.ok ? 'check-circle-2' : 'circle'" class="w-4 h-4" :class="item.ok ? 'text-emerald-400' : 'text-zinc-600'"></i>
                                        <span class="text-xs font-bold" :class="item.ok ? 'text-white' : 'text-zinc-500'" x-text="item.label"></span>
                                    </div>
                                    <i data-lucide="chevron-right" class="w-3 h-3 text-zinc-700" x-show="!item.ok"></i>
                                </div>
                            </template>
                        </div>

                        <div class="border-t border-white/5 pt-6 space-y-4">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-zinc-500 font-bold uppercase">Valor Final</span>
                                <span class="text-xl font-black text-emerald-400" x-text="formatCurrency(valorTotal)"></span>
                            </div>
                        </div>

                        <div class="mt-8 space-y-3">
                            <button type="submit" class="w-full h-12 rounded-xl bg-white text-black font-bold hover:bg-zinc-100 transition-all flex items-center justify-center gap-2 group !text-black">
                                <i data-lucide="save" class="w-5 h-5 text-zinc-400 group-hover:text-black transition-colors !text-zinc-900"></i>
                                Salvar Rascunho
                            </button>
                            <a href="<?= raizUrl('/p/' . $proposta['slug']) ?>" target="_blank" class="w-full h-12 rounded-xl bg-zinc-800 text-white font-bold hover:bg-zinc-700 transition-all flex items-center justify-center gap-2">
                                <i data-lucide="eye" class="w-5 h-5 text-zinc-500"></i>
                                Pré-visualizar PDF
                            </a>
                        </div>
                    </section>

                    <div id="statusSalvar" class="hidden animate-fade-in">
                        <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-2xl text-center text-sm font-bold flex items-center justify-center gap-3">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            <span id="statusMessage">Alterações salvas!</span>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
window.dadosIniciaisPropostaEditor = <?= jsonParaJs([
    'responsavel' => $responsavelForm,
    'contato_tipo' => $contatoTipoForm,
    'tipo' => $tipoPropostaInicial,
    'nome_noivo' => $nomeNoivoForm,
    'nome_noiva' => $nomeNoivaForm,
    'data_casamento' => $dataCasamentoDisplay,
    'whatsapp' => $whatsappForm,
    'data_limite_desconto' => $dataLimiteDescontoDisplay,
    'pacote_dado_andamento' => $pacoteDadoAndamento,
]) ?>;

window.proposta = function() {
    return {
        passoAtual: 1,
        steps: [
            { id: 1, label: 'Cliente & Evento' },
            { id: 2, label: 'Escopo' },
            { id: 3, label: 'Financeiro' },
            { id: 4, label: 'Contrato' }
        ],
        catalogoServicos: <?= $servicosJson ?>,
        servicosSelecionados: [],
        valorSubtotal: 0,
        valorTotalDesconto: 0,
        descontoValor: 0,
        descontoTipo: 'porcentagem',
        valorTotal: 0,
        tipoProposta: '<?= $tipoPropostaInicial ?>',
        mesesContrato: 12,
        statusProposta: <?= jsonParaJs($proposta['status']) ?>,
        contatoTipo: <?= jsonParaJs($contatoTipoForm) ?>,
        responsavel: <?= jsonParaJs($responsavelForm) ?>,
        nomeNoivo: <?= jsonParaJs($nomeNoivoForm) ?>,
        nomeNoiva: <?= jsonParaJs($nomeNoivaForm) ?>,
        dataCasamento: <?= jsonParaJs($dataCasamentoDisplay) ?>,
        whatsapp: <?= jsonParaJs($whatsappForm) ?>,
        condicoesReserva: '',
        pacoteDadoAndamento: <?= jsonParaJs($pacoteDadoAndamento) ?>,
        prazoPrevias: '',
        prazoFinal: '',
        dataLimiteDesconto: <?= jsonParaJs($dataLimiteDescontoDisplay) ?>,
        condicaoEspecial: '',

        // Variáveis de Casamento
        showHeritage: true,
        showCinematic: true,
        showEssencial: true,
        valorHeritage: '',
        baseHeritage: '',
        itensHeritage: <?= jsonParaJs($beneficiosH) ?>,
        valorCinematic: '',
        baseCinematic: '',
        itensCinematic: <?= jsonParaJs($beneficiosC) ?>,
        valorEssencial: '',
        baseEssencial: '',
        itensEssencial: <?= jsonParaJs($beneficiosE) ?>,
        valorBoudoir: '',
        valorPrewedding: '',
        itensPersonalizados: { heritage: [], cinematic: [], essencial: [] },
        includeBoudoirHeritage: false,
        includePreweddingHeritage: false,
        includeBoudoirCinematic: false,
        includePreweddingCinematic: false,
        includeBoudoirEssencial: false,
        includePreweddingEssencial: false,
        upgrades: { heritage: {}, cinematic: {}, essencial: {} },
        escolhaBoudoir: false,
        escolhaPrewedding: false,
        escolhaUpgrades: {},
        escolhaValorTotal: '',
        escolhaCondicoes: '',
        escolhaValorSugerido: 0,

        initEdit() {
            const dados = <?= jsonParaJs($dadosJson) ?>;
            const dataParaBr = (valor) => {
                const texto = String(valor || '').trim();
                const iso = texto.match(/^(\d{4})-(\d{2})-(\d{2})$/);
                return iso ? `${iso[3]}/${iso[2]}/${iso[1]}` : texto;
            };
            this.contatoTipo = dados.contato_tipo || 'noiva';
            this.responsavel = dados.responsavel_manual || dados.responsavel || '';
            this.nomeNoivo = dados.nome_noivo || (this.tipoProposta !== 'casamento' ? (dados.responsavel || '') : '');
            this.nomeNoiva = dados.nome_noiva || '';
            this.dataCasamento = dataParaBr(dados.data_casamento || dados.data_evento || '');
            this.whatsapp = dados.whatsapp || '';
            this.condicoesReserva = dados.condicoes_reserva || 'Reserva mediante sinal.';
            this.prazoPrevias = dados.prazo_previas || '48h';
            this.prazoFinal = dados.prazo_final || '60 dias';
            this.dataLimiteDesconto = dataParaBr(dados.data_limite_desconto || '');
            this.condicaoEspecial = dados.condicao_especial || '';
            this.descontoValor = dados.desconto_valor || 0;
            this.descontoTipo = dados.desconto_tipo || 'porcentagem';

            // Inicializações de Casamento
            this.showHeritage = (dados.show_heritage !== undefined) ? !!dados.show_heritage : true;
            this.showCinematic = (dados.show_cinematic !== undefined) ? !!dados.show_cinematic : true;
            this.showEssencial = (dados.show_essencial !== undefined) ? !!dados.show_essencial : true;
            this.valorHeritage = dados.valor_heritage || '';
            this.baseHeritage = this.calcularBasePacote('heritage', this.valorHeritage, dados.itens_personalizados);
            this.itensHeritage = dados.itens_heritage || <?= jsonParaJs($beneficiosH) ?>;
            this.valorCinematic = dados.valor_cinematic || '';
            this.baseCinematic = this.calcularBasePacote('cinematic', this.valorCinematic, dados.itens_personalizados);
            this.itensCinematic = dados.itens_cinematic || <?= jsonParaJs($beneficiosC) ?>;
            this.valorEssencial = dados.valor_essencial || '';
            this.baseEssencial = this.calcularBasePacote('essencial', dados.valor_essencial || this.valorEssencial, dados.itens_personalizados);
            this.itensEssencial = dados.itens_essencial || <?= jsonParaJs($beneficiosE) ?>;
            this.itensPersonalizados = this.normalizarItensPersonalizados(dados.itens_personalizados || {});

            this.includeBoudoirHeritage = !!(dados.include_boudoir_heritage || dados.include_boudoir);
            this.includePreweddingHeritage = !!(dados.include_prewedding_heritage || dados.include_prewedding);
            this.includeBoudoirCinematic = !!(dados.include_boudoir_cinematic || dados.include_boudoir);
            this.includePreweddingCinematic = !!(dados.include_prewedding_cinematic || dados.include_prewedding);
            this.includeBoudoirEssencial = !!(dados.include_boudoir_essencial || dados.include_boudoir);
            this.includePreweddingEssencial = !!(dados.include_prewedding_essencial || dados.include_prewedding);

            this.upgrades = dados.upgrades || { heritage: {}, cinematic: {}, essencial: {} };
            this.valorBoudoir = dados.valor_boudoir || '';
            this.valorPrewedding = dados.valor_prewedding || '';

            const clienteEscolha = dados.cliente_escolha || {};
            this.escolhaBoudoir = false;
            this.escolhaPrewedding = false;
            this.escolhaUpgrades = {};

            if (clienteEscolha.plano_id) {
                const extras = clienteEscolha.extras || [];
                this.escolhaBoudoir = extras.includes('boudoir_static');
                this.escolhaPrewedding = extras.includes('prewedding_static');

                extras.forEach(ext => {
                    if (ext !== 'boudoir_static' && ext !== 'prewedding_static') {
                        this.escolhaUpgrades[ext] = true;
                    }
                });
            }

            this.catalogoServicos.forEach(s => {
                if (s.categoria === 'wedding' && s.tipo === 'servico') {
                    if (this.escolhaUpgrades[s.id] === undefined) {
                        this.escolhaUpgrades[s.id] = false;
                    }
                }
            });

            this.escolhaValorTotal = clienteEscolha.valor_total || '';
            this.escolhaCondicoes = clienteEscolha.condicoes || '';
            this.escolhaValorSugerido = 0;

            this.servicosSelecionados = (dados.servicos || []).map(s => ({
                id: String(s.id || ''),
                valor: parseFloat(s.valor || 0),
                tipo_cobranca: s.tipo_cobranca || 'recorrente'
            }));

            this.$nextTick(() => {
                this.recalcularTotal();
                if (this.pacoteDadoAndamento) {
                    this.recalcularEscolha();
                }
                if (window.lucide) lucide.createIcons();
            });
        },

        async avancarPasso(proximo) {
            this.passoAtual = proximo;
            await this.salvarSilenciosamente();
        },

        async salvarSilenciosamente() {
            const form = document.getElementById('formAtualizarProposta');
            if (!form) return;

            const statusDiv = document.getElementById('statusSalvar');
            const statusMessage = document.getElementById('statusMessage');
            if (statusDiv && statusMessage) {
                statusMessage.textContent = 'Salvando progresso...';
                statusDiv.classList.remove('hidden');
            }

            const formData = new FormData(form);
            try {
                const response = await fetch('<?= raizUrl('/api/propostas/atualizar.php') ?>', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    if (statusMessage) statusMessage.textContent = 'Progresso salvo!';
                    setTimeout(() => {
                        if (statusDiv) statusDiv.classList.add('hidden');
                    }, 2000);
                }
            } catch (error) {
                console.error('Erro ao salvar silenciosamente:', error);
                if (statusMessage) statusMessage.textContent = 'Erro ao salvar!';
            }
        },

        adicionarServico() {
            this.servicosSelecionados.push({ id: '', valor: 0, tipo_cobranca: 'recorrente' });
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
                item.valor = parseFloat(servico.preco_venda || 0);
            }
            this.recalcularTotal();
        },

        recalcularTotal() {
            if (this.tipoProposta === 'casamento') {
                if (this.pacoteDadoAndamento) {
                    this.recalcularEscolha();
                    this.valorTotal = parseFloat(this.escolhaValorTotal || this.escolhaValorSugerido || 0);
                } else {
                    // Valor do primeiro plano ativo/visível para termos algo no valor
                    let total = 0;
                    if (this.showHeritage) total = parseFloat(this.valorHeritage) || parseFloat(this.baseHeritage) || 7900;
                    else if (this.showCinematic) total = parseFloat(this.valorCinematic) || parseFloat(this.baseCinematic) || 4500;
                    else if (this.showEssencial) total = parseFloat(this.valorEssencial) || parseFloat(this.baseEssencial) || 2800;
                    this.valorTotal = total;
                }
            } else {
                this.valorSubtotal = this.servicosSelecionados.reduce((acc, s) => acc + (parseFloat(s.valor) || 0), 0);
                
                if (this.descontoTipo === 'porcentagem') {
                    this.valorTotalDesconto = this.valorSubtotal * (parseFloat(this.descontoValor || 0) / 100);
                } else {
                    this.valorTotalDesconto = parseFloat(this.descontoValor || 0);
                }
                
                this.valorTotal = Math.max(0, this.valorSubtotal - this.valorTotalDesconto);
            }
        },

        formatCurrency(valor) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(parseFloat(valor || 0));
        },

        normalizarItensPersonalizados(itens) {
            const normalizarLista = (lista) => (Array.isArray(lista) ? lista : []).map(item => ({
                nome: item.nome || '',
                descricao: item.descricao || '',
                valor: item.valor || 0,
                incluido: String(item.incluido ? '1') !== '0'
            }));
            return {
                heritage: normalizarLista(itens.heritage),
                cinematic: normalizarLista(itens.cinematic),
                essencial: normalizarLista(itens.essencial)
            };
        },

        calcularBasePacote(pacote, total, itens) {
            const lista = Array.isArray(itens?.[pacote]) ? itens[pacote] : [];
            const extras = lista.reduce((acc, item) => {
                return acc + (String(item.incluido ? '1') !== '0' ? (parseFloat(String(item.valor || 0).replace(',', '.')) || 0) : 0);
            }, 0);
            return Math.max(0, (parseFloat(String(total || 0).replace(',', '.')) || 0) - extras);
        },

        adicionarItemPersonalizado(pacote) {
            if (!this.itensPersonalizados[pacote]) {
                this.itensPersonalizados[pacote] = [];
            }
            this.itensPersonalizados[pacote].push({ nome: '', descricao: '', valor: 0, incluido: true });
            this.recalcularPacote(pacote);
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        removerItemPersonalizado(pacote, index) {
            this.itensPersonalizados[pacote].splice(index, 1);
            this.recalcularPacote(pacote);
        },

        recalcularPacote(pacote) {
            const mapa = {
                heritage: ['baseHeritage', 'valorHeritage'],
                cinematic: ['baseCinematic', 'valorCinematic'],
                essencial: ['baseEssencial', 'valorEssencial']
            };
            const [baseKey, totalKey] = mapa[pacote];
            const base = parseFloat(String(this[baseKey] || 0).replace(',', '.')) || 0;
            const extras = (this.itensPersonalizados[pacote] || []).reduce((acc, item) => {
                return acc + (item.incluido ? (parseFloat(String(item.valor || 0).replace(',', '.')) || 0) : 0);
            }, 0);
            this[totalKey] = Math.round((base + extras) * 100) / 100;
        },

        onPlanoEscolhidoChange() {
            if (this.pacoteDadoAndamento) {
                if (!this.escolhaCondicoes) {
                    if (this.pacoteDadoAndamento === 'essencial') {
                        this.escolhaCondicoes = 'Entrada de 25% + Saldo parcelado';
                    } else {
                        this.escolhaCondicoes = 'Entrada de 20% + Saldo parcelado';
                    }
                }
            }
            this.recalcularEscolha();
        },

        recalcularEscolha() {
            if (!this.pacoteDadoAndamento) {
                this.escolhaValorSugerido = 0;
                return;
            }

            let valorBase = 0;
            if (this.pacoteDadoAndamento === 'heritage') {
                valorBase = parseFloat(this.valorHeritage) || parseFloat(this.baseHeritage) || 7900;
            } else if (this.pacoteDadoAndamento === 'cinematic') {
                valorBase = parseFloat(this.valorCinematic) || parseFloat(this.baseCinematic) || 4500;
            } else if (this.pacoteDadoAndamento === 'essencial') {
                valorBase = parseFloat(this.valorEssencial) || parseFloat(this.baseEssencial) || 2800;
            }

            let total = valorBase;

            if (this.escolhaBoudoir) {
                total += parseFloat(this.valorBoudoir) || 500;
            }

            if (this.escolhaPrewedding) {
                total += parseFloat(this.valorPrewedding) || 1100;
            }

            Object.keys(this.escolhaUpgrades).forEach(id => {
                if (this.escolhaUpgrades[id]) {
                    const serv = this.catalogoServicos.find(s => String(s.id) === String(id));
                    if (serv) {
                        total += parseFloat(serv.preco_venda) || 0;
                    }
                }
            });

            this.escolhaValorSugerido = total;

            if (!this.escolhaValorTotal) {
                this.escolhaValorTotal = total;
            }
        },

        nomePlanoEscolhido() {
            const nomes = {
                heritage: 'Heritage',
                cinematic: 'Cinematic',
                essencial: 'Essencial'
            };
            return nomes[this.pacoteDadoAndamento] || 'Não definido';
        },

        checklistContrato() {
            if (this.tipoProposta === 'casamento') {
                const contratanteOk = !!this.responsavel;
                return [
                    { label: 'Contratante definido', ok: contratanteOk },
                    { label: 'Dados do Evento', ok: !!(this.nomeNoivo && this.nomeNoiva && this.dataCasamento) },
                    { label: 'Plano Habilitado', ok: !!(this.showHeritage || this.showCinematic || this.showEssencial) },
                    { label: 'Valor Conferido', ok: this.valorTotal > 0 },
                    { label: 'Template Selecionado', ok: !!this.pacoteDadoAndamento }
                ];
            } else {
                return [
                    { label: 'Dados do Evento', ok: !!(this.nomeNoivo || this.responsavel) },
                    { label: 'Escopo Definido', ok: this.servicosSelecionados.length > 0 },
                    { label: 'Valor Conferido', ok: this.valorTotal > 0 }
                ];
            }
        }
    };
};

function registrarPropostaEditor() {
    if (!window.Alpine || window.__propostaEditorRegistrado) return;
    window.__propostaEditorRegistrado = true;

    Alpine.data('proposta', window.proposta);
}

document.addEventListener('alpine:init', registrarPropostaEditor);
if (window.Alpine) {
    registrarPropostaEditor();
}

document.addEventListener('DOMContentLoaded', function() {
    function preencherCamposIniciaisDoBanco() {
        const dados = { ...(window.dadosIniciaisPropostaEditor || {}) };
        document.querySelectorAll('[data-db-field]').forEach(el => {
            if (el.dataset.dbField && el.value) {
                dados[el.dataset.dbField] = el.value;
            }
        });
        const setValue = (selector, value) => {
            if (value === undefined || value === null || value === '') return;
            document.querySelectorAll(selector).forEach(el => {
                el.value = value;
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
                if (el._flatpickr && typeof el._flatpickr.setDate === 'function') {
                    el._flatpickr.setDate(value, false, 'd/m/Y');
                }
            });
        };

        setValue('[name="responsavel"]', dados.responsavel);
        setValue('[name="contato_tipo"]', dados.contato_tipo);
        setValue('[name="tipo"]', dados.tipo);
        setValue('[name="nome_noivo"]', dados.nome_noivo);
        setValue('[name="nome_noiva"]', dados.nome_noiva);
        setValue('[name="data_casamento"]', dados.data_casamento);
        setValue('[name="whatsapp"]', dados.whatsapp);
        setValue('[name="data_limite_desconto"]', dados.data_limite_desconto);
        setValue('[name="pacote_dado_andamento"]', dados.pacote_dado_andamento);
    }

    preencherCamposIniciaisDoBanco();
    setTimeout(preencherCamposIniciaisDoBanco, 100);
    setTimeout(preencherCamposIniciaisDoBanco, 500);

    window.adicionarServico = function() {
        const root = document.getElementById('main-content');
        const alpineData = root && window.Alpine ? window.Alpine.$data(root) : null;
        if (alpineData && typeof alpineData.adicionarServico === 'function') {
            alpineData.adicionarServico();
        }
    };

    setTimeout(function() {
        registrarPropostaEditor();
        const root = document.getElementById('main-content');
        if (root && window.Alpine && !root._x_dataStack) {
            window.Alpine.initTree(root);
        }
    }, 0);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function ativarEtapaEditor(step) {
        const passo = String(step || 1);

        document.querySelectorAll('[data-editor-step]').forEach(el => {
            el.classList.toggle('is-active', el.dataset.editorStep === passo);
        });

        document.querySelectorAll('[data-step-indicator]').forEach(el => {
            const atual = parseInt(el.dataset.stepIndicator || '0', 10);
            const alvo = parseInt(passo, 10);
            el.classList.toggle('active', atual === alvo);
            el.classList.toggle('completed', atual < alvo);
        });

        const alpineRoot = document.getElementById('main-content');
        const alpineData = alpineRoot && window.Alpine ? window.Alpine.$data(alpineRoot) : null;
        if (alpineData && typeof alpineData === 'object') {
            alpineData.passoAtual = parseInt(passo, 10);
            if (typeof alpineData.salvarSilenciosamente === 'function') {
                alpineData.salvarSilenciosamente();
            }
        }

        if (window.lucide) lucide.createIcons();
    }

    document.querySelectorAll('[data-go-step]').forEach(btn => {
        btn.addEventListener('click', function() {
            ativarEtapaEditor(this.dataset.goStep);
        });
    });

    document.querySelectorAll('[data-add-servico-editor]').forEach(btn => {
        btn.addEventListener('click', function() {
            const root = document.getElementById('main-content');
            const alpineData = root && window.Alpine ? window.Alpine.$data(root) : null;
            if (alpineData && typeof alpineData.adicionarServico === 'function') {
                alpineData.adicionarServico();
            }
        });
    });

    ativarEtapaEditor(1);

    const form = document.getElementById('formAtualizarProposta');
    const btnSalvar = document.getElementById('btnSalvar');
    const btnSalvarGerarContrato = document.getElementById('btnSalvarGerarContrato');
    const statusDiv = document.getElementById('statusSalvar');
    const selectCliente = document.getElementById('cliente_id');
    const selectOportunidade = document.getElementById('oportunidade_id');
    const selectTipoProposta = document.getElementById('tipoProposta');
    const selectContatoTipo = document.getElementById('contatoTipoSelect');
    const responsavelInput = document.getElementById('responsavelInput');
    const nomeNoivoInput = document.getElementById('nomeNoivoInput');
    const nomeNoivaInput = document.getElementById('nomeNoivaInput');

    function inicializarCalendariosPtBr(tentativa = 0) {
        if (!window.flatpickr) {
            if (tentativa < 10) {
                setTimeout(() => inicializarCalendariosPtBr(tentativa + 1), 150);
            }
            return;
        }

        const localePt = window.flatpickr.l10ns?.pt || 'pt';
        const formatarDataBr = value => {
            const texto = String(value || '').trim();
            const iso = texto.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (iso) return `${iso[3]}/${iso[2]}/${iso[1]}`;
            return texto;
        };

        document.querySelectorAll('[data-ptbr-calendar]').forEach(input => {
            if (input._flatpickr) return;
            input.value = formatarDataBr(input.value);

            window.flatpickr(input, {
                locale: localePt,
                dateFormat: 'd/m/Y',
                allowInput: true,
                disableMobile: true,
                defaultDate: input.value || null,
                onChange: function(selectedDates, dateStr, instance) {
                    input.value = dateStr;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        });
    }

    function atualizarTipoEventoEditor() {
        const isWedding = (selectTipoProposta?.value || '') === 'casamento';
        form.classList.toggle('is-wedding-editor', isWedding);
        form.classList.toggle('is-non-wedding-editor', !isWedding);

        form.querySelectorAll('[data-wedding-field] input, [data-wedding-field] select, [data-wedding-field] textarea').forEach(el => {
            el.disabled = !isWedding;
        });
        form.querySelectorAll('[data-non-wedding-field] input, [data-non-wedding-field] select, [data-non-wedding-field] textarea').forEach(el => {
            el.disabled = isWedding;
        });
    }

    function sincronizarContratanteCasamento() {
        if ((selectTipoProposta?.value || '') !== 'casamento') return;
        const nome = (responsavelInput?.value || '').trim();
        const tipo = selectContatoTipo?.value || 'noiva';
        if (nome === '') return;

        if (tipo === 'noiva' && nomeNoivaInput && !nomeNoivaInput.dataset.editadoManualmente) {
            nomeNoivaInput.dataset.sincronizando = '1';
            nomeNoivaInput.value = nome;
            nomeNoivaInput.dispatchEvent(new Event('input', { bubbles: true }));
            delete nomeNoivaInput.dataset.sincronizando;
        }
        if (tipo === 'noivo' && nomeNoivoInput && !nomeNoivoInput.dataset.editadoManualmente) {
            nomeNoivoInput.dataset.sincronizando = '1';
            nomeNoivoInput.value = nome;
            nomeNoivoInput.dispatchEvent(new Event('input', { bubbles: true }));
            delete nomeNoivoInput.dataset.sincronizando;
        }
    }

    [nomeNoivoInput, nomeNoivaInput].forEach(input => {
        if (!input) return;
        input.addEventListener('input', function() {
            if (this.dataset.sincronizando === '1') return;
            this.dataset.editadoManualmente = '1';
        });
    });
    selectTipoProposta?.addEventListener('change', atualizarTipoEventoEditor);
    selectContatoTipo?.addEventListener('change', sincronizarContratanteCasamento);
    responsavelInput?.addEventListener('input', sincronizarContratanteCasamento);
    inicializarCalendariosPtBr();
    atualizarTipoEventoEditor();
    sincronizarContratanteCasamento();

    function validarContratoCasamento(formData) {
        if ((formData.get('tipo') || '') !== 'casamento') return [];

        const valor = parseFloat(String(formData.get('escolha_valor_total') || '0').replace(',', '.')) || 0;
        const erros = [];

        const contatoTipo = formData.get('contato_tipo') || 'noiva';
        if (!String(formData.get('responsavel') || '').trim()) erros.push('preencha o nome do cliente/contratante');
        if (!formData.get('nome_noivo') || !formData.get('nome_noiva')) erros.push('preencha o nome dos noivos');
        if (!formData.get('data_casamento')) erros.push('preencha a data do casamento');
        if (!formData.get('whatsapp')) erros.push('preencha o WhatsApp do cliente');
        if (!formData.get('pacote_dado_andamento')) erros.push('escolha o plano fechado pelo casal');
        if (valor <= 0) erros.push('confira o valor final do fechamento');
        if (!String(formData.get('escolha_condicoes') || '').trim()) erros.push('preencha as condicoes de pagamento');

        return erros;
    }

    function setSavingState(isSaving, action) {
        if (btnSalvar) {
            btnSalvar.disabled = isSaving;
            btnSalvar.innerHTML = isSaving && action !== 'gerar-contrato'
                ? '<i class="w-4 h-4 animate-spin"></i> Salvando...'
                : '<i data-lucide="save" class="w-5 h-5 text-zinc-500"></i> Salvar Alterações';
        }
        if (btnSalvarGerarContrato) {
            btnSalvarGerarContrato.disabled = isSaving;
            btnSalvarGerarContrato.innerHTML = isSaving && action === 'gerar-contrato'
                ? '<i class="w-4 h-4 animate-spin"></i> Salvando...'
                : '<i data-lucide="scroll-text" class="w-5 h-5"></i> Salvar e gerar contrato';
        }
        if (window.lucide) lucide.createIcons();
    }

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
        const action = e.submitter?.dataset?.action || 'salvar';
        const formData = new FormData(form);

        if (action === 'gerar-contrato') {
            const erros = validarContratoCasamento(formData);
            if (erros.length > 0) {
                alert('Antes de gerar o contrato, revise:\n\n- ' + erros.join('\n- '));
                return;
            }
        }

        setSavingState(true, action);
        try {
            const response = await fetch('<?= raizUrl('/api/propostas/atualizar.php') ?>', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                const statusMessage = document.getElementById('statusMessage');
                const statusRecomendacao = document.getElementById('statusRecomendacao');
                if (statusMessage) statusMessage.textContent = 'Alterações salvas com sucesso!';
                if (statusRecomendacao) {
                    if (result.recomendacao) {
                        statusRecomendacao.textContent = 'Próximo passo sugerido: ' + result.recomendacao;
                        statusRecomendacao.classList.remove('hidden');
                    } else {
                        statusRecomendacao.textContent = '';
                        statusRecomendacao.classList.add('hidden');
                    }
                }
                if (statusDiv) {
                    statusDiv.classList.remove('hidden');
                    setTimeout(() => statusDiv.classList.add('hidden'), 6000);
                }

                if (action === 'gerar-contrato') {
                    const urlContrato = '<?= raizUrl('/gerenciamento/contrato_gerar.php?proposta_id=') ?>' + encodeURIComponent(result.id);
                    window.location.href = urlContrato;
                }
            } else {
                alert('Erro: ' + (result.erro || 'Falha ao salvar.'));
            }
        } catch (error) {
            alert('Erro na comunicação com o servidor.');
        } finally {
            setSavingState(false, action);
        }
    });
});
</script>

<?php if (!$isModal) include __DIR__ . '/../includes/layout/footer.php'; ?>
