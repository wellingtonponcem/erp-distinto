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

$planosOpcoes = [
    ['id' => 'heritage', 'nome' => 'Experiencia Heritage'],
    ['id' => 'cinematic', 'nome' => 'Experiencia Cinematic'],
    ['id' => 'essencial', 'nome' => 'Registro Essencial'],
];
try {
    $stmtPlanos = $db->query("SELECT id, nome FROM servicos WHERE categoria = 'wedding' AND tipo = 'plano' AND ativo = 1");
    $planosBanco = $stmtPlanos->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($planosBanco)) {
        $planosOpcoes = [];
        foreach ($planosBanco as $plano) {
            $slugPlano = strpos(strtolower($plano['nome']), 'essencial') !== false ? 'essencial' : (strpos(strtolower($plano['nome']), 'cinematic') !== false ? 'cinematic' : 'heritage');
            $planosOpcoes[] = [
                'id' => $slugPlano,
                'nome' => $plano['nome']
            ];
        }
    }
} catch (Exception $e) {
    // Silencia erros se a tabela nao existir
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
    '04 - Experiencias distintas' => [
        'experiencias_distintas_texto' => 'Texto completo da sessao',
    ],
    '05 - Pacotes e investimento' => [
        'pacote_escolhido' => 'Pacote escolhido',
        'valor_total' => 'Valor total',
        'itens_inclusos' => 'Itens inclusos',
        'itens_adicionais' => 'Adicionais selecionados',
        'condicoes_pagamento' => 'Condicoes de pagamento',
        'condicao_especial' => 'Condicao especial',
    ],
    '06 - Prazos e validade' => [
        'prazo_previas' => 'Prazo de previas',
        'prazo_final' => 'Prazo final',
        'validade_proposta' => 'Validade da proposta',
    ],
    '07 - Andamento da negociacao' => [
        'andamento_proposta' => 'Historico/andamento',
    ],
    '08 - Pagina Dinamica de Pacote' => [
        'pacote_nome' => 'Nome do pacote',
        'pacote_valor' => 'Valor do pacote',
        'pacote_itens' => 'Itens do pacote',
        'pacote_adicionais' => 'Adicionais do pacote',
        'pacote_condicoes' => 'Condicoes do pacote',
        'pacote_foto' => 'Foto do pacote (Imagem)',
        'condicao_especial' => 'Condicao especial',
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
    .pdf-field { position: absolute; min-width: 40px; min-height: 24px; border: 1px dashed rgba(255,255,255,.65); background: rgba(0,0,0,.18); cursor: move; white-space: pre-wrap; overflow: visible; padding: 0; }
    .pdf-field-text { position: absolute; inset: 0; padding: 4px; overflow: hidden; pointer-events: none; }
    .pdf-field.active { outline: 2px solid #38bdf8; border-color: #38bdf8; }
    .pdf-resize-handle { position: absolute; width: 10px; height: 10px; background: #38bdf8; border: 2px solid #020617; border-radius: 999px; display: none; z-index: 5; }
    .pdf-field.active .pdf-resize-handle { display: block; }
    .pdf-resize-handle.nw { left: -6px; top: -6px; cursor: nwse-resize; }
    .pdf-resize-handle.n { left: 50%; top: -6px; transform: translateX(-50%); cursor: ns-resize; }
    .pdf-resize-handle.ne { right: -6px; top: -6px; cursor: nesw-resize; }
    .pdf-resize-handle.e { right: -6px; top: 50%; transform: translateY(-50%); cursor: ew-resize; }
    .pdf-resize-handle.se { right: -6px; bottom: -6px; cursor: nwse-resize; }
    .pdf-resize-handle.s { left: 50%; bottom: -6px; transform: translateX(-50%); cursor: ns-resize; }
    .pdf-resize-handle.sw { left: -6px; bottom: -6px; cursor: nesw-resize; }
    .pdf-resize-handle.w { left: -6px; top: 50%; transform: translateY(-50%); cursor: ew-resize; }
    .pdf-guide { position: absolute; background: #22c55e; pointer-events: none; z-index: 4; box-shadow: 0 0 12px rgba(34,197,94,.7); }
    .pdf-guide.v { top: 0; bottom: 0; width: 1px; }
    .pdf-guide.h { left: 0; right: 0; height: 1px; }
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
                <div class="space-y-2">
                    <template x-for="(page, index) in template.config.pages" :key="page.id">
                        <div class="flex items-center gap-1 p-1 bg-zinc-900/5 dark:bg-white/5 rounded-xl">
                            <button type="button" @click="currentPage = index; selectedField = null" class="flex-1 text-left p-3 rounded-lg border text-ellipsis overflow-hidden whitespace-nowrap" :class="currentPage === index ? 'border-zinc-900 bg-zinc-100 text-zinc-950 font-bold dark:border-white dark:bg-zinc-800 dark:text-white' : 'border-transparent text-zinc-500 hover:text-zinc-800'">
                                <span class="text-xs font-black" x-text="'Pagina ' + (index + 1) + (page.is_pacote ? (page.plano_id ? ' (' + obterNomePlano(page.plano_id) + ')' : ' (Pacotes)') : '')"></span>
                            </button>
                            <div class="flex flex-col gap-0.5">
                                <button type="button" @click.stop="movePageUp(index)" class="p-1 text-[9px] border border-zinc-700 rounded bg-zinc-800 text-zinc-300 hover:bg-zinc-700" :disabled="index === 0">▲</button>
                                <button type="button" @click.stop="movePageDown(index)" class="p-1 text-[9px] border border-zinc-700 rounded bg-zinc-800 text-zinc-300 hover:bg-zinc-700" :disabled="index === template.config.pages.length - 1">▼</button>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="mt-4 space-y-2">
                    <button type="button" @click="$refs.replaceUpload.click()" class="btn w-full" :disabled="!page || uploading">Substituir imagem</button>
                    <button type="button" @click="removePage()" class="btn w-full text-red-500" :disabled="!page || uploading">Remover pagina</button>
                    <input type="file" x-ref="replaceUpload" class="hidden" accept="image/png,image/jpeg,image/webp" @change="replacePage($event)">
                    
                    <template x-if="page">
                        <div class="mt-4 p-3 bg-zinc-850 border border-zinc-700 rounded-xl space-y-3">
                            <label class="flex items-center gap-2 text-xs font-black text-zinc-300 cursor-pointer">
                                <input type="checkbox" x-model="page.is_pacote" @change="if(!page.is_pacote) page.plano_id = ''"> Página de Pacotes
                            </label>
                            <template x-if="page.is_pacote">
                                <div>
                                    <label class="prop-label" style="font-size: 9px;">Vincular ao Pacote</label>
                                    <select class="input text-xs" x-model="page.plano_id" style="padding: 4px 8px; height: 32px;">
                                        <option value="">(Rotativo / Sequencial)</option>
                                        <template x-for="p in planosOpcoes" :key="p.id">
                                            <option :value="p.id" x-text="p.nome"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </aside>

            <section class="pdf-stage-wrap" :style="'--editor-zoom:' + zoom" @wheel="handleStageWheel($event)">
                <template x-if="page">
                    <div class="pdf-page-stage" x-ref="stage">
                        <img :src="page.image">
                        <template x-for="(guide, index) in guides" :key="'guide-' + index">
                            <div class="pdf-guide" :class="guide.type" :style="guideStyle(guide)"></div>
                        </template>
                        <template x-for="field in page.fields" :key="field.id">
                            <div class="pdf-field"
                                 :class="{ active: selectedField && selectedField.id === field.id }"
                                 :style="fieldStyle(field)"
                                 @mousedown.prevent="startDrag($event, field)"
                                 @click.stop="selectedField = field">
                                <div class="pdf-field-text" :style="'display:flex; flex-direction:column; justify-content:' + (field.valign || 'flex-start') + ';'">
                                    <template x-if="field.key === 'pacote_foto'">
                                        <img :src="fieldPreview(field)" style="width: 100%; height: 100%; object-fit: cover; pointer-events: none;">
                                    </template>
                                    <template x-if="field.key !== 'pacote_foto'">
                                        <div x-html="fieldPreview(field)" style="width: 100%; height: 100%;"></div>
                                    </template>
                                </div>
                                <template x-if="selectedField && selectedField.id === field.id">
                                    <div>
                                        <span class="pdf-resize-handle nw" @mousedown.stop.prevent="startResize($event, field, 'nw')"></span>
                                        <span class="pdf-resize-handle n" @mousedown.stop.prevent="startResize($event, field, 'n')"></span>
                                        <span class="pdf-resize-handle ne" @mousedown.stop.prevent="startResize($event, field, 'ne')"></span>
                                        <span class="pdf-resize-handle e" @mousedown.stop.prevent="startResize($event, field, 'e')"></span>
                                        <span class="pdf-resize-handle se" @mousedown.stop.prevent="startResize($event, field, 'se')"></span>
                                        <span class="pdf-resize-handle s" @mousedown.stop.prevent="startResize($event, field, 's')"></span>
                                        <span class="pdf-resize-handle sw" @mousedown.stop.prevent="startResize($event, field, 'sw')"></span>
                                        <span class="pdf-resize-handle w" @mousedown.stop.prevent="startResize($event, field, 'w')"></span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
                <div x-show="!page" class="text-center text-zinc-500 py-20">Adicione uma pagina para comecar.</div>
            </section>

            <aside class="card p-4 overflow-auto">
                <button type="button" @click="addField()" class="btn btn-primary w-full mb-2" :disabled="!page">Adicionar campo de texto</button>
                <button type="button" @click="addPhotoField()" class="btn w-full mb-4 bg-zinc-800 text-white hover:bg-zinc-700 font-bold border border-zinc-700" :disabled="!page">Adicionar Foto do Pacote</button>
                <template x-if="selectedField">
                    <div class="space-y-4">
                        <template x-if="selectedField.key === 'pacote_foto'">
                            <div class="p-3 bg-blue-500/10 border border-blue-500/30 rounded-xl text-xs text-blue-400 font-medium leading-relaxed mb-2">
                                <i data-lucide="info" class="w-4 h-4 inline-block mr-1 align-middle"></i>
                                Este campo é uma <strong>Foto Dinâmica do Pacote</strong>. Ele exibirá a imagem correspondente a cada plano em sequência no PDF.
                            </div>
                        </template>
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
                            <div>
                                <label class="prop-label">Formato do texto</label>
                                <select class="input" x-model="selectedField.transform">
                                    <option value="none">Normal</option>
                                    <option value="uppercase">Maiúsculas</option>
                                </select>
                            </div>
                            <div><label class="prop-label">Cor</label><input type="color" class="input h-10" x-model="selectedField.color"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="prop-label">Peso</label><select class="input" x-model="selectedField.weight"><option value="400">Normal</option><option value="700">Negrito</option><option value="900">Black</option></select></div>
                            <div>
                                <label class="prop-label">Alinhamento Horiz.</label>
                                <select class="input" x-model="selectedField.align"><option value="left">Esquerda</option><option value="center">Centro</option><option value="right">Direita</option></select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="prop-label">Alinhamento Vert.</label>
                                <select class="input" x-model="selectedField.valign">
                                    <option value="flex-start">Topo</option>
                                    <option value="center">Centro</option>
                                    <option value="flex-end">Base</option>
                                </select>
                            </div>
                        </div>
                        <button type="button" @click="removeField()" class="btn w-full text-red-500">Remover campo</button>
                    </div>
                </template>
            </aside>
        </div>
    </main>
</div>
function pdfTemplateEditor() {
    return {
        template: <?= json_encode($template, JSON_UNESCAPED_UNICODE) ?>,
        planosOpcoes: <?= json_encode($planosOpcoes, JSON_UNESCAPED_UNICODE) ?>,
        currentPage: 0,
        selectedField: null,
        uploading: false,
        zoom: 1,
        stageScale: 1,
        guides: [],
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
            experiencias_distintas_texto: 'Na Distinto, nao comecamos com ideias soltas. Comecamos com clareza.\n\nDesenhamos tres caminhos estrategicos para que a historia de <b>Igor & Gabriela</b> seja preservada com a forca e a verdade que merecem.\n\nApresentamos nossas propostas de investimento. Cada uma delas foi pensada para transformar o seu casamento em uma experiencia totalmente nova, onde a nossa perspectiva artistica garante que todas as variaveis do dia ganhem o mais bonito sentido.\n\nEscolham o caminho que melhor se conecta com o sonho de voces.\n\n<b>Nossa meta e uma so: arrepiar.</b>',
            pacote_escolhido: 'Experiencia Heritage',
            valor_total: 'R$ 7.900,00',
            itens_inclusos: '• <b>Cobertura Documental Completa:</b> Presença ilimitada no evento. Do making of à última música, sem limite de horas.\n• <b>O Álbum Heritage:</b> Álbum luxo panorâmico no tamanho 25x30cm (aberto 25x60cm), com papel fotográfico de alta gramatura e laminação especial.\n• <b>Réplicas para a Família (Presente):</b> Inclusão de 02 Mini Álbuns réplicas, ideais para presentear os pais com a mesma qualidade do álbum principal.\n• <b>Produção Cinematográfica 4K:</b> Filme completo (8 a 12 min) com áudio dos votos e trilha sonora licenciada.\n• <b>Imagens Aéreas (Drone):</b> Perspectivas cinematográficas para contextualizar o local do seu "sim".\n• <b>Ecossistema Digital e Físico:</b> Galeria online vitalícia, e pen drive personalizado.',
            itens_adicionais: '• <b>Boudoir da Noiva:</b> R$ 500,00\n• <b>Ensaio Pré-Wedding:</b> R$ 1.100,00',
            condicoes_pagamento: 'Entrada de 20% + saldo parcelado em até 6x',
            condicao_especial: '(Condição especial p/ amigos lagoinha)',
            pacote_nome: 'Experiencia Heritage',
            pacote_valor: 'R$ 7.900,00',
            pacote_itens: '• <b>Cobertura Documental Completa:</b> Presença ilimitada no evento. Do making of à última música, sem limite de horas.\n• <b>O Álbum Heritage:</b> Álbum luxo panorâmico no tamanho 25x30cm (aberto 25x60cm), com papel fotográfico de alta gramatura e laminação especial.\n• <b>Réplicas para a Família (Presente):</b> Inclusão de 02 Mini Álbuns réplicas, ideais para presentear os pais com a mesma qualidade do álbum principal.\n• <b>Produção Cinematográfica 4K:</b> Filme completo (8 a 12 min) com áudio dos votos e trilha sonora licenciada.\n• <b>Imagens Aéreas (Drone):</b> Perspectivas cinematográficas para contextualizar o local do seu "sim".\n• <b>Ecossistema Digital e Físico:</b> Galeria online vitalícia, e pen drive personalizado.',
            pacote_adicionais: '• <b>Boudoir da Noiva:</b> R$ 500,00\n• <b>Ensaio Pré-Wedding:</b> R$ 1.100,00',
            pacote_condicoes: 'Entrada de 20% + Saldo parcelado em ate 6x',
            pacote_foto: '<?= raizUrl('/imagens-proposta-casamento/foto-section-07.png') ?>'
        },
        planosMockados: [
            {
                id: 'heritage',
                pacote_nome: 'Experiencia Heritage',
                pacote_escolhido: 'Experiencia Heritage',
                pacote_valor: 'R$ 7.900,00',
                valor_total: 'R$ 7.900,00',
                pacote_itens: '• <b>Cobertura Documental Completa:</b> Presença ilimitada no evento. Do making of à última música, sem limite de horas.\n• <b>O Álbum Heritage:</b> Álbum luxo panorâmico no tamanho 25x30cm (aberto 25x60cm), com papel fotográfico de alta gramatura e laminação especial.\n• <b>Réplicas para a Família (Presente):</b> Inclusão de 02 Mini Álbuns réplicas, ideais para presentear os pais com a mesma qualidade do álbum principal.\n• <b>Produção Cinematográfica 4K:</b> Filme completo (8 a 12 min) com áudio dos votos e trilha sonora licenciada.\n• <b>Imagens Aéreas (Drone):</b> Perspectivas cinematográficas para contextualizar o local do seu "sim".\n• <b>Ecossistema Digital e Físico:</b> Galeria online vitalícia, e pen drive personalizado.',
                itens_inclusos: '• <b>Cobertura Documental Completa:</b> Presença ilimitada no evento. Do making of à última música, sem limite de horas.\n• <b>O Álbum Heritage:</b> Álbum luxo panorâmico no tamanho 25x30cm (aberto 25x60cm), com papel fotográfico de alta gramatura e laminação especial.\n• <b>Réplicas para a Família (Presente):</b> Inclusão de 02 Mini Álbuns réplicas, ideais para presentear os pais com a mesma qualidade do álbum principal.\n• <b>Produção Cinematográfica 4K:</b> Filme completo (8 a 12 min) com áudio dos votos e trilha sonora licenciada.\n• <b>Imagens Aéreas (Drone):</b> Perspectivas cinematográficas para contextualizar o local do seu "sim".\n• <b>Ecossistema Digital e Físico:</b> Galeria online vitalícia, e pen drive personalizado.',
                pacote_adicionais: '• <b>Boudoir da Noiva:</b> R$ 500,00\n• <b>Ensaio Pré-Wedding:</b> R$ 1.100,00',
                itens_adicionais: '• <b>Boudoir da Noiva:</b> R$ 500,00\n• <b>Ensaio Pré-Wedding:</b> R$ 1.100,00',
                pacote_condicoes: 'Entrada de 20% + Saldo parcelado em ate 6x',
                condicoes_pagamento: 'Entrada de 20% + Saldo parcelado em ate 6x',
                pacote_foto: '<?= raizUrl('/imagens-proposta-casamento/foto-section-07.png') ?>',
                condicao_especial: '(Condição especial p/ amigos lagoinha)'
            },
            {
                id: 'cinematic',
                pacote_nome: 'Experiencia Cinematic',
                pacote_escolhido: 'Experiencia Cinematic',
                pacote_valor: 'R$ 4.500,00',
                valor_total: 'R$ 4.500,00',
                pacote_itens: '• <b>Fotografia de Evento (8h):</b> Cobertura focada na essência e na espontaneidade dos convidados.\n• <b>Sessão Engagement (Pré-Wedding):</b> Ensaio de até 3h para conexão do casal com a lente antes do grande dia.\n• <b>Short Film de Cinema:</b> Filme dinâmico (5 a 7 min) com os melhores momentos da cerimônia e recepção.\n• <b>Social Content (Story Maker):</b> Entrega de conteúdo vertical pronto para redes sociais. Seus convidados acompanham os bastidores em tempo real.\n• <b>Making Of Completo:</b> Registro da preparação da noiva e do noivo, capturando a expectation e os detalhes.\n• <b>Bônus:</b> Vídeo Save-the-Date incluso para o anúncio oficial.',
                itens_inclusos: '• <b>Fotografia de Evento (8h):</b> Cobertura focada na essência e na espontaneidade dos convidados.\n• <b>Sessão Engagement (Pré-Wedding):</b> Ensaio de até 3h para conexão do casal com a lente antes do grande dia.\n• <b>Short Film de Cinema:</b> Filme dinâmico (5 a 7 min) com os melhores momentos da cerimônia e recepção.\n• <b>Social Content (Story Maker):</b> Entrega de conteúdo vertical pronto para redes sociais. Seus convidados acompanham os bastidores em tempo real.\n• <b>Making Of Completo:</b> Registro da preparação da noiva e do noivo, capturando a expectativa e os detalhes.\n• <b>Bônus:</b> Vídeo Save-the-Date incluso para o anúncio oficial.',
                pacote_adicionais: '• <b>Boudoir da Noiva:</b> R$ 500,00\n• <b>Ensaio Pré-Wedding:</b> R$ 1.100,00',
                itens_adicionais: '• <b>Boudoir da Noiva:</b> R$ 500,00\n• <b>Ensaio Pré-Wedding:</b> R$ 1.100,00',
                pacote_condicoes: 'Entrada de 20% + Saldo parcelado em ate 6x',
                condicoes_pagamento: 'Entrada de 20% + Saldo parcelado em ate 6x',
                pacote_foto: '<?= raizUrl('/imagens-proposta-casamento/foto-section-08.png') ?>',
                condicao_especial: '(10% de desconto na entrada para contratos até 05/04/2026)'
            },
            {
                id: 'essencial',
                pacote_nome: 'Registro Essencial',
                pacote_escolhido: 'Registro Essencial',
                pacote_valor: 'R$ 2.800,00',
                valor_total: 'R$ 2.800,00',
                pacote_itens: '• <b>Fotografia de Cerimônia (4h):</b> Cobertura pontual focada no protocolo religioso e fotos protocolares de família.\n• <b>Escopo Limitado:</b> Plano focado em registros estáticos. Não inclui vídeo, drone, cobertura de preparativos ou ensaio externo.\n• <b>Entrega Digital:</b> Acesso à galeria online exclusiva para download das fotos editadas.',
                itens_inclusos: '• <b>Fotografia de Cerimônia (4h):</b> Cobertura pontual focada no protocolo religioso e fotos protocolares de família.\n• <b>Escopo Limitado:</b> Plano focado em registros estáticos. Não inclui vídeo, drone, cobertura de preparativos ou ensaio externo.\n• <b>Entrega Digital:</b> Acesso à galeria online exclusiva para download das fotos editadas.',
                pacote_adicionais: '',
                itens_adicionais: '',
                pacote_condicoes: 'Entrada de 25% + Saldo parcelado em ate 5x',
                condicoes_pagamento: 'Entrada de 25% + Saldo parcelado em ate 5x',
                pacote_foto: '<?= raizUrl('/imagens-proposta-casamento/foto-section-09.png') ?>',
                condicao_especial: ''
            }
        ],
        get page() { return this.template.config.pages[this.currentPage] || null; },
        get currentPackageMock() {
            if (!this.page || !this.page.is_pacote) return this.planosMockados[0];
            if (this.page.plano_id) {
                const mock = this.planosMockados.find(p => p.id === this.page.plano_id);
                if (mock) return mock;
            }
            let countPkgPages = this.template.config.pages.filter(p => p.is_pacote).length;
            if (countPkgPages > 1) {
                let pkgPagesSoFar = this.template.config.pages.slice(0, this.currentPage + 1).filter(p => p.is_pacote);
                let pkgIndex = Math.max(0, pkgPagesSoFar.length - 1);
                return this.planosMockados[pkgIndex] || this.planosMockados[0];
            }
            return this.planosMockados[0];
        },
        obterNomePlano(id) {
            const op = this.planosOpcoes.find(p => p.id === id);
            return op ? op.nome : id;
        },
        movePageUp(index) {
            if (index <= 0) return;
            const pages = this.template.config.pages;
            const temp = pages[index];
            pages[index] = pages[index - 1];
            pages[index - 1] = temp;
            this.currentPage = index - 1;
            this.selectedField = null;
        },
        movePageDown(index) {
            const pages = this.template.config.pages;
            if (index >= pages.length - 1) return;
            const temp = pages[index];
            pages[index] = pages[index + 1];
            pages[index + 1] = temp;
            this.currentPage = index + 1;
            this.selectedField = null;
        },
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
            let mock = this.currentPackageMock;
            let raw = field.text || '';
            if (!raw) {
                if (this.page?.is_pacote && mock && mock[field.key] !== undefined) {
                    raw = mock[field.key];
                } else {
                    raw = this.values[field.key] || '{{' + field.key + '}}';
                }
            }
            return String(raw).replace(/\n/g, '<br>');
        },
        fieldStyle(field) {
            const size = Math.max(1, (field.size || 18) * this.stageScale);
            return `left:${field.x}%;top:${field.y}%;width:${field.w}%;height:${field.h}%;font-family:${field.font};font-size:${size}px;color:${field.color};font-weight:${field.weight};text-align:${field.align};line-height:${field.lineHeight || 1.25};text-transform:${field.transform || 'none'};`;
        },
        updateStageScale() {
            if (!this.$refs.stage) return;
            this.stageScale = Math.max(0.1, this.$refs.stage.getBoundingClientRect().width / 960);
        },
        guideStyle(guide) {
            return guide.type === 'v' ? `left:${guide.pos}%;` : `top:${guide.pos}%;`;
        },
        clearGuides() {
            this.guides = [];
        },
        snapRect(rect, field) {
            const threshold = 0.7;
            const guides = [];
            const targetsX = [{ pos: 0 }, { pos: 50 }, { pos: 100 }];
            const targetsY = [{ pos: 0 }, { pos: 50 }, { pos: 100 }];

            (this.page?.fields || []).forEach(other => {
                if (!field || other.id === field.id) return;
                const ox = Number(other.x) || 0;
                const oy = Number(other.y) || 0;
                const ow = Number(other.w) || 0;
                const oh = Number(other.h) || 0;
                targetsX.push({ pos: ox }, { pos: ox + ow / 2 }, { pos: ox + ow });
                targetsY.push({ pos: oy }, { pos: oy + oh / 2 }, { pos: oy + oh });
            });

            let x = rect.x;
            let y = rect.y;
            const w = rect.w;
            const h = rect.h;
            let bestX = null;
            let bestY = null;
            const edgesX = [{ edge: 'left', pos: x }, { edge: 'center', pos: x + w / 2 }, { edge: 'right', pos: x + w }];
            const edgesY = [{ edge: 'top', pos: y }, { edge: 'middle', pos: y + h / 2 }, { edge: 'bottom', pos: y + h }];

            targetsX.forEach(target => edgesX.forEach(edge => {
                const dist = Math.abs(edge.pos - target.pos);
                if (dist <= threshold && (!bestX || dist < bestX.dist)) bestX = { dist, target: target.pos, edge: edge.edge };
            }));
            targetsY.forEach(target => edgesY.forEach(edge => {
                const dist = Math.abs(edge.pos - target.pos);
                if (dist <= threshold && (!bestY || dist < bestY.dist)) bestY = { dist, target: target.pos, edge: edge.edge };
            }));

            if (bestX) {
                if (bestX.edge === 'left') x = bestX.target;
                if (bestX.edge === 'center') x = bestX.target - w / 2;
                if (bestX.edge === 'right') x = bestX.target - w;
                guides.push({ type: 'v', pos: Math.max(0, Math.min(100, bestX.target)) });
            }
            if (bestY) {
                if (bestY.edge === 'top') y = bestY.target;
                if (bestY.edge === 'middle') y = bestY.target - h / 2;
                if (bestY.edge === 'bottom') y = bestY.target - h;
                guides.push({ type: 'h', pos: Math.max(0, Math.min(100, bestY.target)) });
            }

            this.guides = guides;
            return {
                x: Math.max(0, Math.min(100 - w, x)),
                y: Math.max(0, Math.min(100 - h, y)),
                w,
                h
            };
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
        addPhotoField() {
            if (!this.page) return;
            const field = { id: this.newId(), key: 'pacote_foto', text: '', x: 20, y: 20, w: 40, h: 40, font: 'Montserrat', size: 18, color: '#111111', weight: '400', align: 'left', lineHeight: 1.25 };
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
                const next = this.snapRect({
                    x: Math.max(0, Math.min(100 - field.w, startField.x + ((e.clientX - startX) / rect.width) * 100)),
                    y: Math.max(0, Math.min(100 - field.h, startField.y + ((e.clientY - startY) / rect.height) * 100)),
                    w: Number(field.w) || 20,
                    h: Number(field.h) || 8
                }, field);
                field.x = Math.round(next.x * 100) / 100;
                field.y = Math.round(next.y * 100) / 100;
            };
            const up = () => {
                window.removeEventListener('mousemove', move);
                window.removeEventListener('mouseup', up);
                this.clearGuides();
            };
            window.addEventListener('mousemove', move);
            window.addEventListener('mouseup', up);
        },
        startResize(event, field, handle) {
            this.selectedField = field;
            const rect = this.$refs.stage.getBoundingClientRect();
            const startX = event.clientX;
            const startY = event.clientY;
            const start = {
                x: Number(field.x) || 0,
                y: Number(field.y) || 0,
                w: Number(field.w) || 20,
                h: Number(field.h) || 8
            };
            const minW = 2;
            const minH = 2;
            const move = (e) => {
                const dx = ((e.clientX - startX) / rect.width) * 100;
                const dy = ((e.clientY - startY) / rect.height) * 100;
                let x = start.x;
                let y = start.y;
                let w = start.w;
                let h = start.h;

                if (handle.includes('e')) w = start.w + dx;
                if (handle.includes('s')) h = start.h + dy;
                if (handle.includes('w')) {
                    x = start.x + dx;
                    w = start.w - dx;
                }
                if (handle.includes('n')) {
                    y = start.y + dy;
                    h = start.h - dy;
                }

                if (w < minW) {
                    if (handle.includes('w')) x = start.x + start.w - minW;
                    w = minW;
                }
                if (h < minH) {
                    if (handle.includes('n')) y = start.y + start.h - minH;
                    h = minH;
                }

                w = Math.max(minW, Math.min(100 - x, w));
                h = Math.max(minH, Math.min(100 - y, h));
                const snapped = this.snapRect({ x, y, w, h }, field);
                x = snapped.x;
                y = snapped.y;
                w = snapped.w;
                h = snapped.h;

                field.x = Math.round(x * 100) / 100;
                field.y = Math.round(y * 100) / 100;
                field.w = Math.round(w * 100) / 100;
                field.h = Math.round(h * 100) / 100;
            };
            const up = () => {
                window.removeEventListener('mousemove', move);
                window.removeEventListener('mouseup', up);
                this.clearGuides();
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
            
            const renderPage = (page, currentPlan = null) => {
                const fields = (page.fields || []).map(field => {
                    let rawText = field.text || '';
                    if (!rawText) {
                        if (currentPlan && currentPlan[field.key] !== undefined) {
                            rawText = currentPlan[field.key];
                        } else {
                            rawText = this.values[field.key] || '{{' + field.key + '}}';
                        }
                    }
                    
                    if (field.key === 'pacote_foto') {
                        return `<img src="${esc(rawText)}" style="position:absolute;left:${field.x || 0}%;top:${field.y || 0}%;width:${field.w || 20}%;height:${field.h || 8}%;object-fit:cover;overflow:hidden;">`;
                    }
                    
                    const text = esc(rawText)
                        .replace(/&lt;b&gt;/g, '<b>')
                        .replace(/&lt;\/b&gt;/g, '</b>')
                        .replace(/&lt;strong&gt;/g, '<strong>')
                        .replace(/&lt;\/strong&gt;/g, '</strong>')
                        .replace(/\n/g, '<br>');
                    
                    return `<div style="position:absolute;left:${field.x || 0}%;top:${field.y || 0}%;width:${field.w || 20}%;height:${field.h || 8}%;font-family:${esc(field.font || 'Arial')};font-size:${field.size || 18}px;color:${esc(field.color || '#111')};font-weight:${esc(field.weight || '400')};text-align:${esc(field.align || 'left')};line-height:${field.lineHeight || 1.25};text-transform:${field.transform || 'none'};display:flex;flex-direction:column;justify-content:${field.valign || 'flex-start'};white-space:pre-wrap;overflow:hidden;">${text}</div>`;
                }).join('');
                
                return `<section class="page"><img src="${esc(page.image)}">${fields}</section>`;
            };

            const pagesHtml = [];
            const packagePages = this.template.config.pages.filter(p => p.is_pacote);
            const countPkgPages = packagePages.length;
            let pkgCounter = 0;

            this.template.config.pages.forEach(page => {
                if (page.is_pacote) {
                    if (page.plano_id) {
                        const plano = this.planosMockados.find(p => p.id === page.plano_id);
                        if (plano) {
                            pagesHtml.push(renderPage(page, plano));
                        }
                    } else {
                        if (countPkgPages > 1) {
                            const plano = this.planosMockados[pkgCounter] || null;
                            if (plano) {
                                pagesHtml.push(renderPage(page, plano));
                            }
                            pkgCounter++;
                        } else {
                            this.planosMockados.forEach(plano => {
                                pagesHtml.push(renderPage(page, plano));
                            });
                        }
                    }
                } else {
                    pagesHtml.push(renderPage(page));
                }
            });
            
            win.document.write(`<!doctype html><html><head><title>Preview PDF</title>${fontLink ? `<link rel="stylesheet" href="${esc(fontLink)}">` : ''}<style>body{margin:0;background:#222;font-family:Arial,sans-serif}.page{position:relative;width:960px;height:540px;margin:24px auto;background:#fff;overflow:hidden}.page img{position:absolute;inset:0;width:100%;height:100%;object-fit:contain}@media print{@page{size:160mm 90mm;margin:0}body{background:#fff}.page{margin:0;width:160mm;height:90mm;page-break-after:always}}</style></head><body>${pagesHtml.join('')}</body></html>`);
            win.document.close();
        }
    }
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
