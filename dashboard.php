<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

exigirDistinto();

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

    <main id="main-content" class="content-sheet !bg-[#e0e2eb] dark:!bg-black !p-4 md:!p-6">
        <style>
            /* BENTO UI OVERRIDES */
            #main-content {
                min-height: 100vh;
                font-family: 'Outfit', sans-serif;
            }

            .bento-header {
                background-color: #f7f3da;
                border-radius: 32px;
                padding: 24px 32px;
            }

            .dark .bento-header {
                background-color: #111;
                border: 1px solid #222;
            }

            .bento-card {
                border-radius: 32px;
                padding: 28px;
                position: relative;
                overflow: hidden;
            }

            .bento-purple {
                background: linear-gradient(135deg, #b8abfb, #d4c4f9);
                color: #111;
            }

            .dark .bento-purple {
                background: linear-gradient(135deg, #382c73, #4a3875);
                color: #fff;
            }

            .bento-dark {
                background-color: #2b3342;
                color: #fff;
            }

            .dark .bento-dark {
                background-color: #111;
                border: 1px solid #222;
            }

            .bento-light {
                background-color: #f0f3f8;
                color: #111;
            }

            .dark .bento-light {
                background-color: #111;
                border: 1px solid #222;
                color: #eee;
            }

            .bento-child-blue {
                background-color: #6a5ff6;
                color: #fff;
                border-radius: 24px;
                padding: 24px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }

            .dark .bento-child-blue {
                background-color: #4a3fd6;
            }

            .bento-child-green {
                background-color: #a4c9b3;
                color: #1a2f22;
                border-radius: 24px;
                padding: 24px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }

            .dark .bento-child-green {
                background-color: #243b2f;
                color: #a4c9b3;
            }

            .bento-pill-white {
                background: #fff;
                border-radius: 999px;
                padding: 24px 32px;
                display: inline-flex;
                flex-direction: column;
                justify-content: center;
            }

            .dark .bento-pill-white {
                background: rgba(255, 255, 255, 0.05);
                color: #fff;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .bento-pill-dark {
                background: #2b3342;
                color: #fff;
                border-radius: 999px;
                padding: 24px 32px;
                display: inline-flex;
                flex-direction: column;
                justify-content: center;
            }

            .dark .bento-pill-dark {
                background: #000;
                border: 1px solid #222;
            }

            .bento-pill-chart {
                background: rgba(255, 255, 255, 0.4);
                border-radius: 24px;
                padding: 24px;
                backdrop-filter: blur(10px);
            }

            .dark .bento-pill-chart {
                background: rgba(0, 0, 0, 0.3);
            }

            .donut-chart {
                width: 220px;
                height: 220px;
                border-radius: 50%;
                background: conic-gradient(#10b981 0% 40%, #8b5cf6 40% 70%, #6366f1 70% 100%);
                mask-image: radial-gradient(transparent 58%, black 59%);
                -webkit-mask-image: radial-gradient(transparent 58%, black 59%);
            }

            .dark .donut-chart {
                background: conic-gradient(#10b981 0% 40%, #6d28d9 40% 70%, #4338ca 70% 100%);
            }

            .transaction-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }

            .transaction-icon {
                width: 44px;
                height: 44px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        </style>

        <?php include __DIR__ . '/includes/layout/top_nav.php'; ?>

        <!-- Bento Header -->
        <div class="bento-header flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-6">
            <div>
                <h1 class="text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-white mb-1">Dashboard</h1>
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Resumo financeiro e operacional da sua
                    agência</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>"
                    class="bg-black text-white dark:bg-white dark:text-black hover:scale-105 transition-transform px-5 py-2.5 rounded-full text-sm font-bold shadow-lg">
                    Novo Lançamento
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <a href="<?= raizUrl('/gerenciamento/clientes.php') ?>" class="bg-white/90 dark:bg-zinc-900/90 border border-zinc-200 dark:border-zinc-800 rounded-[2rem] p-5 shadow-sm group hover:bg-zinc-900 dark:hover:bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/20">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 group-hover:text-zinc-400 transition-colors">Clientes cadastrados</p>
                <p class="mt-4 text-3xl font-black text-zinc-900 dark:text-white group-hover:text-white dark:group-hover:text-black transition-colors"><?= $crmResumo['total_clientes'] ?? 0 ?></p>
            </a>
            <a href="<?= raizUrl('/gerenciamento/fornecedores.php') ?>" class="bg-white/90 dark:bg-zinc-900/90 border border-zinc-200 dark:border-zinc-800 rounded-[2rem] p-5 shadow-sm group hover:bg-zinc-900 dark:hover:bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/20">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 group-hover:text-zinc-400 transition-colors">Fornecedores cadastrados</p>
                <p class="mt-4 text-3xl font-black text-zinc-900 dark:text-white group-hover:text-white dark:group-hover:text-black transition-colors"><?= $crmResumo['total_fornecedores'] ?? 0 ?></p>
            </a>
            <a href="<?= raizUrl('/gerenciamento/oportunidades.php') ?>" class="bg-white/90 dark:bg-zinc-900/90 border border-zinc-200 dark:border-zinc-800 rounded-[2rem] p-5 shadow-sm group hover:bg-zinc-900 dark:hover:bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/20">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 group-hover:text-zinc-400 transition-colors">Oportunidades abertas</p>
                <p class="mt-4 text-3xl font-black text-zinc-900 dark:text-white group-hover:text-white dark:group-hover:text-black transition-colors"><?= $crmResumo['oportunidades_abertas'] ?? 0 ?></p>
            </a>
            <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>" class="bg-white/90 dark:bg-zinc-900/90 border border-zinc-200 dark:border-zinc-800 rounded-[2rem] p-5 shadow-sm group hover:bg-zinc-900 dark:hover:bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/20">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 group-hover:text-zinc-400 transition-colors">Total de Propostas</p>
                <p class="mt-4 text-3xl font-black text-zinc-900 dark:text-white group-hover:text-white dark:group-hover:text-black transition-colors"><?= $crmResumo['total_propostas'] ?? 0 ?></p>
            </a>
        </div>

        <div class="bg-white/90 dark:bg-zinc-900/90 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-5 shadow-sm mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-semibold text-zinc-600 dark:text-zinc-400">Pipeline de Oportunidades</p>
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Visão por estágio</h2>
                </div>
                <span class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">CRM</span>
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
                   class="relative bg-zinc-50 dark:bg-zinc-950/40 border border-transparent dark:border-white/5 rounded-[2rem] p-5 group hover:bg-zinc-900 dark:hover:bg-white transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-black/20 overflow-hidden">
                    
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 group-hover:text-zinc-400 transition-colors relative z-10"><?= $info['label'] ?></p>
                    <p class="mt-4 text-3xl font-black text-zinc-900 dark:text-white group-hover:text-white dark:group-hover:text-black transition-all relative z-10"><?= $crmPipeline[$key] ?? 0 ?></p>
                    
                    <!-- Efeito de brilho sutil no hover -->
                    <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bento Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

            <!-- Left Column -->
            <div class="flex flex-col gap-6">
                <!-- Revenue Forecast Card -->
                <div class="bento-card bento-purple">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-xl font-medium tracking-tight text-white">Fluxo de Caixa Mensal</h2>
                        <span
                            class="bg-black/5 hover:bg-black/10 cursor-pointer transition-colors text-black/60 px-4 py-1.5 rounded-full text-xs font-medium flex items-center gap-1">Filtro
                            <i data-lucide="chevron-down" style="width:12px;height:12px;"></i></span>
                    </div>

                    <div class="flex flex-col md:flex-row gap-4">
                        <!-- Left Column: Stacked Pills -->
                        <div class="flex flex-col gap-4 w-full md:w-[40%]">
                            <div class="bg-[#f2f4f7] rounded-[32px] p-6 min-h-[160px] flex flex-col justify-center">
                                <p class="text-sm font-medium text-zinc-500 mb-2">Receitas Mês</p>
                                <h3 class="text-4xl font-light tracking-tight text-[#1a1f36]">
                                    <?= formatarMoeda((float) $receitasMes) ?></h3>
                            </div>

                            <div class="bg-[#2b3342] rounded-[32px] p-6 min-h-[160px] flex flex-col justify-center">
                                <p class="text-sm font-medium text-zinc-400 mb-2">Saldo Atual</p>
                                <h3 class="text-4xl font-light tracking-tight text-white">
                                    <?= formatarMoeda((float) $saldoAtual) ?></h3>
                                <?php if ($resumo['debug_contas']): ?>
                                    <p class="text-[10px] text-zinc-500 mt-2 truncate opacity-50 hover:opacity-100 transition-opacity cursor-help"
                                        title="<?= htmlspecialchars($resumo['debug_contas']) ?>">Ver contas</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right Column: Chart -->
                        <div class="bg-[#f2f4f7] rounded-[32px] p-6 flex-1 flex flex-col justify-between min-h-[336px]">
                            <div class="flex justify-between items-start mb-6">
                                <span
                                    class="bg-[#7c5dfa] text-white text-[11px] font-medium px-3 py-1 rounded-lg">Live</span>
                                <div class="text-right">
                                    <h3 class="text-4xl font-light text-[#1a1f36]">
                                        <?= formatarMoeda((float) $resultadoPrev) ?></h3>
                                    <p class="text-[11px] font-medium text-zinc-500 mt-1">Saldo Previsto</p>
                                </div>
                            </div>

                            <?php if (array_sum(array_column($bars, 'valor')) > 0): ?>
                                <div class="h-[140px] flex items-end gap-2 mt-auto">
                                    <?php foreach ($bars as $data => $bar):
                                        // Real data calculation
                                        $heightTop = $maxBar > 0 && $bar['entradas'] > 0 ? max(4, (int) round(($bar['entradas'] / $maxBar) * 100)) : 0;
                                        $heightBottom = $maxBar > 0 && $bar['saidas'] > 0 ? max(4, (int) round(($bar['saidas'] / $maxBar) * 100)) : 0;
                                        $isEmpty = $heightTop == 0 && $heightBottom == 0;
                                        $hasBoth = $heightTop > 0 && $heightBottom > 0;
                                        ?>
                                        <div
                                            class="flex-1 flex flex-col items-center justify-end gap-[1px] group relative h-full">
                                            
                                            <!-- Tooltip -->
                                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-[#1a1f36] text-white text-[10px] py-2 px-3 rounded-xl opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none whitespace-nowrap z-10 shadow-lg scale-95 group-hover:scale-100">
                                                <p class="font-bold border-b border-white/10 pb-1 mb-1">Dia <?= $bar['dia'] ?></p>
                                                <p class="text-[#a4c9b3] font-medium">+ <?= formatarMoeda($bar['entradas']) ?></p>
                                                <p class="text-[#b8abfb] font-medium">- <?= formatarMoeda($bar['saidas']) ?></p>
                                            </div>

                                            <?php if ($isEmpty): ?>
                                                <div class="w-full rounded-full bg-zinc-300/50 dark:bg-zinc-700/50" style="height: 4px; max-width: 14px;"></div>
                                            <?php else: ?>
                                                <!-- Top part (Receitas - Green) -->
                                                <?php if ($heightTop > 0): ?>
                                                    <div class="w-full transition-all duration-300 bg-[#98b8a8] <?= $hasBoth ? 'rounded-t-full' : 'rounded-full' ?>"
                                                        style="height:<?= $heightTop ?>%; max-width: 14px;"></div>
                                                <?php endif; ?>
                                                <!-- Bottom part (Despesas - Purple) -->
                                                <?php if ($heightBottom > 0): ?>
                                                    <div class="w-full transition-all duration-300 bg-[#7c5dfa] <?= $hasBoth ? 'rounded-b-full' : 'rounded-full' ?>"
                                                        style="height:<?= $heightBottom ?>%; max-width: 14px;"></div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="flex justify-between mt-4 px-2">
                                    <span class="text-xs font-medium text-zinc-400">Dia 1</span>
                                    <span class="text-xs font-medium text-zinc-400">Dia <?= date('t') ?></span>
                                </div>
                            <?php else: ?>
                                <div
                                    class="h-full flex items-center justify-center opacity-50 text-sm font-bold text-zinc-500">
                                    Sem dados no período</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Spending By Category -->
                <div class="bg-[#212936] rounded-[32px] p-6 relative overflow-hidden">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-xl font-medium tracking-tight text-white/90">Despesa por Categoria</h2>
                        <span
                            class="bg-white/10 hover:bg-white/20 cursor-pointer transition-colors text-white/60 px-4 py-1.5 rounded-full text-xs font-medium flex items-center gap-1">Filtro
                            <i data-lucide="chevron-down" style="width:12px;height:12px;"></i></span>
                    </div>

                    <div class="flex items-center justify-center py-6">
                        <?php
                        $radius = 70;
                        $circumference = 2 * pi() * $radius;
                        $colors = ['#98b8a8', '#c4b5fd', '#38bdf8', '#7c5dfa', '#10b981'];
                        $offset = 0;
                        $gap = 12; // Gap between segments
                        ?>
                        <svg width="240" height="240" viewBox="0 0 200 200" style="transform: rotate(-90deg);">
                            <?php
                            if (count($gastosPorCategoria) > 0) {
                                $i = 0;
                                foreach ($gastosPorCategoria as $gasto):
                                    $pct = ($gasto['total'] / $totalGastosChart);
                                    $dash = $pct * $circumference;
                                    $color = $colors[$i % count($colors)];
                                    if ($dash > $gap) {
                                        $visibleDash = $dash - $gap;
                                        // Draw segment
                                        ?>
                                        <circle cx="100" cy="100" r="<?= $radius ?>" fill="none" stroke="<?= $color ?>"
                                            stroke-width="26" stroke-linecap="round"
                                            stroke-dasharray="<?= $visibleDash ?> <?= $circumference ?>"
                                            stroke-dashoffset="<?= -$offset ?>" class="transition-all duration-700 ease-out" />
                                        <?php

                                        // Text positioning
                                        $midAngle = (($offset + ($visibleDash / 2)) / $circumference) * 2 * pi();
                                        $textX = 100 + ($radius * cos($midAngle));
                                        $textY = 100 + ($radius * sin($midAngle));
                                        ?>
                                        <text x="<?= $textX ?>" y="<?= $textY ?>" fill="rgba(0,0,0,0.4)" font-size="9"
                                            font-weight="700" text-anchor="middle" dominant-baseline="middle"
                                            transform="rotate(90, <?= $textX ?>, <?= $textY ?>)">
                                            <?= round($pct * 100) ?>%
                                        </text>
                                        <?php
                                    }
                                    $offset += $dash;
                                    $i++;
                                endforeach;
                            } else {
                                ?>
                                <circle cx="100" cy="100" r="<?= $radius ?>" fill="none" stroke="#ffffff10"
                                    stroke-width="26" stroke-linecap="round" />
                                <?php
                            }
                            ?>
                        </svg>
                    </div>

                    <div class="flex flex-wrap justify-center gap-4 mt-2 px-2 pb-2">
                        <?php $i = 0;
                        foreach ($gastosPorCategoria as $gasto):
                            $color = $colors[$i % count($colors)]; ?>
                            <div class="flex items-center gap-1.5">
                                <div class="w-3 h-3 rounded-full" style="background: <?= $color ?>"></div>
                                <p class="text-[11px] font-medium text-white/70 capitalize">
                                    <?= htmlspecialchars($gasto['categoria']) ?></p>
                            </div>
                            <?php $i++; endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="flex flex-col gap-6">
                <!-- Savings / Pendentes -->
                <div class="bento-card bento-dark h-[300px]">
                    <h2 class="text-lg font-bold mb-6">Painel de Pendências</h2>

                    <div class="grid grid-cols-2 gap-4 h-full pb-8">
                        <div class="bento-child-blue">
                            <div>
                                <h3 class="text-lg font-bold leading-tight">A Receber<br><span
                                        class="font-normal opacity-80 text-sm">Neste mês</span></h3>
                            </div>
                            <div>
                                <p class="text-4xl font-light tracking-tight"><?= $qtdReceber ?: '0' ?></p>
                                <p class="text-sm font-bold mt-2 opacity-90"><?= formatarMoeda((float) $receberMes) ?>
                                </p>
                            </div>
                        </div>
                        <div class="bento-child-green">
                            <div>
                                <h3 class="text-lg font-bold leading-tight">A Pagar<br><span
                                        class="font-normal opacity-80 text-sm">Neste mês</span></h3>
                            </div>
                            <div>
                                <p class="text-4xl font-light tracking-tight"><?= $qtdPagar ?: '0' ?></p>
                                <p class="text-sm font-bold mt-2 opacity-90"><?= formatarMoeda((float) $pagarMes) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions -->
                <div class="bento-card bento-light flex-1">
                    <div class="flex justify-between items-start mb-8">
                        <h2 class="text-lg font-bold">Vencimentos Próximos</h2>
                        <span
                            class="bg-[#6a5ff6] text-white px-3 py-1 rounded-full text-[10px] font-bold uppercase">Atenção</span>
                    </div>

                    <div>
                        <?php
                        $todasProximas = array_merge($contasVencidas, $contasProximas);
                        // Remover duplicados (caso as vencidas estejam nos proximos)
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
                                <div class="transaction-item group">
                                    <div class="flex items-center gap-4 min-w-0">
                                        <div
                                            class="transaction-icon <?= $c['tipo'] === 'receber' ? 'bg-[#a4c9b3]/20 text-[#243b2f] dark:text-[#a4c9b3]' : 'bg-red-500/10 text-red-600' ?>">
                                            <i data-lucide="<?= $c['tipo'] === 'receber' ? 'arrow-down-to-line' : 'arrow-up-right' ?>"
                                                class="w-5 h-5"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-sm truncate"><?= sanitizar($c['descricao']) ?></p>
                                            <p class="text-[11px] font-bold opacity-50 uppercase tracking-wide mt-0.5">
                                                <?= $c['vencimento'] < $hoje ? '<span class="text-red-500">Vencido</span>' : formatarData($c['vencimento']) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <p
                                        class="font-extrabold text-base whitespace-nowrap pl-4 <?= $c['tipo'] === 'receber' ? 'text-[#10b981]' : '' ?>">
                                        <?= $c['tipo'] === 'receber' ? '+' : '-' ?>        <?= formatarMoeda((float) $c['valor']) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>"
                        class="block w-full text-center py-4 mt-4 text-xs font-bold uppercase tracking-widest opacity-40 hover:opacity-100 transition-opacity">Ver
                        todos</a>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/layout/footer.php'; ?>