<?php
/**
 * Visualizador Público de Propostas
 * wedistinto.com/p/[slug]
 */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

$slug = $_GET['slug'] ?? '';

if (!$slug) {
    die("Proposta não encontrada.");
}

$db = Database::get();
$stmt = $db->prepare("SELECT * FROM propostas WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$proposta = $stmt->fetch();

if (!$proposta) {
    die("Proposta não encontrada ou expirada.");
}

$dados = json_decode($proposta['dados_json'], true);
$tipo = $proposta['tipo'];
$cliente = $proposta['cliente_nome'];

// Metadados para a Moldura
$dataCriacao = new DateTime($proposta['created_at']);
$mesesPt = [
    '1' => 'JANEIRO', '2' => 'FEVEREIRO', '3' => 'MARÇO',
    '4' => 'ABRIL', '5' => 'MAIO', '6' => 'JUNHO',
    '7' => 'JULHO', '8' => 'AGOSTO', '9' => 'SETEMBRO',
    '10' => 'OUTUBRO', '11' => 'NOVEMBRO', '12' => 'DEZEMBRO'
];
$mesNome = $mesesPt[$dataCriacao->format('n')] ?? 'JUNHO';
$ano = $dataCriacao->format('Y');
$categoriaProjeto = $dados['categoria_projeto'] ?? 'PROJETO DE ESTRATÉGIA';

// Definir arquivo de template
$templateFile = __DIR__ . "/includes/propostas/template-{$tipo}.php";
if (!file_exists($templateFile)) {
    die("Template de proposta não configurado.");
}

// Configurações da Empresa para o Rodapé/Capa
$configEmpresa = $db->query("SELECT * FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $proposta['titulo'] ?> — <?= $cliente ?></title>
    
    <link rel="stylesheet" href="<?= raizUrl('/assets/css/propostas.css') ?>">
    <link rel="stylesheet" href="<?= raizUrl('/assets/css/propostas-mobile.css') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= raizUrl('/favicon.svg') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= raizUrl('/favicon_io/apple-touch-icon.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= raizUrl('/favicon_io/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= raizUrl('/favicon_io/favicon-16x16.png') ?>">
    <link rel="manifest" href="<?= raizUrl('/favicon_io/site.webmanifest') ?>">
    
    <?php if ($tipo === 'casamento'): ?>
    <style>
        @media (max-width: 768px) {
            #btn-approve {
                display: flex !important;
                position: fixed !important;
                bottom: 30px !important;
                left: 50% !important;
                transform: translateX(-50%) translateY(20px) !important;
                width: auto !important;
                min-width: 250px !important;
                justify-content: center !important;
                z-index: 10001 !important;
                opacity: 0 !important;
                pointer-events: none !important;
                transition: opacity 0.8s cubic-bezier(0.4, 0, 0.2, 1), transform 0.8s cubic-bezier(0.4, 0, 0.2, 1) !important;
                
                /* Estilo Pílula */
                background: #a8a8a8 !important;
                color: #fff !important;
                border: none !important;
                padding: 14px 30px !important;
                border-radius: 50px !important;
                font-family: 'Montserrat', sans-serif !important;
                font-size: 0.8rem !important;
                font-weight: 700 !important;
                letter-spacing: 0.1em !important;
                text-transform: uppercase !important;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2) !important;
                white-space: nowrap !important;
            }
            #btn-approve.visible {
                opacity: 1 !important;
                pointer-events: auto !important;
                transform: translateX(-50%) translateY(0) !important;
            }
            .mobile-action-bar {
                display: none !important;
            }
        }
    </style>
    <?php endif; ?>

    <!-- Bibliotecas Externas -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body class="type-<?= $tipo ?>">

    <!-- Header Mobile (visível apenas em telas ≤ 768px via CSS) -->
    <header class="mobile-header no-print" style="display: none;">
        <span class="mobile-header-logo">DISTINTO</span>
        <span class="mobile-header-title"><?= htmlspecialchars($proposta['titulo']) ?> — <?= htmlspecialchars($cliente) ?></span>
    </header>

    <!-- Moldura Global (HUD) -->
    <div class="proposal-hud-lines"></div>
    <div class="proposal-frame">
        <div class="frame-item">
            <div class="frame-top"><?= $categoriaProjeto ?></div>
            <div class="frame-bottom logo-container" id="dynamic-logo">
                <img src="<?= raizUrl('/assets/distinto_logo.svg') ?>" alt="Distinto" id="logo-svg">
                <span class="logo-text">PONCEM STUDIO | DISTINTO</span>
            </div>
        </div>
        <div class="frame-item">
            <div class="frame-top"><?= $mesNome ?></div>
            <div class="frame-bottom">
                <?php 
                    $textoResponsavel = '';
                    if (!empty($dados['responsavel'])) {
                        // Regex robusto para separar por ' e ', ' E ', vírgula ou ponto e vírgula
                        $partesBrutas = preg_split('/(?:\s+[eE]\s+|[,;]\s*)/', $dados['responsavel']);
                        $nomesFinais = [];
                        foreach ($partesBrutas as $p) {
                            $p = trim($p);
                            if (!$p) continue;
                            // Pega apenas o primeiro nome
                            $palavras = explode(' ', $p);
                            $nomesFinais[] = $palavras[0];
                        }
                        
                        $total = count($nomesFinais);
                        if ($total === 1) {
                            $textoResponsavel = $nomesFinais[0];
                        } elseif ($total === 2) {
                            $textoResponsavel = $nomesFinais[0] . ' e ' . $nomesFinais[1];
                        } elseif ($total > 2) {
                            $ultimo = array_pop($nomesFinais);
                            $textoResponsavel = implode(', ', $nomesFinais) . ' e ' . $ultimo;
                        }
                    }
                    echo mb_strtoupper($cliente) . ($textoResponsavel ? " | " . mb_strtoupper($textoResponsavel) : "");
                ?>
            </div>
        </div>
        <div class="frame-item">
            <div class="frame-top"><?= $ano ?></div>
            <div class="frame-bottom">PROPOSTA</div>
        </div>
    </div>

    <div class="proposal-wrapper">
        <!-- Título Fixo para Seções Agrupadas -->
        <?php if ($tipo !== 'casamento'): ?>
        <div class="fixed-section-title">
            <h2>ETAPAS DO<br>PROJETO</h2>
        </div>
        <?php endif; ?>

        <?php include $templateFile; ?>
    </div>

    <!-- Botão Exportar Topo -->
    <button class="btn-export-top no-print" onclick="window.showExportModal()">
        <i data-lucide="file-down"></i>
        <span>PDF</span>
    </button>

    <?php if ($tipo === 'casamento'): ?>
    <button onclick="window.openInteractiveModal()" id="btn-approve" class="btn-floating no-print">
        <span>✨ Escolher Nosso Plano</span>
    </button>
    <?php else: ?>
    <a href="https://wa.me/<?= preg_replace('/\D/', '', $configEmpresa['telefone'] ?? '') ?>?text=<?= rawurlencode('Olá! Gostaria de aprovar a proposta: ' . $proposta['titulo'] . ' (Ref: ' . $slug . ')') ?>"
       id="btn-approve" class="btn-floating no-print">
        <span>Aprovar Proposta</span>
        <i data-lucide="check-circle"></i>
    </a>
    <?php endif; ?>

    <!-- Action Bar Mobile -->
    <?php if ($tipo !== 'casamento'): ?>
    <div class="mobile-action-bar no-print">
        <a href="https://wa.me/<?= preg_replace('/\D/', '', $configEmpresa['telefone'] ?? '') ?>?text=<?= rawurlencode('Olá! Gostaria de aprovar a proposta: ' . $proposta['titulo'] . ' (Ref: ' . $slug . ')') ?>"
           class="mobile-btn-approve">
            <i data-lucide="check-circle"></i>
            <span>Aprovar</span>
        </a>
        <button onclick="window.showExportModal()" class="mobile-btn-pdf">
            <i data-lucide="file-down"></i>
            <span>PDF</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Modal de Exportação -->
    <div id="export-modal" class="export-modal no-print" style="display: none;">
        <div class="export-modal-content">
            <h3>Exportar Proposta</h3>
            <p>Cada seção da proposta será exportada como uma página A4 em paisagem.</p>
            
            <div class="export-options">
                <button onclick="window.exportPDF()" class="export-option">
                    <div class="option-preview horizontal">
                        <div class="mac-screen"></div>
                    </div>
                    <span>Exportar em paisagem</span>
                </button>
            </div>
            
            <button onclick="window.hideExportModal()" class="btn-cancel-export">Cancelar</button>
        </div>
    </div>

    <script>
        // Inicializar ícones
        lucide.createIcons();

        <?php if ($tipo === 'casamento'): ?>
        // Lógica do Botão Flutuante Dinâmico
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('btn-approve');
            const scrollContainer = document.querySelector('.wedding-proposal');
            let scrollTimeout;

            function showFloatingButton() {
                if (!btn) return;
                
                btn.classList.add('visible');
                
                // Limpa o timeout anterior se o usuário continuar scrollando
                clearTimeout(scrollTimeout);
                
                // Define o timeout de 2 segundos para desaparecer
                scrollTimeout = setTimeout(() => {
                    btn.classList.remove('visible');
                }, 2000);
            }

            // Ouvir scroll tanto na janela quanto no container de snap (se existir)
            window.addEventListener('scroll', showFloatingButton, true);
            if (scrollContainer) {
                scrollContainer.addEventListener('scroll', showFloatingButton, true);
            }
        });
        <?php endif; ?>

    </script>
    <script src="<?= raizUrl('/assets/js/propostas.js') ?>?v=pdf-html2pdf-2"></script>

    <?php if ($tipo === 'casamento'): ?>
    <?php
    // Dados para o modal de seleção de pacote
    $mPHeritage   = is_numeric($dados['valor_heritage']   ?? '') ? (float)$dados['valor_heritage']   : 7900;
    $mPCinematic  = is_numeric($dados['valor_cinematic']  ?? '') ? (float)$dados['valor_cinematic']  : 4500;
    $mPEssencial  = is_numeric($dados['valor_essencial']  ?? '') ? (float)$dados['valor_essencial']  : 2800;
    $mPBoudoir    = is_numeric($dados['valor_boudoir']    ?? '') ? (float)$dados['valor_boudoir']    : 800;
    $mPPrewedding = is_numeric($dados['valor_prewedding'] ?? '') ? (float)$dados['valor_prewedding'] : 1200;
    $mCondHC = $dados['condicoes_heritage_cinematic'] ?? 'Entrada de 20% + saldo parcelado em até 6x';
    $mCondE  = $dados['condicoes_essencial']          ?? 'Entrada de 25% + saldo parcelado em até 5x';
    $mNomeCasal = '';
    $nNoivo = trim($dados['nome_noivo'] ?? '');
    $nNoiva = trim($dados['nome_noiva'] ?? '');
    if ($nNoivo && $nNoiva) {
        $mNomeCasal = explode(' ', $nNoivo)[0] . ' & ' . explode(' ', $nNoiva)[0];
    } else {
        $mNomeCasal = $proposta['cliente_nome'];
    }
    ?>

    <!-- Modal: Escolha de Pacote -->
    <div id="plan-modal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.88); backdrop-filter:blur(6px); overflow-y:auto; padding:40px 20px;" onclick="if(event.target===this)window.closePlanModal()">
        <div style="max-width:680px; margin:0 auto; background:#1a1a1a; border-radius:12px; border:1px solid rgba(255,255,255,0.08); overflow:hidden;">

            <!-- Header -->
            <div style="padding:28px 32px 20px; border-bottom:1px solid rgba(255,255,255,0.07); display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <p style="font-family:'Montserrat',sans-serif; font-size:0.6rem; font-weight:700; letter-spacing:0.28em; text-transform:uppercase; color:#c5a880; margin:0 0 4px;">DISTINTO WEDDING</p>
                    <h2 style="font-family:'Montserrat',sans-serif; font-size:1.25rem; font-weight:300; letter-spacing:0.06em; text-transform:uppercase; color:#fff; margin:0;">Escolha seu pacote</h2>
                </div>
                <button onclick="window.closePlanModal()" style="background:none; border:none; cursor:pointer; color:rgba(255,255,255,0.4); padding:4px; line-height:1;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Body -->
            <div style="padding:24px 32px;">

                <!-- Planos -->
                <p style="font-family:'Montserrat',sans-serif; font-size:0.58rem; font-weight:700; letter-spacing:0.25em; text-transform:uppercase; color:rgba(255,255,255,0.35); margin:0 0 12px;">PLANOS</p>
                <div id="m-plan-cards" style="display:flex; flex-direction:column; gap:8px; margin-bottom:24px;">
                    <?php
                    $mPlanos = [
                        'heritage'  => ['label' => 'Experiência Heritage',  'valor' => $mPHeritage,  'cond' => $mCondHC],
                        'cinematic' => ['label' => 'Experiência Cinematic', 'valor' => $mPCinematic, 'cond' => $mCondHC],
                        'essencial' => ['label' => 'Registro Essencial',    'valor' => $mPEssencial, 'cond' => $mCondE],
                    ];
                    foreach ($mPlanos as $mKey => $mPl): ?>
                    <div class="m-plan-card" data-plan="<?= $mKey ?>" data-value="<?= $mPl['valor'] ?>" data-cond="<?= htmlspecialchars($mPl['cond']) ?>"
                        onclick="window.mSelectPlan('<?= $mKey ?>')"
                        style="padding:14px 18px; border-radius:8px; border:1.5px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.03); cursor:pointer; display:flex; align-items:center; gap:14px; transition:border-color 0.2s, background 0.2s;">
                        <div class="m-radio" style="width:17px; height:17px; border-radius:50%; border:2px solid rgba(255,255,255,0.25); flex-shrink:0; display:flex; align-items:center; justify-content:center; transition:border-color 0.2s;">
                            <div class="m-radio-dot" style="width:7px; height:7px; border-radius:50%; background:#c5a880; opacity:0; transition:opacity 0.2s;"></div>
                        </div>
                        <p style="font-family:'Montserrat',sans-serif; font-size:0.82rem; font-weight:600; color:rgba(255,255,255,0.85); margin:0; flex:1; letter-spacing:0.02em;"><?= htmlspecialchars($mPl['label']) ?></p>
                        <p style="font-family:'Montserrat',sans-serif; font-size:0.9rem; font-weight:300; color:rgba(255,255,255,0.85); margin:0; flex-shrink:0; white-space:nowrap;">
                            R$ <?= number_format($mPl['valor'], 0, ',', '.') ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Upgrades -->
                <p style="font-family:'Montserrat',sans-serif; font-size:0.58rem; font-weight:700; letter-spacing:0.25em; text-transform:uppercase; color:rgba(255,255,255,0.35); margin:0 0 12px;">UPGRADES OPCIONAIS</p>
                <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:28px;">
                    <?php
                    $mUpgrades = [
                        'boudoir'    => ['label' => 'Boudoir da Noiva',   'valor' => $mPBoudoir],
                        'prewedding' => ['label' => 'Ensaio Pré-Wedding', 'valor' => $mPPrewedding],
                    ];
                    foreach ($mUpgrades as $mUKey => $mUp): ?>
                    <div style="padding:12px 18px; border-radius:8px; border:1px solid rgba(255,255,255,0.08); background:rgba(255,255,255,0.02); display:flex; align-items:center; gap:12px;">
                        <div style="flex:1;">
                            <p style="font-family:'Montserrat',sans-serif; font-size:0.8rem; font-weight:500; color:rgba(255,255,255,0.8); margin:0 0 2px;"><?= htmlspecialchars($mUp['label']) ?></p>
                            <p style="font-family:'Montserrat',sans-serif; font-size:0.75rem; font-weight:300; color:rgba(255,255,255,0.4); margin:0;">R$ <?= number_format($mUp['valor'], 0, ',', '.') ?></p>
                        </div>
                        <div id="m-toggle-<?= $mUKey ?>" onclick="window.mToggle('<?= $mUKey ?>', <?= $mUp['valor'] ?>)"
                            style="width:38px; height:22px; border-radius:20px; background:rgba(255,255,255,0.12); cursor:pointer; position:relative; flex-shrink:0; transition:background 0.2s;">
                            <div style="width:16px; height:16px; border-radius:50%; background:#fff; position:absolute; top:3px; left:3px; transition:left 0.2s;" class="m-thumb"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Total -->
                <div style="background:rgba(255,255,255,0.04); border-radius:8px; padding:16px 20px; display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                    <span style="font-family:'Montserrat',sans-serif; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; color:rgba(255,255,255,0.45);">Total estimado</span>
                    <span id="m-total" style="font-family:'Montserrat',sans-serif; font-size:1.5rem; font-weight:300; color:#fff;">—</span>
                </div>

                <!-- Condições -->
                <p id="m-cond" style="font-family:'Montserrat',sans-serif; font-size:0.75rem; font-weight:300; color:rgba(255,255,255,0.38); line-height:1.6; margin:0 0 28px; min-height:1.2em;"></p>

                <!-- Botão Enviar -->
                <button id="m-send-btn" onclick="window.mEnviar()" disabled
                    style="width:100%; padding:16px; border-radius:8px; background:#c5a880; border:none; cursor:not-allowed; font-family:'Montserrat',sans-serif; font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.15em; color:#1a1a1a; display:flex; align-items:center; justify-content:center; gap:10px; opacity:0.5; transition:opacity 0.2s;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 2C6.477 2 2 6.477 2 12c0 1.89.525 3.656 1.438 5.168L2 22l4.948-1.42A9.96 9.96 0 0 0 12 22c5.523 0 10-4.477 10-10S17.522 2 12 2z"/></svg>
                    Confirmar e enviar no WhatsApp
                </button>
                <p style="font-family:'Montserrat',sans-serif; font-size:0.65rem; color:rgba(255,255,255,0.25); text-align:center; margin:12px 0 0;">Selecione um plano para continuar</p>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const mNomeCasal = <?= json_encode($mNomeCasal) ?>;
        const mSlug      = <?= json_encode($slug) ?>;
        const WA_NUMBER  = '5527988586935';

        const mPlanData = {
            heritage:  { nome: 'Experiência Heritage',  valor: <?= $mPHeritage ?>,  cond: <?= json_encode($mCondHC) ?> },
            cinematic: { nome: 'Experiência Cinematic', valor: <?= $mPCinematic ?>, cond: <?= json_encode($mCondHC) ?> },
            essencial: { nome: 'Registro Essencial',    valor: <?= $mPEssencial ?>, cond: <?= json_encode($mCondE) ?>  },
        };
        const mUpgradeData = { boudoir: <?= $mPBoudoir ?>, prewedding: <?= $mPPrewedding ?> };
        let mSelected = null;
        const mUpgrades = { boudoir: false, prewedding: false };

        function fmtBRL(v) {
            return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        }

        window.openPlanModal = function () {
            document.getElementById('plan-modal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        };
        window.closePlanModal = function () {
            document.getElementById('plan-modal').style.display = 'none';
            document.body.style.overflow = '';
        };

        window.mSelectPlan = function (key) {
            mSelected = key;
            document.querySelectorAll('.m-plan-card').forEach(card => {
                const on = card.dataset.plan === key;
                card.style.borderColor = on ? '#c5a880' : 'rgba(255,255,255,0.1)';
                card.style.background  = on ? 'rgba(197,168,128,0.08)' : 'rgba(255,255,255,0.03)';
                card.querySelector('.m-radio').style.borderColor     = on ? '#c5a880' : 'rgba(255,255,255,0.25)';
                card.querySelector('.m-radio-dot').style.opacity     = on ? '1' : '0';
            });
            mRefresh();
        };

        window.mToggle = function (key) {
            mUpgrades[key] = !mUpgrades[key];
            const track = document.getElementById('m-toggle-' + key);
            const thumb = track.querySelector('.m-thumb');
            if (mUpgrades[key]) {
                track.style.background = '#c5a880';
                thumb.style.left = '19px';
            } else {
                track.style.background = 'rgba(255,255,255,0.12)';
                thumb.style.left = '3px';
            }
            mRefresh();
        };

        function mRefresh() {
            let total = mSelected ? mPlanData[mSelected].valor : 0;
            if (mUpgrades.boudoir)    total += mUpgradeData.boudoir;
            if (mUpgrades.prewedding) total += mUpgradeData.prewedding;

            document.getElementById('m-total').textContent = mSelected ? fmtBRL(total) : '—';
            document.getElementById('m-cond').textContent  = mSelected ? mPlanData[mSelected].cond : '';

            const btn = document.getElementById('m-send-btn');
            btn.disabled = !mSelected;
            btn.style.opacity = mSelected ? '1' : '0.5';
            btn.style.cursor  = mSelected ? 'pointer' : 'not-allowed';

            const hint = btn.nextElementSibling;
            if (hint) hint.style.display = mSelected ? 'none' : 'block';
        }

        window.mEnviar = function () {
            if (!mSelected) return;
            const p = mPlanData[mSelected];
            let total = p.valor;
            let linhas = 'Plano: ' + p.nome + ' — ' + fmtBRL(p.valor);
            if (mUpgrades.boudoir) {
                total += mUpgradeData.boudoir;
                linhas += '\nUpgrade Boudoir — ' + fmtBRL(mUpgradeData.boudoir);
            }
            if (mUpgrades.prewedding) {
                total += mUpgradeData.prewedding;
                linhas += '\nUpgrade Pré-Wedding — ' + fmtBRL(mUpgradeData.prewedding);
            }
            const msg = 'Olá! Somos ' + mNomeCasal + ' e gostaríamos de confirmar nosso interesse na proposta da Distinto.\n\n' + linhas + '\n\nTotal: ' + fmtBRL(total) + '\n\nRef: ' + mSlug;
            window.open('https://wa.me/' + WA_NUMBER + '?text=' + encodeURIComponent(msg), '_blank');
        };
    })();
    </script>
    <?php endif; ?>
</body>
</html>
