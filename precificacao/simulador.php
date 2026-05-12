<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
exigirAdmin();
$tituloPagina = 'Simulador IA';
include __DIR__ . '/../includes/layout/head.php';

// Buscar serviços para o formulário
$db = Database::get();
$servicos = $db->query('SELECT id, nome, horas_estimadas, custo_producao, custos_variaveis, markup FROM servicos WHERE ativo=1 ORDER BY nome')->fetchAll();
$servicosJson = json_encode($servicos, JSON_UNESCAPED_UNICODE);
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;" x-data="simulador()">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" style="flex:1; padding:28px 32px; overflow-y:auto; max-width:calc(100vw - 240px);">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <div style="margin-bottom:28px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                <h1 style="font-size:22px; font-weight:700; color:#f1f5f9;">Simulador de Proposta</h1>
                <span style="background:linear-gradient(135deg,#7c3aed,#3b82f6); color:white; font-size:10px; font-weight:700; padding:3px 8px; border-radius:6px;">IA</span>
            </div>
            <p style="font-size:14px; color:#6b7280;">Preencha o briefing e a IA sugere escopo e precificação</p>
        </div>

        <div style="display:grid; grid-template-columns:400px 1fr; gap:24px; align-items:start;">

            <!-- Formulário de Briefing -->
            <div class="card" style="padding:24px;">
                <h3 style="font-size:15px; font-weight:600; color:#e2e8f0; margin-bottom:20px; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="file-text" style="width:15px;height:15px;color:#a78bfa;"></i>
                    Briefing do Cliente
                </h3>

                <div style="margin-bottom:16px;">
                    <label class="label">Nome do cliente / empresa</label>
                    <input class="input" x-model="briefing.cliente" placeholder="Ex: Empresa ABC Ltda.">
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Tipo de cliente *</label>
                    <select class="select" x-model="briefing.tipo_cliente">
                        <option value="pequena_empresa">Pequena Empresa</option>
                        <option value="media_empresa">Média Empresa</option>
                        <option value="grande_empresa">Grande Empresa</option>
                        <option value="startup">Startup</option>
                        <option value="profissional_liberal">Profissional Liberal</option>
                        <option value="ecommerce">E-commerce</option>
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Segmento / Nicho *</label>
                    <input class="input" x-model="briefing.segmento" required placeholder="Ex: Alimentação, Moda, Saúde...">
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Serviços desejados *</label>
                    <div style="display:flex; flex-direction:column; gap:8px; max-height:200px; overflow-y:auto; padding:4px 0;">
                        <?php foreach ($servicos as $s): ?>
                        <label style="display:flex; align-items:center; gap:10px; cursor:pointer; font-size:13px; color:#cbd5e1;">
                            <input type="checkbox" value="<?= sanitizar($s['nome']) ?>" @change="toggleServico($event)" style="accent-color:#7c3aed;">
                            <?= sanitizar($s['nome']) ?>
                        </label>
                        <?php endforeach; ?>
                        <?php if (empty($servicos)): ?>
                        <p style="font-size:13px; color:#6b7280;">Nenhum serviço cadastrado. <a href="<?= raizUrl('/precificacao/servicos.php') ?>" style="color:#a78bfa;">Cadastre serviços primeiro.</a></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px;">
                    <div>
                        <label class="label">Prazo do projeto</label>
                        <select class="select" x-model="briefing.prazo">
                            <option value="urgente">Urgente (&lt; 2 sem.)</option>
                            <option value="normal">Normal (1 mês)</option>
                            <option value="longo">Longo (2+ meses)</option>
                            <option value="mensal_recorrente">Mensal Recorrente</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Complexidade</label>
                        <select class="select" x-model="briefing.complexidade">
                            <option value="baixa">Baixa</option>
                            <option value="media">Média</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label">Contexto adicional</label>
                    <textarea class="input" x-model="briefing.contexto" rows="2" placeholder="Objetivos, desafios, observações relevantes..." style="resize:vertical;"></textarea>
                </div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px;">
                    <div>
                        <label class="label">Ferramentas Extras</label>
                        <input class="input" x-model="briefing.ferramentas_extras" placeholder="Ex: Licença extra Adobe...">
                    </div>
                    <div>
                        <label class="label">Terceirização (Projeto)</label>
                        <input class="input" x-model="briefing.terceirizacao_extra" placeholder="Ex: R$ 500 para Fotógrafo">
                    </div>
                </div>

                <button class="btn-primary" style="width:100%; justify-content:center;" @click="simular()" :disabled="gerando || briefing.servicos.length===0">
                    <template x-if="!gerando">
                        <span style="display:flex;align-items:center;gap:6px;">
                            <i data-lucide="sparkles" style="width:15px;height:15px;"></i> Gerar Proposta com IA
                        </span>
                    </template>
                    <template x-if="gerando">
                        <span>Gerando... ⟳</span>
                    </template>
                </button>

                <p x-show="briefing.servicos.length===0" style="font-size:12px; color:#f59e0b; margin-top:8px; text-align:center;">
                    Selecione pelo menos um serviço
                </p>
            </div>

            <!-- Resultado da IA -->
            <div>
                <!-- Estado vazio -->
                <div x-show="!resultado && !gerando" class="card" style="padding:48px; text-align:center;">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,rgba(124,58,237,0.2),rgba(59,130,246,0.2));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i data-lucide="sparkles" style="width:28px;height:28px;color:#a78bfa;"></i>
                    </div>
                    <h3 style="font-size:16px; font-weight:600; color:#e2e8f0; margin-bottom:8px;">Preencha o briefing</h3>
                    <p style="font-size:14px; color:#6b7280;">A IA vai gerar 2 opções de proposta (Básica e Completa) com escopo e justificativa de precificação</p>
                </div>

                <!-- Gerando -->
                <div x-show="gerando" class="card" style="padding:48px; text-align:center;">
                    <div style="width:64px;height:64px;background:linear-gradient(135deg,rgba(124,58,237,0.2),rgba(59,130,246,0.2));border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;animation:spin 2s linear infinite;">
                        <i data-lucide="sparkles" style="width:28px;height:28px;color:#a78bfa;"></i>
                    </div>
                    <p style="font-size:15px; color:#e2e8f0; margin-bottom:8px;">Analisando briefing...</p>
                    <p style="font-size:13px; color:#6b7280;">A IA está montando sua proposta personalizada</p>
                </div>

                <!-- Resultado -->
                <div x-show="resultado && !gerando">
                    <div class="card" style="padding:24px; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
                            <h3 style="font-size:15px; font-weight:600; color:#e2e8f0;">Proposta Gerada pela IA</h3>
                            <div style="display:flex; gap:10px;">
                                <button class="btn-secondary" @click="resultado=null" style="padding:6px 14px; font-size:13px;">
                                    <i data-lucide="refresh-cw" style="width:13px;height:13px;"></i> Refazer
                                </button>
                                <button class="btn-primary" @click="gerarPdf()" style="padding:6px 14px; font-size:13px;">
                                    <i data-lucide="download" style="width:13px;height:13px;"></i> Baixar PDF
                                </button>
                            </div>
                        </div>
                        <!-- Conteúdo markdown renderizado -->
                        <div id="resultado-ia" style="font-size:14px; color:#cbd5e1; line-height:1.8;" x-html="renderMarkdown(resultado)"></div>
                    </div>

                    <!-- Editor manual -->
                    <div class="card" style="padding:20px;">
                        <h4 style="font-size:14px; font-weight:600; color:#e2e8f0; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                            <i data-lucide="edit-3" style="width:14px;height:14px;color:#a78bfa;"></i>
                            Ajuste Manual
                        </h4>
                        <textarea class="input" x-model="resultado" rows="12" style="font-family:monospace; font-size:13px; resize:vertical;"></textarea>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Form oculto para PDF -->
<form id="form-pdf" method="POST" action="<?= raizUrl('/api/pdf/proposta.php') ?>" target="_blank" style="display:none;">
    <input type="hidden" name="cliente" id="pdf-cliente">
    <input type="hidden" name="proposta" id="pdf-proposta">
</form>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
#resultado-ia h2 { font-size:16px; font-weight:700; color:#e2e8f0; margin:20px 0 10px; }
#resultado-ia h3 { font-size:14px; font-weight:600; color:#a78bfa; margin:14px 0 8px; }
#resultado-ia strong { color:#f1f5f9; }
#resultado-ia p { margin-bottom:10px; }
#resultado-ia ul { list-style:disc; padding-left:20px; margin-bottom:10px; }
#resultado-ia li { margin-bottom:4px; }
#resultado-ia hr { border-color:rgba(255,255,255,0.08); margin:20px 0; }
</style>

<script>
const SERVICOS_DATA = <?= $servicosJson ?>;

function simulador() {
    return {
        briefing: {
            cliente: '',
            tipo_cliente: 'media_empresa',
            segmento: '',
            servicos: [],
            prazo: 'mensal_recorrente',
            complexidade: 'media',
            contexto: '',
            ferramentas_extras: '',
            terceirizacao_extra: ''
        },
        gerando: false,
        resultado: null,
        erroIA: '',

        toggleServico(e) {
            const val = e.target.value;
            if (e.target.checked) this.briefing.servicos.push(val);
            else this.briefing.servicos = this.briefing.servicos.filter(s => s !== val);
        },

        async simular() {
            if (this.briefing.servicos.length === 0) return;
            this.gerando = true;
            this.resultado = null;
            this.erroIA = '';

            try {
                const r = await fetch('<?= raizUrl('/api/precificacao/simular.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.briefing)
                });
                const res = await r.json();
                if (r.ok) {
                    this.resultado = res.proposta;
                    this.$nextTick(() => lucide.createIcons());
                } else {
                    toast(res.erro || 'Erro ao chamar IA', 'erro');
                }
            } catch(e) {
                toast('Erro de conexão com o servidor', 'erro');
            }
            this.gerando = false;
        },

        renderMarkdown(text) {
            if (!text) return '';
            return text
                .replace(/^## (.+)$/gm, '<h2>$1</h2>')
                .replace(/^### (.+)$/gm, '<h3>$1</h3>')
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/^- (.+)$/gm, '<li>$1</li>')
                .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
                .replace(/^---$/gm, '<hr>')
                .replace(/\n\n/g, '</p><p>')
                .replace(/\n/g, '<br>');
        },

        gerarPdf() {
            document.getElementById('pdf-cliente').value = this.briefing.cliente || 'Cliente';
            document.getElementById('pdf-proposta').value = this.resultado || '';
            document.getElementById('form-pdf').submit();
        },
    };
}
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
