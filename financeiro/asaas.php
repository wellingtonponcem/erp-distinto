<?php
/**
 * Painel de Controle e Extrato do Asaas
 * Permite visualizar o saldo, últimas cobranças e emitir cobrança manual.
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/asaas.php';
require_once __DIR__ . '/../includes/contratos.php';

exigirAdmin();
$usuario = usuarioAtual();
$db = Database::get();

$sucesso = '';
$erro = '';

// Instancia o serviço do Asaas
$asaas = new AsaasService();

// Processar Emissão de Cobrança Manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'nova_cobranca') {
    $clienteId = $_POST['cliente_id'] ?? '';
    $descricao = trim($_POST['descricao'] ?? '');
    $valorTotal = (float)str_replace(['.', ','], ['', '.'], $_POST['valor_total'] ?? '0');
    $vencimento = $_POST['vencimento'] ?? '';
    $billingType = $_POST['billing_type'] ?? 'UNDEFINED';
    $totalParcelas = (int)($_POST['total_parcelas'] ?? 1);
    
    // Sinal opcional
    $valorSinal = (float)str_replace(['.', ','], ['', '.'], $_POST['valor_sinal'] ?? '0');
    $sinalVencimento = $_POST['sinal_vencimento'] ?? '';

    try {
        if (!$clienteId) throw new Exception("Selecione um cliente.");
        if (!$descricao) throw new Exception("Preencha a descrição da cobrança.");
        if ($valorTotal <= 0) throw new Exception("Preencha um valor válido maior que zero.");
        if (empty($vencimento)) throw new Exception("Selecione a data de vencimento da primeira parcela.");

        // Carrega dados do cliente local
        $stmtC = $db->prepare("SELECT nome, cpf_cnpj, contato FROM clientes WHERE id = ?");
        $stmtC->execute([$clienteId]);
        $cliente = $stmtC->fetch();
        if (!$cliente) throw new Exception("Cliente não encontrado na base local.");

        $dadosCobranca = [
            'cliente_id' => $clienteId,
            'cliente_nome' => $cliente['nome'],
            'cliente_cpf_cnpj' => preg_replace('/\D/', '', $cliente['cpf_cnpj'] ?? ''),
            'cliente_email' => $cliente['contato'] ?? '',
            'cliente_telefone' => '',
            'valor_total' => $valorTotal,
            'vencimento' => $vencimento,
            'billing_type' => $billingType,
            'descricao' => $descricao,
            'external_reference' => 'manual_' . gerarId(),
            'total_parcelas' => $totalParcelas,
            'valor_sinal' => $valorSinal,
            'sinal_vencimento' => $sinalVencimento
        ];

        // Cria a cobrança no Asaas
        $cobrancaRes = $asaas->criarCobranca($dadosCobranca);

        // Grava lançamentos locais
        if (!empty($cobrancaRes)) {
            // Contrato falso/mock para o gravarLancamentoAsaas
            $contratoMock = [
                'cliente_nome' => $cliente['nome'],
                'cliente_id' => $clienteId,
                'titulo' => $descricao,
                'id' => null
            ];

            if (!empty($cobrancaRes['multiplo'])) {
                $sinal = $cobrancaRes['sinal'];
                $saldo = $cobrancaRes['saldo'];

                // 1. Gravar Sinal
                gravarLancamentoAsaas($db, $contratoMock, $sinal, "[Sinal] " . $descricao, $valorSinal, $sinalVencimento ?: date('Y-m-d'));

                // 2. Gravar Saldo
                $saldoRestante = $valorTotal - $valorSinal;
                if (!empty($saldo['installments'])) {
                    foreach ($saldo['installments'] as $idx => $inst) {
                        gravarLancamentoAsaas($db, $contratoMock, $inst, "[Parcela " . ($idx + 1) . "] " . $descricao, (float)$inst['value'], $inst['dueDate']);
                    }
                } else {
                    gravarLancamentoAsaas($db, $contratoMock, $saldo, "[Saldo] " . $descricao, $saldoRestante, $vencimento);
                }
            } else {
                if (!empty($cobrancaRes['installments'])) {
                    foreach ($cobrancaRes['installments'] as $idx => $inst) {
                        gravarLancamentoAsaas($db, $contratoMock, $inst, "[Parcela " . ($idx + 1) . "] " . $descricao, (float)$inst['value'], $inst['dueDate']);
                    }
                } else {
                    gravarLancamentoAsaas($db, $contratoMock, $cobrancaRes, $descricao, $valorTotal, $vencimento);
                }
            }

            $sucesso = 'Cobrança manual gerada com sucesso e lançamentos criados no ERP!';
        }
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Carregar Dados do Asaas (Saldo e Extrato)
$saldoAsaas = 0.0;
$cobrancasAsaas = [];
$estaConfigurado = $asaas->estaConfigurado();

if ($estaConfigurado) {
    try {
        $dadosPainel = $asaas->obterSaldoEExtrato();
        $saldoAsaas = $dadosPainel['saldo'] ?? 0.0;
        $cobrancasAsaas = $dadosPainel['cobrancas'] ?? [];
    } catch (Exception $e) {
        $erro = 'Erro ao conectar com API do Asaas: ' . $e->getMessage();
    }
}

// Carregar Clientes locais para o modal
$clientes = $db->query("SELECT id, nome, cpf_cnpj FROM clientes ORDER BY nome ASC")->fetchAll();

$tituloPagina = 'Asaas Pagamentos';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper" class="flex min-h-screen" x-data="{ modalNovaCobranca: false }">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet !bg-background !p-6 flex flex-col flex-1">
        <?php include __DIR__ . '/../includes/layout/top_nav.php'; ?>

        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-8 mt-2">
            <div>
                <h1 class="font-display-lg text-on-surface mb-1 flex items-center gap-2">
                    <i data-lucide="wallet" class="w-6 h-6 text-primary"></i>
                    Asaas Pagamentos
                </h1>
                <p class="text-body-md text-on-surface-variant">Gestão de faturamento de clientes, conciliação e extrato em tempo real</p>
            </div>
            
            <div class="flex items-center gap-3">
                <?php if ($estaConfigurado): ?>
                    <button @click="modalNovaCobranca = true" class="bg-primary hover:bg-primary-container text-on-primary-container font-bold px-6 py-2.5 rounded-lg text-body-md transition-all active:scale-95 duration-150 shadow-lg flex items-center gap-2">
                        <i data-lucide="plus" class="w-4 h-4"></i> Nova Cobrança Manual
                    </button>
                <?php endif; ?>
                <a href="<?= raizUrl('/configuracoes.php') ?>" class="btn-secondary flex items-center gap-2">
                    <i data-lucide="settings" class="w-4 h-4"></i> Credenciais
                </a>
            </div>
        </div>

        <?php if ($sucesso): ?>
            <div class="bg-primary/10 border border-primary/20 rounded-xl p-4 mb-6 text-sm text-primary">
                <?= sanitizar($sucesso) ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div class="bg-error/10 border border-error/20 rounded-xl p-4 mb-6 text-sm text-error">
                <?= sanitizar($erro) ?>
            </div>
        <?php endif; ?>

        <?php if (!$estaConfigurado): ?>
            <div class="glass-card p-12 rounded-2xl text-center max-w-lg mx-auto my-12 border-dashed border-primary/30">
                <i data-lucide="shield-alert" class="w-12 h-12 text-primary mx-auto mb-4 opacity-80 animate-pulse"></i>
                <h3 class="text-title-sm font-headline-md font-bold text-on-surface mb-2">Configuração do Asaas Pendente</h3>
                <p class="text-body-md text-on-surface-variant mb-6">Insira a sua chave API Key do Asaas no menu de configurações para habilitar extratos e automação de cobranças.</p>
                <a href="<?= raizUrl('/configuracoes.php') ?>" class="bg-primary hover:bg-primary-container text-on-primary-container font-bold px-8 py-3 rounded-lg text-body-md transition-all active:scale-95 duration-150 shadow-lg inline-block">
                    Configurar Agora
                </a>
            </div>
        <?php else: ?>
            <!-- Card de Saldo -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-card-gap mb-8">
                <div class="glass-card p-6 rounded-xl relative overflow-hidden flex flex-col justify-between h-32 group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-primary/5 rounded-full blur-2xl group-hover:bg-primary/10 transition-colors"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-label-caps font-label-caps text-on-surface-variant mb-1">Saldo em Conta Asaas</p>
                            <h3 class="text-3xl font-bold font-headline-md text-primary tracking-tight font-data-tabular"><?= formatarMoeda((float)$saldoAsaas) ?></h3>
                        </div>
                        <span class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <i data-lucide="landmark" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <p class="text-[9px] font-label-caps text-on-surface-variant mt-3">Saldo líquido disponível para transferência imediata</p>
                </div>

                <div class="glass-card p-6 rounded-xl relative overflow-hidden flex flex-col justify-between h-32 group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-tertiary/5 rounded-full blur-2xl group-hover:bg-tertiary/10 transition-colors"></div>
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-label-caps font-label-caps text-on-surface-variant mb-1">Ambiente de Faturamento</p>
                            <h3 class="text-xl font-bold font-headline-md text-tertiary tracking-tight mt-2">
                                <?= ($asaas->estaConfigurado() && $config = $db->query("SELECT asaas_mode FROM configuracao_empresa WHERE id='principal'")->fetchColumn()) === 'prod' ? '🟢 Produção (Real)' : '🧪 Sandbox (Testes)' ?>
                            </h3>
                        </div>
                        <span class="w-8 h-8 rounded-lg bg-tertiary/10 text-tertiary flex items-center justify-center shrink-0">
                            <i data-lucide="check-circle" class="w-4 h-4"></i>
                        </span>
                    </div>
                    <p class="text-[9px] font-label-caps text-on-surface-variant mt-3">Chave API do Asaas activa e validada</p>
                </div>
            </div>

            <!-- Tabela de Cobranças (Extrato) -->
            <div class="glass-card rounded-xl overflow-hidden mb-6 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-title-sm font-headline-md text-on-surface flex items-center gap-2">
                        <i data-lucide="list-ordered" class="w-5 h-5 text-on-surface-variant"></i>
                        Cobranças Recentes no Asaas (Últimas 15)
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead>
                            <tr class="bg-surface-container-low border-b border-outline-variant/20 text-on-surface-variant font-label-caps">
                                <th class="p-4 font-label-caps text-[10px]">ID Cobrança</th>
                                <th class="p-4 font-label-caps text-[10px]">Cliente</th>
                                <th class="p-4 font-label-caps text-[10px]">Descrição</th>
                                <th class="p-4 font-label-caps text-[10px]">Vencimento</th>
                                <th class="p-4 font-label-caps text-[10px] text-right">Valor</th>
                                <th class="p-4 font-label-caps text-[10px]">Meio</th>
                                <th class="p-4 font-label-caps text-[10px]">Status</th>
                                <th class="p-4 font-label-caps text-[10px] text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/20">
                            <?php if (empty($cobrancasAsaas)): ?>
                                <tr>
                                    <td colspan="8" class="p-10 text-center text-on-surface-variant italic">
                                        Nenhuma cobrança encontrada no painel do Asaas.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cobrancasAsaas as $cob): ?>
                                    <?php
                                    $cStatus = strtolower($cob['status'] ?? '');
                                    $statusLabel = 'Pendente';
                                    $statusClass = 'bg-blue-500/10 text-blue-400 border border-blue-500/20';
                                    
                                    if (in_array($cStatus, ['received', 'confirmed'])) {
                                        $statusLabel = 'Pago';
                                        $statusClass = 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
                                    } elseif ($cStatus === 'overdue') {
                                        $statusLabel = 'Vencido';
                                        $statusClass = 'bg-red-500/10 text-red-400 border border-red-500/20';
                                    } elseif ($cStatus === 'deleted') {
                                        $statusLabel = 'Cancelado';
                                        $statusClass = 'bg-surface-variant text-on-surface-variant border border-outline-variant/20';
                                    }

                                    $meio = $cob['billingType'] ?? '—';
                                    $meioLabel = match($meio) {
                                        'BOLETO' => 'Boleto',
                                        'PIX' => 'Pix',
                                        'CREDIT_CARD' => 'Cartão',
                                        'UNDEFINED' => 'Cliente Escolhe',
                                        default => $meio
                                    };
                                    ?>
                                    <tr class="hover:bg-surface-container-high/20 transition-colors group">
                                        <td class="p-4 font-data-tabular font-bold text-primary"><?= $cob['id'] ?></td>
                                        <td class="p-4 font-bold text-on-surface"><?= sanitizar($cob['customerName'] ?? 'Cliente sem nome') ?></td>
                                        <td class="p-4 text-on-surface-variant max-w-[200px] truncate" title="<?= sanitizar($cob['description'] ?? 'Sem descrição') ?>"><?= sanitizar($cob['description'] ?? 'Sem descrição') ?></td>
                                        <td class="p-4 font-data-tabular text-on-surface-variant"><?= formatarData($cob['dueDate']) ?></td>
                                        <td class="p-4 text-right font-data-tabular font-bold text-on-surface"><?= formatarMoeda((float)$cob['value']) ?></td>
                                        <td class="p-4"><span class="px-2 py-0.5 rounded border border-outline-variant/20 bg-surface-container text-on-surface-variant font-label-caps text-[9px]"><?= $meioLabel ?></span></td>
                                        <td class="p-4">
                                            <span class="status-pill inline-block text-[9px] px-2.5 py-0.5 rounded-full font-label-caps <?= $statusClass ?>">
                                                <?= $statusLabel ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-right">
                                            <div class="flex justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <?php if (!empty($cob['invoiceUrl'])): ?>
                                                    <a href="<?= $cob['invoiceUrl'] ?>" target="_blank" class="px-2.5 py-1 rounded bg-surface-variant text-on-surface-variant hover:text-on-surface font-label-caps text-[9px] border border-outline-variant/20 inline-flex items-center gap-1" title="Ver Fatura">
                                                        <i data-lucide="external-link" class="w-3 h-3"></i> Fatura
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($cob['bankSlipUrl'])): ?>
                                                    <a href="<?= $cob['bankSlipUrl'] ?>" target="_blank" class="px-2.5 py-1 rounded bg-surface-variant text-on-surface-variant hover:text-on-surface font-label-caps text-[9px] border border-outline-variant/20 inline-flex items-center gap-1" title="Ver Boleto">
                                                        <i data-lucide="file-text" class="w-3 h-3"></i> Boleto
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Modal Nova Cobrança Manual -->
        <div class="modal-overlay" x-show="modalNovaCobranca" x-cloak @click.self="modalNovaCobranca = false">
            <div class="modal w-full max-w-lg p-6 bg-surface-container-low border border-outline-variant/30 rounded-2xl shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
                <button @click="modalNovaCobranca = false" class="absolute top-6 right-6 text-on-surface-variant hover:text-on-surface transition-colors cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                
                <div class="mb-6">
                    <h3 class="text-title-sm font-headline-md font-bold text-on-surface">Nova Cobrança Manual Asaas</h3>
                    <p class="text-body-md text-on-surface-variant mt-1">Gere uma cobrança única ou parcelada para um cliente. A cobrança e os lançamentos do ERP serão criados ao mesmo tempo.</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="acao" value="nova_cobranca">
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="label">Cliente *</label>
                            <select name="cliente_id" required class="select w-full">
                                <option value="">Selecione um cliente...</option>
                                <?php foreach ($clientes as $cli): ?>
                                    <option value="<?= $cli['id'] ?>"><?= sanitizar($cli['nome']) ?> (<?= formatarCpfCnpj($cli['cpf_cnpj'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="label">Descrição / Serviço *</label>
                            <input type="text" name="descricao" required class="input w-full" placeholder="Ex: Cobertura Fotográfica Casamento">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Valor Total *</label>
                                <input type="text" name="valor_total" required class="input w-full" placeholder="R$ 0,00" oninput="this.value = formatarCampoMoeda(this.value)">
                            </div>
                            <div>
                                <label class="label">Meio de Faturamento</label>
                                <select name="billing_type" class="select w-full">
                                    <option value="UNDEFINED">Indefinido (Cliente Escolhe)</option>
                                    <option value="BOLETO">Boleto Bancário</option>
                                    <option value="PIX">Apenas Pix</option>
                                    <option value="CREDIT_CARD">Apenas Cartão</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Qtd. de Parcelas</label>
                                <input type="number" name="total_parcelas" value="1" min="1" max="60" class="input w-full">
                            </div>
                            <div>
                                <label class="label">Vencimento da 1ª Parcela *</label>
                                <input type="date" name="vencimento" required class="input w-full">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-outline-variant/10 pt-4">
                            <div>
                                <label class="label">Valor do Sinal (Entrada)</label>
                                <input type="text" name="valor_sinal" class="input w-full" placeholder="R$ 0,00" oninput="this.value = formatarCampoMoeda(this.value)">
                            </div>
                            <div>
                                <label class="label">Vencimento do Sinal</label>
                                <input type="date" name="sinal_vencimento" class="input w-full">
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 justify-end">
                        <button type="button" @click="modalNovaCobranca = false" class="btn-secondary">
                            Cancelar
                        </button>
                        <button type="submit" class="btn-primary">
                            Emitir Cobrança
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function formatarCampoMoeda(v) {
                v = v.replace(/\D/g, "");
                v = (v / 100).toFixed(2) + "";
                v = v.replace(".", ",");
                v = v.replace(/(\d)(\d{3},\d{2})/g, "$1.$2");
                v = v.replace(/(\d)(\d{3}\.\d{3},\d{2})/g, "$1.$2");
                return "R$ " + v;
            }
        </script>
    </main>
</div>
<?php include __DIR__ . '/../includes/layout/footer.php'; ?>
