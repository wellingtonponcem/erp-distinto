<?php
require_once __DIR__ . '/../sistema/config/env.php';
require_once __DIR__ . '/../sistema/config/auth.php';
require_once __DIR__ . '/../sistema/config/database.php';
require_once __DIR__ . '/../sistema/includes/helpers.php';
require_once __DIR__ . '/../sistema/includes/assinatura.php';
exigirAutenticacao();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$usuario   = usuarioAtual();
$userId    = $usuario['id'];
$dadosSub  = getDadosAssinatura($userId);
$subStatus = $dadosSub['status'] ?? 'trial';

try {
    $db = Database::get();

    // Migração: Adicionar colunas se não existirem
    try {
        $db->exec("ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS intencao TEXT");
        $db->exec("ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS tema TEXT");
        $db->exec("ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS numero INTEGER");
        $db->exec("ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS comentarios INTEGER DEFAULT 0");
        $db->exec("ALTER TABLE roteiros ADD COLUMN IF NOT EXISTS salvamentos INTEGER DEFAULT 0");
    } catch (Exception $e) {
        // Ignora se já existirem
    }

    // Filtrar por user_id para garantir que o usuário só acessa seus próprios roteiros
    $stmt = $db->prepare("SELECT id, titulo, gancho, quebra_crenca, desenvolvimento, conexao, fechamento, cta, tags, formato, status, score, views, likes, shares, reposts, comentarios, salvamentos, intencao, tema, numero FROM roteiros WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
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
    <title><?= sanitizar($roteiro['titulo']) ?> — Roteiros</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: var(--bg); color: var(--text); font-family: var(--sans); font-size: 15px; line-height: 1.65; min-height: 100vh; }

        .page-wrap {
            max-width: 860px;
            margin: 0 auto;
            padding: 2.5rem 2rem 5rem;
        }

        .back-link {
            text-decoration: none;
            color: var(--muted);
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1.5rem;
            transition: color 0.15s;
        }
        .back-link:hover { color: var(--accent); }

        .header-main {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
        }

        .header-id {
            font-family: var(--display);
            font-size: 80px;
            color: var(--accent);
            line-height: 0.8;
            opacity: 0.9;
            flex-shrink: 0;
        }

        .header-intent {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--accent);
        }

        .header-main h1 {
            font-family: var(--serif);
            font-style: italic;
            font-size: 1.2rem;
            line-height: 1.3;
            color: var(--text);
            margin: 0.4rem 0;
            font-weight: 400;
        }

        .header-theme {
            font-size: 14px;
            color: var(--muted);
            margin-top: 4px;
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
        .status-pendente  { background: rgba(255,255,255,0.1); color: #fff; }
        .status-gravado   { background: rgba(71,255,133,0.15); color: #47ff85; }
        .status-editado   { background: rgba(255,180,50,0.15); color: #ffb432; }
        .status-postado   { background: rgba(71,163,255,0.15); color: #47a3ff; }

        .score-display {
            text-align: center;
            padding: 1.25rem 1.5rem;
            background: rgba(232, 255, 71, 0.05);
            border: 1px solid rgba(232, 255, 71, 0.2);
            border-radius: 14px;
            margin-left: auto;
            flex-shrink: 0;
        }
        .score-val {
            font-family: var(--display);
            font-size: 48px;
            color: var(--accent);
            line-height: 1;
        }

        /* Script content */
        .main-content {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 3rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
        }

        .script-section { margin-bottom: 2rem; }

        .section-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 14px;
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

        .form-control {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 14px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 15px;
            overflow: hidden;
            resize: none;
            transition: border-color 0.2s;
        }
        .form-control:focus { outline: none; border-color: rgba(232,255,71,0.4); }
        .form-control:read-only {
            cursor: default;
            border-color: transparent;
            background: transparent;
            padding-left: 0;
            padding-right: 0;
        }

        .hook-block {
            background: var(--surface2) !important;
            border-left: 4px solid var(--accent) !important;
            border-top: none !important;
            border-right: none !important;
            border-bottom: none !important;
            padding: 1.25rem 1.5rem !important;
            border-radius: 4px !important;
            font-family: var(--serif);
            font-style: italic;
            font-size: 1rem;
            color: var(--text);
            line-height: 1.5;
        }

        .closing-block {
            background: rgba(232,255,71,0.03) !important;
            border: 1px solid rgba(232,255,71,0.2) !important;
            border-radius: 8px !important;
            padding: 1.5rem 2rem !important;
            font-family: var(--serif);
            font-style: italic;
            font-size: 1rem;
            color: var(--accent);
            line-height: 1.5;
        }

        /* Metadata cards */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .meta-card {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 1.5rem;
            border-radius: 16px;
        }
        .meta-card h3 {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--muted);
            margin-bottom: 1rem;
        }

        .metrics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

        /* Sticky save button */
        .btn-save {
            background: var(--accent);
            color: #0a0a0a;
            width: 100%;
            padding: 15px;
            border-radius: 100px;
            font-weight: 800;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: none;
            cursor: pointer;
            transition: transform 0.2s;
            box-shadow: 0 10px 30px rgba(232,255,71,0.2);
        }
        .btn-save:hover { transform: translateY(-2px); }
        .btn-save:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        @media (max-width: 768px) {
            .page-wrap { padding: 1.5rem 1rem 5rem; }
            .header-main { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .header-id { font-size: 60px; }
            .score-display { width: 100%; margin-left: 0; display: flex; justify-content: space-between; align-items: center; padding: 1rem; }
            .score-val { font-size: 32px; }
            .main-content { padding: 1.75rem 1.25rem; }
        }
    </style>
</head>
<body x-data="scriptDetail()">
<div style="display:flex; min-height:100vh;">

    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main style="flex:1; overflow-y:auto; max-width:calc(100vw - 52px);">
        <div class="page-wrap">

            <a href="index.php" class="back-link">
                <i class="fa-solid fa-arrow-left"></i> Voltar para roteiros
            </a>

            <!-- Header -->
            <div class="header-main">
                <div class="header-id" x-text="String(data.numero || 0).padStart(2, '0')"></div>
                <div style="flex:1; min-width:0;">
                    <div class="header-intent" x-text="data.intencao || 'INTENÇÃO NÃO DEFINIDA'"></div>
                    <h1 x-text="data.titulo"></h1>
                    <div class="header-theme" x-text="data.tema ? 'Tema: ' + data.tema : 'Sem tema definido'"></div>
                    <div :class="'status-badge status-' + data.status" x-text="data.status" style="margin-top: 0.75rem;"></div>
                </div>
                <div class="score-display">
                    <div class="score-val" x-text="Math.round(data.score)"></div>
                    <div style="font-size:10px; text-transform:uppercase; color:var(--muted); margin-top:4px; letter-spacing:0.1em;">Score</div>
                </div>
            </div>

            <!-- Conteúdo do roteiro -->
            <div class="main-content">
                <div class="script-section">
                    <div class="section-label">Gancho — 3 primeiros segundos</div>
                    <textarea class="form-control hook-block" :readonly="!editing"
                        x-init="resize($el)" @input="resize($el)"
                        x-model="data.gancho"></textarea>
                </div>

                <div class="script-section">
                    <div class="section-label">Quebra de Crença</div>
                    <textarea class="form-control" :readonly="!editing"
                        x-init="resize($el)" @input="resize($el)"
                        style="background:transparent; border-color:transparent; padding-left:0; padding-right:0;"
                        x-model="data.quebra_crenca"></textarea>
                </div>

                <div class="script-section">
                    <div class="section-label">Desenvolvimento</div>
                    <textarea class="form-control" :readonly="!editing"
                        x-init="resize($el)" @input="resize($el)"
                        style="background:transparent; border-color:transparent; padding-left:0; padding-right:0;"
                        x-model="data.desenvolvimento"></textarea>
                </div>

                <div class="script-section">
                    <div class="section-label">Conexão Emocional</div>
                    <textarea class="form-control" :readonly="!editing"
                        x-init="resize($el)" @input="resize($el)"
                        style="background:transparent; border-color:transparent; padding-left:0; padding-right:0;"
                        x-model="data.conexao"></textarea>
                </div>

                <div class="script-section">
                    <div class="section-label">Fechamento Impactante</div>
                    <textarea class="form-control closing-block" :readonly="!editing"
                        x-init="resize($el)" @input="resize($el)"
                        x-model="data.fechamento"></textarea>
                </div>

                <div class="script-section" style="margin-bottom:0;">
                    <div class="section-label">CTA (Call to Action)</div>
                    <textarea class="form-control" :readonly="!editing"
                        x-init="resize($el)" @input="resize($el)"
                        style="background:var(--surface2); border-left:3px solid var(--accent2); border-radius:0 8px 8px 0; padding-left:15px;"
                        x-model="data.cta"></textarea>
                </div>
            </div>

            <!-- Metadados -->
            <div class="meta-grid">
                <!-- Identificação -->
                <div class="meta-card">
                    <h3>Identificação &amp; Intenção</h3>
                    <div style="margin-bottom:12px;">
                        <label style="font-size:11px; color:var(--muted); display:block; margin-bottom:5px;">Nº de Registro</label>
                        <input type="number" class="form-control" x-model="data.numero" readonly
                            style="opacity:0.5; cursor:not-allowed; background:var(--bg);">
                    </div>
                    <div style="margin-bottom:12px;">
                        <label style="font-size:11px; color:var(--muted); display:block; margin-bottom:5px;">Intenção</label>
                        <input type="text" class="form-control" x-model="data.intencao" @input="editing = true"
                            placeholder="Ex: CONSTRUIR AUTORIDADE">
                    </div>
                    <div>
                        <label style="font-size:11px; color:var(--muted); display:block; margin-bottom:5px;">Tema</label>
                        <input type="text" class="form-control" x-model="data.tema" @input="editing = true"
                            placeholder="Ex: Exposição e medo de aparecer">
                    </div>
                </div>

                <!-- Status -->
                <div class="meta-card">
                    <h3>Status &amp; Classificação</h3>
                    <div style="margin-bottom:12px;">
                        <label style="font-size:11px; color:var(--muted); display:block; margin-bottom:5px;">Status</label>
                        <select class="form-control" x-model="data.status" @change="editing = true">
                            <option value="pendente">Pendente</option>
                            <option value="gravado">Gravado</option>
                            <option value="editado">Editado</option>
                            <option value="postado">Postado</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:11px; color:var(--muted); display:block; margin-bottom:5px;">Tags (separadas por vírgula)</label>
                        <input type="text" class="form-control" x-model="data.tags" @input="editing = true"
                            placeholder="marketing, autoridade, reels">
                    </div>
                </div>

                <!-- Métricas -->
                <div class="meta-card">
                    <h3>Métricas de Performance</h3>
                    <div class="metrics-grid">
                        <div>
                            <label style="font-size:10px; color:var(--muted); display:block; margin-bottom:5px;">
                                <i class="fa-solid fa-heart" style="color:#ff4747; margin-right:4px;"></i> Likes
                            </label>
                            <input type="number" class="form-control" x-model="data.likes" @input="editing = true">
                        </div>
                        <div>
                            <label style="font-size:10px; color:var(--muted); display:block; margin-bottom:5px;">
                                <i class="fa-solid fa-comment" style="color:var(--accent); margin-right:4px;"></i> Comentários
                            </label>
                            <input type="number" class="form-control" x-model="data.comentarios" @input="editing = true">
                        </div>
                        <div>
                            <label style="font-size:10px; color:var(--muted); display:block; margin-bottom:5px;">
                                <i class="fa-solid fa-paper-plane" style="color:var(--accent); margin-right:4px;"></i> Envios
                            </label>
                            <input type="number" class="form-control" x-model="data.shares" @input="editing = true">
                        </div>
                        <div>
                            <label style="font-size:10px; color:var(--muted); display:block; margin-bottom:5px;">
                                <i class="fa-solid fa-arrows-rotate" style="color:var(--accent); margin-right:4px;"></i> Repost
                            </label>
                            <input type="number" class="form-control" x-model="data.reposts" @input="editing = true">
                        </div>
                        <div>
                            <label style="font-size:10px; color:var(--muted); display:block; margin-bottom:5px;">
                                <i class="fa-solid fa-bookmark" style="color:var(--accent); margin-right:4px;"></i> Salvamentos
                            </label>
                            <input type="number" class="form-control" x-model="data.salvamentos" @input="editing = true">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botão salvar sticky -->
            <div style="position:sticky; bottom:1.5rem; z-index:100;">
                <button @click="handleActionButton()" class="btn-save" :disabled="saving">
                    <span x-show="!saving" x-text="editing ? 'Salvar Alterações' : 'Editar Roteiro'"></span>
                    <span x-show="saving">Salvando...</span>
                </button>
            </div>

        </div><!-- /.page-wrap -->
    </main>
</div>

<script>
function scriptDetail() {
    return {
        data: <?= json_encode($roteiro) ?>,
        saving: false,
        editing: false,

        init() {
            this.$nextTick(() => this.resizeAll());
            window.addEventListener('load', () => this.resizeAll());
        },

        resize(el) {
            if (!el) return;
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 'px';
        },

        resizeAll() {
            document.querySelectorAll('textarea').forEach(ta => this.resize(ta));
        },

        handleActionButton() {
            if (this.editing) {
                this.save();
            } else {
                this.editing = true;
                this.$nextTick(() => this.resizeAll());
            }
        },

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
                    this.editing = false;
                    this.$nextTick(() => this.resizeAll());
                }
            })
            .catch(() => { this.saving = false; });
        }
    }
}
</script>
</body>
</html>
