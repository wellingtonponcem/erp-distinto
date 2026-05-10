<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
exigirAutenticacao();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$db = Database::get();
$stmt = $db->prepare("SELECT * FROM roteiros WHERE id = ?");
$stmt->execute([$id]);
$roteiro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$roteiro) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $roteiro['titulo']; ?> — Detalhes</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
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

        .back-link:hover { color: var(--accent); }

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

        .status-pendente { background: rgba(255,255,255,0.1); color: #fff; }
        .status-gravado { background: rgba(71,255,133,0.15); color: #47ff85; }
        .status-postado { background: rgba(71,163,255,0.15); color: #47a3ff; }

        .layout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
        }

        .main-content {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 2.5rem;
            border-radius: 12px;
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

        .script-section { margin-bottom: 2rem; }
        .section-label {
            font-size: 9px;
            font-weight: 500;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        .hook-block {
            background: var(--surface2);
            border-left: 3px solid var(--accent);
            padding: 1rem 1.2rem;
            border-radius: 0 4px 4px 0;
            font-family: var(--serif);
            font-style: italic;
            font-size: 18px;
            color: var(--text);
            margin-bottom: 1.5rem;
        }

        .content-body { font-size: 16px; white-space: pre-wrap; }

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
            background: rgba(232,255,71,0.05);
            border: 1px solid rgba(232,255,71,0.2);
            border-radius: 8px;
        }

        .score-val {
            font-family: var(--display);
            font-size: 48px;
            color: var(--accent);
            line-height: 1;
        }

        @media (max-width: 768px) {
            .layout-grid { grid-template-columns: 1fr; }
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

        <div class="layout-grid">
            <div class="main-content">
                <div class="script-section">
                    <div class="section-label">Gancho / Título IA</div>
                    <div class="hook-block" x-text="data.gancho"></div>
                </div>

                <div class="script-section">
                    <div class="section-label">Conteúdo do Roteiro</div>
                    <textarea class="form-control" style="min-height: 400px; background: transparent; border: none; padding: 0;" x-model="data.conteudo"></textarea>
                </div>

                <div class="script-section">
                    <div class="section-label">CTA (Call to Action)</div>
                    <input type="text" class="form-control" x-model="data.cta">
                </div>
            </div>

            <div class="sidebar">
                <div class="sidebar-card">
                    <h3>Status & Classificação</h3>
                    <select class="form-control" x-model="data.status">
                        <option value="pendente">Pendente</option>
                        <option value="gravado">Gravado</option>
                        <option value="editado">Editado</option>
                        <option value="postado">Postado</option>
                    </select>
                    <label style="font-size: 11px; color: var(--muted);">Tags (separadas por vírgula)</label>
                    <input type="text" class="form-control" x-model="data.tags">
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

                <button @click="save()" class="btn-save" :disabled="saving">
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
                    fetch('../api/roteiros/salvar.php', {
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
