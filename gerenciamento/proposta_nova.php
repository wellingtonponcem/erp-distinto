<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();

$tituloPagina = 'Nova Proposta';
$db = Database::get();

// Buscar clientes
$stmtClientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome ASC");
$clientes = $stmtClientes->fetchAll();

// Buscar serviços para o modelo Marketing
$stmtServicos = $db->query("SELECT id, nome, descricao FROM servicos ORDER BY nome ASC");
$servicos = $stmtServicos->fetchAll();

include __DIR__ . '/../includes/layout/head.php';
?>

<div id="app-wrapper">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet">
        <div class="app-topbar">
            <div class="top-nav">
                <a href="<?= raizUrl('/dashboard.php') ?>">Visão Geral</a>
                <a href="<?= raizUrl('/gerenciamento/propostas.php') ?>">Propostas</a>
                <a href="#" class="active">Nova Proposta</a>
            </div>
        </div>

        <div class="mb-8">
            <h1 class="page-title">Criar Nova Proposta</h1>
            <p class="page-subtitle">Preencha os dados abaixo para gerar uma proposta personalizada com IA.</p>
        </div>

        <form id="formGerarProposta" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <section class="card p-6">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Informações Básicas</h3>
                    
                    <div class="flex gap-6 mb-6 pb-6 border-b border-zinc-100">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="modo_cliente" value="cadastrado" checked class="w-4 h-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                            <span class="text-xs font-bold text-zinc-700">Cliente Cadastrado</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="modo_cliente" value="lead" class="w-4 h-4 border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                            <span class="text-xs font-bold text-zinc-700">Novo Lead / Prospect</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <!-- Modo Cliente Cadastrado -->
                        <div id="wrapperClienteCadastrado" class="form-group">
                            <label class="label">Selecione o Cliente</label>
                            <select name="cliente_id" id="cliente_id" class="input">
                                <option value="">Selecione um cliente...</option>
                                <?php foreach ($clientes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= sanitizar($c['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Modo Novo Lead -->
                        <div id="wrapperNovoLead" class="hidden animate-fade-in">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="label">Nome da Empresa / Projeto</label>
                                    <input type="text" name="empresa_nome" class="input" placeholder="Ex: Innovare Solar">
                                </div>
                                <div class="form-group">
                                    <label class="label">Responsável(is)</label>
                                    <input type="text" name="responsavel" class="input" placeholder="Ex: João Silva, Maria Souza">
                                    <p class="text-[10px] text-zinc-500 mt-1">Use vírgula para separar múltiplos nomes.</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="form-group md:col-span-2">
                                <label class="label">Título da Proposta</label>
                                <input type="text" name="titulo" class="input" placeholder="Ex: Gestão de Tráfego 2024" required>
                            </div>
                            <div class="form-group">
                                <label class="label">Tipo de Serviço</label>
                                <select name="tipo" id="tipoProposta" class="input" required>
                                    <option value="marketing">Marketing Digital</option>
                                    <option value="casamento">Casamento</option>
                                    <option value="15anos">15 Anos</option>
                                    <option value="filmmaker">Filmmaker (Cinematic)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="label">Valor Total (R$)</label>
                                <input type="number" step="0.01" name="valor_total" class="input" placeholder="0,00" required>
                            </div>
                            <div class="form-group">
                                <label class="label">Subtítulo (Opcional)</label>
                                <input type="text" name="subtitulo" class="input" placeholder="Ex: Planejamento Estratégico Q3">
                            </div>
                        </div>
                    </div>
                </section>

                <section class="card p-6" id="sectionServicos">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Serviços Inclusos (Apenas Marketing)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <?php foreach ($servicos as $s): ?>
                            <label class="flex items-center gap-3 p-3 border border-zinc-100 rounded-lg cursor-pointer hover:bg-zinc-50 transition-colors">
                                <input type="checkbox" name="servicos[]" value="<?= $s['id'] ?>" class="w-4 h-4 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                <div>
                                    <p class="text-xs font-bold text-zinc-900"><?= sanitizar($s['nome']) ?></p>
                                    <p class="text-[10px] text-zinc-500 line-clamp-1"><?= sanitizar($s['descricao']) ?></p>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="card p-6">
                    <h3 class="text-sm font-bold text-zinc-900 mb-4">Briefing para IA</h3>
                    <div class="form-group">
                        <label class="label">Instruções Adicionais (Opcional)</label>
                        <textarea name="briefing" class="input min-h-[120px]" placeholder="Dê detalhes sobre o cliente ou o projeto para que a IA gere textos mais precisos..."></textarea>
                    </div>
                </section>
            </div>

            <div class="space-y-6">
                <section class="card p-6 bg-zinc-900 text-white">
                    <h3 class="text-sm font-bold mb-4 opacity-80">Ações</h3>
                    <button type="submit" id="btnGerar" class="btn-primary w-full justify-center gap-2 bg-white text-zinc-900 hover:bg-zinc-200">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        Gerar Proposta Web
                    </button>
                    <p class="mt-4 text-[11px] opacity-60 text-center">
                        Ao clicar em gerar, nossa IA irá processar os dados e criar uma página exclusiva para o cliente.
                    </p>
                </section>

                <div id="resultadoProposta" class="hidden animate-fade-in">
                    <section class="card p-6 border-2 border-emerald-500">
                        <div class="flex items-center gap-3 text-emerald-600 mb-4">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            <span class="font-bold text-sm">Gerada com Sucesso!</span>
                        </div>
                        <p class="text-xs text-zinc-600 mb-4">A proposta já está online. Você pode copiar o link ou visualizar agora.</p>
                        <div class="space-y-2">
                            <a href="#" id="linkVisualizar" target="_blank" class="btn-secondary w-full justify-center">Visualizar</a>
                            <button type="button" id="btnCopiarLink" class="btn-secondary w-full justify-center gap-2">
                                <i data-lucide="copy" class="w-4 h-4"></i>
                                Copiar Link
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </form>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formGerarProposta');
    const tipoSelect = document.getElementById('tipoProposta');
    const sectionServicos = document.getElementById('sectionServicos');
    const btnGerar = document.getElementById('btnGerar');
    const resultadoDiv = document.getElementById('resultadoProposta');
    const linkVisualizar = document.getElementById('linkVisualizar');
    const btnCopiarLink = document.getElementById('btnCopiarLink');
    let linkGerado = '';

    const radiosModo = document.querySelectorAll('input[name="modo_cliente"]');
    const wrapperCadastrado = document.getElementById('wrapperClienteCadastrado');
    const wrapperLead = document.getElementById('wrapperNovoLead');
    const selectCliente = document.querySelector('select[name="cliente_id"]');
    const inputEmpresa = document.querySelector('input[name="empresa_nome"]');
    const inputResponsavel = document.querySelector('input[name="responsavel"]');

    radiosModo.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'cadastrado') {
                wrapperCadastrado.classList.remove('hidden');
                wrapperLead.classList.add('hidden');
                selectCliente.required = true;
                inputEmpresa.required = false;
                inputResponsavel.required = false;
            } else {
                wrapperCadastrado.classList.add('hidden');
                wrapperLead.classList.remove('hidden');
                selectCliente.required = false;
                inputEmpresa.required = true;
                inputResponsavel.required = true;
            }
        });
    });

    // Iniciar com o modo correto
    document.querySelector('input[name="modo_cliente"]:checked').dispatchEvent(new Event('change'));

    // Alternar visibilidade da seção de serviços
    tipoSelect.addEventListener('change', function() {
        if (this.value === 'marketing') {
            sectionServicos.classList.remove('hidden');
        } else {
            sectionServicos.classList.add('hidden');
        }
    });

    // Regra de Negócio: Redes Sociais exige Gestão de Tráfego
    sectionServicos.addEventListener('change', function(e) {
        if (e.target.type === 'checkbox') {
            const labelText = e.target.closest('label').innerText.toLowerCase();
            const isSocialMedia = labelText.includes('redes sociais') || labelText.includes('instagram') || labelText.includes('facebook');
            
            if (isSocialMedia && e.target.checked) {
                // Procurar o checkbox de tráfego
                const checkboxes = sectionServicos.querySelectorAll('input[type="checkbox"]');
                let trafficSelected = false;
                
                checkboxes.forEach(cb => {
                    const cbLabel = cb.closest('label').innerText.toLowerCase();
                    if (cbLabel.includes('tráfego') || cbLabel.includes('trafego') || cbLabel.includes('ads')) {
                        if (!cb.checked) {
                            cb.checked = true;
                            trafficSelected = true;
                        }
                    }
                });

                if (trafficSelected) {
                    alert('Aviso: Atualmente a Gestão de Redes Sociais é vendida obrigatoriamente com Gestão de Tráfego para garantir o impulsionamento e resultados reais.');
                }
            }
        }
    });

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        btnGerar.disabled = true;
        btnGerar.innerHTML = '<i class="w-4 h-4 animate-spin"></i> Processando IA...';
        
        const formData = new FormData(form);
        
        try {
            const response = await fetch('<?= raizUrl('/api/propostas/gerar.php') ?>', {
                method: 'POST',
                body: formData
            });
            
            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                console.error('Erro ao processar JSON:', responseText);
                throw new Error('O servidor retornou uma resposta inválida (não JSON). Verifique o console para detalhes.');
            }
            
            if (!response.ok) {
                throw new Error(result.erro || 'Erro desconhecido no servidor.');
            }
            
            if (result.success) {
                // Usar a URL base do sistema para o link
                const baseUrl = window.location.origin + window.location.pathname.split('/gerenciamento/')[0];
                linkGerado = `${baseUrl}/p/${result.slug}`;
                linkVisualizar.href = linkGerado;
                resultadoDiv.classList.remove('hidden');
                
                // Scroll para o resultado em mobile
                resultadoDiv.scrollIntoView({ behavior: 'smooth' });
            } else {
                alert('Erro: ' + (result.error || 'Falha ao gerar proposta.'));
            }
        } catch (error) {
            console.error(error);
            alert('Erro na comunicação com o servidor.');
        } finally {
            btnGerar.disabled = false;
            btnGerar.innerHTML = '<i data-lucide="sparkles" class="w-4 h-4"></i> Gerar Proposta Web';
            lucide.createIcons();
        }
    });

    btnCopiarLink.addEventListener('click', function() {
        navigator.clipboard.writeText(linkGerado).then(() => {
            const originalText = this.innerHTML;
            this.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> Copiado!';
            setTimeout(() => {
                this.innerHTML = originalText;
                lucide.createIcons();
            }, 2000);
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
