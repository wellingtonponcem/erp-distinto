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

    <main id="main-content" style="flex:1; padding:28px 32px; overflow-y:auto;" x-data="{ addCusto: false }">

        <div style="margin-bottom:28px; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size:22px; font-weight:700; color:#f1f5f9;">Financeiro SaaS</h1>
                <p style="font-size:14px; color:#6b7280; margin-top:2px;">P&L, custos de IA, infraestrutura e gestão de preços</p>
            </div>
            <div style="font-size:12px; color:#6b7280;"><?= date('F Y') ?></div>
        </div>

        <?php if ($sucesso): ?>
        <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:14px; color:#34d399;">
            <?= sanitizar($sucesso) ?>
        </div>
        <?php endif; ?>

        <!-- ── Bloco 1: Usuários ──────────────────────────────────────────── -->
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px,1fr)); gap:16px; margin-bottom:28px;">
            <?php foreach ([
                ['Total de usuários',   $totalUsers,   '#94a3b8'],
                ['Em trial',            $totalTrial,   '#818cf8'],
                ['Assinantes ativos',   $totalAtivos,  '#34d399'],
                ['Expirados/Cancelados',$totalExpirado,'#f87171'],
            ] as [$label, $val, $cor]): ?>
            <div class="card" style="padding:20px; text-align:center;">
                <div style="font-size:28px; font-weight:800; color:<?= $cor ?>;"><?= $val ?></div>
                <div style="font-size:12px; color:#6b7280; margin-top:4px;"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ── Bloco 2: P&L ──────────────────────────────────────────────── -->
        <div class="card" style="padding:24px; margin-bottom:28px;">
            <h3 style="font-size:15px; font-weight:600; color:#e2e8f0; margin-bottom:20px;">P&L — <?= date('M/Y') ?></h3>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:16px; margin-bottom:20px; flex-wrap:wrap;">
                <div>
                    <div style="font-size:12px; color:#6b7280; margin-bottom:4px;">Receita Mensal</div>
                    <div style="font-size:22px; font-weight:700; color:#34d399;">R$ <?= number_format($receitaTotalMes, 2, ',', '.') ?></div>
                    <div style="font-size:11px; color:#4b5563; margin-top:2px;">
                        Mensal: R$<?= number_format($receitaMensal,2,',','.') ?> +
                        Anual pró-rata: R$<?= number_format($receitaAnualProrateada,2,',','.') ?>
                    </div>
                </div>
                <div>
                    <div style="font-size:12px; color:#6b7280; margin-bottom:4px;">Custos de Infra</div>
                    <div style="font-size:22px; font-weight:700; color:#f87171;">R$ <?= number_format($totalCustoInfraMes, 2, ',', '.') ?></div>
                    <div style="font-size:11px; color:#4b5563; margin-top:2px;">Normalizado p/ mês</div>
                </div>
                <div>
                    <div style="font-size:12px; color:#6b7280; margin-bottom:4px;">Custos de IA</div>
                    <div style="font-size:22px; font-weight:700; color:#fbbf24;">R$ <?= number_format($totalCustoIAbrl, 2, ',', '.') ?></div>
                    <div style="font-size:11px; color:#4b5563; margin-top:2px;">USD <?= number_format($totalCustoIAusd, 4, ',', '.') ?> × R$<?= number_format($cambioUSD,2,',','.') ?></div>
                </div>
                <div>
                    <div style="font-size:12px; color:#6b7280; margin-bottom:4px;">Resultado</div>
                    <div style="font-size:22px; font-weight:700; color:<?= $resultado >= 0 ? '#34d399' : '#f87171' ?>;">
                        R$ <?= number_format($resultado, 2, ',', '.') ?>
                    </div>
                    <div style="font-size:11px; color:#4b5563; margin-top:2px;">
                        Custo mín/sub: R$<?= number_format($custoMinSub,2,',','.') ?>
                    </div>
                </div>
            </div>

            <!-- Barra visual P&L -->
            <?php if ($receitaTotalMes > 0): ?>
            <div style="margin-top:8px;">
                <div style="display:flex; justify-content:space-between; font-size:11px; color:#6b7280; margin-bottom:4px;">
                    <span>Custos (<?= round(($totalCustos / $receitaTotalMes) * 100) ?>%)</span>
                    <span>Margem (<?= round(($resultado / $receitaTotalMes) * 100) ?>%)</span>
                </div>
                <div style="height:8px; background:#1e293b; border-radius:8px; overflow:hidden; display:flex;">
                    <div style="width:<?= min(100, round(($totalCustoInfraMes/$receitaTotalMes)*100)) ?>%; background:#ef4444; transition:width 0.4s;"></div>
                    <div style="width:<?= min(100-round(($totalCustoInfraMes/$receitaTotalMes)*100), round(($totalCustoIAbrl/$receitaTotalMes)*100)) ?>%; background:#f59e0b; transition:width 0.4s;"></div>
                    <?php if ($resultado > 0): ?>
                    <div style="flex:1; background:#10b981;"></div>
                    <?php endif; ?>
                </div>
                <div style="display:flex; gap:16px; font-size:11px; color:#6b7280; margin-top:6px;">
                    <span style="display:flex; align-items:center; gap:4px;"><span style="width:8px; height:8px; border-radius:2px; background:#ef4444; display:inline-block;"></span>Infra</span>
                    <span style="display:flex; align-items:center; gap:4px;"><span style="width:8px; height:8px; border-radius:2px; background:#f59e0b; display:inline-block;"></span>IA</span>
                    <span style="display:flex; align-items:center; gap:4px;"><span style="width:8px; height:8px; border-radius:2px; background:#10b981; display:inline-block;"></span>Resultado</span>
                </div>
            </div>
            <?php else: ?>
            <div style="font-size:13px; color:#4b5563; font-style:italic;">Sem receita registrada este mês.</div>
            <?php endif; ?>
        </div>

        <!-- ── Bloco 3: Custos de IA ─────────────────────────────────────── -->
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:28px;">
            <div class="card" style="padding:24px;">
                <h3 style="font-size:15px; font-weight:600; color:#e2e8f0; margin-bottom:16px;">Consumo de IA — <?= date('M/Y') ?></h3>
                <?php foreach ([
                    ['groq',   'Groq',   '#818cf8'],
                    ['gemini', 'Gemini', '#34d399'],
                ] as [$key, $label, $cor]): ?>
                <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size:13px;">
                    <span style="color:#94a3b8;"><?= $label ?></span>
                    <span style="color:<?= $cor ?>;">
                        <?= number_format($tokensPorProvider[$key]['tokens'] ?? 0, 0, ',', '.') ?> tokens
                        (USD <?= number_format($tokensPorProvider[$key]['custo_usd'] ?? 0, 4, ',', '.') ?>)
                    </span>
                </div>
                <?php endforeach; ?>
                <div style="border-top:1px solid #334155; padding-top:12px; font-size:13px; display:flex; justify-content:space-between;">
                    <span style="color:#6b7280;">Total</span>
                    <span style="color:#fbbf24; font-weight:700;"><?= number_format($totalTokens, 0, ',', '.') ?> tokens · R$<?= number_format($totalCustoIAbrl, 4, ',', '.') ?></span>
                </div>
                <?php if ($totalCustoIAbrl == 0): ?>
                <div style="margin-top:12px; font-size:12px; color:#4b5563; font-style:italic;">APIs em tier gratuito — custo zero por enquanto.</div>
                <?php endif; ?>
            </div>

            <!-- Top consumidores -->
            <div class="card" style="padding:24px;">
                <h3 style="font-size:15px; font-weight:600; color:#e2e8f0; margin-bottom:16px;">Top Usuários por Tokens</h3>
                <?php if (empty($topUsuarios)): ?>
                <div style="font-size:13px; color:#4b5563; font-style:italic;">Nenhum dado de IA registrado este mês.</div>
                <?php else: ?>
                <?php foreach ($topUsuarios as $u): ?>
                <div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:12px;">
                    <span style="color:#94a3b8; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:60%;">
                        <?= sanitizar($u['nome'] ?? $u['user_id']) ?>
                    </span>
                    <span style="color:#fbbf24; flex-shrink:0;"><?= number_format($u['total_tokens'], 0, ',', '.') ?> tk</span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Bloco 4: Custos de Infraestrutura ─────────────────────────── -->
        <div class="card" style="padding:24px; margin-bottom:28px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="font-size:15px; font-weight:600; color:#e2e8f0;">Custos de Infraestrutura</h3>
                <button @click="addCusto = !addCusto" class="btn-primary" style="padding:7px 16px; font-size:12px;">+ Adicionar</button>
            </div>

            <!-- Formulário de novo custo -->
            <div x-show="addCusto" x-cloak style="background:#1e293b; padding:16px; border-radius:8px; margin-bottom:16px;">
                <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end;">
                    <input type="hidden" name="acao" value="add_custo">
                    <div>
                        <label class="label" style="font-size:11px;">Descrição</label>
                        <input class="input" name="descricao" required placeholder="VPS Hetzner" style="width:160px;">
                    </div>
                    <div>
                        <label class="label" style="font-size:11px;">Valor</label>
                        <input class="input" type="number" step="0.01" name="valor" required placeholder="0.00" style="width:90px;">
                    </div>
                    <div>
                        <label class="label" style="font-size:11px;">Moeda</label>
                        <select class="input" name="moeda" style="width:70px;">
                            <option value="BRL">BRL</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div>
                        <label class="label" style="font-size:11px;">Período</label>
                        <select class="input" name="periodo" style="width:90px;">
                            <option value="mensal">Mensal</option>
                            <option value="anual">Anual</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary" style="padding:10px 20px; font-size:13px; height:42px;">Salvar</button>
                    <button type="button" @click="addCusto=false" class="btn-secondary" style="height:42px;">Cancelar</button>
                </form>
            </div>

            <?php if (empty($custos)): ?>
            <div style="font-size:13px; color:#4b5563; font-style:italic;">Nenhum custo cadastrado.</div>
            <?php else: ?>
            <table style="width:100%; font-size:13px; border-collapse:collapse;">
                <thead>
                    <tr style="color:#6b7280; font-size:11px; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid #334155;">
                        <th style="text-align:left; padding:8px 0;">Descrição</th>
                        <th style="text-align:right; padding:8px 0;">Valor</th>
                        <th style="text-align:right; padding:8px 0;">Período</th>
                        <th style="text-align:right; padding:8px 0;">Mês equiv.</th>
                        <th style="padding:8px 0;"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($custos as $c): ?>
                <?php $mesequiv = ($c['periodo'] === 'anual') ? ($c['valor'] / 12) : $c['valor']; ?>
                <tr style="border-bottom:1px solid #1e293b;">
                    <td style="padding:10px 0; color:#94a3b8;"><?= sanitizar($c['descricao']) ?></td>
                    <td style="text-align:right; color:#e2e8f0;"><?= $c['moeda'] ?> <?= number_format($c['valor'], 2, ',', '.') ?></td>
                    <td style="text-align:right; color:#6b7280;"><?= $c['periodo'] ?></td>
                    <td style="text-align:right; color:#94a3b8;">R$ <?= number_format($mesequiv, 2, ',', '.') ?></td>
                    <td style="text-align:right; padding-left:12px;">
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Remover este custo?')">
                            <input type="hidden" name="acao" value="del_custo">
                            <input type="hidden" name="custo_id" value="<?= $c['id'] ?>">
                            <button type="submit" style="background:none; border:none; color:#6b7280; cursor:pointer; font-size:12px;" title="Remover">✕</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="border-top:1px solid #334155;">
                        <td colspan="3" style="padding:10px 0; font-size:12px; color:#6b7280;">Total mensal</td>
                        <td style="text-align:right; color:#f87171; font-weight:700;">R$ <?= number_format($totalCustoInfraMes, 2, ',', '.') ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <?php endif; ?>
        </div>

        <!-- ── Bloco 5: Gestão de Preços ────────────────────────────────── -->
        <div class="card" style="padding:24px; margin-bottom:28px;">
            <h3 style="font-size:15px; font-weight:600; color:#e2e8f0; margin-bottom:4px;">Gestão de Preços & Tarifas</h3>
            <p style="font-size:12px; color:#6b7280; margin-bottom:20px;">Altera valores para novos assinantes. "Aplicar a todos" atualiza também os assinantes existentes (apenas referência interna — o Abacate Pay cobra o valor configurado lá).</p>

            <form method="POST">
                <input type="hidden" name="acao" value="salvar_precos">
                <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:16px; margin-bottom:20px;">
                    <div>
                        <label class="label">Plano Mensal (R$)</label>
                        <input class="input" type="number" step="0.01" name="plano_mensal_preco" value="<?= $precoMensal ?>" required>
                    </div>
                    <div>
                        <label class="label">Plano Anual (R$)</label>
                        <input class="input" type="number" step="0.01" name="plano_anual_preco" value="<?= $precoAnual ?>" required>
                    </div>
                    <div>
                        <label class="label">Câmbio USD → BRL</label>
                        <input class="input" type="number" step="0.01" name="custo_usd_brl" value="<?= $cambioUSD ?>" required>
                    </div>
                    <div>
                        <label class="label">Groq custo/1k tokens (USD)</label>
                        <input class="input" type="number" step="0.000001" name="groq_custo" value="<?= $custoGroq1k ?>" placeholder="0.000000">
                        <p style="font-size:11px; color:#4b5563; margin-top:4px;">0 = grátis / tier free</p>
                    </div>
                    <div>
                        <label class="label">Gemini custo/1k tokens (USD)</label>
                        <input class="input" type="number" step="0.000001" name="gemini_custo" value="<?= $custoGemini1k ?>" placeholder="0.000000">
                        <p style="font-size:11px; color:#4b5563; margin-top:4px;">0 = grátis / tier free</p>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                    <button type="submit" class="btn-primary">Salvar para novos</button>
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#94a3b8; cursor:pointer;">
                        <input type="checkbox" name="aplicar_todos" style="width:16px; height:16px;">
                        Aplicar também a todos os assinantes ativos
                    </label>
                </div>
            </form>
        </div>

    </main>
</div>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
