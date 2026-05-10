<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

exigirAutenticacao();

$tituloPagina = 'Dashboard';

$db = Database::get();
$hoje = date('Y-m-d');
$mesInicio = date('Y-m-01');
$mesFim = date('Y-m-t');

$queryResumo = $db->prepare("
    SELECT
        SUM(CASE WHEN tipo='receber' AND status='pago' THEN valor_pago ELSE 0 END) as total_recebido,
        SUM(CASE WHEN tipo='pagar' AND status='pago' THEN valor_pago ELSE 0 END) as total_pago,
        SUM(CASE WHEN tipo='receber' AND vencimento BETWEEN ? AND ? AND status='pago' THEN valor_pago ELSE 0 END) as receitas_mes,
        SUM(CASE WHEN tipo='pagar'   AND vencimento BETWEEN ? AND ? AND status='pago' THEN valor_pago ELSE 0 END) as despesas_mes,
        SUM(CASE WHEN tipo='receber' AND vencimento BETWEEN ? AND ? AND status NOT IN ('pago','cancelado') THEN (valor - valor_pago) ELSE 0 END) as receber_mes,
        SUM(CASE WHEN tipo='pagar'   AND vencimento BETWEEN ? AND ? AND status NOT IN ('pago','cancelado') THEN (valor - valor_pago) ELSE 0 END) as pagar_mes
    FROM lancamentos WHERE status != 'cancelado'
");
$queryResumo->execute([$mesInicio, $mesFim, $mesInicio, $mesFim, $mesInicio, $mesFim, $mesInicio, $mesFim]);
$resumo = $queryResumo->fetch();

// Cálculo do Saldo Atual baseado na soma real dos bancos
$stmtSaldoInicial = $db->query("SELECT SUM(saldo_inicial) FROM contas_bancarias WHERE ativo=1");
$saldoInicialTotal = (float)$stmtSaldoInicial->fetchColumn() ?: 0;

$stmtFluxoBancos = $db->query("
    SELECT SUM(CASE WHEN l.tipo='receber' THEN l.valor_pago ELSE -l.valor_pago END) 
    FROM lancamentos l
    INNER JOIN contas_bancarias c ON l.conta_id = c.id
    WHERE l.status IN ('pago', 'efetivado') AND c.ativo = 1
");
$fluxoBancos = (float)$stmtFluxoBancos->fetchColumn() ?: 0;

$saldoAtual = $saldoInicialTotal + $fluxoBancos;

$receitasMes = $resumo['receitas_mes'] ?? 0;
$despesasMes = $resumo['despesas_mes'] ?? 0;
$receberMes = $resumo['receber_mes'] ?? 0;
$pagarMes = $resumo['pagar_mes'] ?? 0;
$resultadoPrev = $saldoAtual + $receberMes - $pagarMes;

// Contagem de depoimentos por categoria para o card do dashboard
$depoimentosResumo = [];
try {
    $stmtDep = $db->query("SELECT categoria, COUNT(*) as total, SUM(ativo) as ativos FROM depoimentos GROUP BY categoria");
    foreach ($stmtDep->fetchAll() as $row) {
        $depoimentosResumo[$row['categoria']] = $row;
    }
} catch (Exception $e) {
    // Tabela ainda não existe — rode setup/migration_depoimentos.php
}
$totalDepoimentos = array_sum(array_column($depoimentosResumo, 'total'));
$totalAtivos = array_sum(array_column($depoimentosResumo, 'ativos'));

// Buscar contagem de itens para os KPIs
$stmtQtdRec = $db->prepare("SELECT COUNT(*) FROM lancamentos WHERE tipo='receber' AND vencimento BETWEEN ? AND ? AND status NOT IN ('pago','cancelado')");
$stmtQtdRec->execute([$mesInicio, $mesFim]);
$qtdReceber = $stmtQtdRec->fetchColumn();

$stmtQtdPag = $db->prepare("SELECT COUNT(*) FROM lancamentos WHERE tipo='pagar' AND vencimento BETWEEN ? AND ? AND status NOT IN ('pago','cancelado')");
$stmtQtdPag->execute([$mesInicio, $mesFim]);
$qtdPagar = $stmtQtdPag->fetchColumn();

$vencidas = $db->prepare("
    SELECT id, tipo, descricao, valor, valor_pago, vencimento, cliente_fornecedor
    FROM lancamentos
    WHERE vencimento < ? AND status IN ('pendente','pago_parcial','atrasado')
    ORDER BY vencimento ASC LIMIT 10
");
$vencidas->execute([$hoje]);
$contasVencidas = $vencidas->fetchAll();

$seteDias = date('Y-m-d', strtotime('+7 days'));
$proximas = $db->prepare("
    SELECT id, tipo, descricao, valor, valor_pago, vencimento, cliente_fornecedor
    FROM lancamentos
    WHERE vencimento BETWEEN ? AND ? AND status IN ('pendente','pago_parcial')
    ORDER BY vencimento ASC LIMIT 10
");
$proximas->execute([$hoje, $seteDias]);
$contasProximas = $proximas->fetchAll();

$fluxoMes = $db->prepare("
    SELECT DATE(vencimento) as data,
           SUM(CASE WHEN tipo='receber' THEN valor ELSE 0 END) as entradas,
           SUM(CASE WHEN tipo='pagar'   THEN valor ELSE 0 END) as saidas
    FROM lancamentos
    WHERE vencimento BETWEEN ? AND ?
      AND status != 'cancelado'
    GROUP BY DATE(vencimento)
    ORDER BY data ASC
");
$fluxoMes->execute([$mesInicio, $mesFim]);
$dadosFluxo = $fluxoMes->fetchAll();

$bars = [];
$maxBar = 1;

$dInicio = new DateTime($mesInicio);
$dFim = new DateTime($mesFim);
while ($dInicio <= $dFim) {
    $data = $dInicio->format('Y-m-d');
    $bars[$data] = [
        'dia' => (int) $dInicio->format('d'),
        'valor' => 0,
        'entradas' => 0,
        'saidas' => 0,
    ];
    $dInicio->modify('+1 day');
}

foreach ($dadosFluxo as $row) {
    if (!isset($bars[$row['data']])) continue;
    $valor = (float) $row['entradas'] + (float) $row['saidas'];
    $bars[$row['data']]['valor'] = $valor;
    $bars[$row['data']]['entradas'] = (float) $row['entradas'];
    $bars[$row['data']]['saidas'] = (float) $row['saidas'];
    $maxBar = max($maxBar, $valor);
}

$kpis = [
    ['label' => 'Saldo Atual', 'value' => $saldoAtual, 'trend' => 'Caixa real', 'up' => $saldoAtual >= 0],
    ['label' => 'Receitas (Mês Realizado)', 'value' => $receitasMes, 'trend' => 'Efetivado', 'up' => true],
    ['label' => 'Despesas (Mês Realizado)', 'value' => $despesasMes, 'trend' => 'Efetivado', 'up' => false],
    ['label' => 'A Receber (Restante Mês)', 'value' => $receberMes, 'trend' => 'Pendente', 'up' => true],
    ['label' => 'A Pagar (Restante Mês)', 'value' => $pagarMes, 'trend' => 'Pendente', 'up' => false],
    ['label' => 'Resultado Previsto', 'value' => $resultadoPrev, 'trend' => 'Final do Mês', 'up' => $resultadoPrev >= 0],
];

include __DIR__ . '/includes/layout/head.php';
?>

<div id="app-wrapper">
    <?php include __DIR__ . '/includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet">
        <div class="app-topbar">
            <div class="top-nav">
                <a href="<?= raizUrl('/dashboard.php') ?>">Visao geral</a>
                <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>">Lancamentos</a>
                <a href="<?= raizUrl('/financeiro/configuracoes.php') ?>">Custos fixos</a>
                <a href="<?= raizUrl('/precificacao/servicos.php') ?>">Servicos</a>
                <a href="<?= raizUrl('/precificacao/simulador.php') ?>">Simulador</a>
            </div>
            <div style="display:flex; align-items:center; gap:10px; color:#777777; font-size:12px; font-weight:700;">
                <span>PT-BR</span>
                <span><?= sanitizar(usuarioAtual()['email']) ?></span>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5 mb-5">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Resumo financeiro da sua agencia com os dados atuais do sistema.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>" class="btn-primary">
                    <i data-lucide="receipt-text" class="w-4 h-4"></i>
                    Ver lancamentos
                </a>
                <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>" class="btn-secondary">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Novo lancamento
                </a>
            </div>
        </div>

        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3 mb-5">
            <?php foreach ($kpis as $kpi): ?>
                <article class="card p-6 min-h-[116px] flex flex-col justify-between">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[12px] font-bold text-zinc-500"><?= $kpi['label'] ?></p>
                            <h2 class="mt-2 text-[28px] leading-none font-extrabold tracking-[-0.04em] text-zinc-950">
                                <?= formatarMoeda((float) $kpi['value']) ?>
                            </h2>
                        </div>
                        <span class="<?= $kpi['up'] ? 'trend-up' : 'trend-down' ?>">
                            <i data-lucide="<?= $kpi['up'] ? 'trending-up' : 'trending-down' ?>" class="w-3 h-3"></i>
                            <?= $kpi['trend'] ?>
                        </span>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="card p-5 lg:p-7 mb-5">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-8">
                <div>
                    <p class="text-[12px] font-bold text-zinc-500">Fluxo de Caixa (Mês Atual)</p>
                    <h2 class="mt-1 text-[24px] font-extrabold tracking-[-0.04em] text-zinc-950">
                        <?= formatarMoeda((float) array_sum(array_column($bars, 'valor'))) ?>
                    </h2>
                    <p class="mt-1 text-xs font-medium text-zinc-400">Movimentações efetivadas e previstas do dia 1 ao fim do mês.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button class="btn-secondary" style="min-height:32px; padding:7px 10px;">
                        <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                    </button>
                    <button class="btn-primary" style="min-height:32px; padding:7px 13px;">30 dias</button>
                    <button class="btn-secondary" style="min-height:32px; padding:7px 13px;">Mes</button>
                </div>
            </div>

            <?php if (array_sum(array_column($bars, 'valor')) <= 0): ?>
                <div class="h-[220px] flex flex-col items-center justify-center text-zinc-400">
                    <i data-lucide="bar-chart-3" class="w-11 h-11 mb-3 opacity-25"></i>
                    <p class="text-sm font-bold">Nenhum lancamento previsto no periodo</p>
                </div>
            <?php else: ?>
                <div class="h-[240px] flex items-end gap-2 border-b border-zinc-100 pb-4 overflow-x-auto">
                    <?php foreach ($bars as $data => $bar):
                        $height = $bar['valor'] > 0 ? max(18, (int) round(($bar['valor'] / $maxBar) * 128)) : 6;
                        $isToday = $data === $hoje;
                    ?>
                        <div class="min-w-[24px] flex-1 flex flex-col items-center justify-end gap-2">
                            <div
                                title="<?= formatarData($data) ?> - <?= formatarMoeda((float) $bar['valor']) ?>"
                                style="height:<?= $height ?>px;"
                                class="w-full max-w-[28px] rounded-md <?= $isToday ? 'bg-white border-2 border-zinc-950 bg-[repeating-linear-gradient(135deg,#111_0,#111_2px,#fff_2px,#fff_5px)]' : ($bar['valor'] > 0 ? 'bg-zinc-950' : 'bg-zinc-200') ?>">
                            </div>
                            <span class="text-[10px] font-bold <?= $isToday ? 'text-zinc-950' : 'text-zinc-400' ?>"><?= $bar['dia'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <article class="card p-5">
                <div class="flex items-start justify-between gap-3 mb-7">
                    <div>
                        <p class="text-[12px] font-bold text-zinc-500">Resultado previsto</p>
                        <h3 class="mt-1 text-[22px] font-extrabold tracking-[-0.04em] text-zinc-950"><?= formatarMoeda((float) $resultadoPrev) ?></h3>
                        <p class="text-xs text-zinc-400 mt-1">Recebimentos menos pagamentos em aberto.</p>
                    </div>
                    <span class="<?= $resultadoPrev >= 0 ? 'trend-up' : 'trend-down' ?>"><?= $resultadoPrev >= 0 ? '+OK' : 'Risco' ?></span>
                </div>
                <div class="h-[98px] flex items-end gap-3">
                    <?php
                    $quarters = [$saldoAtual, $receberMes, $pagarMes, abs($resultadoPrev)];
                    $maxQuarter = max(array_map('abs', $quarters)) ?: 1;
                    foreach ($quarters as $idx => $value):
                        $h = max(22, (int) round((abs($value) / $maxQuarter) * 84));
                    ?>
                        <div class="flex-1 rounded-lg <?= $idx === 3 ? 'bg-zinc-900' : 'bg-zinc-100' ?>" style="height:<?= $h ?>px;"></div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="card p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-extrabold text-zinc-950">Vencidos</h3>
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-500"></i>
                </div>
                <div class="space-y-3">
                    <?php if (empty($contasVencidas)): ?>
                        <p class="py-8 text-center text-xs font-bold text-zinc-400">Tudo em dia.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($contasVencidas, 0, 4) as $c): ?>
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-100 bg-zinc-50 px-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-extrabold text-zinc-900"><?= sanitizar($c['descricao']) ?></p>
                                    <p class="text-[11px] font-medium text-zinc-400"><?= formatarData($c['vencimento']) ?></p>
                                </div>
                                <p class="text-xs font-extrabold <?= $c['tipo'] === 'receber' ? 'text-emerald-600' : 'text-red-600' ?>">
                                    <?= formatarMoeda((float) $c['valor']) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>

            <article class="card p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-extrabold text-zinc-950">Proximos 7 dias</h3>
                    <i data-lucide="calendar-days" class="w-4 h-4 text-zinc-500"></i>
                </div>
                <div class="space-y-3">
                    <?php if (empty($contasProximas)): ?>
                        <p class="py-8 text-center text-xs font-bold text-zinc-400">Sem vencimentos proximos.</p>
                    <?php else: ?>
                        <?php foreach (array_slice($contasProximas, 0, 4) as $c): ?>
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-zinc-100 bg-zinc-50 px-3 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-extrabold text-zinc-900"><?= sanitizar($c['descricao']) ?></p>
                                    <p class="text-[11px] font-medium text-zinc-400"><?= formatarData($c['vencimento']) ?></p>
                                </div>
                                <p class="text-xs font-extrabold <?= $c['tipo'] === 'receber' ? 'text-emerald-600' : 'text-red-600' ?>">
                                    <?= formatarMoeda((float) $c['valor']) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>
        </section>

        <!-- Card: Depoimentos -->
        <section class="card p-5 mt-5">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <p class="text-[12px] font-bold text-zinc-500">Prova Social</p>
                    <h3 class="mt-1 text-[20px] font-extrabold tracking-[-0.04em] text-zinc-950">Depoimentos</h3>
                </div>
                <a href="<?= raizUrl('/gerenciamento/depoimentos.php') ?>" class="btn-secondary" style="min-height:32px; padding:6px 14px; font-size:12px;">
                    <i data-lucide="settings-2" class="w-3.5 h-3.5"></i>
                    Gerenciar
                </a>
            </div>

            <?php if (empty($depoimentosResumo)): ?>
                <div class="flex flex-col items-center justify-center py-8 text-zinc-400">
                    <i data-lucide="message-square-quote" class="w-8 h-8 mb-2 opacity-30"></i>
                    <p class="text-xs font-bold">Nenhum depoimento cadastrado</p>
                    <a href="<?= raizUrl('/setup/migration_depoimentos.php') ?>" class="mt-3 text-xs font-bold text-zinc-500 underline">
                        Rodar migration
                    </a>
                </div>
            <?php else: ?>
                <?php
                $catLabels = [
                    'casamento' => ['label' => 'Casamento',        'cor' => '#f59e0b'],
                    'filmmaker' => ['label' => 'Filmmaker',         'cor' => '#8b5cf6'],
                    '15anos'    => ['label' => '15 Anos',           'cor' => '#ec4899'],
                    'marketing' => ['label' => 'Marketing Digital', 'cor' => '#10b981'],
                ];
                ?>
                <div class="grid grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
                    <?php foreach ($catLabels as $cat => $info): ?>
                    <?php $d = $depoimentosResumo[$cat] ?? ['total' => 0, 'ativos' => 0]; ?>
                    <a href="<?= raizUrl('/gerenciamento/depoimentos.php') ?>?cat=<?= $cat ?>"
                        class="rounded-xl border border-zinc-100 bg-zinc-50 p-4 hover:bg-zinc-100 transition-all">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:<?= $info['cor'] ?>;"></span>
                            <span class="text-[11px] font-bold text-zinc-500 uppercase tracking-wide"><?= $info['label'] ?></span>
                        </div>
                        <p class="text-2xl font-extrabold text-zinc-900 tracking-tight"><?= $d['total'] ?></p>
                        <p class="text-[11px] text-zinc-400 mt-0.5">
                            <?= $d['ativos'] ?> ativo<?= $d['ativos'] != 1 ? 's' : '' ?>
                        </p>
                    </a>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-zinc-400">
                    Total: <strong class="text-zinc-700"><?= $totalDepoimentos ?></strong> depoimentos &mdash;
                    <strong class="text-emerald-600"><?= $totalAtivos ?></strong> ativos no momento.
                </p>
            <?php endif; ?>
        </section>
    </main>
</div>

<?php include __DIR__ . '/includes/layout/footer.php'; ?>
