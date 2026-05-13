<?php
/**
 * Painel Financeiro SaaS — P&L, Custos de IA, Infraestrutura, Preços
 * Acesso restrito a nível 1 (admin)
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/assinatura.php';

exigirAdmin();
$usuario = usuarioAtual();
if (($usuario['nivel'] ?? 0) < 1) {
    header('Location: /');
    exit;
}

$tituloPagina = 'Financeiro SaaS';
$db = Database::get();

// ── Carregar configurações ────────────────────────────────────────────────────
$cfg = $db->query("SELECT plano_mensal_preco, plano_anual_preco, custo_usd_brl,
    groq_custo_por_1k_tokens, gemini_custo_por_1k_tokens
    FROM configuracao_empresa WHERE id = 'principal' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

$precoMensal   = (float)($cfg['plano_mensal_preco']           ?? 15.00);
$precoAnual    = (float)($cfg['plano_anual_preco']            ?? 158.00);
$cambioUSD     = (float)($cfg['custo_usd_brl']                ?? 5.70);
$custoGroq1k   = (float)($cfg['groq_custo_por_1k_tokens']    ?? 0.000000);
$custoGemini1k = (float)($cfg['gemini_custo_por_1k_tokens']  ?? 0.000000);

// ── Receita ───────────────────────────────────────────────────────────────────
$totalAtivos    = (int) $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn();
$receitaMensal  = (float) $db->query("SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE status='active' AND plan='mensal'")->fetchColumn();
$receitaAnual   = (float) $db->query("SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE status='active' AND plan='anual'")->fetchColumn();
$receitaAnualProrateada = $receitaAnual / 12;
$receitaTotalMes = $receitaMensal + $receitaAnualProrateada;

// ── Custos de IA (mês atual) ──────────────────────────────────────────────────
$mesAtual = date('Y-m');
try {
    $stmtIA = $db->prepare(
        "SELECT provider,
            SUM(tokens_in + tokens_out) AS total_tokens,
            SUM(custo_usd) AS custo_usd
         FROM log_ia_calls
         WHERE DATE_TRUNC('month', created_at) = DATE_TRUNC('month', CURRENT_DATE)
         GROUP BY provider"
    );
    $stmtIA->execute();
    $custosIA = $stmtIA->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $custosIA = [];
}

$totalTokens = 0;
$totalCustoIAusd = 0.0;
$tokensPorProvider = [];
foreach ($custosIA as $c) {
    $totalTokens += (int)$c['total_tokens'];
    $totalCustoIAusd += (float)$c['custo_usd'];
    $tokensPorProvider[$c['provider']] = [
        'tokens' => (int)$c['total_tokens'],
        'custo_usd' => (float)$c['custo_usd'],
    ];
}
$totalCustoIAbrl = $totalCustoIAusd * $cambioUSD;

// Top 5 usuários por consumo de IA (mês atual)
try {
    $stmtTop = $db->prepare(
        "SELECT l.user_id, u.nome, u.email,
            SUM(l.tokens_in + l.tokens_out) AS total_tokens,
            SUM(l.custo_usd) AS custo_usd
         FROM log_ia_calls l
         LEFT JOIN users u ON u.id = l.user_id
         WHERE DATE_TRUNC('month', l.created_at) = DATE_TRUNC('month', CURRENT_DATE)
         GROUP BY l.user_id, u.nome, u.email
         ORDER BY total_tokens DESC
         LIMIT 5"
    );
    $stmtTop->execute();
    $topUsuarios = $stmtTop->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $topUsuarios = [];
}

// ── Custos de Infraestrutura ──────────────────────────────────────────────────
try {
    $custos = $db->query(
        "SELECT id, descricao, valor, moeda, periodo, ativo FROM custos_infraestrutura WHERE ativo = TRUE ORDER BY id"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $custos = [];
}

$totalCustoInfraMes = 0.0;
foreach ($custos as $c) {
    $v = (float)$c['valor'];
    $totalCustoInfraMes += ($c['periodo'] === 'anual') ? ($v / 12) : $v;
}

// ── P&L ───────────────────────────────────────────────────────────────────────
$totalCustos  = $totalCustoInfraMes + $totalCustoIAbrl;
$resultado    = $receitaTotalMes - $totalCustos;
$custoMinSub  = $totalAtivos > 0 ? round($totalCustos / $totalAtivos, 2) : 0;

// ── Trial / Assinantes ────────────────────────────────────────────────────────
$totalTrial   = (int) $db->query("SELECT COUNT(*) FROM users WHERE subscription_status = 'trial'")->fetchColumn();
$totalExpirado = (int) $db->query("SELECT COUNT(*) FROM users WHERE subscription_status IN ('expired','cancelled')")->fetchColumn();
$totalUsers   = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

$sucesso = '';
$erro = '';

// ── POST: salvar preços / câmbio ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar_precos') {
        $novoMensal  = (float)($_POST['plano_mensal_preco'] ?? $precoMensal);
        $novoAnual   = (float)($_POST['plano_anual_preco']  ?? $precoAnual);
        $novoCambio  = (float)($_POST['custo_usd_brl']      ?? $cambioUSD);
        $novoGroq    = (float)($_POST['groq_custo']         ?? $custoGroq1k);
        $novoGemini  = (float)($_POST['gemini_custo']       ?? $custoGemini1k);
        $aplicarTodos = isset($_POST['aplicar_todos']);

        $db->prepare("UPDATE configuracao_empresa SET
            plano_mensal_preco = ?, plano_anual_preco = ?, custo_usd_brl = ?,
            groq_custo_por_1k_tokens = ?, gemini_custo_por_1k_tokens = ?
            WHERE id = 'principal'"
        )->execute([$novoMensal, $novoAnual, $novoCambio, $novoGroq, $novoGemini]);

        if ($aplicarTodos) {
            $db->prepare("UPDATE subscriptions SET amount = ? WHERE status = 'active' AND plan = 'mensal'")->execute([$novoMensal]);
            $db->prepare("UPDATE subscriptions SET amount = ? WHERE status = 'active' AND plan = 'anual'")->execute([$novoAnual]);
        }

        $sucesso = 'Preços e tarifas salvos com sucesso.';
        header('Location: saas.php?ok=1');
        exit;
    }

    if ($acao === 'add_custo') {
        $desc   = trim($_POST['descricao'] ?? '');
        $val    = (float)($_POST['valor']  ?? 0);
        $moeda  = $_POST['moeda']   ?? 'BRL';
        $per    = $_POST['periodo'] ?? 'mensal';
        if ($desc && $val > 0) {
            $db->prepare("INSERT INTO custos_infraestrutura (descricao, valor, moeda, periodo) VALUES (?,?,?,?)")
               ->execute([$desc, $val, $moeda, $per]);
        }
        header('Location: saas.php');
        exit;
    }

    if ($acao === 'del_custo') {
        $delId = (int)($_POST['custo_id'] ?? 0);
        if ($delId) {
            $db->prepare("UPDATE custos_infraestrutura SET ativo = FALSE WHERE id = ?")->execute([$delId]);
        }
        header('Location: saas.php');
        exit;
    }
}

if (isset($_GET['ok'])) $sucesso = 'Configurações salvas com sucesso.';

include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" style="flex:1; padding:28px 32px; overflow-y:auto;" 
          x-data="{ 
            addCusto: false, 
            showSimulador: false,
            simCambio: <?= $cambioUSD ?>,
            simGroq: <?= $custoGroq1k ?>,
            simGemini: <?= $custoGemini1k ?>,
            get simCustoIA() {
                let groqTokens = <?= $tokensPorProvider['groq']['tokens'] ?? 0 ?>;
                let geminiTokens = <?= $tokensPorProvider['gemini']['tokens'] ?? 0 ?>;
                let custoUSD = (groqTokens * (this.simGroq / 1000)) + (geminiTokens * (this.simGemini / 1000));
                return custoUSD * this.simCambio;
            },
            get simResultado() {
                let receita = <?= $receitaTotalMes ?>;
                let infra = <?= $totalCustoInfraMes ?>;
                return receita - (infra + this.simCustoIA);
            }
          }">

        <div style="margin-bottom:28px; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size:22px; font-weight:700; color:#f1f5f9;">Financeiro SaaS</h1>
                <p style="font-size:14px; color:#6b7280; margin-top:2px;">Inteligência de negócio, P&L e gestão de infraestrutura</p>
            </div>
            <div style="display:flex; gap:10px;">
                <button @click="showSimulador = !showSimulador" class="btn-secondary" style="background:rgba(255,255,255,0.03); border-color:rgba(255,255,255,0.1); color:#f1f5f9; font-weight:700;">
                    <i data-lucide="calculator" style="width:16px; height:16px; margin-right:6px;"></i>
                    Simular Cenários
                </button>
                <div style="font-size:12px; color:#6b7280; align-self:center;"><?= date('F Y') ?></div>
            </div>
        </div>

        <!-- ── Simulador de Cenários (BI) ── -->
        <div x-show="showSimulador" x-cloak x-transition style="margin-bottom:28px; background:rgba(129,140,248,0.05); border:1px solid rgba(129,140,248,0.2); border-radius:20px; padding:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="font-size:14px; font-weight:800; color:#818cf8; text-transform:uppercase; letter-spacing:0.05em; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="wand-2" style="width:16px; height:16px;"></i>
                    Simulador de Impacto (Modo Inteligência)
                </h3>
                <button @click="showSimulador = false" style="background:none; border:none; color:#6b7280; cursor:pointer;"><i data-lucide="x" style="width:16px; height:16px;"></i></button>
            </div>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:20px;">
                <div>
                    <label class="label-premium">Câmbio Simulado</label>
                    <input type="range" min="4" max="7" step="0.05" x-model="simCambio" style="width:100%; accent-color:#818cf8;">
                    <div style="display:flex; justify-content:space-between; font-size:12px; color:#f1f5f9; font-weight:700; margin-top:8px;">
                        <span>R$ 4.00</span>
                        <span style="color:#818cf8;">R$ <span x-text="parseFloat(simCambio).toFixed(2)"></span></span>
                        <span>R$ 7.00</span>
                    </div>
                </div>
                <div>
                    <label class="label-premium">Custo IA (Tokens/1k)</label>
                    <div style="display:flex; gap:10px;">
                        <input class="input" type="number" step="0.0001" x-model="simGroq" style="background:rgba(0,0,0,0.2); font-size:11px;" title="Groq">
                        <input class="input" type="number" step="0.0001" x-model="simGemini" style="background:rgba(0,0,0,0.2); font-size:11px;" title="Gemini">
                    </div>
                </div>
                <div style="padding:12px; background:rgba(0,0,0,0.2); border-radius:12px; border:1px solid rgba(255,255,255,0.05); display:flex; flex-direction:column; justify-content:center;">
                    <div style="font-size:10px; font-weight:800; color:#6b7280; text-transform:uppercase; margin-bottom:4px;">Resultado Estimado</div>
                    <div style="font-size:18px; font-weight:900;" :class="simResultado >= 0 ? 'text-emerald-400' : 'text-red-400'">
                        R$ <span x-text="parseFloat(simResultado).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($sucesso): ?>
        <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:14px; color:#34d399;">
            <?= sanitizar($sucesso) ?>
        </div>
        <?php endif; ?>

        <!-- ── Bloco 1: Usuários (Top Cards) ──────────────────────────────────────────── -->
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px,1fr)); gap:20px; margin-bottom:28px;">
            <?php foreach ([
                ['Total de usuários',   $totalUsers,   '#94a3b8', 'users'],
                ['Em trial',            $totalTrial,   '#818cf8', 'clock'],
                ['Assinantes ativos',   $totalAtivos,  '#34d399', 'check-circle'],
                ['Expirados/Cancelados',$totalExpirado,'#f87171', 'x-circle'],
            ] as [$label, $val, $cor, $icon]): ?>
            <div class="card" style="padding:24px; position:relative; overflow:hidden; background:rgba(255,255,255,0.02); border-color:rgba(255,255,255,0.05);">
                <div style="position:absolute; top:16px; right:16px; color:<?= $cor ?>; opacity:0.3;">
                    <i data-lucide="<?= $icon ?>" style="width:22px; height:22px;"></i>
                </div>
                <div style="font-size:36px; font-weight:800; color:#f1f5f9; line-height:1;"><?= $val ?></div>
                <div style="font-size:12px; font-weight:700; color:#6b7280; margin-top:10px; text-transform:uppercase; letter-spacing:0.04em;"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Bloco 2: P&L (Profit and Loss) ──────────────────────────────────────── -->
        <div class="card" style="padding:28px; margin-bottom:28px; position:relative; background:rgba(10,10,10,0.4); border-color:rgba(255,255,255,0.05);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <h3 style="font-size:16px; font-weight:700; color:#e2e8f0; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="bar-chart-3" style="width:18px; height:18px; color:#94a3b8;"></i>
                    P&L — <?= date('M/Y') ?>
                </h3>
                <button class="btn-secondary" style="background:transparent; border:1px solid rgba(255,255,255,0.1); color:#94a3b8; font-size:11px; padding:6px 14px; border-radius:99px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; transition:all 0.2s;" onmouseover="this.style.borderColor='rgba(255,255,255,0.3)'; this.style.color='#f1f5f9'" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)'; this.style.color='#94a3b8'">
                    Relatório Mensal
                </button>
            </div>

            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:24px; margin-bottom:32px;">
                <div>
                    <div style="font-size:11px; font-weight:700; color:#6b7280; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.04em;">Receita Mensal</div>
                    <div style="font-size:26px; font-weight:800; color:#34d399;">R$ <?= number_format($receitaTotalMes, 2, ',', '.') ?></div>
                    <div style="font-size:11px; margin-top:8px; display:flex; flex-direction:column; gap:4px;">
                        <span style="color:#6b7280;">Mensal: <span style="color:#f1f5f9; font-weight:600;">R$<?= number_format($receitaMensal,2,',','.') ?></span></span>
                        <span style="color:#6b7280;">Anual pró-rata: <span style="color:#f1f5f9; font-weight:600;">R$<?= number_format($receitaAnualProrateada,2,',','.') ?></span></span>
                    </div>
                </div>
                <div>
                    <div style="font-size:11px; font-weight:700; color:#6b7280; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.04em;">Custos de Infra</div>
                    <div style="font-size:26px; font-weight:800; color:#f87171;">R$ <?= number_format($totalCustoInfraMes, 2, ',', '.') ?></div>
                    <div style="font-size:11px; color:#6b7280; margin-top:8px; font-style:italic;">Normalizado p/ mês</div>
                </div>
                <div>
                    <div style="font-size:11px; font-weight:700; color:#6b7280; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.04em;">Custos de IA</div>
                    <div style="font-size:26px; font-weight:800; color:#fbbf24;">R$ <?= number_format($totalCustoIAbrl, 2, ',', '.') ?></div>
                    <div style="font-size:11px; margin-top:8px;">
                        <span style="color:#6b7280;">USD <?= number_format($totalCustoIAusd, 4, ',', '.') ?></span>
                        <span style="color:#4b5563; margin: 0 4px;">×</span>
                        <span style="color:#f1f5f9; font-weight:600;">R$<?= number_format($cambioUSD,2,',','.') ?></span>
                    </div>
                </div>
                <div style="padding-left:24px; border-left:1px solid rgba(255,255,255,0.05); position:relative;">
                    <div style="font-size:11px; font-weight:700; color:#6b7280; margin-bottom:8px; text-transform:uppercase; letter-spacing:0.04em;">Resultado Líquido</div>
                    <div style="font-size:32px; font-weight:900; color:#ffffff; line-height:1;">
                        R$ <?= number_format($resultado, 2, ',', '.') ?>
                    </div>
                    <div style="font-size:11px; color:#6b7280; margin-top:10px;">
                        Custo mín/sub: <span style="color:#f1f5f9; font-weight:600;">R$<?= number_format($custoMinSub,2,',','.') ?></span>
                    </div>
                    <!-- Indicador Lateral Premium -->
                    <div style="position:absolute; left:0; top:0; bottom:0; width:4px; background:<?= $resultado >= 0 ? '#34d399' : '#f87171' ?>; opacity:0.8; border-radius:4px; box-shadow: 0 0 15px <?= $resultado >= 0 ? 'rgba(52,211,153,0.3)' : 'rgba(248,113,113,0.3)' ?>;"></div>
                </div>
            </div>

            <!-- Barra Visual P&L (Termômetro) -->
            <div style="background:rgba(0,0,0,0.2); padding:20px; border-radius:16px; border:1px solid rgba(255,255,255,0.03);">
                <?php if ($receitaTotalMes > 0): 
                    $percInfra = ($totalCustoInfraMes / $receitaTotalMes) * 100;
                    $percIA = ($totalCustoIAbrl / $receitaTotalMes) * 100;
                    $percMargem = ($resultado / $receitaTotalMes) * 100;
                ?>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                    <div style="display:flex; gap:20px;">
                        <span style="display:flex; align-items:center; gap:8px; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.02em;">
                            <span style="width:10px; height:10px; border-radius:3px; background:#ef4444;"></span> Infra (<?= round($percInfra) ?>%)
                        </span>
                        <span style="display:flex; align-items:center; gap:8px; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.02em;">
                            <span style="width:10px; height:10px; border-radius:3px; background:#f59e0b;"></span> IA (<?= round($percIA) ?>%)
                        </span>
                        <?php if ($resultado > 0): ?>
                        <span style="display:flex; align-items:center; gap:8px; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.02em;">
                            <span style="width:10px; height:10px; border-radius:3px; background:#10b981;"></span> Margem (<?= round($percMargem) ?>%)
                        </span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:13px; font-weight:800; color:<?= $resultado >= 0 ? '#10b981' : '#ef4444' ?>; letter-spacing:-0.01em;">
                        <?= $resultado >= 0 ? '+' : '' ?><?= round($percMargem, 1) ?>%
                    </div>
                </div>
                <div style="height:12px; background:rgba(0,0,0,0.3); border-radius:99px; overflow:hidden; display:flex; border:1px solid rgba(255,255,255,0.03);">
                    <div style="width:<?= min(100, $percInfra) ?>%; background:#ef4444; box-shadow: 0 0 10px rgba(239, 68, 68, 0.1);"></div>
                    <div style="width:<?= min(100 - $percInfra, $percIA) ?>%; background:#f59e0b; box-shadow: 0 0 10px rgba(245, 158, 11, 0.1);"></div>
                    <?php if ($resultado > 0): ?>
                    <div style="flex:1; background:#10b981; box-shadow: 0 0 10px rgba(16, 185, 129, 0.1);"></div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div style="display:flex; align-items:center; justify-content:center; gap:12px; padding:15px; border:1px dashed rgba(255,255,255,0.05); border-radius:12px;">
                    <div style="height:8px; width:100px; background:rgba(255,255,255,0.03); border-radius:99px; position:relative; overflow:hidden;">
                        <div style="position:absolute; inset:0; background:linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent); animation: skeleton-scan 2s infinite;"></div>
                    </div>
                    <div style="font-size:12px; color:#4b5563; font-weight:600;">Aguardando dados de receita para gerar o termômetro.</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Bloco 3: Custos de IA & Top Usuários ─────────────────────────────────────── -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:28px;">
            <div class="card" style="padding:24px; background:rgba(255,255,255,0.01); border-color:rgba(255,255,255,0.05);">
                <h3 style="font-size:15px; font-weight:700; color:#e2e8f0; margin-bottom:24px; display:flex; align-items:center; gap:10px;">
                    <i data-lucide="cpu" style="width:18px; height:18px; color:#fbbf24;"></i>
                    Consumo de IA — <?= date('M/Y') ?>
                </h3>
                
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <?php foreach ([
                        ['groq',   'Groq',   '#818cf8', 'zap'],
                        ['gemini', 'Gemini', '#34d399', 'brain-circuit'],
                    ] as [$key, $label, $cor, $icon]): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 16px; border-radius:12px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <div style="display:flex; align-items:center; gap:14px;">
                            <div style="width:36px; height:36px; border-radius:10px; background:rgba(<?= $key=='groq'?'129,140,248':'52,211,153' ?>, 0.1); display:flex; align-items:center; justify-content:center; color:<?= $cor ?>;">
                                <i data-lucide="<?= $icon ?>" style="width:18px; height:18px;"></i>
                            </div>
                            <div>
                                <div style="font-size:14px; font-weight:700; color:#f1f5f9;"><?= $label ?></div>
                                <div style="font-size:11px; color:#6b7280; font-weight:500;"><?= number_format($tokensPorProvider[$key]['tokens'] ?? 0, 0, ',', '.') ?> tokens</div>
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:14px; font-weight:800; color:<?= $cor ?>; letter-spacing:-0.01em;">USD <?= number_format($tokensPorProvider[$key]['custo_usd'] ?? 0, 4, ',', '.') ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top:20px; padding:20px 16px 0; border-top:1px solid rgba(255,255,255,0.06); display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:13px; color:#6b7280; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">Total Estimado</span>
                    <span style="font-size:22px; color:#fbbf24; font-weight:900; letter-spacing:-0.02em;">R$ <?= number_format($totalCustoIAbrl, 2, ',', '.') ?></span>
                </div>
            </div>

            <!-- Top consumidores -->
            <div class="card" style="padding:24px; background:rgba(255,255,255,0.01); border-color:rgba(255,255,255,0.05);">
                <h3 style="font-size:15px; font-weight:700; color:#e2e8f0; margin-bottom:24px; display:flex; align-items:center; gap:10px;">
                    <i data-lucide="crown" style="width:18px; height:18px; color:#34d399;"></i>
                    Top Usuários (Simétrico)
                </h3>

                <?php if (empty($topUsuarios)): ?>
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:50px 0; color:#4b5563; border:1px dashed rgba(255,255,255,0.03); border-radius:16px;">
                    <i data-lucide="users-2" style="width:36px; height:36px; margin-bottom:12px; opacity:0.1;"></i>
                    <div style="font-size:13px; font-style:italic; font-weight:500;">Nenhum registro de consumo.</div>
                </div>
                <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php foreach ($topUsuarios as $u): 
                        $nomes = explode(' ', $u['nome'] ?? 'User');
                        $iniciais = strtoupper(substr($nomes[0], 0, 1) . (isset($nomes[1]) ? substr($nomes[1], 0, 1) : ''));
                        $custoBRL = ($u['custo_usd'] ?? 0) * $cambioUSD;
                    ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; border-radius:12px; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                        <div style="display:flex; align-items:center; gap:12px; flex:1; min-width:0;">
                            <div style="width:38px; height:38px; border-radius:99px; background:linear-gradient(135deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03)); border:1px solid rgba(255,255,255,0.06); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; color:#f1f5f9; flex-shrink:0;">
                                <?= $iniciais ?>
                            </div>
                            <div style="overflow:hidden;">
                                <div style="font-size:14px; font-weight:700; color:#f1f5f9; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= sanitizar($u['nome'] ?? $u['user_id']) ?></div>
                                <div style="font-size:11px; color:#6b7280; font-weight:500;"><?= number_format($u['total_tokens'], 0, ',', '.') ?> tokens</div>
                            </div>
                        </div>
                        <div style="text-align:right; flex-shrink:0; padding-left:16px;">
                            <div style="font-size:15px; font-weight:800; color:#fbbf24; letter-spacing:-0.01em;">R$ <?= number_format($custoBRL, 2, ',', '.') ?></div>
                            <div style="font-size:10px; color:#4b5563; font-weight:600; text-transform:uppercase;">$<?= number_format($u['custo_usd'] ?? 0, 3, ',', '.') ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Bloco 4: Custos de Infraestrutura ─────────────────────────── -->
        <div class="card" style="padding:28px; margin-bottom:28px; background:rgba(255,255,255,0.01); border-color:rgba(255,255,255,0.05);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
                <h3 style="font-size:16px; font-weight:700; color:#e2e8f0; display:flex; align-items:center; gap:10px;">
                    <i data-lucide="database" style="width:20px; height:20px; color:#818cf8;"></i>
                    Custos de Infraestrutura
                </h3>
                <button @click="addCusto = !addCusto" class="btn-primary" style="padding:10px 22px; font-size:13px; border-radius:12px; display:flex; align-items:center; gap:8px; background:#1e293b; border-color:rgba(255,255,255,0.1); color:#f1f5f9;">
                    <i data-lucide="plus" style="width:16px; height:16px;"></i>
                    Adicionar Custo
                </button>
            </div>

            <!-- Formulário de novo custo -->
            <div x-show="addCusto" x-cloak x-transition style="background:rgba(0,0,0,0.3); padding:28px; border-radius:20px; border:1px solid rgba(255,255,255,0.06); margin-bottom:28px; box-shadow:0 20px 40px rgba(0,0,0,0.4);">
                <form method="POST" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(150px, 1fr)); gap:24px; align-items:end;">
                    <input type="hidden" name="acao" value="add_custo">
                    <div>
                        <label class="label-premium">Descrição do Serviço</label>
                        <input class="input" name="descricao" required placeholder="Ex: Supabase Cloud" style="background:rgba(0,0,0,0.3); border-color:rgba(255,255,255,0.1);">
                    </div>
                    <div>
                        <label class="label-premium">Valor</label>
                        <div style="position:relative;">
                            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:13px; font-weight:800; color:#4b5563;">$</span>
                            <input class="input" type="number" step="0.01" name="valor" required placeholder="0.00" style="padding-left:32px; background:rgba(0,0,0,0.3); border-color:rgba(255,255,255,0.1);">
                        </div>
                    </div>
                    <div>
                        <label class="label-premium">Moeda</label>
                        <select class="input" name="moeda" style="background:rgba(0,0,0,0.3); border-color:rgba(255,255,255,0.1);">
                            <option value="BRL">BRL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div>
                        <label class="label-premium">Periodicidade</label>
                        <select class="input" name="periodo" style="background:rgba(0,0,0,0.3); border-color:rgba(255,255,255,0.1);">
                            <option value="mensal">Mensal</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                    <div style="display:flex; gap:12px;">
                        <button type="submit" class="btn-primary" style="flex:1; height:44px; background:#10b981; border:none; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);">Confirmar</button>
                        <button type="button" @click="addCusto=false" class="btn-secondary" style="height:44px; width:44px; padding:0; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.03);"><i data-lucide="x" style="width:18px; height:18px;"></i></button>
                    </div>
                </form>
            </div>

            <?php if (empty($custos)): ?>
            <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding:70px 0; background:rgba(0,0,0,0.15); border:2px dashed rgba(255,255,255,0.03); border-radius:24px;">
                <div style="width:72px; height:72px; border-radius:20px; background:rgba(255,255,255,0.01); display:flex; align-items:center; justify-content:center; margin-bottom:24px; color:#4b5563; border:1px solid rgba(255,255,255,0.03);">
                    <i data-lucide="cloud-lightning" style="width:36px; height:36px;"></i>
                </div>
                <div style="font-size:16px; font-weight:700; color:#f1f5f9; margin-bottom:6px;">Gestão de Infra Zero</div>
                <div style="font-size:13px; color:#6b7280; margin-bottom:24px; text-align:center; max-width:300px;">Cadastre seus serviços de hospedagem e APIs para obter o cálculo real da sua margem líquida.</div>
                <button @click="addCusto = true" class="btn-primary" style="padding:12px 32px; border-radius:99px; background:#10b981; border:none; color:#ffffff; font-weight:800; box-shadow:0 8px 20px rgba(16, 185, 129, 0.2);">
                    Começar a Gerir
                </button>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%; font-size:13px; border-collapse:collapse;">
                    <thead>
                        <tr style="color:#6b7280; font-size:11px; text-transform:uppercase; letter-spacing:0.1em; border-bottom:1px solid rgba(255,255,255,0.06);">
                            <th style="text-align:left; padding:20px 12px;">Serviço</th>
                            <th style="text-align:right; padding:20px 12px;">Valor Original</th>
                            <th style="text-align:right; padding:20px 12px;">Ciclo de Fatura</th>
                            <th style="text-align:right; padding:20px 12px;">Custo Mensal (BRL)</th>
                            <th style="padding:20px 12px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($custos as $c): ?>
                    <?php $mesequiv = ($c['periodo'] === 'anual') ? ($c['valor'] / 12) : $c['valor']; ?>
                    <tr style="border-bottom:1px solid rgba(255,255,255,0.02); transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.015)'" onmouseout="this.style.background='transparent'">
                        <td style="padding:18px 12px; color:#f1f5f9; font-weight:700;"><?= sanitizar($c['descricao']) ?></td>
                        <td style="text-align:right; color:#cbd5e1; font-weight:500;"><?= $c['moeda'] ?> <?= number_format($c['valor'], 2, ',', '.') ?></td>
                        <td style="text-align:right; padding:18px 12px;">
                            <span style="padding:5px 12px; background:rgba(255,255,255,0.03); border-radius:8px; font-size:10px; font-weight:800; text-transform:uppercase; color:#94a3b8; border:1px solid rgba(255,255,255,0.06);">
                                <?= $c['periodo'] ?>
                            </span>
                        </td>
                        <td style="text-align:right; color:#f1f5f9; font-weight:800;">R$ <?= number_format($mesequiv, 2, ',', '.') ?></td>
                        <td style="text-align:right; padding:18px 12px;">
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Remover este custo?')">
                                <input type="hidden" name="acao" value="del_custo">
                                <input type="hidden" name="custo_id" value="<?= $c['id'] ?>">
                                <button type="submit" style="width:36px; height:36px; border-radius:10px; border:none; background:rgba(248,113,113,0.08); color:#f87171; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s;">
                                    <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="padding:32px 12px; font-size:12px; color:#4b5563; font-weight:800; text-transform:uppercase; letter-spacing:0.05em;">Custo de Infra Total</td>
                            <td style="text-align:right; padding:32px 12px; color:#f87171; font-size:20px; font-weight:900; letter-spacing:-0.02em;">R$ <?= number_format($totalCustoInfraMes, 2, ',', '.') ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Bloco 5: Gestão de Preços ────────────────────────────────── -->
        <div class="card" style="padding:28px; margin-bottom:28px; border-left:4px solid #10b981; background:rgba(255,255,255,0.01); border-color:rgba(255,255,255,0.05);">
            <div style="margin-bottom:32px;">
                <h3 style="font-size:16px; font-weight:700; color:#e2e8f0; display:flex; align-items:center; gap:10px;">
                    <i data-lucide="sliders" style="width:20px; height:20px; color:#34d399;"></i>
                    Configuração de Precificação Global
                </h3>
                <p style="font-size:13px; color:#6b7280; margin-top:6px; font-weight:500;">Ajuste os valores dos planos e as taxas de conversão para faturamento.</p>
            </div>

            <form method="POST">
                <input type="hidden" name="acao" value="salvar_precos">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:28px; margin-bottom:40px;">
                    <div>
                        <label class="label-premium">Ticket Plano Mensal</label>
                        <div style="position:relative;">
                            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:13px; font-weight:800; color:#4b5563;">R$</span>
                            <input class="input" type="number" step="0.01" name="plano_mensal_preco" value="<?= $precoMensal ?>" required style="padding-left:40px; background:rgba(0,0,0,0.25); border-color:rgba(255,255,255,0.1); font-weight:700;">
                        </div>
                    </div>
                    <div>
                        <label class="label-premium">Ticket Plano Anual</label>
                        <div style="position:relative;">
                            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:13px; font-weight:800; color:#4b5563;">R$</span>
                            <input class="input" type="number" step="0.01" name="plano_anual_preco" value="<?= $precoAnual ?>" required style="padding-left:40px; background:rgba(0,0,0,0.25); border-color:rgba(255,255,255,0.1); font-weight:700;">
                        </div>
                    </div>
                    <div>
                        <label class="label-premium" style="display:flex; align-items:center; gap:8px;">
                            Taxa de Câmbio (USD/BRL)
                            <i data-lucide="help-circle" style="width:15px; height:15px; color:#6b7280; cursor:help;" title="Definição manual para cálculos de custos."></i>
                        </label>
                        <div style="position:relative;">
                            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:13px; font-weight:800; color:#4b5563;">R$</span>
                            <input class="input" type="number" step="0.01" name="custo_usd_brl" value="<?= $cambioUSD ?>" required style="padding-left:40px; background:rgba(0,0,0,0.25); border-color:rgba(255,255,255,0.1); font-weight:700;">
                        </div>
                    </div>
                    <div>
                        <label class="label-premium">Custo Groq (1k tokens)</label>
                        <div style="position:relative;">
                            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:13px; font-weight:800; color:#4b5563;">$</span>
                            <input class="input" type="number" step="0.000001" name="groq_custo" value="<?= $custoGroq1k ?>" placeholder="0.000000" style="padding-left:28px; background:rgba(0,0,0,0.25); border-color:rgba(255,255,255,0.1);">
                        </div>
                    </div>
                    <div>
                        <label class="label-premium">Custo Gemini (1k tokens)</label>
                        <div style="position:relative;">
                            <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:13px; font-weight:800; color:#4b5563;">$</span>
                            <input class="input" type="number" step="0.000001" name="gemini_custo" value="<?= $custoGemini1k ?>" placeholder="0.000000" style="padding-left:28px; background:rgba(0,0,0,0.25); border-color:rgba(255,255,255,0.1);">
                        </div>
                    </div>
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; gap:20px; flex-wrap:wrap; padding:30px; background:rgba(0,0,0,0.25); border-radius:20px; border:1px solid rgba(255,255,255,0.05);">
                    <div style="flex:1; display:flex; align-items:center; gap:16px;">
                        <div style="width:40px; height:40px; border-radius:12px; background:rgba(251,191,36,0.1); display:flex; align-items:center; justify-content:center; color:#fbbf24; flex-shrink:0;">
                            <i data-lucide="alert-triangle" style="width:20px; height:20px;"></i>
                        </div>
                        <label style="display:flex; align-items:center; gap:12px; font-size:14px; color:#94a3b8; cursor:pointer;">
                            <input type="checkbox" name="aplicar_todos" style="width:20px; height:20px; accent-color:#fbbf24; cursor:pointer;">
                            <span>Atualizar valor em <strong>todas as assinaturas ativas</strong> (Ação Crítica)</span>
                        </label>
                    </div>
                    <button type="submit" class="btn-primary" style="padding:14px 36px; font-size:14px; background:#10b981; border:none; box-shadow:0 8px 25px rgba(16, 185, 129, 0.25); font-weight:800; border-radius:14px;">
                        Salvar Configurações
                    </button>
                </div>
            </form>
        </div>

        <script>
            // Forçar re-renderização dos ícones Lucide após Alpine carregar
            document.addEventListener('alpine:initialized', () => {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });
        </script>

    </main>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
