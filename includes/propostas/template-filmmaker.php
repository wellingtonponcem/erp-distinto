<?php
/**
 * Template Filmmaker - Cinematic Dark/Orange
 */
?>
<div class="theme-filmmaker">
    <!-- Slide 1: Hero Cinematic -->
    <section class="proposal-page" style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.8)), url('https://images.unsplash.com/photo-1492691523567-6170f0295dbd?auto=format&fit=crop&q=80&w=1200'); background-size: cover; background-position: center;">
        <div class="page-content">
            <div style="font-family: var(--font-cinematic); font-size: 14px; text-transform: uppercase; letter-spacing: 5px; color: var(--accent); margin-bottom: 20px;">
                Filmmaking & Storytelling
            </div>
            <h1 style="font-size: 80px; line-height: 0.9; margin: 0; text-transform: uppercase;">
                Capturando<br><span class="highlight" style="font-size: 0.8em;">Momentos Únicos</span>
            </h1>
            <div style="width: 100px; height: 4px; background: var(--accent); margin: 40px 0;"></div>
            <p style="font-size: 24px; font-weight: 300; max-width: 500px; opacity: 0.8;">
                Proposta exclusiva para <strong><?= $cliente ?></strong>
            </p>
        </div>
        <div style="padding: 40px 10vw; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.1);">
            <div style="font-family: var(--font-cinematic); font-size: 18px; letter-spacing: 2px;">DISTINTO <span style="color: var(--accent)">FILMS</span></div>
            <div style="font-size: 12px; opacity: 0.5; letter-spacing: 2px; text-transform: uppercase;">Production No. <?= date('Y') ?>-<?= rand(100,999) ?></div>
        </div>
    </section>

    <!-- Slide 2: A Visão -->
    <section class="proposal-page">
        <div class="page-content">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center;">
                <div>
                    <div style="font-family: var(--font-cinematic); font-size: 14px; text-transform: uppercase; letter-spacing: 3px; color: var(--accent); margin-bottom: 30px;">
                        01. A Visão Criativa
                    </div>
                    <h2 style="font-family: var(--font-cinematic); font-size: 42px; margin-bottom: 30px;">A Arte de Contar <span class="highlight">Histórias</span></h2>
                    <div style="font-size: 18px; line-height: 1.8; opacity: 0.8;">
                        <?= $dados['secoes']['visao'] ?? 'Nossa missão é transformar seu evento em uma obra cinematográfica, focando na emoção e na estética de cada frame.' ?>
                    </div>
                </div>
                <div style="position: relative;">
                    <div style="width: 100%; height: 500px; background: url('https://images.unsplash.com/photo-1485846234645-a62644f84728?auto=format&fit=crop&q=80&w=600'); background-size: cover; border: 1px solid rgba(255,255,255,0.1);"></div>
                    <div style="position: absolute; bottom: -20px; right: -20px; width: 150px; height: 150px; border: 2px solid var(--accent); z-index: -1;"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Slide 3: O Pacote -->
    <section class="proposal-page">
        <div class="page-content">
            <div style="font-family: var(--font-cinematic); font-size: 14px; text-transform: uppercase; letter-spacing: 3px; color: var(--accent); margin-bottom: 60px;">
                02. O Que Está Incluso
            </div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px;">
                <div style="padding: 30px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02);">
                    <h3 style="font-family: var(--font-cinematic); font-size: 18px; color: var(--accent); margin-bottom: 20px;">EQUIPAMENTO</h3>
                    <p style="font-size: 14px; opacity: 0.7;">Câmeras 4K Cinema Line, Estabilizadores e Iluminação profissional.</p>
                </div>
                <div style="padding: 30px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02);">
                    <h3 style="font-family: var(--font-cinematic); font-size: 18px; color: var(--accent); margin-bottom: 20px;">EQUIPE</h3>
                    <p style="font-size: 14px; opacity: 0.7;">Diretor de fotografia e assistente para cobertura completa.</p>
                </div>
                <div style="padding: 30px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.02);">
                    <h3 style="font-family: var(--font-cinematic); font-size: 18px; color: var(--accent); margin-bottom: 20px;">PÓS-PRODUÇÃO</h3>
                    <p style="font-size: 14px; opacity: 0.7;">Color grading cinemático, Sound design e trilha sonora licenciada.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Slide 4: Investimento -->
    <section class="proposal-page">
        <div class="page-content" style="align-items: center; text-align: center;">
            <div style="font-family: var(--font-cinematic); font-size: 14px; text-transform: uppercase; letter-spacing: 3px; color: var(--accent); margin-bottom: 40px;">
                03. Orçamento de Produção
            </div>
            <div style="border: 1px solid var(--accent); padding: 60px 100px; display: inline-block;">
                <div style="font-family: var(--font-cinematic); font-size: 16px; letter-spacing: 2px; margin-bottom: 10px; opacity: 0.6;">INVESTIMENTO TOTAL</div>
                <div style="font-family: var(--font-cinematic); font-size: 64px; font-weight: 700; color: var(--accent);"><?= formatarMoeda($proposta['valor_total']) ?></div>
                <div style="margin-top: 20px; font-size: 14px; opacity: 0.4;">Production code: #<?= strtoupper(substr($proposta['slug'], 0, 8)) ?></div>
            </div>
            <p style="margin-top: 40px; font-style: italic; opacity: 0.6;">"Because every frame tells a story."</p>
        </div>
    </section>
</div>
