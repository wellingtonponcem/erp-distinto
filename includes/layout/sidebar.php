<?php
require_once __DIR__ . '/../../config/database.php';
$usuario = usuarioAtual();

// Patch: Se o nível não estiver na sessão, tenta buscar no banco e atualizar a sessão
if (!isset($_SESSION['user_nivel'])) {
    $db = Database::get();
    $stmt = $db->prepare("SELECT nivel FROM users WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    $nivel = $stmt->fetchColumn();
    if ($nivel !== false) {
        $_SESSION['user_nivel'] = (int)$nivel;
        $usuario['nivel'] = (int)$nivel;
    }
}

$paginaAtual = $_SERVER['SCRIPT_NAME'];
function menuAtivo(string $path): string {
    global $paginaAtual;
    return str_contains($paginaAtual, $path) ? 'ativo' : '';
}
?>
<aside class="sidebar flex flex-col">
    <div style="padding:24px 20px 18px;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
            <div>
                <div style="color:#ffffff; font-size:18px; font-weight:800; letter-spacing:-0.04em;">DISTINTO</div>
                <div class="sidebar-copy" style="margin-top:3px; color:#686868; font-size:10px; font-weight:800; letter-spacing:0.08em;">AGENCY ERP</div>
            </div>
            <div style="width:32px; height:32px; display:grid; place-items:center; border-radius:999px; background:#2a2a2a; color:#e8e8e8;">
                <i data-lucide="menu" style="width:16px;height:16px;"></i>
            </div>
        </div>
    </div>
    
    <?php
    $dbSummary = Database::get();
    $mesIni = date('Y-m-01');
    $mesFim = date('Y-m-t');

    $summary = $dbSummary->prepare("
        SELECT 
            SUM(CASE WHEN tipo='receber' AND status IN ('pago','efetivado') THEN valor_pago ELSE 0 END) as total_recebido,
            SUM(CASE WHEN tipo='pagar' AND status IN ('pago','efetivado') THEN valor_pago ELSE 0 END) as total_gasto,
            SUM(CASE WHEN tipo='receber' AND status NOT IN ('pago','cancelado') THEN (valor - valor_pago) ELSE 0 END) as total_a_receber,
            SUM(CASE WHEN tipo='pagar' AND status NOT IN ('pago','cancelado') THEN (valor - valor_pago) ELSE 0 END) as total_a_pagar
        FROM lancamentos
        WHERE vencimento BETWEEN ? AND ?
    ");
    $summary->execute([$mesIni, $mesFim]);
    $res = $summary->fetch();

    $totalRec = (float)($res['total_recebido'] ?? 0);
    $totalGas = (float)($res['total_gasto'] ?? 0);
    $aReceber = (float)($res['total_a_receber'] ?? 0);
    $aPagar   = (float)($res['total_a_pagar'] ?? 0);
    $balanco  = $totalRec - $totalGas;
    ?>

    <div style="padding:0 14px 16px; display:flex; flex-direction:column; gap:8px;">
        <div style="background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.06); border-radius:12px; padding:12px;">
            <div style="font-size:9px; font-weight:800; color:#686868; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:10px; display:flex; align-items:center; justify-content:space-between; gap:5px;">
                <span><i data-lucide="activity" style="width:10px;height:10px;display:inline;"></i> RESUMO DO MÊS</span>
                <span style="opacity:0.5; font-size:8px;"><?= date('m/Y') ?></span>
            </div>
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                <div>
                    <div style="font-size:10px; color:#969696; font-weight:600;">Recebido</div>
                    <div style="font-size:13px; color:#10b981; font-weight:800;"><?= formatarMoeda($totalRec) ?></div>
                </div>
                <div>
                    <div style="font-size:10px; color:#969696; font-weight:600;">Gasto</div>
                    <div style="font-size:13px; color:#ef4444; font-weight:800;"><?= formatarMoeda($totalGas) ?></div>
                </div>
                <div>
                    <div style="font-size:10px; color:#969696; font-weight:600;">A Receber</div>
                    <div style="font-size:12px; color:#34d399; font-weight:600; opacity:0.8;"><?= formatarMoeda($aReceber) ?></div>
                </div>
                <div>
                    <div style="font-size:10px; color:#969696; font-weight:600;">A Pagar</div>
                    <div style="font-size:12px; color:#f87171; font-weight:600; opacity:0.8;"><?= formatarMoeda($aPagar) ?></div>
                </div>
            </div>
            
            <div style="margin-top:12px; padding-top:10px; border-top:1px solid rgba(255,255,255,0.06);">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="font-size:11px; color:#ffffff; font-weight:800;">Balanço Real</div>
                    <div style="font-size:14px; color:<?= $balanco >= 0 ? '#10b981' : '#ef4444' ?>; font-weight:800;"><?= formatarMoeda($balanco) ?></div>
                </div>
            </div>
        </div>
    </div>

    <nav style="flex:1; padding:8px 14px; overflow-y:auto;">
        <div class="nav-section">Principal</div>
        <a href="<?= raizUrl('/dashboard.php') ?>" class="nav-link <?= menuAtivo('/dashboard') ?>">
            <i data-lucide="layout-dashboard" style="width:17px;height:17px;"></i>
            <span class="nav-label">Dashboard</span>
        </a>

        <div class="nav-section">Financeiro</div>
        <a href="<?= raizUrl('/financeiro/lancamentos.php') ?>" class="nav-link <?= menuAtivo('/lancamentos') ?>">
            <i data-lucide="receipt-text" style="width:17px;height:17px;"></i>
            <span class="nav-label">Lancamentos</span>
        </a>
        <a href="<?= raizUrl('/financeiro/contas.php') ?>" class="nav-link <?= menuAtivo('/financeiro/contas') ?>">
            <i data-lucide="landmark" style="width:17px;height:17px;"></i>
            <span class="nav-label">Bancos</span>
        </a>
        <a href="<?= raizUrl('/financeiro/configuracoes.php') ?>" class="nav-link <?= menuAtivo('/financeiro/configuracoes') ?>">
            <i data-lucide="calculator" style="width:17px;height:17px;"></i>
            <span class="nav-label">Custos Fixos</span>
        </a>

        <div class="nav-section">Servicos</div>
        <a href="<?= raizUrl('/precificacao/servicos.php') ?>" class="nav-link <?= menuAtivo('/servicos') ?>">
            <i data-lucide="package" style="width:17px;height:17px;"></i>
            <span class="nav-label">Tabela de Precos</span>
        </a>
        <a href="<?= raizUrl('/precificacao/consultor.php') ?>" class="nav-link <?= menuAtivo('/consultor') ?>">
            <i data-lucide="sparkles" style="width:17px;height:17px;"></i>
            <span class="nav-label">Consultor IA</span>
        </a>

        <div class="nav-section">Comercial</div>
        <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>" class="nav-link <?= menuAtivo('/gerenciamento/propostas') ?>">
            <i data-lucide="file-text" style="width:17px;height:17px;"></i>
            <span class="nav-label">Propostas Web</span>
        </a>
        <a href="<?= raizUrl('/gerenciamento/depoimentos.php') ?>" class="nav-link <?= menuAtivo('/gerenciamento/depoimentos') ?>">
            <i data-lucide="message-square-quote" style="width:17px;height:17px;"></i>
            <span class="nav-label">Depoimentos</span>
        </a>
        <a href="<?= raizUrl('/roteiros/index.php') ?>" class="nav-link <?= menuAtivo('/roteiros') ?>">
            <i data-lucide="video" style="width:17px;height:17px;"></i>
            <span class="nav-label">Roteiros</span>
        </a>

        <div class="nav-section">Sistema</div>
        <a href="<?= raizUrl('/configuracoes.php') ?>" class="nav-link <?= menuAtivo('/configuracoes') ?>">
            <i data-lucide="settings" style="width:17px;height:17px;"></i>
            <span class="nav-label">Ajustes</span>
        </a>
        <?php if ($usuario['nivel'] == 1): ?>
        <a href="<?= raizUrl('/gerenciamento/usuarios.php') ?>" class="nav-link <?= menuAtivo('/gerenciamento/usuarios') ?>">
            <i data-lucide="users" style="width:17px;height:17px;"></i>
            <span class="nav-label">Usuarios</span>
        </a>
        <?php endif; ?>
    </nav>

    <div style="padding:16px 16px 20px; border-top:1px solid rgba(255,255,255,0.06);">
        <div style="display:flex; align-items:center; gap:10px; padding:10px; border-radius:12px; background:rgba(255,255,255,0.04);">
            <div style="width:36px; height:36px; display:grid; place-items:center; flex-shrink:0; border-radius:999px; background:#f6f6f6; color:#111111; font-size:13px; font-weight:800;">
                <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
            </div>
            <div class="user-meta" style="min-width:0; flex:1;">
                <div style="color:#ffffff; font-size:12px; font-weight:800; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.2;"><?= sanitizar($usuario['nome']) ?></div>
                <div style="color:#777777; font-size:10px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= sanitizar($usuario['email']) ?></div>
            </div>
            
            <div x-data="{ 
                isDark: document.documentElement.classList.contains('dark'),
                toggle() {
                    this.isDark = !this.isDark;
                    if (this.isDark) {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('dark-mode', 'true');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('dark-mode', 'false');
                    }
                }
            }" style="display:flex; align-items:center; gap:4px;">
                <button @click="toggle()" :title="isDark ? 'Ativar Modo Claro' : 'Ativar Modo Escuro'" style="display:grid; place-items:center; width:28px; height:28px; flex-shrink:0; color:#868686; border-radius:8px; background:none; border:none; cursor:pointer;">
                    <template x-if="isDark">
                        <i data-lucide="sun" style="width:15px;height:15px;"></i>
                    </template>
                    <template x-if="!isDark">
                        <i data-lucide="moon" style="width:15px;height:15px;"></i>
                    </template>
                </button>

                <a href="<?= raizUrl('/api/auth/logout.php') ?>" title="Sair" style="display:grid; place-items:center; width:28px; height:28px; flex-shrink:0; color:#868686; border-radius:8px; text-decoration:none;">
                    <i data-lucide="log-out" style="width:15px;height:15px;"></i>
                </a>
            </div>
        </div>
    </div>
</aside>
