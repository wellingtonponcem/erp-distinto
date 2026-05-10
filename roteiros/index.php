<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
exigirAutenticacao();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Roteiros — Distinto</title>
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#0a0a0a">
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

        .card-info { flex: 1; }
        
        .card-status {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--accent);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--accent); }
        .status-gravado { color: #47ff85; }
        .status-gravado .status-dot { background: #47ff85; }
        .status-postado { color: #47a3ff; }
        .status-postado .status-dot { background: #47a3ff; }

        .card-title {
            font-family: var(--serif);
            font-style: italic;
            font-size: 22px;
            line-height: 1.2;
            margin-bottom: 5px;
        }

        .card-meta {
            font-size: 12px;
            color: var(--muted);
            display: flex;
            gap: 15px;
        }

        .score-badge {
            background: rgba(232,255,71,0.1);
            color: var(--accent);
            padding: 4px 12px;
            border-radius: 100px;
            font-size: 14px;
            font-family: var(--display);
            letter-spacing: 0.05em;
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
            .header { flex-direction: column; align-items: flex-start; gap: 1.5rem; }
            .modal-content { padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body x-data="scriptManager()">
    <div class="page-wrap">
        <div class="header">
            <div class="header-title">
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.2em; color: var(--accent); margin-bottom: 8px;">Sistema de Gestão</div>
                <h1>Meus <em>Roteiros</em></h1>
            </div>
            <div class="header-actions">
                <a href="conhecimento.php" class="btn-secondary">Base de Conhecimento</a>
                <button @click="openModal()" class="btn-primary">+ Novo Roteiro</button>
            </div>
        </div>

        <div class="nav-bar">
            <div class="nav-pills">
                <button class="nav-pill" :class="filter === 'todos' ? 'active' : ''" @click="setFilter('todos')">Todos</button>
                <button class="nav-pill" :class="filter === 'pendente' ? 'active' : ''" @click="setFilter('pendente')">Pendentes</button>
                <button class="nav-pill" :class="filter === 'gravado' ? 'active' : ''" @click="setFilter('gravado')">Gravados</button>
                <button class="nav-pill" :class="filter === 'postado' ? 'active' : ''" @click="setFilter('postado')">Postados</button>
            </div>
        </div>

        <div class="cards-list">
            <template x-for="script in filteredScripts" :key="script.id">
                <a :href="'detalhes.php?id=' + script.id" class="script-card">
                    <div class="card-info">
                        <div class="card-status" :class="'status-' + script.status">
                            <div class="status-dot"></div>
                            <span x-text="script.status"></span>
                        </div>
                        <div class="card-title" x-text="script.titulo"></div>
                        <div class="card-meta">
                            <span x-text="script.formato"></span>
                            <span x-text="formatDate(script.created_at)"></span>
                        </div>
                    </div>
                    <div class="score-badge">
                        Score: <span x-text="Math.round(script.score)"></span>
                    </div>
                </a>
            </template>
        </div>
    </div>

    <!-- Modal Novo Roteiro -->
    <div class="modal-overlay" x-show="showModal" x-cloak x-transition>
        <div class="modal-content" @click.away="showModal = false">
            <button class="close-modal" @click="showModal = false">&times;</button>
            <h2 style="font-family: var(--serif); font-style: italic; font-size: 32px; margin-bottom: 2rem;">Criar Novo Roteiro</h2>
            
            <div class="form-group">
                <label>Tema ou Título</label>
                <input type="text" x-model="newScript.tema" class="form-control" placeholder="Ex: Medo de aparecer na câmera">
            </div>

            <div class="form-group">
                <label>Briefing / Detalhes (Opcional)</label>
                <textarea x-model="newScript.briefing" class="form-control" placeholder="Dê mais contexto para a IA..."></textarea>
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

    <script>
        // Registrar Service Worker para PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('../sw.js')
                    .then(reg => console.log('SW registrado!', reg))
                    .catch(err => console.log('Erro no SW', err));
            });
        }

        function scriptManager() {
            return {
                scripts: [],
                filter: 'todos',
                showModal: false,
                loadingIA: false,
                newScript: { tema: '', briefing: '' },

                init() {
                    this.fetchScripts();
                },

                fetchScripts() {
                    fetch('../api/roteiros/listar.php')
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) this.scripts = data.data;
                        });
                },

                get filteredScripts() {
                    if (this.filter === 'todos') return this.scripts;
                    return this.scripts.filter(s => s.status === this.filter);
                },

                setFilter(f) { this.filter = f; },

                openModal() {
                    this.newScript = { tema: '', briefing: '' };
                    this.showModal = true;
                },

                formatDate(dateStr) {
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('pt-BR');
                },

                generateIA() {
                    if (!this.newScript.tema) return alert('Informe o tema');
                    this.loadingIA = true;
                    
                    fetch('../api/roteiros/gerar.php', {
                        method: 'POST',
                        body: JSON.stringify(this.newScript)
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.loadingIA = false;
                        if (data.success) {
                            // Redireciona para página de edição com o conteúdo gerado
                            // Por enquanto vamos simular o salvamento
                            this.saveManual(data.roteiro);
                        } else {
                            alert('Erro: ' + data.error);
                        }
                    });
                },

                saveManual(conteudoGerado = null) {
                    const payload = {
                        titulo: this.newScript.tema,
                        conteudo: conteudoGerado || '',
                        status: 'pendente'
                    };

                    fetch('../api/roteiros/salvar.php', {
                        method: 'POST',
                        body: JSON.stringify(payload)
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'detalhes.php?id=' + data.id;
                        }
                    });
                }
            }
        }
    </script>
</body>
</html>
