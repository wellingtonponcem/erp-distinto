<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/assinatura.php';
exigirAutenticacao();

$db      = Database::get();
$usuario = usuarioAtual();
$userId  = $usuario['id'];

// Limite de conhecimento
$limiteConhec = verificarLimiteConhecimento($userId);
$totalArquivos = $limiteConhec['total'];
$limiteArquivos = $limiteConhec['limite'];

// Garante que a coluna sincronizado existe (migração segura)
try {
    $db->exec("ALTER TABLE roteiros_conhecimento ADD COLUMN IF NOT EXISTS sincronizado BOOLEAN DEFAULT FALSE");
} catch (Exception $e) {}

// Buscar arquivos/fontes do usuário atual
try {
    $stmt = $db->prepare("SELECT id, nome_arquivo, caminho_arquivo, tipo_arquivo, ativo, created_at, COALESCE(sincronizado, FALSE) as sincronizado FROM roteiros_conhecimento WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
} catch (Exception $e) {
    $stmt = $db->prepare("SELECT id, nome_arquivo, caminho_arquivo, tipo_arquivo, ativo, created_at, FALSE as sincronizado FROM roteiros_conhecimento WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
}
$arquivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar memória mestra do usuário
try {
    $stmtMem = $db->prepare("SELECT conteudo FROM roteiros_memoria WHERE user_id = ? LIMIT 1");
    $stmtMem->execute([$userId]);
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* NotebookLM Style Upload Zone */
        .upload-zone {
            background: var(--surface);
            border: 2px dashed var(--border);
            padding: 4rem 2rem;
            text-align: center;
            border-radius: 24px;
            margin-bottom: 3rem;
            transition: all 0.3s;
            position: relative;
        }

        .upload-zone:hover {
            border-color: rgba(232, 255, 71, 0.3);
            background: rgba(255,255,255,0.01);
        }

        .upload-title {
            font-size: 24px;
            font-family: var(--serif);
            font-style: italic;
            margin-bottom: 1rem;
            color: var(--text);
        }

        .upload-subtitle {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 2.5rem;
        }

        .input-pills {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .pill {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            padding: 10px 20px;
            border-radius: 100px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 13px;
            font-weight: 500;
            color: var(--text);
        }

        .pill:hover {
            background: rgba(255,255,255,0.07);
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        .pill i { font-size: 14px; }
        .pill i.fa-youtube { color: #ff0000; }

        /* File List */
        .file-list { display: flex; flex-direction: column; gap: 10px; }
        .file-item {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 1.2rem 1.5rem;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-info { display: flex; align-items: center; gap: 15px; }
        .file-icon { color: var(--accent); font-size: 18px; }
        .file-name { font-weight: 500; font-size: 14px; }
        .file-date { font-size: 11px; color: var(--muted); }

        .btn-delete {
            background: none; border: none; color: #ff4747; cursor: pointer; font-size: 12px;
            opacity: 0.6; transition: opacity 0.2s;
        }
        .btn-delete:hover { opacity: 1; }

        /* Memória Mestra */
        .memory-section {
            margin-top: 5rem;
            border-top: 1px solid var(--border);
            padding-top: 3rem;
        }

        .memory-card {
            background: linear-gradient(145deg, #181818, #111111);
            border: 1px solid rgba(232, 255, 71, 0.1);
            border-radius: 20px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .memory-content {
            font-size: 14px;
            line-height: 1.8;
            color: rgba(255,255,255,0.8);
            white-space: pre-wrap;
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
            background: rgba(0,0,0,0.92);
            backdrop-filter: blur(10px);
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
            border-radius: 24px;
            max-width: 450px;
            width: 100%;
            text-align: center;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
        }

        .modal-title {
            font-family: var(--display);
            font-size: 28px;
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
            gap: 12px;
            justify-content: center;
        }

        .btn-modal-cancel {
            background: rgba(255,255,255,0.05);
            color: var(--text);
            border: none;
            padding: 10px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 500;
        }

        .progress-container {
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            margin-top: 25px;
            overflow: hidden;
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
            margin-top: 15px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            font-weight: 600;
        }
    </style>
</head>
<body x-data="knowledgeManager()">
    <div class="page-wrap">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <a href="index.php" class="back-link">← Voltar para Roteiros</a>
            <button @click="rebuildMemory()" class="btn-accent" style="padding: 6px 14px; font-size: 11px; border-radius: 6px;" :disabled="uploading">
                <i class="fa-solid fa-sync" :class="uploading ? 'fa-spin' : ''"></i> Sincronizar Memória
            </button>
        </div>
        
        <div class="header">
            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 0.25em; color: var(--accent); margin-bottom: 12px; font-weight: 700;">Inteligência Artificial</div>
            <h1>Base de <em>Conhecimento</em></h1>

            <!-- Contador de arquivos -->
            <div style="display:flex; align-items:center; gap:12px; margin-top:16px;">
                <div style="flex:1; max-width:200px;">
                    <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--muted); margin-bottom:5px;">
                        <span>Fontes de conhecimento</span>
                        <span style="color:<?= $totalArquivos >= $limiteArquivos ? '#ef4444' : 'var(--accent)' ?>; font-weight:700;">
                            <?= $totalArquivos ?> / <?= $limiteArquivos ?>
                        </span>
                    </div>
                    <div style="height:4px; background:rgba(255,255,255,0.06); border-radius:4px; overflow:hidden;">
                        <div style="height:100%; width:<?= round(($totalArquivos / $limiteArquivos) * 100) ?>%; background:<?= $totalArquivos >= $limiteArquivos ? '#ef4444' : 'var(--accent)' ?>; border-radius:4px; transition:width 0.4s;"></div>
                    </div>
                </div>
                <?php if ($totalArquivos >= $limiteArquivos): ?>
                <div style="font-size:12px; color:#fca5a5; background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); padding:4px 12px; border-radius:100px;">
                    ⚠️ Limite atingido — remova fontes para adicionar novas
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- NotebookLM Style Input Zone -->
        <div class="upload-zone" 
             x-show="!uploading"
             @dragover.prevent="isDragging = true"
             @dragleave.prevent="isDragging = false"
             @drop.prevent="isDragging = false; handleDrop($event)"
             :style="isDragging ? 'border-color: var(--accent); background: rgba(232, 255, 71, 0.05)' : ''">
            
            <div class="upload-title">ou solte seus arquivos</div>
            <div class="upload-subtitle">pdf, imagens, documentos, links e outros</div>
            
            <div class="input-pills">
                <div class="pill" @click="$refs.fileInput.click()">
                    <i class="fa-solid fa-arrow-up-from-bracket"></i> Enviar arquivos
                </div>
                <div class="pill" @click="openLinkModal()">
                    <i class="fa-solid fa-link"></i>
                    <i class="fa-brands fa-youtube"></i> Sites
                </div>
                <div class="pill" @click="openTextModal()">
                    <i class="fa-solid fa-paste"></i> Texto copiado
                </div>
            </div>
            <input type="file" x-ref="fileInput" @change="uploadFile($event.target.files[0])" accept=".pdf,.txt,.md,.png,.jpg,.jpeg" style="display: none;">
        </div>

        <!-- Estado de Upload / Processamento -->
        <div x-show="uploading" class="upload-zone" style="opacity: 0.9; border-style: solid;">
            <div class="status-msg" x-text="statusMsg"></div>
            <div class="progress-container">
                <div class="progress-bar" :style="`width: ${progress}%`"></div>
            </div>
        </div>

        <div class="file-list">
            <template x-for="file in files" :key="file.id">
                <div class="file-item">
                    <div class="file-info" style="cursor: pointer; flex: 1;" @click="openFonteModal(file)">
                        <div class="file-icon">
                            <template x-if="file.tipo_arquivo === 'url'"><i class="fa-solid fa-link"></i></template>
                            <template x-if="file.tipo_arquivo === 'text'"><i class="fa-solid fa-paste"></i></template>
                            <template x-if="file.tipo_arquivo === 'pdf'"><i class="fa-solid fa-file-pdf"></i></template>
                            <template x-if="['png','jpg','jpeg'].includes(file.tipo_arquivo)"><i class="fa-solid fa-image"></i></template>
                            <template x-if="!['url','text','pdf','png','jpg','jpeg'].includes(file.tipo_arquivo)"><i class="fa-solid fa-file-lines"></i></template>
                        </div>
                        <div>
                            <div class="file-name" x-text="file.nome_arquivo" style="text-decoration: none; transition: color 0.2s;" :style="'cursor:pointer'" onmouseenter="this.style.color='var(--accent)'" onmouseleave="this.style.color=''"></div>
                            <div class="file-date" x-text="formatDate(file.created_at)"></div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <template x-if="file.sincronizado">
                            <span title="Destilado na Memória Mestra" style="font-size: 9px; color: var(--accent); font-weight: 700; letter-spacing: 1px; background: rgba(232,255,71,0.08); padding: 3px 8px; border-radius: 20px; border: 1px solid rgba(232,255,71,0.2);">✦ NA MEMÓRIA</span>
                        </template>
                        <template x-if="!file.sincronizado">
                            <span title="Ainda não destilado" style="font-size: 9px; color: #888; font-weight: 600; letter-spacing: 1px; background: rgba(255,255,255,0.03); padding: 3px 8px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.08);">PENDENTE</span>
                        </template>
                        <button class="btn-delete" @click="deleteFile(file.id)" :disabled="uploading">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Memória Mestra Consolidada -->
        <template x-if="masterMemory">
            <div class="memory-section">
                <div class="memory-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 style="font-family: var(--serif); font-style: italic; font-size: 28px;">O que a IA sabe</h2>
                    <span class="memory-badge">Cérebro Digital</span>
                </div>
                <div class="memory-card">
                    <div class="memory-content" x-text="masterMemory"></div>
                </div>
            </div>
        </template>

        <!-- Modal Customizado -->
        <template x-if="modal.show">
            <div class="modal-overlay">
                <div class="modal-card">
                    <div class="modal-title" x-text="modal.title"></div>
                    <div class="modal-text" x-text="modal.text"></div>
                    
                    <template x-if="modal.input === 'url'">
                        <input type="url" x-model="modal.inputValue" placeholder="https://exemplo.com ou link do YouTube" 
                               style="width: 100%; padding: 14px; background: #000; border: 1px solid var(--border); border-radius: 12px; color: #fff; margin-bottom: 2rem; outline: none; font-family: inherit;">
                    </template>

                    <template x-if="modal.input === 'textarea'">
                        <textarea x-model="modal.inputValue" placeholder="Cole seu conhecimento estratégico aqui..." rows="8"
                               style="width: 100%; padding: 14px; background: #000; border: 1px solid var(--border); border-radius: 12px; color: #fff; margin-bottom: 2rem; font-family: inherit; font-size: 14px; outline: none; resize: none;"></textarea>
                    </template>

                    <div class="modal-footer">
                        <button class="btn-modal-cancel" @click="modal.show = false">Cancelar</button>
                        <button class="btn-accent" @click="modalAction()">Confirmar</button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Modal de Ver/Editar Texto da Fonte -->
        <template x-if="textModal.show">
            <div class="modal-overlay" @click.self="textModal.show = false">
                <div class="modal-card" style="max-width: 700px; width: 95vw; max-height: 85vh; display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div>
                            <div class="modal-title" style="font-size: 20px;" x-text="textModal.nome"></div>
                            <div style="font-size: 11px; color: var(--muted); margin-top: 4px;" x-text="textModal.editavel ? 'Clique no texto para editar' : 'Texto extraído pela IA (somente leitura)'"></div>
                        </div>
                        <button @click="textModal.show = false" style="background: none; border: none; color: var(--muted); cursor: pointer; font-size: 20px; line-height: 1;">✕</button>
                    </div>

                    <template x-if="textModal.carregando">
                        <div style="text-align: center; padding: 3rem; color: var(--muted);">Carregando...</div>
                    </template>

                    <template x-if="!textModal.carregando">
                        <div style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; padding-right: 8px;">
                            
                            <template x-if="!textModal.editavel">
                                <div 
                                    x-text="textModal.texto"
                                    style="color: #fff; font-family: inherit; font-size: 14px; line-height: 1.7; white-space: pre-wrap; word-wrap: break-word;"
                                ></div>
                            </template>

                            <template x-if="textModal.editavel">
                                <textarea 
                                    x-model="textModal.texto"
                                    style="flex: 1; min-height: 450px; padding: 16px; background: #111; border: 1px solid rgba(232,255,71,0.3); border-radius: 12px; color: #fff; font-family: inherit; font-size: 14px; line-height: 1.7; outline: none; resize: vertical; width: 100%; box-sizing: border-box; overflow: hidden;"
                                    x-init="$nextTick(() => { $el.style.height = $el.scrollHeight + 'px' })"
                                    @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                                ></textarea>
                            </template>

                            <div class="modal-footer" style="padding: 0; margin-top: 1rem; position: sticky; bottom: 0; background: var(--bg-card); padding-top: 1rem;">
                                <button class="btn-modal-cancel" @click="textModal.show = false">Fechar</button>
                                <template x-if="textModal.editavel">
                                    <button class="btn-accent" @click="salvarTextoFonte()" :disabled="textModal.salvando">
                                        <span x-text="textModal.salvando ? 'Salvando...' : 'Salvar Alterações'"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
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
                isDragging: false,
                progress: 0,
                statusMsg: '',
                modal: {
                    show: false,
                    title: '',
                    text: '',
                    type: 'alert',
                    input: '',
                    inputValue: '',
                    onConfirm: null
                },

                textModal: {
                    show: false,
                    id: null,
                    nome: '',
                    tipo: '',
                    texto: '',
                    carregando: true,
                    editavel: false,
                    salvando: false
                },

                showAlert(title, text) {
                    this.modal = { show: true, title, text, type: 'alert', onConfirm: null };
                },

                async openFonteModal(file) {
                    this.textModal = {
                        show: true,
                        id: file.id,
                        nome: file.nome_arquivo,
                        tipo: file.tipo_arquivo,
                        texto: '',
                        carregando: true,
                        editavel: file.tipo_arquivo === 'text',
                        salvando: false
                    };
                    try {
                        const res = await fetch(`../api/roteiros/fonte_texto.php?id=${file.id}`);
                        const text = await res.text();
                        try {
                            const data = JSON.parse(text);
                            if (data.success) {
                                this.textModal.texto = data.fonte.texto_extraido || '(Sem conteúdo extraído ainda)';
                            } else {
                                this.textModal.texto = 'Erro da API: ' + (data.error || data.erro || text);
                            }
                        } catch(jsonErr) {
                            this.textModal.texto = 'Resposta inválida do servidor:\n\n' + text.replace(/<[^>]*>/g, '').trim().substring(0, 500);
                        }
                    } catch(e) {
                        this.textModal.texto = 'Falha de rede ao carregar o texto: ' + e.message;
                    }
                    this.textModal.carregando = false;
                },

                async salvarTextoFonte() {
                    this.textModal.salvando = true;
                    const formData = new FormData();
                    formData.append('id', this.textModal.id);
                    formData.append('texto', this.textModal.texto);
                    try {
                        const res = await fetch('../api/roteiros/fonte_texto.php', { method: 'POST', body: formData });
                        const data = await res.json();
                        if (data.success) {
                            // Marca a fonte como pendente na lista (precisará resincronizar)
                            const idx = this.files.findIndex(f => f.id === this.textModal.id);
                            if (idx !== -1) this.files[idx].sincronizado = false;
                            this.textModal.show = false;
                        } else {
                            this.showAlert('Erro', data.error);
                        }
                    } catch(e) {
                        this.showAlert('Erro', 'Falha ao salvar.');
                    }
                    this.textModal.salvando = false;
                },

                showConfirm(title, text, callback) {
                    this.modal = { show: true, title, text, type: 'confirm', onConfirm: callback };
                },

                openLinkModal() {
                    this.modal = { 
                        show: true, title: 'Adicionar Link', text: 'Insira a URL de um site, artigo ou vídeo do YouTube:', 
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
                    this.statusMsg = type === 'url' ? 'Lendo conteúdo...' : 'Salvando texto...';

                    fetch('../api/roteiros/salvar_fonte.php', {
                        method: 'POST',
                        body: JSON.stringify({ type, value })
                    })
                    .then(r => r.json())
                    .then(res => {
                        this.uploading = false;
                        if (res.success) {
                            this.adicionarResultado(res);
                        } else {
                            this.showAlert('Ops!', res.error);
                        }
                    })
                    .catch(() => this.showAlert('Erro', 'Falha na comunicação com o servidor.'));
                },

                handleDrop(e) {
                    const file = e.dataTransfer.files[0];
                    if (file) this.uploadFile(file);
                },

                uploadFile(file) {
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
                            if (this.progress === 100) this.statusMsg = 'Destilando Conhecimento (IA)...';
                        }
                    });

                    xhr.onreadystatechange = () => {
                        if (xhr.readyState === 4) {
                            this.uploading = false;
                            try {
                                const res = JSON.parse(xhr.responseText);
                                if (res.success) {
                                    this.adicionarResultado(res);
                                } else {
                                    this.showAlert('Ops!', res.error || 'Falha no processamento');
                                }
                            } catch(e) {
                                const preview = xhr.responseText
                                    ? xhr.responseText.replace(/<[^>]*>/g, '').trim().substring(0, 300)
                                    : 'Sem resposta do servidor';
                                this.showAlert('Erro ' + xhr.status, preview || 'O servidor encontrou um problema técnico.');
                            }
                        }
                    };
                    xhr.open('POST', '../api/roteiros/upload_conhecimento.php', true);
                    xhr.send(formData);
                },

                deleteFile(id) {
                    this.showConfirm('Remover Fonte', 'Deseja remover esta fonte? A memória será reconstruída.', () => {
                        fetch('../api/roteiros/deletar_conhecimento.php', {
                            method: 'POST',
                            body: JSON.stringify({ id })
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) location.reload();
                            else this.showAlert('Erro', res.error);
                        });
                    });
                },

                // Atualiza o estado local com o resultado do upload/fonte — sem F5
                adicionarResultado(res) {
                    if (res.arquivo) {
                        // Marca como sincronizado com base na resposta do servidor
                        const novoArquivo = res.arquivo;
                        novoArquivo.sincronizado = res.arquivo.sincronizado || false;
                        this.files.unshift(novoArquivo);
                    }
                    if (res.nova_memoria) {
                        this.masterMemory = res.nova_memoria;
                    }
                    // Animação: rola suavemente até a memória atualizada
                    this.$nextTick(() => {
                        const mem = document.querySelector('.memory-section');
                        if (mem) mem.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                },

                async rebuildMemory() {
                    const pendentes = this.files.filter(f => !f.sincronizado);

                    if (pendentes.length === 0) {
                        this.showAlert('Memória Atualizada', 'Todas as fontes já estão na memória mestra. Nada a processar.');
                        return;
                    }

                    this.uploading = true;
                    const total = pendentes.length;

                    for (let i = 0; i < total; i++) {
                        const fonte = pendentes[i];
                        this.statusMsg = `Processando (${i + 1}/${total}): ${fonte.nome_arquivo}`;
                        this.progress = Math.round((i / total) * 100);

                        try {
                            const formData = new FormData();
                            formData.append('id', fonte.id);

                            const res = await fetch('../api/roteiros/processar_fonte.php', {
                                method: 'POST',
                                body: formData
                            });
                            const data = await res.json();

                            if (data.success) {
                                // Atualiza o badge do arquivo na lista em tempo real
                                const idx = this.files.findIndex(f => f.id === fonte.id);
                                if (idx !== -1) this.files[idx].sincronizado = data.sincronizado;
                                // Atualiza a memória mestra na tela
                                if (data.nova_memoria) this.masterMemory = data.nova_memoria;
                            }
                            // Se falhou, continua para o próximo sem parar
                        } catch(e) { /* segue pro próximo */ }
                    }

                    this.progress = 100;
                    this.statusMsg = `✓ ${total} fonte(s) processada(s) com sucesso!`;
                    setTimeout(() => { this.uploading = false; }, 2000);
                },

                formatDate(dateStr) {
                    return new Date(dateStr).toLocaleDateString('pt-BR');
                }
            }
        }
    </script>
</body>
</html>
