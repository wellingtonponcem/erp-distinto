<?php
require_once __DIR__ . '/../sistema/config/env.php';
require_once __DIR__ . '/../sistema/config/auth.php';
require_once __DIR__ . '/../sistema/config/database.php';
require_once __DIR__ . '/../sistema/includes/helpers.php';
require_once __DIR__ . '/../sistema/includes/assinatura.php';

exigirAutenticacao();

$db      = Database::get();
$usuario = usuarioAtual();
$userId  = $usuario['id'];

$dadosSub  = getDadosAssinatura($userId);
$subStatus = $dadosSub['status'] ?? 'trial';

// Garantir colunas extras na tabela
try {
    $db->exec("CREATE TABLE IF NOT EXISTS roteiros_config_usuario (
        user_id         VARCHAR(32) PRIMARY KEY,
        persona         TEXT,
        estilo          TEXT,
        tom_voz         TEXT,
        palavras_usa    TEXT,
        palavras_evita  TEXT,
        frases_exemplo  TEXT,
        ganchos_fav     TEXT,
        nicho           VARCHAR(100),
        publico_alvo    TEXT,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    // Adicionar colunas se já existir com schema antigo
    foreach (['palavras_usa','palavras_evita','frases_exemplo','ganchos_fav','nicho','publico_alvo'] as $col) {
        try { $db->exec("ALTER TABLE roteiros_config_usuario ADD COLUMN IF NOT EXISTS $col TEXT"); } catch (Exception $e) {}
    }
} catch (Exception $e) {}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("INSERT INTO roteiros_config_usuario
        (user_id, persona, estilo, tom_voz, palavras_usa, palavras_evita, frases_exemplo, ganchos_fav, nicho, publico_alvo, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ON CONFLICT (user_id) DO UPDATE SET
            persona        = EXCLUDED.persona,
            estilo         = EXCLUDED.estilo,
            tom_voz        = EXCLUDED.tom_voz,
            palavras_usa   = EXCLUDED.palavras_usa,
            palavras_evita = EXCLUDED.palavras_evita,
            frases_exemplo = EXCLUDED.frases_exemplo,
            ganchos_fav    = EXCLUDED.ganchos_fav,
            nicho          = EXCLUDED.nicho,
            publico_alvo   = EXCLUDED.publico_alvo,
            updated_at     = CURRENT_TIMESTAMP");

    $stmt->execute([
        $userId,
        $_POST['persona']        ?? '',
        $_POST['estilo']         ?? '',
        $_POST['tom_voz']        ?? 'Educativo',
        $_POST['palavras_usa']   ?? '',
        $_POST['palavras_evita'] ?? '',
        $_POST['frases_exemplo'] ?? '',
        $_POST['ganchos_fav']    ?? '',
        $_POST['nicho']          ?? '',
        $_POST['publico_alvo']   ?? '',
    ]);

    $mensagem = "Identidade atualizada com sucesso!";
}

// Buscar config
$stmt = $db->prepare("SELECT * FROM roteiros_config_usuario WHERE user_id = ?");
$stmt->execute([$userId]);
$cfg = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$tons = [
    ['Educativo',   'fa-graduation-cap', 'Explica, ensina, conduz'],
    ['Direto',      'fa-bolt',           'Sem rodeios, vai ao ponto'],
    ['Provocador',  'fa-fire',           'Questiona, incomoda, desperta'],
    ['Inspirador',  'fa-star',           'Motiva, eleva, emociona'],
    ['Autoridade',  'fa-crown',          'Expert que sabe do que fala'],
    ['Agressivo',   'fa-fist-raised',    'Intenso, urgente, chama à ação'],
    ['Engraçado',   'fa-face-grin-wide', 'Leveza, ironia, entretenimento'],
    ['Cético',      'fa-magnifying-glass','Questiona mitos, vai contra a corrente'],
];
$tomAtual = $cfg['tom_voz'] ?? 'Educativo';

$nichos = ['Marketing Digital','Finanças Pessoais','Saúde e Bem-estar','Empreendedorismo','Educação','Moda e Beleza','Culinária','Tecnologia','Esporte e Fitness','Espiritualidade','Outro'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voz & Estilo — Roteiros</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg: #0a0a0a; --surface: #111; --surface2: #181818;
            --border: rgba(255,255,255,0.07); --accent: #E8FF47; --accent2: #FF4747;
            --text: #F0EDE6; --muted: #888;
            --serif: 'Instrument Serif', Georgia, serif;
            --sans: 'DM Sans', sans-serif;
            --display: 'Bebas Neue', sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); font-family: var(--sans); min-height: 100vh; }

        .field-input {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 13px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-family: var(--sans);
            transition: border-color 0.2s;
            outline: none;
            resize: vertical;
        }
        .field-input:focus { border-color: rgba(232,255,71,0.4); }
        .field-input::placeholder { color: var(--muted); }
        select.field-input { cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23888' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 14px center; padding-right:36px; }

        .field-label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 16px;
        }
        .section-title {
            font-family: var(--serif);
            font-style: italic;
            font-size: 22px;
            color: var(--text);
            margin-bottom: 6px;
        }
        .section-sub {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 24px;
            line-height: 1.5;
        }

        /* Tom de voz */
        .tom-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .tom-option { display: none; }
        .tom-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px 10px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .tom-label:hover {
            border-color: rgba(232,255,71,0.2);
            background: rgba(255,255,255,0.03);
        }
        .tom-option:checked + .tom-label {
            border-color: var(--accent);
            background: rgba(232,255,71,0.06);
            color: var(--accent);
        }
        .tom-icon { font-size: 20px; }
        .tom-name { font-size: 12px; font-weight: 700; color: inherit; }
        .tom-desc { font-size: 10px; color: var(--muted); line-height: 1.3; }
        .tom-option:checked + .tom-label .tom-name { color: var(--accent); }

        /* Progress bar (completude do perfil) */
        .progress-ring { position: relative; display: inline-flex; align-items: center; justify-content: center; }
        .progress-ring svg { transform: rotate(-90deg); }
        .progress-ring-text { position: absolute; font-family: var(--display); font-size: 20px; color: var(--accent); }

        @media (max-width: 700px) {
            .tom-grid { grid-template-columns: repeat(2, 1fr); }
            .two-col { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body x-data="{}">
<div style="display:flex; min-height:100vh;">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main style="flex:1; overflow-y:auto; padding:2.5rem 2rem 5rem; max-width:calc(100vw - 52px);">
        <div style="max-width:760px; margin:0 auto;">

            <!-- Cabeçalho -->
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:2.5rem; flex-wrap:wrap; gap:16px;">
                <div>
                    <div style="font-size:10px; letter-spacing:0.2em; text-transform:uppercase; color:var(--accent); margin-bottom:10px; font-weight:600;">Identidade</div>
                    <h1 style="font-family:var(--display); font-size:clamp(40px,6vw,60px); line-height:0.95; margin-bottom:10px;">
                        Voz & <em style="font-family:var(--serif); font-style:italic; color:var(--accent);">Estilo</em>
                    </h1>
                    <p style="color:var(--muted); font-size:15px; max-width:480px;">
                        Ensine a IA como você pensa, fala e escreve. Quanto mais você preencher, mais seus roteiros vão soar como você.
                    </p>
                </div>

                <?php
                // Calcular completude do perfil
                $campos = ['persona','estilo','tom_voz','palavras_usa','palavras_evita','frases_exemplo','ganchos_fav','nicho','publico_alvo'];
                $preenchidos = count(array_filter($campos, fn($c) => !empty($cfg[$c])));
                $pct = round(($preenchidos / count($campos)) * 100);
                $circunf = 2 * pi() * 26; // raio 26
                $dashoffset = $circunf * (1 - $pct / 100);
                ?>
                <div style="text-align:center; flex-shrink:0;">
                    <div class="progress-ring">
                        <svg width="70" height="70" viewBox="0 0 70 70">
                            <circle cx="35" cy="35" r="26" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="5"/>
                            <circle cx="35" cy="35" r="26" fill="none" stroke="#E8FF47" stroke-width="5"
                                    stroke-dasharray="<?= round($circunf, 1) ?>"
                                    stroke-dashoffset="<?= round($dashoffset, 1) ?>"
                                    stroke-linecap="round"/>
                        </svg>
                        <span class="progress-ring-text"><?= $pct ?>%</span>
                    </div>
                    <div style="font-size:11px; color:var(--muted); margin-top:4px;">Perfil completo</div>
                </div>
            </div>

            <?php if ($mensagem): ?>
            <div style="background:rgba(232,255,71,0.08); border:1px solid rgba(232,255,71,0.25); color:var(--accent); padding:14px 18px; border-radius:12px; margin-bottom:20px; font-size:14px; display:flex; align-items:center; gap:10px;">
                <i class="fa-solid fa-check-circle"></i> <?= sanitizar($mensagem) ?>
            </div>
            <?php endif; ?>

            <form method="POST">

                <!-- 1. Quem é você -->
                <div class="section-card">
                    <div class="section-title">Quem é você?</div>
                    <p class="section-sub">Defina sua persona — a IA vai usar isso como ponto de partida em cada roteiro.</p>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;" class="two-col">
                        <div>
                            <label class="field-label">Nicho principal</label>
                            <select class="field-input" name="nicho">
                                <option value="">Selecionar...</option>
                                <?php foreach ($nichos as $n): ?>
                                <option value="<?= $n ?>" <?= ($cfg['nicho'] ?? '') === $n ? 'selected' : '' ?>><?= $n ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="field-label">Público-alvo</label>
                            <input class="field-input" type="text" name="publico_alvo"
                                   value="<?= sanitizar($cfg['publico_alvo'] ?? '') ?>"
                                   placeholder="Ex: empreendedores iniciantes 25–40 anos">
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Sua persona em uma frase</label>
                        <textarea class="field-input" name="persona" rows="3"
                                  placeholder="Ex: Sou um estrategista digital que ajuda coaches a escalar seu negócio com conteúdo de autoridade..."><?= sanitizar($cfg['persona'] ?? '') ?></textarea>
                        <p style="font-size:11px; color:var(--muted); margin-top:6px;">Quem você é, o que defende e por que o seu público te segue.</p>
                    </div>
                </div>

                <!-- 2. Como você escreve -->
                <div class="section-card">
                    <div class="section-title">Como você escreve</div>
                    <p class="section-sub">Seu ritmo, maneirismos e vocabulário característico. Seja específico.</p>

                    <div style="margin-bottom:16px;">
                        <label class="field-label">Estilo de escrita</label>
                        <textarea class="field-input" name="estilo" rows="3"
                                  placeholder="Ex: Começo sempre com uma pergunta que provoca. Uso frases curtas. Gosto de comparar coisas cotidianas com conceitos de negócio..."><?= sanitizar($cfg['estilo'] ?? '') ?></textarea>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;" class="two-col">
                        <div>
                            <label class="field-label">Palavras / expressões que você usa</label>
                            <textarea class="field-input" name="palavras_usa" rows="3"
                                      placeholder="Ex: cara, olha só, deixa eu te contar, na prática, sem enrolação..."><?= sanitizar($cfg['palavras_usa'] ?? '') ?></textarea>
                            <p style="font-size:11px; color:var(--muted); margin-top:6px;">Sua linguagem natural. Gírias, bordões, expressões.</p>
                        </div>
                        <div>
                            <label class="field-label">Palavras que você evita</label>
                            <textarea class="field-input" name="palavras_evita" rows="3"
                                      placeholder="Ex: alavancar, disrupção, sinergia, incrível, épico..."><?= sanitizar($cfg['palavras_evita'] ?? '') ?></textarea>
                            <p style="font-size:11px; color:var(--muted); margin-top:6px;">Clichês ou termos que soam falso pra você.</p>
                        </div>
                    </div>
                </div>

                <!-- 3. Tom de Voz -->
                <div class="section-card">
                    <div class="section-title">Tom de voz</div>
                    <p class="section-sub">Como você quer soar? Escolha o tom dominante dos seus vídeos.</p>

                    <div class="tom-grid">
                        <?php foreach ($tons as [$nome, $icon, $desc]): ?>
                        <div>
                            <input type="radio" class="tom-option" name="tom_voz"
                                   id="tom_<?= $nome ?>" value="<?= $nome ?>"
                                   <?= $tomAtual === $nome ? 'checked' : '' ?>>
                            <label class="tom-label" for="tom_<?= $nome ?>">
                                <i class="fa-solid <?= $icon ?> tom-icon"></i>
                                <span class="tom-name"><?= $nome ?></span>
                                <span class="tom-desc"><?= $desc ?></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 4. Exemplos reais -->
                <div class="section-card">
                    <div class="section-title">Exemplos <em style="font-style:italic;">reais</em></div>
                    <p class="section-sub">Cole trechos dos seus melhores roteiros ou vídeos. A IA vai aprender diretamente com o seu jeito.</p>

                    <div style="margin-bottom:16px;">
                        <label class="field-label">Trechos / frases que você mais gosta dos seus vídeos</label>
                        <textarea class="field-input" name="frases_exemplo" rows="5"
                                  placeholder="Cole aqui frases, parágrafos ou trechos de roteiros seus que capturaram bem seu estilo..."><?= sanitizar($cfg['frases_exemplo'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="field-label">Tipos de gancho que funcionam para você</label>
                        <textarea class="field-input" name="ganchos_fav" rows="4"
                                  placeholder="Ex: Gosto de começar com uma statística absurda. Ou com uma pergunta que parece boba mas tem uma reviravolta. Ou contando uma situação embaraçosa que passei..."><?= sanitizar($cfg['ganchos_fav'] ?? '') ?></textarea>
                        <p style="font-size:11px; color:var(--muted); margin-top:6px;">A IA vai priorizar esses padrões para criar os ganchos dos seus próximos roteiros.</p>
                    </div>
                </div>

                <!-- Salvar -->
                <button type="submit" style="
                    width:100%; background:var(--accent); color:#0a0a0a;
                    border:none; padding:18px; border-radius:100px;
                    font-weight:800; font-size:16px; cursor:pointer;
                    transition:transform 0.2s; text-transform:uppercase;
                    letter-spacing:0.05em;">
                    <i class="fa-solid fa-floppy-disk" style="margin-right:8px;"></i>
                    Salvar Identidade
                </button>

            </form>

            <!-- Dica -->
            <div style="margin-top:20px; padding:16px 20px; background:rgba(232,255,71,0.04); border:1px solid rgba(232,255,71,0.1); border-radius:12px; font-size:13px; color:var(--muted); line-height:1.6;">
                <strong style="color:var(--accent);">💡 Dica:</strong> Quanto mais detalhes você preencher aqui, mais precisa fica a geração. Combine isso com a <a href="<?= raizUrl('/roteiros/conhecimento.php') ?>" style="color:var(--accent); text-decoration:none;">Base de Conhecimento</a> para resultados ainda melhores.
            </div>

        </div>
    </main>
</div>
</body>
</html>
