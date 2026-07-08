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

$crmResumo = [
    'total_clientes' => 0,
    'total_fornecedores' => 0,
    'oportunidades_abertas' => 0,
    'propostas_com_oportunidade' => 0,
];
$crmPipeline = [
    'novo' => 0,
    'qualificado' => 0,
    'proposta' => 0,
    'negociacao' => 0,
    'ganha' => 0,
    'perdida' => 0,
];
try {
    $crmResumo = $db->query("SELECT 
        (SELECT COUNT(*) FROM clientes) as total_clientes,
        (SELECT COUNT(*) FROM fornecedores) as total_fornecedores,
        (SELECT COUNT(*) FROM oportunidades WHERE etapa NOT IN ('ganha', 'perdida')) as oportunidades_abertas,
        (SELECT COUNT(*) FROM propostas) as total_propostas,
        (SELECT COUNT(*) FROM propostas WHERE status = 'rascunho') as propostas_rascunho,
        (SELECT COUNT(*) FROM propostas WHERE status = 'pendente') as propostas_pendentes
    ")->fetch();

    foreach ($db->query("SELECT etapa, COUNT(*) as total FROM oportunidades GROUP BY etapa")->fetchAll() as $row) {
        if (isset($crmPipeline[$row['etapa']])) {
            $crmPipeline[$row['etapa']] = $row['total'];
        }
    }
    
    // Adicionar propostas pendentes que não estão no CRM (opcional, mas ajuda a ver volume)
    // Se o usuário quer ver no pipeline, 'rascunho' e 'proposta' são os estágios ideais
    $stmtOrfas = $db->query("SELECT status, COUNT(*) as total FROM propostas WHERE oportunidade_id IS NULL OR oportunidade_id = '' GROUP BY status");
    foreach ($stmtOrfas->fetchAll() as $row) {
        if ($row['status'] === 'pendente') {
            $crmPipeline['proposta'] += $row['total'];
        }
        if ($row['status'] === 'rascunho') {
            $crmPipeline['novo'] += $row['total'];
        }
    }
} catch (Exception $e) {
    // Se o CRM ainda não estiver configurado, exibimos valores zerados.
}

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
$stmtSaldoInicial = $db->query("SELECT id, nome, saldo_inicial FROM contas_bancarias WHERE ativo=1");
$contas = $stmtSaldoInicial->fetchAll();
$saldoInicialTotal = 0;
$debug_str = "";

$saldoAtual = 0;

foreach ($contas as $c) {
    $saldoInicialTotal += (float) $c['saldo_inicial'];
    $calc = $db->prepare("
        SELECT SUM(CASE WHEN tipo='receber' THEN valor_pago ELSE -valor_pago END) as fluxo
        FROM lancamentos 
        WHERE conta_id = ? AND valor_pago > 0
    ");
    $calc->execute([$c['id']]);
    $fluxo = (float) $calc->fetchColumn();
    $saldo_conta = (float) $c['saldo_inicial'] + $fluxo;
    $saldoAtual += $saldo_conta;

    $debug_str .= $c['nome'] . " (Inic: " . $c['saldo_inicial'] . " + Fluxo: " . $fluxo . " = " . $saldo_conta . ") | ";
}

// Para debugar na tela temporariamente, injetaremos o $debug_str no resumo
$resumo['debug_contas'] = $debug_str;

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
    if (!isset($bars[$row['data']]))
        continue;
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

// Gastos por categoria para o gráfico
$stmtCat = $db->prepare("
    SELECT categoria, SUM(valor_pago) as total
    FROM lancamentos
    WHERE tipo='pagar' AND valor_pago > 0 AND vencimento BETWEEN ? AND ?
    GROUP BY categoria
    ORDER BY total DESC
    LIMIT 4
");
$stmtCat->execute([$mesInicio, $mesFim]);
$gastosPorCategoria = $stmtCat->fetchAll();
$totalGastosChart = array_sum(array_column($gastosPorCategoria, 'total'));
if ($totalGastosChart == 0)
    $totalGastosChart = 1; // Evitar divisão por zero

include __DIR__ . '/includes/layout/head.php';
?>

<div id="app-wrapper">
    <?php include __DIR__ . '/includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet !bg-background !p-6 flex flex-col">
        <?php include __DIR__ . '/includes/layout/top_nav.php'; ?>

        <!-- Page Header -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-8 mt-2">
            <div>
                <h1 class="font-display-lg text-on-surface mb-1">Dashboard</h1>
                <p class="text-body-md text-on-surface-variant">Resumo financeiro e operacional da sua agência</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>"
                    class="bg-primary hover:bg-primary-container text-on-primary-container font-bold px-6 py-2.5 rounded-lg text-body-md transition-all active:scale-95 duration-150 shadow-lg">
                    Novo Lançamento
                </a>
            </div>
        </div>

        <!-- Metric Cards (Bento Style) -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-card-gap mb-6">
            <a href="<?= raizUrl('/gerenciamento/clientes.php') ?>" class="glass-card p-6 rounded-xl flex flex-col justify-between h-32 group">
                <span class="font-label-caps text-on-surface-variant uppercase">Clientes cadastrados</span>
                <div class="flex items-baseline justify-between">
                    <span class="text-3xl font-bold font-headline-md text-primary group-hover:scale-105 transition-transform"><?= $crmResumo['total_clientes'] ?? 0 ?></span>
                    <i data-lucide="users" class="w-6 h-6 text-primary-container/40"></i>
                </div>
            </a>
            <a href="<?= raizUrl('/gerenciamento/fornecedores.php') ?>" class="glass-card p-6 rounded-xl flex flex-col justify-between h-32 group">
                <span class="font-label-caps text-on-surface-variant uppercase">Fornecedores cadastrados</span>
                <div class="flex items-baseline justify-between">
                    <span class="text-3xl font-bold font-headline-md text-on-surface group-hover:scale-105 transition-transform"><?= $crmResumo['total_fornecedores'] ?? 0 ?></span>
                    <i data-lucide="shopping-bag" class="w-6 h-6 text-outline-variant/40"></i>
                </div>
            </a>
            <a href="<?= raizUrl('/gerenciamento/oportunidades.php') ?>" class="glass-card p-6 rounded-xl flex flex-col justify-between h-32 group">
                <span class="font-label-caps text-on-surface-variant uppercase">Oportunidades abertas</span>
                <div class="flex items-baseline justify-between">
                    <span class="text-3xl font-bold font-headline-md text-tertiary group-hover:scale-105 transition-transform"><?= $crmResumo['oportunidades_abertas'] ?? 0 ?></span>
                    <i data-lucide="trending-up" class="w-6 h-6 text-tertiary/40"></i>
                </div>
            </a>
            <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>" class="glass-card p-6 rounded-xl flex flex-col justify-between h-32 border-l-4 border-l-primary group">
                <span class="font-label-caps text-on-surface-variant uppercase">Total de Propostas</span>
                <div class="flex items-baseline justify-between">
                    <span class="text-3xl font-bold font-headline-md text-on-surface group-hover:scale-105 transition-transform"><?= $crmResumo['total_propostas'] ?? 0 ?></span>
                    <i data-lucide="file-text" class="w-6 h-6 text-primary/40"></i>
                </div>
            </a>
        </div>

        <!-- Pipeline Overview -->
        <div class="glass-card p-6 rounded-xl mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="font-label-caps text-on-surface-variant">Pipeline de Oportunidades</p>
                    <h2 class="text-title-sm font-headline-md text-on-surface mt-1">Visão por estágio</h2>
                </div>
                <span class="font-label-caps text-on-surface-variant opacity-60">CRM</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                <?php 
                $etapas = [
                    'novo' => ['label' => 'Novo', 'color' => 'zinc'],
                    'qualificado' => ['label' => 'Qualificado', 'color' => 'zinc'],
                    'proposta' => ['label' => 'Proposta', 'color' => 'zinc'],
                    'negociacao' => ['label' => 'Negociação', 'color' => 'zinc'],
                    'ganha' => ['label' => 'Ganha', 'color' => 'zinc'],
                    'perdida' => ['label' => 'Perdida', 'color' => 'zinc'],
                ];
                foreach ($etapas as $key => $info):
                ?>
                <a href="<?= raizUrl('/gerenciamento/oportunidades.php?etapa=' . $key) ?>" 
                   class="text-center p-3 rounded-lg bg-surface-container-low/30 border border-outline-variant/20 hover:border-primary/50 transition-all group">
                    <span class="block font-label-caps text-[9px] text-on-surface-variant mb-1 group-hover:text-primary transition-colors"><?= $info['label'] ?></span>
                    <span class="text-headline-md font-headline-md font-bold block text-on-surface"><?= $crmPipeline[$key] ?? 0 ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bento Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-card-gap mb-6">

            <!-- Left Column: Fluxo de Caixa e Despesas (Col-span 8) -->
            <div class="xl:col-span-8 flex flex-col gap-card-gap">
                <!-- Fluxo de Caixa -->
                <div class="glass-card luminous-gradient rounded-xl p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <p class="font-label-caps text-on-surface-variant">Fluxo de Caixa Mensal</p>
                            <h3 class="text-title-sm font-headline-md text-on-surface mt-1">Visão Geral</h3>
                        </div>
                        <span class="bg-primary/20 text-primary px-3 py-1 rounded text-[10px] font-bold">LIVE</span>
                    </div>

                    <div class="flex flex-col md:flex-row gap-card-gap">
                        <div class="flex flex-col gap-4 w-full md:w-1/3">
                            <div class="bg-surface-container-low/40 p-4 rounded-xl border border-outline-variant/10">
                                <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">Receitas Mês</p>
                                <p class="text-2xl font-bold font-data-tabular text-on-surface"><?= formatarMoeda((float) $receitasMes) ?></p>
                            </div>

                            <div class="bg-surface-container-low/40 p-4 rounded-xl border border-outline-variant/10">
                                <p class="font-label-caps text-[10px] text-on-surface-variant mb-1">Saldo Atual</p>
                                <p class="text-2xl font-bold font-data-tabular text-primary"><?= formatarMoeda((float) $saldoAtual) ?></p>
                                <?php if ($resumo['debug_contas']): ?>
                                    <a href="<?= raizUrl('/financeiro/contas.php') ?>" class="text-[10px] text-primary/60 hover:text-primary transition-colors block mt-2"
                                        title="<?= htmlspecialchars($resumo['debug_contas']) ?>">Ver contas</a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Graph -->
                        <div class="bg-surface-container-low/20 rounded-xl p-4 flex-1 flex flex-col justify-between border border-outline-variant/10 min-h-[220px]">
                            <div class="flex justify-between items-start mb-4">
                                <span class="bg-primary/10 text-primary text-[10px] font-bold px-2 py-0.5 rounded uppercase">Previsão</span>
                                <div class="text-right">
                                    <p class="text-2xl font-bold font-data-tabular text-on-surface"><?= formatarMoeda((float) $resultadoPrev) ?></p>
                                    <p class="font-label-caps text-[9px] text-on-surface-variant mt-1">Saldo Previsto</p>
                                </div>
                            </div>

                            <?php if (array_sum(array_column($bars, 'valor')) > 0): ?>
                                <div class="h-[120px] flex items-end gap-1.5 mt-auto relative">
                                    <?php foreach ($bars as $data => $bar):
                                        $heightTop = $maxBar > 0 && $bar['entradas'] > 0 ? max(4, (int) round(($bar['entradas'] / $maxBar) * 100)) : 0;
                                        $heightBottom = $maxBar > 0 && $bar['saidas'] > 0 ? max(4, (int) round(($bar['saidas'] / $maxBar) * 100)) : 0;
                                        $isEmpty = $heightTop == 0 && $heightBottom == 0;
                                        $hasBoth = $heightTop > 0 && $heightBottom > 0;
                                        ?>
                                        <div class="flex-1 flex flex-col items-center justify-end gap-[1px] group relative h-full cursor-pointer">
                                            
                                            <!-- Tooltip -->
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-[#1a1f36] text-white text-[10px] py-2 px-3 rounded-xl opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none whitespace-nowrap z-10 shadow-lg scale-95 group-hover:scale-100">
                                                <p class="font-bold border-b border-white/10 pb-1 mb-1">Dia <?= $bar['dia'] ?></p>
                                                <p class="text-[#10b981] font-medium">+ <?= formatarMoeda($bar['entradas']) ?></p>
                                                <p class="text-[#7c5cff] font-medium">- <?= formatarMoeda($bar['saidas']) ?></p>
                                            </div>

                                            <?php if ($isEmpty): ?>
                                                <div class="w-full rounded-full bg-outline-variant/30" style="height: 4px; max-width: 12px;"></div>
                                            <?php else: ?>
                                                <!-- Top part (Receitas - Green) -->
                                                <?php if ($heightTop > 0): ?>
                                                    <div class="w-full transition-all duration-300 bg-[#10b981] <?= $hasBoth ? 'rounded-t-full' : 'rounded-full' ?>"
                                                        style="height:<?= $heightTop ?>%; max-width: 12px;"></div>
                                                <?php endif; ?>
                                                <!-- Bottom part (Despesas - Purple) -->
                                                <?php if ($heightBottom > 0): ?>
                                                    <div class="w-full transition-all duration-300 bg-[#7c5cff] <?= $hasBoth ? 'rounded-b-full' : 'rounded-full' ?>"
                                                        style="height:<?= $heightBottom ?>%; max-width: 12px;"></div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="w-full h-[1px] bg-outline-variant/30 absolute bottom-0 left-0"></div>
                                </div>
                                <div class="flex justify-between mt-2 text-[9px] text-on-surface-variant font-bold">
                                    <span>DIA 1</span>
                                    <span>DIA <?= date('t') ?></span>
                                </div>
                            <?php else: ?>
                                <div class="h-full flex items-center justify-center opacity-50 text-sm font-bold text-zinc-500">Sem dados no período</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Spending By Category -->
                <div class="glass-card p-6 rounded-xl">
                    <h3 class="text-title-sm font-headline-md text-on-surface mb-6">Despesa por Categoria</h3>
                    
                    <div class="flex flex-col md:flex-row items-center justify-around gap-6">
                        <div class="relative w-40 h-40">
                            <!-- Donut Chart -->
                            <svg class="w-full h-full transform -rotate-90">
                                <circle cx="80" cy="80" fill="transparent" r="64" stroke="#1f1f22" stroke-width="12"></circle>
                                <?php
                                $radius = 64;
                                $circumference = 2 * pi() * $radius;
                                $colors = ['#7c5cff', '#cabeff', '#ffb780', '#10b981', '#ef4444'];
                                $offset = 0;
                                $i = 0;
                                if (count($gastosPorCategoria) > 0) {
                                    foreach ($gastosPorCategoria as $gasto) {
                                        $pct = ($gasto['total'] / $totalGastosChart);
                                        $dash = $pct * $circumference;
                                        $color = $colors[$i % count($colors)];
                                        ?>
                                        <circle cx="80" cy="80" fill="transparent" r="64" stroke="<?= $color ?>" stroke-width="12"
                                                stroke-dasharray="<?= $dash ?> <?= $circumference ?>"
                                                stroke-dashoffset="<?= -$offset ?>" class="transition-all duration-500" />
                                        <?php
                                        $offset += $dash;
                                        $i++;
                                    }
                                }
                                ?>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold font-headline-md leading-none">
                                    <?= count($gastosPorCategoria) > 0 ? '94%' : '0%' ?>
                                </span>
                                <span class="font-label-caps text-[9px] text-on-surface-variant mt-1">TOTAL</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full md:w-auto flex-1 max-w-md">
                            <?php $i = 0;
                            foreach ($gastosPorCategoria as $gasto):
                                $color = $colors[$i % count($colors)]; 
                                $pctGasto = ($gasto['total'] / $totalGastosChart) * 100;
                                ?>
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-surface-container-low/20 border border-outline-variant/10">
                                    <div class="w-2.5 h-2.5 rounded-full shrink-0" style="background: <?= $color ?>"></div>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-label-caps text-[10px] text-on-surface-variant truncate"><?= htmlspecialchars($gasto['categoria']) ?></p>
                                        <p class="font-data-tabular font-bold text-on-surface mt-0.5"><?= formatarMoeda((float)$gasto['total']) ?></p>
                                    </div>
                                    <span class="font-data-tabular text-xs text-on-surface-variant shrink-0"><?= round($pctGasto) ?>%</span>
                                </div>
                            <?php $i++; endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Pendências e Vencimentos (Col-span 4) -->
            <div class="xl:col-span-4 flex flex-col gap-card-gap">
                <!-- Pendências -->
                <div class="glass-card p-6 rounded-xl">
                    <h3 class="text-title-sm font-headline-md text-on-surface mb-6">Painel de Pendências</h3>
                    <div class="space-y-4">
                        <div class="bg-primary-container/10 p-4 rounded-xl border border-primary/20 relative overflow-hidden flex flex-col justify-between min-h-[110px]">
                            <div>
                                <p class="font-label-caps text-[10px] text-primary">A Receber</p>
                                <p class="text-3xl font-light font-data-tabular mt-2 text-on-surface"><?= $qtdReceber ?: '0' ?></p>
                            </div>
                            <p class="font-data-tabular text-xs mt-2 text-primary font-bold"><?= formatarMoeda((float) $receberMes) ?></p>
                        </div>
                        <div class="bg-error-container/10 p-4 rounded-xl border border-error/20 relative overflow-hidden flex flex-col justify-between min-h-[110px]">
                            <div>
                                <p class="font-label-caps text-[10px] text-error font-bold">A Pagar</p>
                                <p class="text-3xl font-light font-data-tabular mt-2 text-on-surface"><?= $qtdPagar ?: '0' ?></p>
                            </div>
                            <p class="font-data-tabular text-xs mt-2 text-error font-bold"><?= formatarMoeda((float) $pagarMes) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="glass-card p-6 rounded-xl flex-1 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-title-sm font-headline-md text-on-surface">Vencimentos Próximos</h3>
                            <span class="bg-error/10 text-error px-2.5 py-0.5 rounded text-[10px] font-bold uppercase">Atenção</span>
                        </div>

                        <div class="space-y-4">
                            <?php
                            $todasProximas = array_merge($contasVencidas, $contasProximas);
                            $ids = [];
                            $listaUnica = [];
                            foreach ($todasProximas as $c) {
                                if (!in_array($c['id'], $ids)) {
                                    $ids[] = $c['id'];
                                    $listaUnica[] = $c;
                                }
                            }
                            usort($listaUnica, fn($a, $b) => strtotime($a['vencimento']) - strtotime($b['vencimento']));
                            $listaUnica = array_slice($listaUnica, 0, 5);
                            ?>

                            <?php if (empty($listaUnica)): ?>
                                <div class="py-10 text-center opacity-50 font-bold">Nenhum vencimento próximo.</div>
                            <?php else: ?>
                                <?php foreach ($listaUnica as $c): ?>
                                    <div class="flex items-center justify-between p-2.5 rounded-lg hover:bg-surface-container-high/35 transition-all">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 <?= $c['tipo'] === 'receber' ? 'bg-[#10b981]/10 text-[#10b981]' : 'bg-error/10 text-error' ?>">
                                                <i data-lucide="<?= $c['tipo'] === 'receber' ? 'arrow-down-left' : 'arrow-up-right' ?>" class="w-4 h-4"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-bold text-sm truncate"><?= sanitizar($c['descricao']) ?></p>
                                                <p class="font-label-caps text-[9px] text-on-surface-variant mt-0.5">
                                                    <?= $c['vencimento'] < $hoje ? '<span class="text-error font-bold">Vencido</span>' : formatarData($c['vencimento']) ?>
                                                </p>
                                            </div>
                                        </div>
                                        <p class="font-data-tabular font-bold text-sm whitespace-nowrap pl-2 <?= $c['tipo'] === 'receber' ? 'text-[#10b981]' : 'text-on-surface' ?>">
                                            <?= $c['tipo'] === 'receber' ? '+' : '-' ?> <?= formatarMoeda((float) $c['valor']) ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>"
                        class="block w-full text-center py-3 mt-6 border-t border-outline-variant/30 font-label-caps text-[10px] text-on-surface-variant hover:text-primary transition-colors">
                        Ver todos
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/layout/footer.php'; ?>
