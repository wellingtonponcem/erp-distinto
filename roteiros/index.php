<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/assinatura.php';
exigirAutenticacao();

$usuario     = usuarioAtual();
$dadosSub    = getDadosAssinatura($usuario['id']);
$limiteInfo  = verificarLimiteDiario($usuario['id']);

$isAtivo        = $dadosSub['assinante_ativo'] ?? false;
$isTrialExp     = $dadosSub['trial_expirado']  ?? false;
$diasRestantes  = $dadosSub['dias_restantes']  ?? 35;
$diasTrial      = $dadosSub['dias_trial']      ?? 0;
$criadosHoje    = $dadosSub['criados_hoje']    ?? 0;
$limiteHoje     = $dadosSub['limite_hoje'];          // null = sem limite (ativo)
$podeCriar      = $dadosSub['pode_criar']      ?? true;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Roteiros — Meus Roteiros</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <meta name="theme-color" content="#0a0a0a">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg: #0a0a0a;
            --surface: #111111;
            --surface2: #181818;
            --border: rgba(255,255,255,0.07);
            --accent: #E8FF47;
            --accent2: #FF4747;
            --text: #F0EDE6;
            --muted: #888;
            --serif: 'Instrument Serif', Georgia, serif;
            --sans: 'DM Sans', sans-serif;
            --display: 'Bebas Neue', sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: var(--sans);
            font-size: 15px;
            line-height: 1.65;
            min-height: 100vh;
        }

        .page-wrap {
            max-width: 1000px;
            margin: 0 auto;
            padding: 3rem 2rem 5rem;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-bottom: 1px solid var(--border);
            padding-bottom: 2rem;
            margin-bottom: 3rem;
        }

        .header-title h1 {
            font-family: var(--display);
            font-size: clamp(40px, 6vw, 64px);
            line-height: 0.92;
            letter-spacing: 0.01em;
            color: var(--text);
        }

        .header-title h1 em {
            font-family: var(--serif);
            font-style: italic;
            color: var(--accent);
        }

        .btn-primary {
            background: var(--accent);
            color: #0a0a0a;
            padding: 10px 24px;
            border-radius: 100px;
            font-weight: 500;
            text-decoration: none;
            font-size: 14px;
            transition: transform 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-secondary {
            background: transparent;
            color: var(--text);
            padding: 10px 24px;
            border-radius: 100px;
            font-weight: 500;
            text-decoration: none;
            font-size: 14px;
            border: 1px solid var(--border);
            cursor: pointer;
            margin-right: 10px;
        }

        /* Nav pills */
        .nav-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .nav-pills { display: flex; gap: 8px; flex-wrap: wrap; }

        .nav-pill {
            font-size: 12px;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 100px;
            border: 1px solid var(--border);
            color: var(--muted);
            cursor: pointer;
            transition: all 0.2s;
            background: transparent;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .pill-count {
            font-size: 10px;
            font-family: var(--display);
            background: rgba(255,255,255,0.1);
            padding: 2px 6px;
            border-radius: 10px;
            opacity: 0.7;
        }

        .nav-pill.active .pill-count {
            background: rgba(10,10,10,0.2);
            opacity: 1;
        }

        .nav-pill:hover, .nav-pill.active {
            background: var(--accent);
            color: #0a0a0a;
            border-color: var(--accent);
        }

        /* Cards List */
        .cards-list { display: flex; flex-direction: column; gap: 10px; }

        .script-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
        }

        .script-card:hover {
            border-color: var(--accent);
            transform: translateX(5px);
        }

        /* Swipe Actions Support */
        .swipe-container {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            background: var(--surface);
            margin-bottom: 10px;
        }

        .swipe-actions {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: stretch;
            z-index: 1;
        }

        .swipe-btn {
            border: none;
            color: #0a0a0a;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .swipe-btn-archive { background: #4a4a4a; color: white; }
        .swipe-btn-delete { background: var(--accent2); color: white; }

        .script-card {
            position: relative;
            z-index: 2;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s cubic-bezier(0.2, 0.8, 0.2, 1), border-color 0.2s;
            touch-action: pan-y;
            user-select: none;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .card-num {
            font-family: var(--display);
            font-size: 42px;
            color: var(--accent);
            line-height: 1;
        }

        .score-mini {
            background: rgba(232,255,71,0.05);
            border: 1px solid rgba(232,255,71,0.1);
            color: var(--accent);
            padding: 4px 12px;
            border-radius: 100px;
            font-family: var(--display);
            font-size: 14px;
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .desktop-delete {
            background: transparent;
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 14px;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.2s;
            z-index: 10;
        }
        .desktop-delete:hover {
            color: #ff4a4a;
            background: rgba(255, 74, 74, 0.1);
        }

        @media (max-width: 768px) {
            .desktop-delete {
                display: none !important;
            }
        }

        .card-info { 
            width: 100%;
        }

        .card-title {
            font-family: var(--serif);
            font-style: italic;
            font-size: 24px;
            line-height: 1.2;
            margin-bottom: 8px;
            color: var(--text);
        }

        .card-meta-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 11px;
            color: var(--muted);
        }

        /* Custom Alert/Confirm Modal */
        .custom-modal-card {
            background: var(--surface2);
            border: 1px solid var(--border);
            padding: 2.5rem;
            border-radius: 24px;
            max-width: 450px;
            width: 90vw;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
        }
        .custom-modal-title {
            font-family: var(--display);
            font-size: 28px;
            margin-bottom: 1rem;
            color: var(--text);
        }
        .custom-modal-text {
            color: var(--muted);
            margin-bottom: 2rem;
            font-size: 15px;
        }
        .custom-modal-footer {
            display: flex;
            gap: 10px;
        }
        .btn-modal-cancel {
            flex: 1;
            padding: 14px;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 12px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-modal-danger {
            background: var(--accent2);
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            flex: 1;
        }

        /* Modal / New Page simulation */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-content {
            background: var(--surface);
            width: 100%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 12px;
            border: 1px solid var(--border);
            padding: 3rem;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 20px; right: 20px;
            background: none; border: none;
            color: var(--muted);
            font-size: 24px;
            cursor: pointer;
        }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-size: 12px; color: var(--muted); margin-bottom: 5px; }
        .form-control {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 12px;
            border-radius: 6px;
            font-family: inherit;
        }

        textarea.form-control { min-height: 120px; }

        @media (max-width: 600px) {
            .page-wrap { padding: 2rem 1.25rem 4rem; }
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
                text-align: left;
            }
            .header-actions {
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .btn-secondary {
                margin-right: 0;
                text-align: center;
            }
            .btn-primary {
                text-align: center;
            }
            .modal-content { padding: 2rem 1.5rem; }
        }

        /* ── Offline / Sync ─────────────────────────────── */
        .offline-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,71,71,0.15);
            border: 1px solid rgba(255,71,71,0.3);
            color: #ff4747;
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 100px;
            letter-spacing: 0.05em;
        }

        .sync-btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--muted);
            font-size: 11px;
            padding: 4px 12px;
            border-radius: 100px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .sync-btn:hover { border-color: var(--accent); color: var(--accent); }
        .sync-btn:disabled { opacity: 0.4; cursor: not-allowed; }

        /* ── Reader Overlay ─────────────────────────────── */
        .reader-overlay {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: var(--bg);
            z-index: 3000;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .reader-header {
            position: sticky;
            top: 0;
            background: rgba(10,10,10,0.97);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }

        .reader-close {
            background: none; border: none;
            color: var(--muted); font-size: 22px;
            cursor: pointer; padding: 6px 10px;
            line-height: 1;
        }

        .reader-progress {
            font-family: var(--display);
            font-size: 22px;
            color: var(--accent);
            letter-spacing: 0.05em;
        }

        .reader-nav-arrows {
            display: flex;
            gap: 8px;
        }

        .reader-arrow {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            width: 36px; height: 36px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
        }
        .reader-arrow:not(:disabled):hover { border-color: var(--accent); color: var(--accent); }
        .reader-arrow:disabled { opacity: 0.25; cursor: not-allowed; }

        .reader-body {
            padding: 2.5rem 1.5rem 9rem;
            max-width: 680px;
            margin: 0 auto;
        }

        .reader-num {
            font-family: var(--display);
            font-size: 80px;
            color: var(--accent);
            line-height: 1;
            opacity: 0.7;
        }

        .reader-title {
            font-family: var(--serif);
            font-style: italic;
            font-size: 26px;
            line-height: 1.2;
            margin: 0.5rem 0 0.75rem;
        }

        .reader-meta {
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border);
        }

        .reader-section { margin-bottom: 2rem; }

        .reader-label {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .reader-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .reader-text {
            font-size: 1.05rem;
            line-height: 1.8;
            white-space: pre-wrap;
            color: var(--text);
        }

        .reader-gancho {
            background: var(--surface2);
            border-left: 3px solid var(--accent);
            padding: 1.25rem 1.5rem;
            border-radius: 0 8px 8px 0;
            font-family: var(--serif);
            font-style: italic;
            font-size: 1.15rem;
            line-height: 1.5;
        }

        .reader-cta {
            background: rgba(232,255,71,0.04);
            border: 1px solid rgba(232,255,71,0.2);
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            color: var(--accent);
            font-size: 1rem;
            line-height: 1.5;
        }

        .reader-footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            background: rgba(10,10,10,0.97);
            border-top: 1px solid var(--border);
            padding: 1rem 1.5rem;
            display: flex;
            gap: 10px;
            z-index: 10;
        }

        .reader-btn {
            flex: 1;
            padding: 13px;
            border-radius: 100px;
            border: 1px solid var(--border);
            color: var(--text);
            background: transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .reader-btn:disabled { opacity: 0.25; cursor: not-allowed; }
        .reader-btn:not(:disabled):hover { border-color: var(--accent); color: var(--accent); }
        .reader-btn.accent {
            background: var(--accent);
            color: #0a0a0a;
            border-color: var(--accent);
            font-weight: 700;
            flex: 2;
        }

        .empty-offline {
            text-align: center;
            padding: 5rem 2rem;
            color: var(--muted);
        }
        .empty-offline i { font-size: 40px; margin-bottom: 1rem; opacity: 0.3; }

        /* ── Trial Banner ───────────────────────────────── */
        .trial-banner {
            border-radius: 12px;
            padding: 14px 20px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }
        .trial-banner.neutral { background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.2); }
        .trial-banner.warning { background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.25); }
        .trial-banner.danger  { background: rgba(239,68,68,0.08);  border: 1px solid rgba(239,68,68,0.25); animation: pulseAlert 2s infinite; }
        .trial-banner.expired { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.4); }

        @keyframes pulseAlert {
            0%,100% { border-color: rgba(239,68,68,0.25); }
            50%      { border-color: rgba(239,68,68,0.6);  }
        }

        .trial-bar {
            height: 4px;
            border-radius: 4px;
            background: rgba(255,255,255,0.08);
            margin-top: 8px;
            overflow: hidden;
            width: 160px;
            max-width: 100%;
        }
        .trial-bar-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.4s;
        }
        .trial-bar-fill.neutral { background: #818cf8; }
        .trial-bar-fill.warning { background: #fbbf24; }
        .trial-bar-fill.danger  { background: #ef4444; }

        /* ── Daily counter ──────────────────────────────── */
        .daily-counter {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--muted);
            padding: 6px 14px;
            border: 1px solid var(--border);
            border-radius: 100px;
            white-space: nowrap;
        }
        .daily-counter.warning { color: #fbbf24; border-color: rgba(245,158,11,0.4); }
        .daily-counter.danger  { color: #ef4444;  border-color: rgba(239,68,68,0.4); }

        /* ── Paywall Modal ──────────────────────────────── */
        .paywall-card {
            background: var(--surface2);
            border: 1px solid rgba(99,102,241,0.3);
            padding: 2.5rem;
            border-radius: 24px;
            max-width: 480px;
            width: 90vw;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0,0,0,0.6);
        }
        .paywall-icon { font-size: 44px; margin-bottom: 1rem; }
        .paywall-title {
            font-family: var(--display);
            font-size: 30px;
            color: var(--text);
            margin-bottom: 0.5rem;
        }
        .paywall-text { color: var(--muted); font-size: 14px; margin-bottom: 2rem; line-height: 1.6; }
        .btn-paywall-primary {
            display: block;
            width: 100%;
            padding: 14px;
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 10px;
        }
        .btn-paywall-secondary {
            display: block;
            width: 100%;
            padding: 12px;
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
        }
    </style>
</head>
<body x-data="scriptManager()">
<div style="display:flex; min-height:100vh;">
    <?php
    $subStatus = $dadosSub['status'] ?? 'trial';
    include __DIR__ . '/includes/sidebar.php';
    ?>
    <div style="flex:1; min-width:0; overflow-x:hidden;">
    <div class="page-wrap">

        <?php
        // ── Trial Banner ──────────────────────────────────────────────────
        if (!$isAtivo):
            $pct = $isTrialExp ? 0 : round(($diasRestantes / 35) * 100);
            $cor = $isTrialExp ? 'expired' : ($diasRestantes <= 7 ? 'danger' : ($diasRestantes <= 14 ? 'warning' : 'neutral'));
        ?>
        <div class="trial-banner <?= $cor ?>">
            <div>
                <?php if ($isTrialExp): ?>
                    <div style="font-size:14px; font-weight:700; color:#fca5a5; margin-bottom:2px;">⛔ Trial encerrado</div>
                    <div style="font-size:12px; color:#9ca3af;">Seus roteiros existentes estão disponíveis para leitura.</div>
                <?php elseif ($cor === 'danger'): ?>
                    <div style="font-size:14px; font-weight:700; color:#fca5a5; margin-bottom:2px;">🔴 <?= $diasRestantes ?> dia<?= $diasRestantes !== 1 ? 's' : '' ?> restante<?= $diasRestantes !== 1 ? 's' : '' ?> no trial</div>
                    <div style="font-size:12px; color:#9ca3af;">Assine agora para não perder o acesso à criação.</div>
                <?php elseif ($cor === 'warning'): ?>
                    <div style="font-size:14px; font-weight:700; color:#fbbf24; margin-bottom:2px;">⚠️ <?= $diasRestantes ?> dias restantes no trial</div>
                    <div style="font-size:12px; color:#9ca3af;">Aproveite ao máximo antes de expirar.</div>
                <?php else: ?>
                    <div style="font-size:14px; font-weight:600; color:#a5b4fc; margin-bottom:2px;">✨ Trial gratuito · <?= $diasRestantes ?> dias restantes</div>
                <?php endif; ?>

                <?php if (!$isTrialExp): ?>
                <div class="trial-bar" style="margin-top:8px;">
                    <div class="trial-bar-fill <?= $cor ?>" style="width:<?= $pct ?>%;"></div>
                </div>
                <?php endif; ?>
            </div>
            <a href="<?= raizUrl('/assinar.php') ?>" style="
                display:inline-block; white-space:nowrap;
                background:#6366f1; color:#fff; padding:9px 20px;
                border-radius:100px; font-size:13px; font-weight:700;
                text-decoration:none; flex-shrink:0;">
                <?= $isTrialExp ? 'Assinar agora' : 'Ver planos' ?>
            </a>
        </div>
        <?php endif; ?>

        <div class="header">
            <div class="header-title">
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.2em; color: var(--accent); margin-bottom: 8px;">Sistema de Gestão</div>
                <h1>Meus <em>Roteiros</em></h1>
                <div style="display:flex; align-items:center; gap:10px; margin-top:10px; flex-wrap:wrap;">
                    <span x-show="isOffline" class="offline-badge">
                        <i class="fa-solid fa-wifi-slash" style="font-size:10px;"></i> OFFLINE
                    </span>
                    <button @click="syncOffline()" :disabled="syncing || isOffline" class="sync-btn" x-show="!isOffline">
                        <i class="fa-solid fa-rotate" :class="syncing ? 'fa-spin' : ''"></i>
                        <span x-text="syncing ? ' Sincronizando...' : (lastSync ? ' Sincronizado' : ' Sincronizar Offline')"></span>
                    </button>
                    <span x-show="lastSync && !syncing" class="sync-btn" style="cursor:default; pointer-events:none;" x-text="'⏱ ' + formatSync(lastSync)"></span>
                </div>
            </div>
            <div class="header-actions">
                <button @click="openModal()" class="btn-primary">+ Novo Roteiro</button>
            </div>
        </div>

        <div class="nav-bar">
            <div class="nav-pills">
                <button class="nav-pill" :class="filter === 'pendente' ? 'active' : ''" @click="setFilter('pendente')">
                    Pendentes <span class="pill-count" x-text="getCount('pendente')"></span>
                </button>
                <button class="nav-pill" :class="filter === 'gravado' ? 'active' : ''" @click="setFilter('gravado')">
                    Gravados <span class="pill-count" x-text="getCount('gravado')"></span>
                </button>
                <button class="nav-pill" :class="filter === 'postado' ? 'active' : ''" @click="setFilter('postado')">
                    Postados <span class="pill-count" x-text="getCount('postado')"></span>
                </button>
                <button class="nav-pill" :class="filter === 'todos' ? 'active' : ''" @click="setFilter('todos')">
                    Todos <span class="pill-count" x-text="scripts.length"></span>
                </button>
                <?php if (!$isAtivo && !$isTrialExp && $limiteHoje !== null): ?>
                <?php
                $restantes = max(0, $limiteHoje - $criadosHoje);
                $contCor = $restantes === 0 ? 'danger' : ($restantes <= 1 ? 'warning' : '');
                ?>
                <div class="daily-counter <?= $contCor ?>">
                    <i class="fa-solid fa-pen-to-square" style="font-size:10px;"></i>
                    <?= $criadosHoje ?> / <?= $limiteHoje ?> hoje
                </div>
                <?php endif; ?>
            </div>
            <button
                x-show="pendentes.length > 0"
                @click="abrirLeitura(0)"
                class="nav-pill"
                style="background: var(--accent); color: #0a0a0a; border-color: var(--accent); font-weight: 700;">
                <i class="fa-solid fa-book-open" style="font-size:11px;"></i>
                Modo Leitura
                <span class="pill-count" style="background:rgba(0,0,0,0.15);" x-text="pendentes.length"></span>
            </button>
        </div>

        <div class="cards-list">
            <template x-if="scripts.length === 0">
                <div style="text-align: center; padding: 4rem; color: var(--muted); border: 1px dashed var(--border); border-radius: 8px;">
                    Nenhum roteiro encontrado.
                </div>
            </template>

            <template x-for="script in filteredScripts" :key="script.id">
                <div class="swipe-container" x-data="{ 
                        startX: 0, 
                        currentX: 0, 
                        offsetX: 0, 
                        isSwiping: false,
                        maxSwipe: 150,
                        open: false,
                        
                        touchStart(e) {
                            this.startX = e.touches[0].clientX;
                            this.isSwiping = true;
                        },
                        touchMove(e) {
                            if (!this.isSwiping) return;
                            let moveX = e.touches[0].clientX;
                            let diff = moveX - this.startX;
                            
                            // Apenas para a esquerda
                            if (diff > 0 && !this.open) return;
                            
                            this.offsetX = this.open ? diff - this.maxSwipe : diff;
                            
                            // Limites
                            if (this.offsetX < -this.maxSwipe) this.offsetX = -this.maxSwipe;
                            if (this.offsetX > 0) this.offsetX = 0;
                        },
                        touchEnd() {
                            this.isSwiping = false;
                            if (this.offsetX < -70) {
                                this.offsetX = -this.maxSwipe;
                                this.open = true;
                            } else {
                                this.offsetX = 0;
                                this.open = false;
                            }
                        },
                        archive() {
                            $dispatch('open-confirm', {
                                title: 'Arquivar Roteiro',
                                text: 'Tem certeza que deseja arquivar este roteiro?',
                                confirmText: 'Arquivar',
                                action: () => {
                                    // Lógica de arquivamento aqui
                                    this.offsetX = 0;
                                    this.open = false;
                                }
                            });
                        },
                        remove() {
                            $dispatch('open-confirm', {
                                title: 'Apagar Roteiro',
                                text: 'Tem certeza que deseja apagar este roteiro permanentemente? Esta ação não pode ser desfeita.',
                                confirmText: 'Apagar',
                                action: () => {
                                    fetch('../api/roteiros/remover.php?id=' + script.id)
                                        .then(r => r.json())
                                        .then(data => {
                                            if(data.success) {
                                                $root.closest('.swipe-container').remove();
                                            }
                                        });
                                }
                            });
                        }
                    }">
                    <div class="swipe-actions">
                        <button @click.prevent="archive()" class="swipe-btn swipe-btn-archive">Arquivar</button>
                        <button @click.prevent="remove()" class="swipe-btn swipe-btn-delete">Apagar</button>
                    </div>
                    <div class="script-card"
                        :style="`transform: translateX(${offsetX}px); cursor: pointer;`"
                        @click.stop="$dispatch('abrir-roteiro', {id: script.id})"
                        @touchstart="touchStart($event)"
                        @touchmove="touchMove($event)"
                        @touchend="touchEnd()">
                        
                        <div class="card-header">
                            <div class="card-num" x-text="String(script.numero || 0).padStart(2, '0')"></div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <button class="desktop-delete" @click.stop.prevent="remove()" title="Excluir Roteiro">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <div class="score-mini">
                                    <span style="opacity: 0.5; font-size: 9px;">SCORE</span>
                                    <span x-text="Math.round(script.score)"></span>
                                </div>
                            </div>
                        </div>

                        <div class="card-info">
                            <div class="card-status" :class="'status-' + script.status">
                                <div class="status-dot"></div>
                                <span x-text="script.status"></span>
                            </div>
                            <div class="card-title" x-text="script.titulo"></div>
                            
                            <div class="card-meta-row">
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <span x-text="script.formato"></span>
                                    <span x-text="formatDate(script.created_at)"></span>
                                </div>
                                <i class="fa-solid fa-chevron-right" style="opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div><!-- /.page-wrap -->
    </div><!-- /.flex-content -->

    <!-- ── Reader Offline (full-screen, fora do flex) ───────────────── -->
    <div class="reader-overlay" x-show="viewMode === 'leitura'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

        <!-- Header fixo -->
        <div class="reader-header">
            <button class="reader-close" @click="fecharLeitura()">×</button>
            <div class="reader-progress" x-text="(readerIndex + 1) + ' / ' + pendentes.length"></div>
            <div class="reader-nav-arrows">
                <button class="reader-arrow" @click="anterior()" :disabled="readerIndex === 0" title="Anterior">‹</button>
                <button class="reader-arrow" @click="proximo()" :disabled="readerIndex >= pendentes.length - 1" title="Próximo">›</button>
            </div>
        </div>

        <!-- Conteúdo do roteiro -->
        <template x-if="scriptAtual">
            <div class="reader-body">
                <div class="reader-num" x-text="String(scriptAtual.numero || 0).padStart(2, '0')"></div>
                <div class="reader-title" x-text="scriptAtual.titulo"></div>
                <div class="reader-meta" x-text="(scriptAtual.intencao || '') + (scriptAtual.tema ? ' · ' + scriptAtual.tema : '')"></div>

                <!-- Gancho -->
                <div class="reader-section" x-show="scriptAtual.gancho">
                    <div class="reader-label">Gancho — 3 primeiros segundos</div>
                    <div class="reader-gancho reader-text" x-text="scriptAtual.gancho"></div>
                </div>

                <!-- Quebra de Crença -->
                <div class="reader-section" x-show="scriptAtual.quebra_crenca">
                    <div class="reader-label">Quebra de Crença</div>
                    <div class="reader-text" x-text="scriptAtual.quebra_crenca"></div>
                </div>

                <!-- Desenvolvimento -->
                <div class="reader-section" x-show="scriptAtual.desenvolvimento">
                    <div class="reader-label">Desenvolvimento</div>
                    <div class="reader-text" x-text="scriptAtual.desenvolvimento"></div>
                </div>

                <!-- Conexão Emocional -->
                <div class="reader-section" x-show="scriptAtual.conexao">
                    <div class="reader-label">Conexão Emocional</div>
                    <div class="reader-text" x-text="scriptAtual.conexao"></div>
                </div>

                <!-- Fechamento -->
                <div class="reader-section" x-show="scriptAtual.fechamento">
                    <div class="reader-label">Fechamento Impactante</div>
                    <div class="reader-text" style="color: var(--accent); font-family: var(--serif); font-style: italic; font-size: 1.1rem;" x-text="scriptAtual.fechamento"></div>
                </div>

                <!-- CTA -->
                <div class="reader-section" x-show="scriptAtual.cta">
                    <div class="reader-label">CTA</div>
                    <div class="reader-cta reader-text" x-text="scriptAtual.cta"></div>
                </div>
            </div>
        </template>

        <!-- Estado vazio -->
        <template x-if="!scriptAtual">
            <div class="empty-offline">
                <i class="fa-solid fa-file-circle-xmark"></i>
                <p>Nenhum roteiro pendente disponível offline.<br>Conecte à internet e sincronize.</p>
            </div>
        </template>

        <!-- Footer fixo -->
        <div class="reader-footer">
            <button class="reader-btn" @click="anterior()" :disabled="readerIndex === 0">← Anterior</button>
            <button class="reader-btn accent" @click="proximo()" :disabled="readerIndex >= pendentes.length - 1">Próximo →</button>
        </div>
    </div>

    <!-- Modal Novo Roteiro -->
    <div class="modal-overlay" x-show="showModal" x-cloak x-transition>
        <div class="modal-content" @click.away="showModal = false">
            <button class="close-modal" @click="showModal = false">&times;</button>
            <h2 style="font-family: var(--serif); font-style: italic; font-size: 32px; margin-bottom: 2rem;">Criar Novo Roteiro</h2>
            
            <div class="form-group">
                <label>Briefing / O que você quer falar?</label>
                <textarea x-model="newScript.briefing" class="form-control" 
                    placeholder="Descreva o conteúdo ou deixe em branco para a IA criar algo baseado no seu estilo e base de conhecimento..."></textarea>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 2rem;">
                <button @click="generateIA()" class="btn-primary" :disabled="loadingIA" style="flex: 1;">
                    <span x-show="!loadingIA">Gerar com IA (Groq)</span>
                    <span x-show="loadingIA">Gerando...</span>
                </button>
                <button @click="saveManual()" class="btn-secondary">Criar Manual</button>
            </div>
        </div>
    </div>

    <!-- Modal Customizado (Alert / Confirm) -->
    <div class="modal-overlay" x-show="customModal.show" x-cloak x-transition style="z-index: 2000;">
        <div class="custom-modal-card" @click.away="customModal.show = false">
            <div class="custom-modal-title" x-text="customModal.title"></div>
            <div class="custom-modal-text" x-text="customModal.text"></div>

            <div class="custom-modal-footer">
                <button class="btn-modal-cancel" @click="customModal.show = false">Cancelar</button>
                <button class="btn-modal-danger" @click="customModalAction()">
                    <span x-text="customModal.confirmText"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Paywall -->
    <div class="modal-overlay" x-show="paywallModal.show" x-cloak x-transition style="z-index: 2500;">
        <div class="paywall-card">
            <div class="paywall-icon" x-text="paywallModal.motivo === 'trial_expirado' ? '🔒' : '⏳'"></div>
            <div class="paywall-title" x-text="paywallModal.motivo === 'trial_expirado' ? 'Trial Encerrado' : 'Limite Diário'"></div>
            <div class="paywall-text" x-text="paywallModal.mensagem"></div>
            <a href="<?= raizUrl('/assinar.php') ?>" class="btn-paywall-primary">
                Ver planos · a partir de R$<?= number_format(PLANO_MENSAL_PRECO, 2, ',', '.') ?>/mês
            </a>
            <button class="btn-paywall-secondary" @click="paywallModal.show = false">
                <span x-text="paywallModal.motivo === 'trial_expirado' ? 'Continuar em modo leitura' : 'Tentar amanhã'"></span>
            </button>
        </div>
    </div>

    <script>
        // Registrar Service Worker para PWA
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../sw.js')
                .then(reg => {
                    console.log('[SW] Registrado, scope:', reg.scope);
                    // Força atualização do SW se houver uma versão nova
                    reg.update();
                })
                .catch(err => console.log('[SW] Erro:', err));
        }

        function scriptManager() {
            return {
                scripts: [],
                filter: 'pendente',
                showModal: false,
                loadingIA: false,
                newScript: { tema: '', briefing: '' },
                customModal: {
                    show: false,
                    title: '',
                    text: '',
                    confirmText: 'Confirmar',
                    onConfirm: null
                },
                paywallModal: {
                    show: false,
                    motivo: '',
                    mensagem: ''
                },

                // ── Offline ────────────────────────────────────────
                pendentes: [],      // conteúdo completo dos pendentes (localStorage)
                viewMode: 'lista',  // 'lista' | 'leitura'
                readerIndex: 0,
                isOffline: false,
                lastSync: null,
                syncing: false,

                init() {
                    this.isOffline = !navigator.onLine;
                    this.loadLocalData();
                    this.fetchScripts();

                    window.addEventListener('online',  () => { this.isOffline = false; this.syncOffline(); });
                    window.addEventListener('offline', () => { this.isOffline = true;  });

                    window.addEventListener('open-confirm', (e) => {
                        this.customModal = {
                            show: true,
                            title: e.detail.title,
                            text: e.detail.text,
                            confirmText: e.detail.confirmText || 'Confirmar',
                            onConfirm: e.detail.action
                        };
                    });

                    window.addEventListener('abrir-roteiro', (e) => {
                        const script = this.scripts.find(s => s.id === e.detail.id);
                        if (script) this.abrirRoteiro(script);
                    });
                },

                customModalAction() {
                    if (this.customModal.onConfirm) {
                        this.customModal.onConfirm();
                    }
                    this.customModal.show = false;
                },

                // ── Offline helpers ────────────────────────────────
                loadLocalData() {
                    try {
                        const raw = localStorage.getItem('distinto_pendentes');
                        if (raw) {
                            const parsed = JSON.parse(raw);
                            this.pendentes = parsed.data    || [];
                            this.lastSync  = parsed.synced_at || null;
                        }
                    } catch(e) {}
                },

                syncOffline() {
                    if (!navigator.onLine || this.syncing) return;
                    this.syncing = true;
                    fetch('<?= raizUrl('/api/roteiros/pendentes.php') ?>')
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                this.pendentes = data.data;
                                this.lastSync  = data.synced_at;
                                localStorage.setItem('distinto_pendentes', JSON.stringify({
                                    data:      data.data,
                                    synced_at: data.synced_at
                                }));
                            }
                        })
                        .catch(() => {})
                        .finally(() => { this.syncing = false; });
                },

                get scriptAtual() {
                    return this.pendentes[this.readerIndex] || null;
                },

                abrirLeitura(index) {
                    this.readerIndex = index || 0;
                    this.viewMode = 'leitura';
                    document.body.style.overflow = 'hidden';
                },

                fecharLeitura() {
                    this.viewMode = 'lista';
                    document.body.style.overflow = '';
                },

                proximo() {
                    if (this.readerIndex < this.pendentes.length - 1) {
                        this.readerIndex++;
                        this.$nextTick(() => {
                            document.querySelector('.reader-overlay')?.scrollTo({ top: 0, behavior: 'smooth' });
                        });
                    }
                },

                anterior() {
                    if (this.readerIndex > 0) {
                        this.readerIndex--;
                        this.$nextTick(() => {
                            document.querySelector('.reader-overlay')?.scrollTo({ top: 0, behavior: 'smooth' });
                        });
                    }
                },

                // Abre roteiro: modo leitura se offline OU se pendente local, detalhes se online
                abrirRoteiro(script) {
                    if (this.isOffline) {
                        const idx = this.pendentes.findIndex(p => p.id === script.id);
                        if (idx !== -1) {
                            this.abrirLeitura(idx);
                        } else {
                            alert('Roteiro não disponível offline. Conecte-se e sincronize primeiro.');
                        }
                        return;
                    }
                    window.location.href = 'detalhes.php?id=' + script.id;
                },

                formatSync(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr.replace(' ', 'T'));
                    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                },

                // ── Lista ──────────────────────────────────────────
                fetchScripts() {
                    fetch('<?= raizUrl('/api/roteiros/listar.php') ?>')
                        .then(r => {
                            if (!r.ok) throw new Error('Erro HTTP: ' + r.status);
                            return r.json();
                        })
                        .then(data => {
                            if (data.success) {
                                this.scripts = data.data;
                                // Sincroniza conteúdo completo para uso offline
                                this.syncOffline();
                            } else {
                                alert('Erro na API: ' + data.error);
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            if (!this.isOffline) alert('Falha ao carregar roteiros: ' + err.message);
                        });
                },

                get filteredScripts() {
                    if (this.filter === 'todos') return this.scripts;
                    return this.scripts.filter(s => s.status === this.filter);
                },

                setFilter(f) { this.filter = f; },

                getCount(status) {
                    return this.scripts.filter(s => s.status === status).length;
                },

                openModal() {
                    <?php if ($isTrialExp): ?>
                    // Trial expirado: bloquear direto
                    this.abrirPaywall('trial_expirado', 'Seu período de trial encerrou. Assine para continuar criando roteiros — seus roteiros existentes ficam disponíveis para leitura.');
                    return;
                    <?php elseif (!$podeCriar && !$isAtivo): ?>
                    // Limite diário atingido
                    this.abrirPaywall('limite_diario_atingido', 'Você atingiu o limite de criação de hoje. Assine para criar roteiros sem limites diários.');
                    return;
                    <?php endif; ?>
                    this.newScript = { tema: '', briefing: '' };
                    this.showModal = true;
                },

                abrirPaywall(motivo, mensagem) {
                    this.paywallModal = { show: true, motivo, mensagem };
                    this.showModal = false;
                },

                formatDate(dateStr) {
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('pt-BR');
                },

                generateIA() {
                    this.loadingIA = true;

                    fetch('<?= raizUrl('/api/roteiros/gerar.php') ?>', {
                        method: 'POST',
                        body: JSON.stringify({ briefing: this.newScript.briefing })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.loadingIA = false;
                        if (data.paywall) {
                            this.showModal = false;
                            this.abrirPaywall(data.motivo, data.mensagem || 'Limite atingido.');
                            return;
                        }
                        if (data.success) {
                            this.saveManual(data.roteiro);
                        } else {
                            alert('Erro: ' + data.error);
                        }
                    })
                    .catch(() => { this.loadingIA = false; });
                },

                saveManual(roteiroIA = null) {
                    const payload = roteiroIA ? {
                        titulo: roteiroIA.titulo,
                        gancho: roteiroIA.gancho,
                        quebra_crenca: roteiroIA.quebra_crenca,
                        desenvolvimento: roteiroIA.desenvolvimento,
                        conexao: roteiroIA.conexao,
                        fechamento: roteiroIA.fechamento,
                        cta: roteiroIA.cta,
                        status: 'pendente',
                        is_ia_generated: true
                    } : {
                        titulo: 'Novo Roteiro ' + new Date().toLocaleDateString(),
                        status: 'pendente',
                        is_ia_generated: false
                    };

                    fetch('<?= raizUrl('/api/roteiros/salvar.php') ?>', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.paywall) {
                            this.showModal = false;
                            this.abrirPaywall(data.motivo, data.mensagem || 'Limite atingido.');
                            return;
                        }
                        if (data.success) {
                            window.location.href = 'detalhes.php?id=' + data.id;
                        }
                    });
                },
            }
        }
    </script>
</div><!-- /.flex-wrapper -->
</body>
</html>
