<?php
/**
 * Sidebar colapsável — seção Roteiros
 * Identidade visual: fundo #0a0a0a, accent #E8FF47, Bebas Neue + Instrument Serif
 */
$paginaAtual = basename($_SERVER['PHP_SELF'], '.php');

// Dados de assinatura para o badge do rodapé (se assinatura.php já foi incluído)
$subStatus = $_SESSION['user_subscription_status'] ?? ($dadosSub['status'] ?? 'trial');
$nomeUsuario = $usuario['nome'] ?? ($_SESSION['user_nome'] ?? 'Usuário');
$iniciais = strtoupper(mb_substr($nomeUsuario, 0, 1) . (strpos($nomeUsuario, ' ') !== false ? mb_substr(strstr($nomeUsuario, ' '), 1, 1) : ''));
?>
<style>
    /* ── Roteiros Sidebar ─────────────────────────────────────────────── */
    :root {
        --rs-bg:       #0e0e0e;
        --rs-surface:  #161616;
        --rs-border:   rgba(255,255,255,0.06);
        --rs-accent:   #E8FF47;
        --rs-text:     #F0EDE6;
        --rs-muted:    rgba(240,237,230,0.4);
        --rs-width:    220px;
        --rs-collapsed:52px;
    }

    .rs-sidebar {
        width: var(--rs-width);
        min-width: var(--rs-width);
        height: 100vh;
        position: sticky;
        top: 0;
        background: var(--rs-bg);
        border-right: 1px solid var(--rs-border);
        display: flex;
        flex-direction: column;
        transition: width 0.25s cubic-bezier(0.4,0,0.2,1), min-width 0.25s cubic-bezier(0.4,0,0.2,1);
        overflow: hidden;
        z-index: 200;
        flex-shrink: 0;
    }
    .rs-sidebar.collapsed {
        width: var(--rs-collapsed);
        min-width: var(--rs-collapsed);
    }

    /* Logo / Header */
    .rs-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 14px 16px;
        border-bottom: 1px solid var(--rs-border);
        flex-shrink: 0;
        min-height: 64px;
    }
    .rs-logo {
        display: flex;
        align-items: baseline;
        gap: 5px;
        white-space: nowrap;
        overflow: hidden;
        opacity: 1;
        transition: opacity 0.15s;
    }
    .collapsed .rs-logo { opacity: 0; pointer-events: none; }
    .rs-logo-main {
        font-family: 'Bebas Neue', sans-serif;
        font-size: 18px;
        letter-spacing: 0.06em;
        color: var(--rs-text);
    }
    .rs-logo-sub {
        font-family: 'Instrument Serif', Georgia, serif;
        font-style: italic;
        font-size: 15px;
        color: var(--rs-accent);
        font-weight: 400;
    }
    .rs-toggle {
        background: none;
        border: 1px solid var(--rs-border);
        color: var(--rs-muted);
        width: 28px;
        height: 28px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.2s;
        font-size: 12px;
    }
    .rs-toggle:hover {
        border-color: var(--rs-accent);
        color: var(--rs-accent);
    }
    .collapsed .rs-toggle { margin: 0 auto; }

    /* Search */
    .rs-search {
        padding: 12px 10px;
        border-bottom: 1px solid var(--rs-border);
        flex-shrink: 0;
    }
    .rs-search-inner {
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--rs-surface);
        border: 1px solid var(--rs-border);
        border-radius: 10px;
        padding: 8px 10px;
        cursor: pointer;
        transition: border-color 0.2s;
        white-space: nowrap;
        overflow: hidden;
    }
    .rs-search-inner:hover { border-color: rgba(232,255,71,0.3); }
    .rs-search-icon { color: var(--rs-muted); font-size: 12px; flex-shrink: 0; }
    .rs-search-text { font-size: 12px; color: var(--rs-muted); overflow: hidden; transition: all 0.2s; }
    .collapsed .rs-search-text { width: 0; opacity: 0; }

    /* Nav */
    .rs-nav {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 8px 8px;
        scrollbar-width: thin;
        scrollbar-color: var(--rs-border) transparent;
    }
    .rs-nav::-webkit-scrollbar { width: 3px; }
    .rs-nav::-webkit-scrollbar-thumb { background: var(--rs-border); border-radius: 4px; }

    .rs-section-label {
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: var(--rs-muted);
        padding: 12px 8px 6px;
        white-space: nowrap;
        overflow: hidden;
        transition: opacity 0.15s;
    }
    .collapsed .rs-section-label { opacity: 0; height: 0; padding: 0; }

    .rs-section-dot {
        display: none;
        width: 4px;
        height: 4px;
        border-radius: 50%;
        background: var(--rs-border);
        margin: 10px auto 4px;
    }
    .collapsed .rs-section-dot { display: block; }

    .rs-link {
        display: flex;
        align-items: center;
        gap: 11px;
        padding: 9px 8px;
        border-radius: 10px;
        text-decoration: none;
        color: var(--rs-muted);
        font-size: 13px;
        font-weight: 500;
        transition: all 0.15s;
        white-space: nowrap;
        overflow: hidden;
        position: relative;
        margin-bottom: 1px;
    }
    .rs-link:hover {
        background: rgba(255,255,255,0.04);
        color: var(--rs-text);
    }
    .rs-link.active {
        background: rgba(232,255,71,0.08);
        color: var(--rs-accent);
        font-weight: 600;
    }
    .rs-link.active::before {
        content: '';
        position: absolute;
        left: 0; top: 20%; bottom: 20%;
        width: 3px;
        background: var(--rs-accent);
        border-radius: 0 3px 3px 0;
    }
    .rs-icon {
        width: 20px;
        text-align: center;
        font-size: 14px;
        flex-shrink: 0;
        transition: transform 0.2s;
    }
    .rs-link:hover .rs-icon { transform: scale(1.1); }
    .rs-label { transition: opacity 0.15s; }
    .collapsed .rs-label { opacity: 0; width: 0; overflow: hidden; }

    /* Badge inline */
    .rs-badge {
        margin-left: auto;
        font-size: 9px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 100px;
        background: rgba(232,255,71,0.12);
        color: var(--rs-accent);
        letter-spacing: 0.05em;
        flex-shrink: 0;
        transition: opacity 0.15s;
    }
    .collapsed .rs-badge { opacity: 0; width: 0; overflow: hidden; padding: 0; }

    /* Footer / User */
    .rs-footer {
        border-top: 1px solid var(--rs-border);
        padding: 10px 8px 14px;
        flex-shrink: 0;
    }
    .rs-user {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px;
        border-radius: 12px;
        cursor: default;
        overflow: hidden;
        margin-bottom: 6px;
    }
    .rs-avatar {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: var(--rs-accent);
        color: #0a0a0a;
        font-family: 'Bebas Neue', sans-serif;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        letter-spacing: 0.05em;
    }
    .rs-user-info { overflow: hidden; transition: opacity 0.15s; }
    .collapsed .rs-user-info { opacity: 0; width: 0; }
    .rs-user-name {
        font-size: 12px;
        font-weight: 700;
        color: var(--rs-text);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .rs-user-plan {
        font-size: 10px;
        color: var(--rs-muted);
        white-space: nowrap;
    }
    .rs-user-plan.active-plan { color: var(--rs-accent); }

    /* Tooltip para collapsed */
    .rs-link[data-tip]:hover::after {
        content: attr(data-tip);
        position: absolute;
        left: calc(var(--rs-collapsed) + 6px);
        top: 50%;
        transform: translateY(-50%);
        background: #1a1a1a;
        border: 1px solid var(--rs-border);
        color: var(--rs-text);
        font-size: 12px;
        padding: 5px 10px;
        border-radius: 8px;
        white-space: nowrap;
        pointer-events: none;
        z-index: 999;
        opacity: 0;
        animation: tooltipIn 0.15s 0.4s forwards;
    }
    @keyframes tooltipIn { to { opacity: 1; } }

    @media (max-width: 640px) {
        .rs-sidebar { display: none; }
    }
</style>

<aside class="rs-sidebar <?= isset($_COOKIE['rs_sidebar_collapsed']) && $_COOKIE['rs_sidebar_collapsed'] === '1' ? 'collapsed' : '' ?>"
       id="rs-sidebar"
       x-data="{
           collapsed: document.cookie.includes('rs_sidebar_collapsed=1'),
           toggle() {
               this.collapsed = !this.collapsed;
               document.cookie = 'rs_sidebar_collapsed=' + (this.collapsed ? '1' : '0') + ';path=/;max-age=31536000';
           }
       }"
       :class="collapsed ? 'collapsed' : ''">

    <!-- Header / Logo -->
    <div class="rs-header">
        <div class="rs-logo">
            <span class="rs-logo-main">MEUS</span>
            <span class="rs-logo-sub">Roteiros</span>
        </div>
        <button class="rs-toggle" @click="toggle()" :title="collapsed ? 'Expandir' : 'Recolher'">
            <i class="fa-solid" :class="collapsed ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
        </button>
    </div>

    <!-- Search / Buscar roteiro -->
    <div class="rs-search">
        <div class="rs-search-inner" onclick="document.dispatchEvent(new CustomEvent('rs-search-open'))">
            <i class="fa-solid fa-magnifying-glass rs-search-icon"></i>
            <span class="rs-search-text">Buscar roteiro...</span>
        </div>
    </div>

    <!-- Nav -->
    <nav class="rs-nav">

        <!-- Roteiros -->
        <div class="rs-section-label">Roteiros</div>
        <div class="rs-section-dot"></div>

        <a href="<?= raizUrl('/roteiros/index.php') ?>"
           class="rs-link <?= $paginaAtual === 'index' ? 'active' : '' ?>"
           data-tip="Meus Roteiros">
            <i class="fa-solid fa-film rs-icon"></i>
            <span class="rs-label">Meus Roteiros</span>
        </a>

        <a href="<?= raizUrl('/roteiros/conhecimento.php') ?>"
           class="rs-link <?= $paginaAtual === 'conhecimento' ? 'active' : '' ?>"
           data-tip="Base de Conhecimento">
            <i class="fa-solid fa-brain rs-icon"></i>
            <span class="rs-label">Base de Conhecimento</span>
        </a>

        <!-- Identidade -->
        <div class="rs-section-label">Identidade</div>
        <div class="rs-section-dot"></div>

        <a href="<?= raizUrl('/roteiros/voz.php') ?>"
           class="rs-link <?= $paginaAtual === 'voz' ? 'active' : '' ?>"
           data-tip="Voz & Estilo">
            <i class="fa-solid fa-microphone rs-icon"></i>
            <span class="rs-label">Voz & Estilo</span>
        </a>

        <a href="<?= raizUrl('/roteiros/perfil.php') ?>"
           class="rs-link <?= $paginaAtual === 'perfil' ? 'active' : '' ?>"
           data-tip="Meu Perfil">
            <i class="fa-regular fa-circle-user rs-icon"></i>
            <span class="rs-label">Meu Perfil</span>
        </a>

        <!-- Conta -->
        <div class="rs-section-label">Conta</div>
        <div class="rs-section-dot"></div>

        <a href="<?= raizUrl('/assinar.php') ?>"
           class="rs-link <?= $paginaAtual === 'assinar' ? 'active' : '' ?>"
           data-tip="Assinatura">
            <i class="fa-solid fa-bolt rs-icon"></i>
            <span class="rs-label">Assinatura</span>
            <?php if ($subStatus === 'trial'): ?>
            <span class="rs-badge">TRIAL</span>
            <?php elseif ($subStatus === 'active'): ?>
            <span class="rs-badge" style="background:rgba(52,211,153,0.12); color:#34d399;">PRO</span>
            <?php endif; ?>
        </a>

    </nav>

    <!-- Footer / User -->
    <div class="rs-footer">
        <div class="rs-user">
            <div class="rs-avatar"><?= $iniciais ?: strtoupper(mb_substr($nomeUsuario, 0, 2)) ?></div>
            <div class="rs-user-info">
                <div class="rs-user-name"><?= sanitizar($nomeUsuario) ?></div>
                <div class="rs-user-plan <?= $subStatus === 'active' ? 'active-plan' : '' ?>">
                    <?= $subStatus === 'active' ? '✦ Assinante Pro' : ($subStatus === 'trial' ? 'Trial gratuito' : 'Acesso expirado') ?>
                </div>
            </div>
        </div>
        <a href="<?= raizUrl('/api/auth/logout.php') ?>"
           class="rs-link"
           style="color: rgba(255,71,71,0.6);"
           data-tip="Sair"
           onmouseover="this.style.color='#ff4747'"
           onmouseout="this.style.color='rgba(255,71,71,0.6)'">
            <i class="fa-solid fa-right-from-bracket rs-icon"></i>
            <span class="rs-label">Sair</span>
        </a>
    </div>
</aside>
