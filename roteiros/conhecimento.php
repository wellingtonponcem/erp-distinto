<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
exigirAutenticacao();

$db = Database::get();

// Buscar arquivos
$stmt = $db->query("SELECT * FROM roteiros_conhecimento ORDER BY created_at DESC");
$arquivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar memória mestra consolidada
try {
    $stmtMem = $db->query("SELECT conteudo FROM roteiros_memoria LIMIT 1");
    $memoriaMestra = $stmtMem->fetchColumn();
} catch(Exception $e) {
    $memoriaMestra = "";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Base de Conhecimento — Distinto</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg: #0a0a0a;
            --surface: #111111;
            --surface2: #181818;
            --border: rgba(255,255,255,0.07);
            --accent: #E8FF47;
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
            max-width: 800px;
            margin: 0 auto;
            padding: 3rem 2rem 5rem;
        }

        .header { margin-bottom: 3rem; }
        .back-link {
            text-decoration: none;
            color: var(--muted);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
        }

        h1 {
            font-family: var(--display);
            font-size: 48px;
            line-height: 1;
            color: var(--text);
        }

        .upload-card {
            background: var(--surface);
            border: 2px dashed var(--border);
            padding: 3rem;
            text-align: center;
            border-radius: 12px;
            margin-bottom: 3rem;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .upload-card:hover { border-color: var(--accent); }

        .input-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 3rem;
        }

        .input-option {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 1.5rem;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .input-option:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
            background: var(--surface2);
        }

        .input-option i {
            font-size: 24px;
            color: var(--accent);
            margin-bottom: 10px;
            display: block;
        }

        .input-option span {
            font-size: 13px;
            font-weight: 500;
            display: block;
        }

        .input-option .desc {
            font-size: 10px;
            color: var(--muted);
            margin-top: 5px;
        }

        .file-list { display: flex; flex-direction: column; gap: 10px; }
        
        .file-item {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .file-info { display: flex; align-items: center; gap: 15px; }
        .file-icon { color: var(--accent); font-size: 20px; }
        .file-name { font-weight: 500; }
        .file-date { font-size: 12px; color: var(--muted); }

        .btn-delete {
            background: none; border: none; color: #ff4747; cursor: pointer; font-size: 12px;
        }

        /* Memória Mestra */
        .memory-section {
            margin-top: 5rem;
            border-top: 1px solid var(--border);
            padding-top: 3rem;
        }

        .memory-card {
            background: linear-gradient(145deg, #181818, #111111);
            border: 1px solid rgba(232, 255, 71, 0.1);
            border-radius: 16px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .memory-card::before {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 150px; height: 150px;
            background: radial-gradient(circle, rgba(232, 255, 71, 0.05) 0%, transparent 70%);
            pointer-events: none;
        }

        .memory-content {
            font-size: 14px;
            line-height: 1.8;
            color: rgba(255,255,255,0.8);
            white-space: pre-wrap;
        }

        .memory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .memory-badge {
            background: var(--accent);
            color: #000;
            font-size: 10px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .btn-accent {
            background: var(--accent);
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        /* Modal Customizado */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.9);
            backdrop-filter: blur(8px);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .modal-card {
            background: var(--surface2);
            border: 1px solid var(--border);
            padding: 2.5rem;
            border-radius: 20px;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        .modal-title {
            font-family: var(--display);
            font-size: 24px;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .modal-text {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 2rem;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-modal-cancel {
            background: rgba(255,255,255,0.05);
            color: var(--text);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }

        .progress-container {
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            margin-top: 20px;
            overflow: hidden;
            display: none;
        }

        .progress-bar {
            height: 100%;
            background: var(--accent);
            width: 0%;
            transition: width 0.3s;
        }

        .status-msg {
            font-size: 11px;
            color: var(--accent);
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        input[type="file"] { 
            position: absolute;
            width: 0;
            height: 0;
            opacity: 0;
            overflow: hidden;
            pointer-events: none;
        }
    </style>
</head>
<body x-data="knowledgeManager()">
    <div class="page-wrap">
        <a href="index.php" class="back-link">← Voltar para Roteiros</a>
        
        <div class="header" style="display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.2em; color: var(--accent); margin-bottom: 8px;">Aprendizado IA (NotebookLM Style)</div>
                <h1>Base de <em>Conhecimento</em></h1>
            </div>
            <button @click="rebuildMemory()" class="btn-accent" :disabled="uploading">
                <i class="fa-solid fa-sync" :class="uploading ? 'fa-spin' : ''"></i> Sincronizar Memória
            </button>
        </div>

        <div class="input-grid" x-show="!uploading">
            <div class="input-option" @click="$refs.fileInput.click()">
                <i class="fa-solid fa-file-arrow-up"></i>
                <span>Arquivo</span>
                <div class="desc">PDF, TXT, MD</div>
            </div>
            <div class="input-option" @click="openLinkModal()">
                <i class="fa-solid fa-link"></i>
                <span>Link / URL</span>
                <div class="desc">Artigos, Blogs, Sites</div>
            </div>
            <div class="input-option" @click="openTextModal()">
                <i class="fa-solid fa-paste"></i>
                <span>Texto Copiado</span>
                <div class="desc">Colar manualmente</div>
            </div>
            <input type="file" x-ref="fileInput" @change="uploadFile($event)" accept=".pdf,.txt,.md" style="display: none;">
        </div>

        <div x-show="uploading" class="upload-card" style="pointer-events: none; opacity: 0.9">
            <div class="status-msg" x-text="statusMsg"></div>
            <div class="progress-container" style="display: block;">
                <div class="progress-bar" :style="`width: ${progress}%`"></div>
            </div>
        </div>

        <div class="file-list">
            <template x-for="file in files" :key="file.id">
                <div class="file-item">
                    <div class="file-info">
                        <div class="file-icon" x-text="file.tipo_arquivo === 'pdf' ? '📕' : '📄'"></div>
                        <div>
                            <div class="file-name" x-text="file.nome_arquivo"></div>
                            <div class="file-date" x-text="formatDate(file.created_at)"></div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <span style="font-size: 10px; color: #47ff85;" x-show="file.ativo">Processado</span>
                        <button class="btn-delete" @click="deleteFile(file.id)" :disabled="uploading">
                            <i class="fa-solid fa-trash"></i> Excluir
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Memória Mestra Consolidada -->
        <template x-if="masterMemory">
            <div class="memory-section">
                <div class="memory-header">
                    <h2 style="font-family: var(--serif); font-style: italic; font-size: 24px;">Memória Mestra Destilada</h2>
                    <span class="memory-badge">Inteligência Consolidada</span>
                </div>
                <div class="memory-card">
                    <div class="memory-content" x-text="masterMemory"></div>
                </div>
            </div>
        </template>

        <!-- Modal Customizado (Geral) -->
        <template x-if="modal.show">
            <div class="modal-overlay">
                <div class="modal-card">
                    <div class="modal-title" x-text="modal.title"></div>
                    <div class="modal-text" x-text="modal.text"></div>
                    
                    <template x-if="modal.input === 'url'">
                        <input type="url" x-model="modal.inputValue" placeholder="https://exemplo.com/artigo" 
                               style="width: 100%; padding: 12px; background: #000; border: 1px solid var(--border); border-radius: 8px; color: #fff; margin-bottom: 1.5rem;">
                    </template>

                    <template x-if="modal.input === 'textarea'">
                        <textarea x-model="modal.inputValue" placeholder="Cole seu texto aqui..." rows="6"
                               style="width: 100%; padding: 12px; background: #000; border: 1px solid var(--border); border-radius: 8px; color: #fff; margin-bottom: 1.5rem; font-family: inherit; font-size: 13px;"></textarea>
                    </template>

                    <div class="modal-footer">
                        <button class="btn-modal-cancel" @click="modal.show = false">Cancelar</button>
                        <button class="btn-accent" @click="modalAction()">Confirmar</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
        function knowledgeManager() {
            return {
                files: <?php echo json_encode($arquivos); ?>,
                masterMemory: <?php echo json_encode($memoriaMestra ?: ''); ?>,
                uploading: false,
                progress: 0,
                statusMsg: '',
                modal: {
                    show: false,
                    title: '',
                    text: '',
                    type: 'alert', // 'alert', 'confirm', 'input'
                    input: '', // 'url', 'textarea'
                    inputValue: '',
                    onConfirm: null
                },

                showAlert(title, text) {
                    this.modal = { show: true, title, text, type: 'alert', onConfirm: null };
                },

                showConfirm(title, text, callback) {
                    this.modal = { show: true, title, text, type: 'confirm', onConfirm: callback };
                },

                openLinkModal() {
                    this.modal = { 
                        show: true, title: 'Adicionar Link', text: 'Insira a URL de um site ou artigo:', 
                        type: 'confirm', input: 'url', inputValue: '',
                        onConfirm: () => this.saveSource('url', this.modal.inputValue)
                    };
                },

                openTextModal() {
                    this.modal = { 
                        show: true, title: 'Colar Texto', text: 'Cole o conhecimento estratégico abaixo:', 
                        type: 'confirm', input: 'textarea', inputValue: '',
                        onConfirm: () => this.saveSource('text', this.modal.inputValue)
                    };
                },

                modalAction() {
                    if (this.modal.onConfirm) this.modal.onConfirm();
                    this.modal.show = false;
                },

                saveSource(type, value) {
                    if (!value) return;
                    this.uploading = true;
                    this.progress = 50;
                    this.statusMsg = type === 'url' ? 'Lendo site...' : 'Salvando texto...';

                    fetch('../api/roteiros/salvar_fonte.php', {
                        method: 'POST',
                        body: JSON.stringify({ type, value })
                    })
                    .then(r => r.json())
                    .then(res => {
                        this.uploading = false;
                        if (res.success) {
                            location.reload();
                        } else {
                            this.showAlert('Erro', res.error);
                        }
                    });
                },

                uploadFile(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    this.uploading = true;
                    this.progress = 0;
                    this.statusMsg = 'Subindo arquivo...';

                    const formData = new FormData();
                    formData.append('arquivo', file);

                    const xhr = new XMLHttpRequest();
                    
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            this.progress = Math.round((e.loaded / e.total) * 100);
                            if (this.progress === 100) {
                                this.statusMsg = 'Processando pela IA (Destilando Conhecimento)...';
                            }
                        }
                    });

                    xhr.onreadystatechange = () => {
                        if (xhr.readyState === 4) {
                            this.uploading = false;
                            if (xhr.status === 200) {
                                try {
                                    const res = JSON.parse(xhr.responseText);
                                    if (res.success) {
                                        location.reload();
                                    } else {
                                        this.showAlert('Ops!', res.error);
                                    }
                                } catch(e) {
                                    this.showAlert('Erro', 'Resposta inválida do servidor');
                                }
                            } else {
                                this.showAlert('Erro', 'Falha crítica no servidor');
                            }
                        }
                    };

                    xhr.open('POST', '../api/roteiros/upload_conhecimento.php', true);
                    xhr.send(formData);
                },

                deleteFile(id) {
                    this.showConfirm('Remover Fonte', 'Deseja remover esta fonte? A memória da IA será reconstruída sem este conteúdo.', () => {
                        fetch('../api/roteiros/deletar_conhecimento.php', {
                            method: 'POST',
                            body: JSON.stringify({ id })
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                location.reload();
                            } else {
                                this.showAlert('Erro', res.error);
                            }
                        });
                    });
                },

                rebuildMemory() {
                    this.showConfirm('Sincronizar Memória', 'Deseja reconstruir a memória mestra? A IA lerá todas as fontes novamente.', () => {
                        this.uploading = true;
                        this.statusMsg = 'Reconstruindo Memória Mestra...';
                        this.progress = 50;

                        fetch('../api/roteiros/sincronizar_memoria.php', { method: 'POST' })
                        .then(r => r.json())
                        .then(res => {
                            this.uploading = false;
                            if (res.success) {
                                location.reload();
                            } else {
                                this.showAlert('Erro', res.error);
                            }
                        });
                    });
                },

                formatDate(dateStr) {
                    return new Date(dateStr).toLocaleDateString('pt-BR');
                }
            }
        }
    </script>
</body>
</html>
