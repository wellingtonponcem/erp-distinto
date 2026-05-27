<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pdf_templates.php';

exigirAdmin();

$db = Database::get();
garantirTabelasPdfTemplates($db);

$id = $_GET['id'] ?? '';
$template = [
    'id' => $id ?: gerarId(),
    'nome' => 'Novo template',
    'tipo' => 'casamento',
    'ativo' => 1,
    'config' => ['pages' => []],
];

if ($id) {
    $stmt = $db->prepare("SELECT * FROM pdf_templates WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) die('Template nao encontrado.');
    $template = [
        'id' => $row['id'],
        'nome' => $row['nome'],
        'tipo' => $row['tipo'],
        'ativo' => (int)$row['ativo'],
        'config' => json_decode($row['config_json'] ?? '{}', true) ?: ['pages' => []],
    ];
}

$camposPorSessao = [
    '01 - Capa' => [
        'nome_casal' => 'Nome do casal',
        'nome_noivo' => 'Nome do noivo',
        'nome_noiva' => 'Nome da noiva',
        'data_casamento' => 'Data do casamento',
    ],
    '02 - O registro do que voces estao construindo' => [
        'saudacao_casal' => 'Saudacao: Ola, casal',
        'mensagem_pessoal' => 'Texto principal da sessao',
    ],
    '03 - Visao e missao' => [
        'visao_ia' => 'Texto de visao gerado por IA',
    ],
    '04 - Pacotes e investimento' => [
        'pacote_escolhido' => 'Pacote escolhido',
        'valor_total' => 'Valor total',
        'itens_inclusos' => 'Itens inclusos',
        'condicoes_pagamento' => 'Condicoes de pagamento',
    ],
    '05 - Prazos e validade' => [
        'prazo_previas' => 'Prazo de previas',
        'prazo_final' => 'Prazo final',
        'validade_proposta' => 'Validade da proposta',
    ],
    '06 - Andamento da negociacao' => [
        'andamento_proposta' => 'Historico/andamento',
    ],
    '99 - Dados gerais' => [
        'cliente_nome' => 'Nome do cliente',
        'titulo_proposta' => 'Titulo da proposta',
    ],
];

$tituloPagina = 'Editor de Template PDF';
include __DIR__ . '/../includes/layout/head.php';
?>

<style>
    .pdf-editor-shell { display: grid; grid-template-columns: 220px minmax(0, 1fr) 300px; gap: 16px; min-height: calc(100vh - 120px); }
    .pdf-stage-wrap { overflow: auto; background: #0b0b0b; border-radius: 12px; padding: 18px; }
    .pdf-page-stage { position: relative; width: calc(100% * var(--editor-zoom, 1)); min-width: 720px; aspect-ratio: 16 / 9; margin: 0 auto; background: #fff; box-shadow: 0 24px 80px rgba(0,0,0,.35); overflow: hidden; }
    .pdf-page-stage img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: contain; user-select: none; pointer-events: none; }
    .pdf-field { position: absolute; min-width: 40px; min-height: 24px; border: 1px dashed rgba(255,255,255,.65); background: rgba(0,0,0,.18); cursor: move; white-space: pre-wrap; overflow: hidden; padding: 4px; }
    .pdf-field.active { outline: 2px solid #38bdf8; border-color: #38bdf8; }
    .prop-label { display:block; font-size:10px; font-weight:900; text-transform:uppercase; letter-spacing:.08em; color:#71717a; margin-bottom:6px; }
    .editor-error { border: 1px solid rgba(239,68,68,.3); background: rgba(239,68,68,.08); color: #fca5a5; border-radius: 10px; padding: 10px 12px; font-size: 12px; font-weight: 700; }
</style>

<div id="app-wrapper" x-data="pdfTemplateEditor()" x-init="init()">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content" class="content-sheet">
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                <h1 class="page-title text-2xl">Editor de Template PDF</h1>
                <p class="page-subtitle text-zinc-500">Use imagens do Canva como fundo e posicione os campos dinamicos.</p>
            </div>
            <div class="flex gap-2">
                <button type="button" @click="preview()" class="btn">Pre-visualizar</button>
                <button type="button" @click="save()" class="btn btn-primary">Salvar</button>
            </div>
        </div>

        <div x-show="error" x-text="error" class="editor-error mb-4"></div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
            <div>
                <label class="prop-label">Nome</label>
                <input class="input" x-model="template.nome">
            </div>
            <div>
                <label class="prop-label">Tipo</label>
                <select class="input" x-model="template.tipo">
                    <option value="casamento">Casamento</option>
                    <option value="15anos">15 Anos</option>
                    <option value="filmmaker">Filmmaker</option>
                    <option value="marketing">Marketing</option>
                </select>
            </div>
            <label class="flex items-center gap-2 pt-7 text-sm font-bold text-zinc-700">
                <input type="checkbox" x-model="template.ativo"> Template ativo
            </label>
        </div>

        <div class="pdf-editor-shell">
            <aside class="card p-4 overflow-auto">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-black">Paginas</h2>
                    <button type="button" @click="$refs.upload.click()" class="text-xs font-bold" :disabled="uploading" x-text="uploading ? 'Enviando...' : 'Adicionar'"></button>
                    <input type="file" x-ref="upload" class="hidden" accept="image/png,image/jpeg,image/webp" @change="uploadPage($event)">
                </div>
                <template x-for="(page, index) in template.config.pages" :key="page.id">
                    <button type="button" @click="currentPage = index; selectedField = null" class="w-full text-left p-3 rounded-lg mb-2 border" :class="currentPage === index ? 'border-zinc-900 bg-zinc-100' : 'border-zinc-200'">
                        <span class="text-xs font-black" x-text="'Pagina ' + (index + 1)"></span>
                    </button>
                </template>
                <div class="mt-4 space-y-2">
                    <button type="button" @click="$refs.replaceUpload.click()" class="btn w-full" :disabled="!page || uploading">Substituir imagem</button>
                    <button type="button" @click="removePage()" class="btn w-full text-red-500" :disabled="!page || uploading">Remover pagina</button>
                    <input type="file" x-ref="replaceUpload" class="hidden" accept="image/png,image/jpeg,image/webp" @change="replacePage($event)">
                </div>
            </aside>

            <section class="pdf-stage-wrap" :style="'--editor-zoom:' + zoom" @wheel="handleStageWheel($event)">
                <template x-if="page">
                    <div class="pdf-page-stage" x-ref="stage">
                        <img :src="page.image">
                        <template x-for="field in page.fields" :key="field.id">
                            <div class="pdf-field"
                                 :class="{ active: selectedField && selectedField.id === field.id }"
                                 :style="fieldStyle(field)"
                                 @mousedown.prevent="startDrag($event, field)"
                                 @click.stop="selectedField = field"
                                 x-text="fieldPreview(field)">
                            </div>
                        </template>
                    </div>
                </template>
                <div x-show="!page" class="text-center text-zinc-500 py-20">Adicione uma pagina para comecar.</div>
            </section>

            <aside class="card p-4 overflow-auto">
                <button type="button" @click="addField()" class="btn btn-primary w-full mb-4" :disabled="!page">Adicionar campo</button>
                <template x-if="selectedField">
                    <div class="space-y-4">
                        <div>
                            <label class="prop-label">Campo dinamico</label>
                            <select class="input" x-model="selectedField.key">
                                <?php foreach ($camposPorSessao as $sessao => $campos): ?>
                                    <optgroup label="<?= sanitizar($sessao) ?>">
                                        <?php foreach ($campos as $campo => $label): ?>
                                            <option value="<?= $campo ?>"><?= sanitizar($label) ?> (<?= $campo ?>)</option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="prop-label">Texto fixo opcional</label>
                            <textarea class="input" rows="2" x-model="selectedField.text"></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="prop-label">X %</label><input type="number" class="input" x-model.number="selectedField.x"></div>
                            <div><label class="prop-label">Y %</label><input type="number" class="input" x-model.number="selectedField.y"></div>
                            <div><label class="prop-label">Largura %</label><input type="number" class="input" x-model.number="selectedField.w"></div>
                            <div><label class="prop-label">Altura %</label><input type="number" class="input" x-model.number="selectedField.h"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="prop-label">Fonte</label>
                                <select class="input" x-model="selectedField.font" @change="loadGoogleFont(selectedField.font)">
                                    <template x-for="font in fontOptions" :key="font">
                                        <option :value="font" x-text="font"></option>
                                    </template>
                                </select>
                            </div>
                            <div><label class="prop-label">Tamanho</label><input type="number" class="input" x-model.number="selectedField.size"></div>
                            <div><label class="prop-label">Cor</label><input type="color" class="input h-10" x-model="selectedField.color"></div>
                            <div><label class="prop-label">Peso</label><select class="input" x-model="selectedField.weight"><option value="400">Normal</option><option value="700">Negrito</option><option value="900">Black</option></select></div>
                        </div>
                        <div>
                            <label class="prop-label">Alinhamento</label>
                            <select class="input" x-model="selectedField.align"><option value="left">Esquerda</option><option value="center">Centro</option><option value="right">Direita</option></select>
                        </div>
                        <button type="button" @click="removeField()" class="btn w-full text-red-500">Remover campo</button>
                    </div>
                </template>
            </aside>
        </div>
    </main>
</div>

<script>
function pdfTemplateEditor() {
    return {
        template: <?= json_encode($template, JSON_UNESCAPED_UNICODE) ?>,
        currentPage: 0,
        selectedField: null,
        uploading: false,
        zoom: 1,
        stageScale: 1,
        error: '',
        fontOptions: [
            'Outfit', 'Montserrat', 'Playfair Display', 'Cormorant Garamond',
            'Libre Baskerville', 'Lora', 'Merriweather', 'Poppins',
            'Inter', 'Roboto', 'Open Sans', 'Raleway', 'Nunito Sans',
            'Cinzel', 'Bodoni Moda', 'Great Vibes', 'Parisienne',
            'Dancing Script', 'Sacramento'
        ],
        values: {
            nome_casal: 'Igor & Gabriela',
            saudacao_casal: 'Ola, Igor & Gabriela!',
            mensagem_pessoal: 'A gente sabe que fotografia e muito mais do que so apertar um botao. Nosso trabalho e capturar o que voces sentem um pelo outro, de um jeito que pareca real e sem poses forcadas.',
            visao_ia: 'Uma leitura sensivel da historia do casal, transformando o dia em memoria visual com verdade, beleza e intencao.',
            pacote_escolhido: 'Experiencia Heritage',
            valor_total: 'R$ 7.900,00',
            itens_inclusos: 'Cobertura documental\nAlbum\nDrone',
            condicoes_pagamento: 'Entrada de 20% + saldo parcelado'
        },
        get page() { return this.template.config.pages[this.currentPage] || null; },
        init() {
            if (!this.template.config) this.template.config = { pages: [] };
            if (!this.template.config.pages) this.template.config.pages = [];
            this.template.config.pages.forEach(page => {
                (page.fields || []).forEach(field => {
                    if (field.font && field.font.includes(',')) {
                        field.font = field.font.split(',')[0].replace(/["']/g, '').trim();
                    }
                    if (field.font && !this.fontOptions.includes(field.font)) {
                        this.fontOptions.push(field.font);
                    }
                });
            });
            this.usedFonts().forEach(font => this.loadGoogleFont(font));
            this.$nextTick(() => this.updateStageScale());
            window.addEventListener('resize', () => this.updateStageScale());
        },
        newId() {
            return window.crypto?.randomUUID ? crypto.randomUUID() : 'id-' + Date.now() + '-' + Math.random().toString(16).slice(2);
        },
        fieldPreview(field) {
            return field.text || this.values[field.key] || '{{' + field.key + '}}';
        },
        fieldStyle(field) {
            const size = Math.max(1, (field.size || 18) * this.stageScale);
            return `left:${field.x}%;top:${field.y}%;width:${field.w}%;height:${field.h}%;font-family:${field.font};font-size:${size}px;color:${field.color};font-weight:${field.weight};text-align:${field.align};line-height:${field.lineHeight || 1.25};`;
        },
        updateStageScale() {
            if (!this.$refs.stage) return;
            this.stageScale = Math.max(0.1, this.$refs.stage.getBoundingClientRect().width / 960);
        },
        usedFonts() {
            const fonts = new Set();
            this.template.config.pages.forEach(page => {
                (page.fields || []).forEach(field => {
                    if (field.font) fonts.add(field.font);
                });
            });
            return Array.from(fonts);
        },
        googleFontUrl(fonts) {
            const families = [...new Set(fonts.filter(Boolean))]
                .map(font => 'family=' + encodeURIComponent(font).replace(/%20/g, '+') + ':wght@400;500;600;700;800;900')
                .join('&');
            return families ? 'https://fonts.googleapis.com/css2?' + families + '&display=swap' : '';
        },
        loadGoogleFont(font) {
            if (!font) return;
            const id = 'google-font-' + font.toLowerCase().replace(/[^a-z0-9]+/g, '-');
            if (document.getElementById(id)) return;
            const link = document.createElement('link');
            link.id = id;
            link.rel = 'stylesheet';
            link.href = this.googleFontUrl([font]);
            document.head.appendChild(link);
        },
        addField() {
            if (!this.page) return;
            const field = { id: this.newId(), key: 'nome_casal', text: '', x: 10, y: 10, w: 25, h: 8, font: 'Montserrat', size: 24, color: '#111111', weight: '700', align: 'left', lineHeight: 1.25 };
            this.loadGoogleFont(field.font);
            this.page.fields.push(field);
            this.selectedField = field;
        },
        removeField() {
            if (!this.page || !this.selectedField) return;
            this.page.fields = this.page.fields.filter(f => f.id !== this.selectedField.id);
            this.selectedField = null;
        },
        async parseJsonResponse(response) {
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (error) {
                throw new Error(text ? text.slice(0, 300) : `Resposta invalida do servidor (${response.status})`);
            }
        },
        async uploadImage(file) {
            this.error = '';
            this.uploading = true;
            try {
                const form = new FormData();
                form.append('imagem', file);
                const res = await fetch('<?= raizUrl('/api/pdf-templates/upload-page.php') ?>', { method: 'POST', body: form });
                const data = await this.parseJsonResponse(res);
                if (!res.ok || !data.success) throw new Error(data.erro || 'Falha no upload.');
                return data.url;
            } finally {
                this.uploading = false;
            }
        },
        async uploadPage(event) {
            const file = event.target.files[0];
            event.target.value = '';
            if (!file) return;
            try {
                const url = await this.uploadImage(file);
                this.template.config.pages.push({ id: this.newId(), image: url, fields: [] });
                this.currentPage = this.template.config.pages.length - 1;
                this.selectedField = null;
                this.$nextTick(() => this.updateStageScale());
            } catch (error) {
                this.error = error.message || 'Falha no upload.';
                alert(this.error);
            }
        },
        async replacePage(event) {
            const file = event.target.files[0];
            event.target.value = '';
            if (!this.page || !file) return;
            try {
                this.page.image = await this.uploadImage(file);
            } catch (error) {
                this.error = error.message || 'Falha no upload.';
                alert(this.error);
            }
        },
        removePage() {
            if (!this.page || !confirm('Remover esta pagina do template?')) return;
            this.template.config.pages.splice(this.currentPage, 1);
            this.currentPage = Math.max(0, this.currentPage - 1);
            this.selectedField = null;
        },
        handleStageWheel(event) {
            if (!event.altKey) return;
            event.preventDefault();
            const delta = event.deltaY > 0 ? -0.08 : 0.08;
            this.zoom = Math.max(0.45, Math.min(2.5, Math.round((this.zoom + delta) * 100) / 100));
            this.$nextTick(() => this.updateStageScale());
        },
        startDrag(event, field) {
            this.selectedField = field;
            const rect = this.$refs.stage.getBoundingClientRect();
            const startX = event.clientX;
            const startY = event.clientY;
            const startField = { x: field.x, y: field.y };
            const move = (e) => {
                field.x = Math.max(0, Math.min(100 - field.w, startField.x + ((e.clientX - startX) / rect.width) * 100));
                field.y = Math.max(0, Math.min(100 - field.h, startField.y + ((e.clientY - startY) / rect.height) * 100));
            };
            const up = () => {
                window.removeEventListener('mousemove', move);
                window.removeEventListener('mouseup', up);
            };
            window.addEventListener('mousemove', move);
            window.addEventListener('mouseup', up);
        },
        async save() {
            this.error = '';
            this.usedFonts().forEach(font => this.loadGoogleFont(font));
            try {
                const res = await fetch('<?= raizUrl('/api/pdf-templates/save.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.template)
                });
                const data = await this.parseJsonResponse(res);
                if (!res.ok || !data.success) throw new Error(data.erro || 'Falha ao salvar.');
                this.template.id = data.id;
                history.replaceState(null, '', '<?= raizUrl('/gerenciamento/pdf_template_editor.php?id=') ?>' + data.id);
                alert('Template salvo.');
            } catch (error) {
                this.error = error.message || 'Falha ao salvar.';
                alert(this.error);
            }
        },
        preview() {
            if (!this.template.config.pages.length) return alert('Adicione pelo menos uma pagina.');
            const win = window.open('', '_blank');
            if (!win) return alert('Permita pop-ups para visualizar o template.');
            const esc = (value) => String(value || '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
            const fontLink = this.googleFontUrl(this.usedFonts());
            const pages = this.template.config.pages.map(page => {
                const fields = (page.fields || []).map(field => {
                    const text = esc(field.text || this.values[field.key] || '{{' + field.key + '}}').replace(/\n/g, '<br>');
                    return `<div style="position:absolute;left:${field.x || 0}%;top:${field.y || 0}%;width:${field.w || 20}%;height:${field.h || 8}%;font-family:${esc(field.font || 'Arial')};font-size:${field.size || 18}px;color:${esc(field.color || '#111')};font-weight:${esc(field.weight || '400')};text-align:${esc(field.align || 'left')};line-height:${field.lineHeight || 1.25};white-space:pre-wrap;overflow:hidden;">${text}</div>`;
                }).join('');
                return `<section class="page"><img src="${esc(page.image)}">${fields}</section>`;
            }).join('');
            win.document.write(`<!doctype html><html><head><title>Preview PDF</title>${fontLink ? `<link rel="stylesheet" href="${esc(fontLink)}">` : ''}<style>body{margin:0;background:#222;font-family:Arial,sans-serif}.page{position:relative;width:960px;height:540px;margin:24px auto;background:#fff;overflow:hidden}.page img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain}@media print{@page{size:160mm 90mm;margin:0}body{background:#fff}.page{margin:0;width:160mm;height:90mm;page-break-after:always}}</style></head><body>${pages}</body></html>`);
            win.document.close();
        }
    }
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
