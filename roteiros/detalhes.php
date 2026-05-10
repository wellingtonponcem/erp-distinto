<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
exigirAutenticacao();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    $db = Database::get();
    $stmt = $db->prepare("SELECT id, titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, tags, formato, status, score, views, likes, shares, reposts FROM roteiros WHERE id = ?");
    $stmt->execute([$id]);
    $roteiro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$roteiro) {
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    die("Erro ao buscar roteiro: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $roteiro['titulo']; ?> — Detalhes</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap"
        rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg: #0a0a0a;
            --surface: #111111;
            --surface2: #181818;
            --border: rgba(255, 255, 255, 0.07);
            --accent: #E8FF47;
            --accent2: #FF4747;
            --text: #F0EDE6;
            --muted: #888;
            --serif: 'Instrument Serif', Georgia, serif;
            --sans: 'DM Sans', sans-serif;
            --display: 'Bebas Neue', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: var(--sans);
            font-size: 15px;
            line-height: 1.65;
            min-height: 100vh;
        }

        .page-wrap {
            max-width: 900px;
            margin: 0 auto;
            padding: 3rem 2rem 5rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 3rem;
        }

        .back-link {
            text-decoration: none;
            color: var(--muted);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
        }

        .back-link:hover {
            color: var(--accent);
        }

        h1 {
            font-family: var(--display);
            font-size: clamp(32px, 5vw, 52px);
            line-height: 1;
            color: var(--text);
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 10px;
        }

        .status-pendente {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .status-gravado {
            background: rgba(71, 255, 133, 0.15);
            color: #47ff85;
        }

        .status-postado {
            background: rgba(71, 163, 255, 0.15);
            color: #47a3ff;
        }

        .layout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
        }


        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .sidebar-card {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 1.5rem;
            border-radius: 8px;
        }

        .sidebar-card h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--muted);
            margin-bottom: 1rem;
        }

        .script-section {
            margin-bottom: 2rem;
        }

        .section-label {
            font-size: 10px;
            font-weight: 500;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .hook-block {
            background: var(--surface2);
            border-left: 4px solid var(--accent) !important;
            padding: 2rem !important;
            border-radius: 4px;
            font-family: var(--serif);
            font-style: italic;
            font-size: 1rem;
            color: var(--text);
            margin-bottom: 2.5rem;
            line-height: 1.4;
            width: 100%;
            border-top: none;
            border-right: none;
            border-bottom: none;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
        }

        .main-content {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 4rem;
            /* Mais respiro */
            border-radius: 4px;
        }

        .content-body {
            font-size: 1rem;
            line-height: 1.8;
            letter-spacing: 0.01em;
            color: var(--text);
            white-space: pre-wrap;
        }

        .closing-block {
            background: rgba(232, 255, 71, 0.05);
            border: 1px solid rgba(232, 255, 71, 0.15);
            border-radius: 4px;
            padding: 1.5rem;
            font-family: var(--serif);
            font-style: italic;
            font-size: 20px;
            color: var(--accent);
            line-height: 1.5;
            width: 100%;
        }

        .form-control {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px;
            border-radius: 6px;
            font-family: inherit;
            margin-bottom: 10px;
        }

        .btn-save {
            background: var(--accent);
            color: #0a0a0a;
            width: 100%;
            padding: 12px;
            border-radius: 100px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .score-display {
            text-align: center;
            padding: 1.5rem;
            background: rgba(232, 255, 71, 0.05);
            border: 1px solid rgba(232, 255, 71, 0.2);
            border-radius: 8px;
        }

        .score-val {
            font-family: var(--display);
            font-size: 48px;
            color: var(--accent);
            line-height: 1;
        }

        @media (max-width: 768px) {
            .layout-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body x-data="scriptDetail()">
    <div class="page-wrap">
        <a href="index.php" class="back-link">← Voltar para todos</a>

        <div class="header">
            <div>
                <h1 x-text="data.titulo"></h1>
                <div :class="'status-badge status-' + data.status" x-text="data.status"></div>
            </div>
            <div class="score-display">
                <div class="score-val" x-text="Math.round(data.score)"></div>
                <div style="font-size: 10px; text-transform: uppercase; color: var(--muted);">Score Atual</div>
            </div>
        </div>

        <div class="layout-grid" style="display: block;">
            <!-- Conteúdo Principal Estruturado -->
            <div class="main-content" style="margin-bottom: 2rem;">
                <div class="script-section">
                    <div class="section-label">GANCHO — 3 PRIMEIROS SEGUNDOS</div>
                    <textarea class="form-control hook-block"
                        style="border: none; min-height: 80px; resize: none; margin-bottom: 0;"
                        x-model="data.gancho"></textarea>
                </div>

                <div class="script-section">
                    <div class="section-label">Quebra de Crença</div>
                    <textarea class="form-control content-body"
                        style="background: transparent; border: none; padding: 0; min-height: 100px; resize: vertical;"
                        x-model="data.quebra_crenca"></textarea>
                </div>

                <div class="script-section">
                    <div class="section-label">Desenvolvimento</div>
                    <textarea class="form-control content-body"
                        style="background: transparent; border: none; padding: 0; min-height: 150px; resize: vertical;"
                        x-model="data.desenvolvimento"></textarea>
                </div>

                <div class="script-section">
                    <div class="section-label">Conexão Emocional</div>
                    <textarea class="form-control content-body"
                        style="background: transparent; border: none; padding: 0; min-height: 100px; resize: vertical;"
                        x-model="data.conexao"></textarea>
                </div>

                <div class="script-section">
                    <div class="section-label">Fechamento Impactante</div>
                    <textarea class="form-control closing-block"
                        style="background: rgba(232,255,71,0.05); border: 1px solid rgba(232,255,71,0.15); border-radius: 4px; padding: 1.5rem; min-height: 100px; resize: vertical;"
                        x-model="data.fechamento"></textarea>
                </div>

                <div class="script-section">
                    <div class="section-label">CTA (Call to Action)</div>
                    <textarea class="form-control"
                        style="background: var(--surface2); border-left: 3px solid var(--accent2); border-radius: 0 4px 4px 0; min-height: 80px;"
                        x-model="data.cta"></textarea>
                </div>
            </div>

            <!-- Ações e Métricas (Agora abaixo do texto) -->
            <div class="metrics-area"
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
                <div class="sidebar-card">
                    <h3>Status & Classificação</h3>
                    <div style="margin-bottom: 1rem;">
                        <label style="font-size: 11px; color: var(--muted); display: block; margin-bottom: 5px;">Status
                            Atual</label>
                        <select class="form-control" x-model="data.status">
                            <option value="pendente">Pendente</option>
                            <option value="gravado">Gravado</option>
                            <option value="editado">Editado</option>
                            <option value="postado">Postado</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 11px; color: var(--muted); display: block; margin-bottom: 5px;">Tags
                            (separadas por vírgula)</label>
                        <input type="text" class="form-control" x-model="data.tags">
                    </div>
                </div>

                <div class="sidebar-card">
                    <h3>Métricas de Performance</h3>
                    <div class="metrics-grid">
                        <div>
                            <label style="font-size: 10px;">Views</label>
                            <input type="number" class="form-control" x-model="data.views">
                        </div>
                        <div>
                            <label style="font-size: 10px;">Likes</label>
                            <input type="number" class="form-control" x-model="data.likes">
                        </div>
                        <div>
                            <label style="font-size: 10px;">Shares</label>
                            <input type="number" class="form-control" x-model="data.shares">
                        </div>
                        <div>
                            <label style="font-size: 10px;">Reposts</label>
                            <input type="number" class="form-control" x-model="data.reposts">
                        </div>
                    </div>
                </div>
            </div>

            <div style="position: sticky; bottom: 2rem; z-index: 100;">
                <button @click="save()" class="btn-save" :disabled="saving"
                    style="box-shadow: 0 10px 30px rgba(232,255,71,0.2);">
                    <span x-show="!saving">Salvar Alterações</span>
                    <span x-show="saving">Salvando...</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        function scriptDetail() {
            return {
                data: <?php echo json_encode($roteiro); ?>,
                saving: false,

                save() {
                    this.saving = true;
                    fetch('<?= raizUrl('/api/roteiros/salvar.php') ?>', {
                        method: 'POST',
                        body: JSON.stringify(this.data)
                    })
                        .then(r => r.json())
                        .then(res => {
                            this.saving = false;
                            if (res.success) {
                                this.data.score = res.score;
                                alert('Salvo com sucesso!');
                            }
                        });
                }
            }
        }
    </script>
</body>

</html>