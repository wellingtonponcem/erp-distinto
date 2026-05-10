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

    <main id="main-content" class="content-sheet !bg-[#e0e2eb] dark:!bg-black !p-4 md:!p-6">
        <style>
            /* BENTO UI OVERRIDES */
            #main-content { min-height: 100vh; font-family: 'Outfit', sans-serif; }
            .bento-header { background-color: #f7f3da; border-radius: 32px; padding: 24px 32px; }
            .dark .bento-header { background-color: #111; border: 1px solid #222; }
            
            .bento-card { border-radius: 32px; padding: 28px; position: relative; overflow: hidden; }
            
            .bento-purple { background: linear-gradient(135deg, #b8abfb, #d4c4f9); color: #111; }
            .dark .bento-purple { background: linear-gradient(135deg, #382c73, #4a3875); color: #fff; }
            
            .bento-dark { background-color: #2b3342; color: #fff; }
            .dark .bento-dark { background-color: #111; border: 1px solid #222; }
            
            .bento-light { background-color: #f0f3f8; color: #111; }
            .dark .bento-light { background-color: #111; border: 1px solid #222; color: #eee; }
            
            .bento-child-blue { background-color: #6a5ff6; color: #fff; border-radius: 24px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between; }
            .dark .bento-child-blue { background-color: #4a3fd6; }
            
            .bento-child-green { background-color: #a4c9b3; color: #1a2f22; border-radius: 24px; padding: 24px; display: flex; flex-direction: column; justify-content: space-between; }
            .dark .bento-child-green { background-color: #243b2f; color: #a4c9b3; }

            .bento-pill-white { background: #fff; border-radius: 999px; padding: 24px 32px; display: inline-flex; flex-direction: column; justify-content: center;}
            .dark .bento-pill-white { background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); }
            
            .bento-pill-dark { background: #2b3342; color: #fff; border-radius: 999px; padding: 24px 32px; display: inline-flex; flex-direction: column; justify-content: center;}
            .dark .bento-pill-dark { background: #000; border: 1px solid #222; }
            
            .bento-pill-chart { background: rgba(255,255,255,0.4); border-radius: 24px; padding: 24px; backdrop-filter: blur(10px); }
            .dark .bento-pill-chart { background: rgba(0,0,0,0.3); }

            .donut-chart {
                width: 220px; height: 220px; border-radius: 50%;
                background: conic-gradient(#10b981 0% 40%, #8b5cf6 40% 70%, #6366f1 70% 100%);
                mask-image: radial-gradient(transparent 58%, black 59%);
                -webkit-mask-image: radial-gradient(transparent 58%, black 59%);
            }
            .dark .donut-chart { background: conic-gradient(#10b981 0% 40%, #6d28d9 40% 70%, #4338ca 70% 100%); }
            
            .transaction-item { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
            .transaction-icon { width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; }
            
            .top-nav-bento { background: #fff; border-radius: 999px; padding: 6px; display: inline-flex; gap: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
            .dark .top-nav-bento { background: #111; border: 1px solid #222; }
            .top-nav-bento a { padding: 10px 18px; font-size: 13px; font-weight: 600; color: #555; border-radius: 999px; transition: 0.2s; }
            .dark .top-nav-bento a { color: #aaa; }
            .top-nav-bento a:hover { background: #f0f0f0; color: #111; }
            .dark .top-nav-bento a:hover { background: #222; color: #fff; }
            .top-nav-bento a.active { background: #e0f2fe; color: #0369a1; }
            .dark .top-nav-bento a.active { background: #0c4a6e; color: #38bdf8; }
        </style>

        <!-- Top Navigation Area -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
            <div class="top-nav-bento">
                <a href="<?= raizUrl('/dashboard.php') ?>" class="active">Dashboard</a>
                <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>">Lançamentos</a>
                <a href="<?= raizUrl('/financeiro/configuracoes.php') ?>">Custos</a>
                <a href="<?= raizUrl('/precificacao/simulador.php') ?>">Simulador</a>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-zinc-500 bg-white dark:bg-zinc-900 px-4 py-2 rounded-full shadow-sm">PT-BR</span>
                <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-900 px-4 py-2 rounded-full shadow-sm"><?= sanitizar(usuarioAtual()['email']) ?></span>
            </div>
        </div>

        <!-- Bento Header -->
        <div class="bento-header flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-6">
            <div>
                <h1 class="text-4xl font-extrabold tracking-tight text-zinc-900 dark:text-white mb-1">Dashboard</h1>
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Resumo financeiro e operacional da sua agência</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>" class="bg-black text-white dark:bg-white dark:text-black hover:scale-105 transition-transform px-5 py-2.5 rounded-full text-sm font-bold shadow-lg">
                    Novo Lançamento
                </a>
            </div>
        </div>

        <!-- Bento Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            
            <!-- Left Column -->
            <div class="flex flex-col gap-6">
                <!-- Revenue Forecast Card -->
                <div class="bento-card bento-purple">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-lg font-bold">Fluxo de Caixa Mensal</h2>
                        <span class="bg-white/30 dark:bg-black/30 px-3 py-1 rounded-full text-xs font-bold">Resumo</span>
                    </div>
                    
                    <div class="flex flex-col md:flex-row gap-4 mb-6">
                        <div class="bento-pill-white flex-1 min-h-[140px]">
                            <p class="text-xs font-bold text-zinc-500 mb-1">Receitas Realizadas</p>
                            <h3 class="text-3xl font-extrabold tracking-tight"><?= formatarMoeda((float) $receitasMes) ?></h3>
                        </div>
                        <div class="bento-pill-dark flex-1 min-h-[140px]">
                            <p class="text-xs font-bold text-zinc-400 mb-1">Saldo Atual</p>
                            <h3 class="text-3xl font-extrabold tracking-tight"><?= formatarMoeda((float) $saldoAtual) ?></h3>
                        </div>
                    </div>
                    
                    <div class="bento-pill-chart">
                        <div class="flex justify-between items-end mb-4">
                            <div>
                                <h3 class="text-3xl font-extrabold"><?= formatarMoeda((float) array_sum(array_column($bars, 'valor'))) ?></h3>
                                <p class="text-xs font-bold opacity-70 mt-1">Movimentação Total do Mês</p>
                            </div>
                            <span class="bg-[#6a5ff6] text-white text-[10px] font-bold px-2 py-1 rounded">Live</span>
                        </div>
                        
                        <?php if (array_sum(array_column($bars, 'valor')) > 0): ?>
                        <div class="h-[120px] flex items-end gap-1.5 mt-4">
                            <?php foreach ($bars as $data => $bar):
                                $height = $bar['valor'] > 0 ? max(10, (int) round(($bar['valor'] / $maxBar) * 100)) : 5;
                                $isToday = $data === $hoje;
                            ?>
                                <div class="flex-1 flex flex-col items-center justify-end gap-1 group relative">
                                    <div class="w-full rounded-t-sm transition-all duration-300 <?= $isToday ? 'bg-[#6a5ff6]' : ($bar['valor'] > 0 ? 'bg-black/60 dark:bg-white/80' : 'bg-black/10 dark:bg-white/10') ?>" 
                                         style="height:<?= $height ?>%; max-width: 12px;"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="flex justify-between mt-2 px-1">
                            <span class="text-[10px] font-bold opacity-50">Dia 1</span>
                            <span class="text-[10px] font-bold opacity-50">Dia <?= date('t') ?></span>
                        </div>
                        <?php else: ?>
                            <div class="h-[120px] flex items-center justify-center opacity-50 text-sm font-bold">Sem dados no período</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Spending By Category -->
                <div class="bento-card bento-dark">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-lg font-bold">Proporção Financeira</h2>
                        <span class="bg-white/10 px-3 py-1 rounded-full text-xs font-bold text-zinc-300">Análise</span>
                    </div>
                    
                    <div class="flex items-center justify-center py-4 relative">
                        <div class="donut-chart"></div>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <p class="text-[11px] font-bold text-zinc-400">Resultado Previsto</p>
                            <h3 class="text-xl font-extrabold text-white mt-1"><?= formatarMoeda((float) $resultadoPrev) ?></h3>
                        </div>
                    </div>
                    
                    <div class="flex justify-between mt-6 px-4">
                        <div class="text-center">
                            <div class="w-3 h-3 rounded-full bg-[#10b981] mx-auto mb-2"></div>
                            <p class="text-[10px] font-bold text-zinc-400 uppercase">Receber</p>
                            <p class="text-sm font-extrabold mt-1"><?= formatarMoeda((float) $receberMes) ?></p>
                        </div>
                        <div class="text-center">
                            <div class="w-3 h-3 rounded-full bg-[#8b5cf6] mx-auto mb-2"></div>
                            <p class="text-[10px] font-bold text-zinc-400 uppercase">Pagar</p>
                            <p class="text-sm font-extrabold mt-1"><?= formatarMoeda((float) $pagarMes) ?></p>
                        </div>
                        <div class="text-center">
                            <div class="w-3 h-3 rounded-full bg-[#6366f1] mx-auto mb-2"></div>
                            <p class="text-[10px] font-bold text-zinc-400 uppercase">Despesas</p>
                            <p class="text-sm font-extrabold mt-1"><?= formatarMoeda((float) $despesasMes) ?></p>
                        </div>
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
                                <h3 class="text-lg font-bold leading-tight">A Receber<br><span class="font-normal opacity-80 text-sm">Neste mês</span></h3>
                            </div>
                            <div>
                                <p class="text-4xl font-light tracking-tight"><?= $qtdReceber ?: '0' ?></p>
                                <p class="text-sm font-bold mt-2 opacity-90"><?= formatarMoeda((float) $receberMes) ?></p>
                            </div>
                        </div>
                        <div class="bento-child-green">
                            <div>
                                <h3 class="text-lg font-bold leading-tight">A Pagar<br><span class="font-normal opacity-80 text-sm">Neste mês</span></h3>
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
                        <span class="bg-[#6a5ff6] text-white px-3 py-1 rounded-full text-[10px] font-bold uppercase">Atenção</span>
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
                        
                        <?php if(empty($listaUnica)): ?>
                            <div class="py-10 text-center opacity-50 font-bold">Nenhum vencimento próximo.</div>
                        <?php else: ?>
                            <?php foreach ($listaUnica as $c): ?>
                            <div class="transaction-item group">
                                <div class="flex items-center gap-4 min-w-0">
                                    <div class="transaction-icon <?= $c['tipo'] === 'receber' ? 'bg-[#a4c9b3]/20 text-[#243b2f] dark:text-[#a4c9b3]' : 'bg-red-500/10 text-red-600' ?>">
                                        <i data-lucide="<?= $c['tipo'] === 'receber' ? 'arrow-down-to-line' : 'arrow-up-right' ?>" class="w-5 h-5"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-sm truncate"><?= sanitizar($c['descricao']) ?></p>
                                        <p class="text-[11px] font-bold opacity-50 uppercase tracking-wide mt-0.5">
                                            <?= $c['vencimento'] < $hoje ? '<span class="text-red-500">Vencido</span>' : formatarData($c['vencimento']) ?>
                                        </p>
                                    </div>
                                </div>
                                <p class="font-extrabold text-base whitespace-nowrap pl-4 <?= $c['tipo'] === 'receber' ? 'text-[#10b981]' : '' ?>">
                                    <?= $c['tipo'] === 'receber' ? '+' : '-' ?><?= formatarMoeda((float) $c['valor']) ?>
                                </p>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>" class="block w-full text-center py-4 mt-4 text-xs font-bold uppercase tracking-widest opacity-40 hover:opacity-100 transition-opacity">Ver todos</a>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/layout/footer.php'; ?>
