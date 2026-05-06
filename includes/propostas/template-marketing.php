<?php
/**
 * Template Marketing Digital - Modelo Innovare
 */
?>
<div class="theme-marketing">
    <!-- Slide 1: Hero -->
    <section class="proposal-page dark-page">
        <div class="marketing-aura"></div>
        
        <div class="page-content">
            <div style="font-family: var(--font-heading); font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 4px; margin-bottom: 20px; color: rgba(255,255,255,0.6);">
                Proposta Comercial
            </div>
            <h1 style="font-family: var(--font-heading); font-weight: 800; font-size: 80px; line-height: 0.9; margin: 0; text-transform: uppercase; letter-spacing: -3px;">
                <?= $proposta['titulo'] ?>
            </h1>
            <p style="font-size: 24px; margin-top: 30px; font-weight: 300; max-width: 600px; color: rgba(255,255,255,0.8);">
                Estratégia de crescimento acelerado para <strong style="color: #fff; border-bottom: 2px solid #fff;"><?= $cliente ?></strong>
            </p>
        </div>

        <!-- Rodapé do Slide 1 -->
        <div style="padding: 40px 10vw; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2;">
            <img src="/assets/img/logo-white.png" alt="Distinto" style="height: 30px; opacity: 0.8;">
            <span style="font-size: 12px; letter-spacing: 2px; text-transform: uppercase; opacity: 0.5;"><?= date('Y') ?></span>
        </div>
    </section>

    <!-- Slide 2: O Desafio -->
    <section class="proposal-page">
        <div class="page-content">
            <div style="font-family: var(--font-heading); font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 2px; color: #000; margin-bottom: 60px;">
                01. O Cenário Atual
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 80px;">
                <div>
                    <h2 style="font-family: var(--font-heading); font-size: 48px; font-weight: 800; line-height: 1; margin-bottom: 30px; text-transform: uppercase;">
                        O Desafio do<br>Crescimento
                    </h2>
                </div>
                <div style="font-size: 18px; line-height: 1.6; color: #444;">
                    <?= $dados['secoes']['desafio'] ?? 'Análise estratégica do seu mercado e identificação de gargalos de conversão.' ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Slide 3: Serviços -->
    <?php if (!empty($dados['servicos'])): ?>
    <section class="proposal-page dark-page">
        <div class="page-content" style="justify-content: flex-start; padding-top: 15vh;">
            <div style="font-family: var(--font-heading); font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 2px; color: rgba(255,255,255,0.6); margin-bottom: 60px;">
                02. Escopo de Atuação
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px;">
                <?php foreach ($dados['servicos'] as $index => $servico): ?>
                <div style="background: rgba(255,255,255,0.05); padding: 40px; border-left: 4px solid #fff;">
                    <span style="font-family: var(--font-heading); font-weight: 800; opacity: 0.3; font-size: 32px; display: block; margin-bottom: 10px;">0<?= $index + 1 ?></span>
                    <h3 style="font-family: var(--font-heading); font-size: 24px; text-transform: uppercase; margin-bottom: 15px;"><?= $servico['nome'] ?></h3>
                    <p style="font-size: 15px; opacity: 0.7; line-height: 1.5;"><?= $servico['descricao'] ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Slide 4: Investimento -->
    <section class="proposal-page">
        <div class="page-content" style="align-items: center; text-align: center;">
            <div style="font-family: var(--font-heading); font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 2px; color: #000; margin-bottom: 40px;">
                03. Investimento & Parceria
            </div>
            <div style="background: #000; color: #fff; padding: 60px 100px; border-radius: 0; position: relative;">
                <p style="text-transform: uppercase; font-size: 14px; letter-spacing: 2px; opacity: 0.6; margin-bottom: 10px;">Valor Mensal do Projeto</p>
                <div style="font-family: var(--font-heading); font-size: 72px; font-weight: 800;"><?= formatarMoeda($proposta['valor_total']) ?></div>
                <div style="margin-top: 30px; font-size: 14px; opacity: 0.5;">
                    Válido até <?= formatarData($proposta['validade'] ?? date('Y-m-d', strtotime('+7 days'))) ?>
                </div>
            </div>
            <p style="margin-top: 40px; max-width: 500px; font-size: 16px; color: #666;">
                Este investimento contempla toda a infraestrutura técnica, criativa e estratégica necessária para atingirmos os resultados propostos.
            </p>
        </div>
    </section>
</div>
