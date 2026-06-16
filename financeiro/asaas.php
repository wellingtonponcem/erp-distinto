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
<div id="app-wrapper" style="display:flex; min-height:100vh;" x-data="{ modalNovaCobranca: false }">
    <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

    <main id="main-content" style="flex:1; padding:28px 32px; overflow-y:auto; background:#0A0A0A !important;">
        
        <div style="margin-bottom:28px; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size:22px; font-weight:700; color:#FFFFFF !important; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="wallet" style="width:24px; height:24px; color:#a78bfa;"></i>
                    Asaas Pagamentos
                </h1>
                <p style="font-size:14px; color:#9CA3AF !important; margin-top:2px;">Gestão de faturamento de clientes, conciliação e extrato em tempo real</p>
            </div>
            
            <div style="display:flex; gap:10px;">
                <?php if ($estaConfigurado): ?>
                    <button @click="modalNovaCobranca = true" class="btn-primary" style="background:#a78bfa !important; border:none; color:#000000 !important; font-weight:800; padding:10px 20px; border-radius:12px; cursor:pointer; display:flex; align-items:center; gap:6px;">
                        <i data-lucide="plus" style="width:16px; height:16px;"></i> Nova Cobrança Manual
                    </button>
                <?php endif; ?>
                <a href="<?= raizUrl('/configuracoes.php') ?>" class="btn-secondary" style="background:#141414 !important; border:1px solid rgba(255,255,255,0.1); color:#FFFFFF !important; font-weight:700; padding:10px 20px; border-radius:12px; display:inline-flex; align-items:center; gap:6px;">
                    <i data-lucide="settings" style="width:16px; height:16px;"></i> Configurar Credenciais
                </a>
            </div>
        </div>

        <?php if ($sucesso): ?>
            <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:12px; padding:16px; margin-bottom:24px; font-size:14px; color:#34d399;">
                <?= sanitizar($sucesso) ?>
            </div>
        <?php endif; ?>

        <?php if ($erro): ?>
            <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:12px; padding:16px; margin-bottom:24px; font-size:14px; color:#f87171;">
                <?= sanitizar($erro) ?>
            </div>
        <?php endif; ?>

        <?php if (!$estaConfigurado): ?>
            <div style="background:#141414; border:1px dashed rgba(167,139,250,0.3); border-radius:24px; padding:48px; text-align:center; max-width:600px; margin: 40px auto;">
                <i data-lucide="shield-alert" style="width:48px; height:48px; color:#a78bfa; margin:0 auto 20px; opacity:0.8;"></i>
                <h3 style="font-size:18px; font-weight:700; color:#FFFFFF; margin-bottom:10px;">Configuração do Asaas Pendente</h3>
                <p style="font-size:14px; color:#9CA3AF; margin-bottom:24px;">Insira a sua chave API Key do Asaas no menu de configurações para habilitar extratos e automação de cobranças.</p>
                <a href="<?= raizUrl('/configuracoes.php') ?>" class="btn-primary" style="background:#a78bfa !important; border:none; color:#000000 !important; font-weight:800; padding:12px 32px; border-radius:12px; text-decoration:none; display:inline-block;">
                    Configurar Agora
                </a>
            </div>
        <?php else: ?>
            <!-- Card de Saldo -->
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px; margin-bottom:28px;">
                <div style="background:#141414; border:1px solid rgba(255,255,255,0.06); border-radius:20px; padding:24px; position:relative; overflow:hidden;">
                    <div style="position:absolute; top:20px; right:20px; color:#a78bfa; opacity:0.15;">
                        <i data-lucide="landmark" style="width:40px; height:40px;"></i>
                    </div>
                    <div style="font-size:11px; font-weight:800; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">Saldo em Conta Asaas</div>
                    <div style="font-size:32px; font-weight:900; color:#FFFFFF; line-height:1;"><?= formatarMoeda($saldoAsaas) ?></div>
                    <div style="font-size:12px; color:#9CA3AF; margin-top:10px;">Saldo líquido disponível para transferência imediata</div>
                </div>

                <div style="background:#141414; border:1px solid rgba(255,255,255,0.06); border-radius:20px; padding:24px; position:relative; overflow:hidden;">
                    <div style="position:absolute; top:20px; right:20px; color:#34d399; opacity:0.15;">
                        <i data-lucide="check-circle" style="width:40px; height:40px;"></i>
                    </div>
                    <div style="font-size:11px; font-weight:800; color:#9CA3AF; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">Ambiente de Faturamento</div>
                    <div style="font-size:24px; font-weight:900; color:#34d399; line-height:1; display:flex; align-items:center; gap:8px; margin-top:8px;">
                        <?= ($asaas->estaConfigurado() && $config = $db->query("SELECT asaas_mode FROM configuracao_empresa WHERE id='principal'")->fetchColumn()) === 'prod' ? '🟢 Produção (Real)' : '🧪 Sandbox (Testes)' ?>
                    </div>
                    <div style="font-size:12px; color:#9CA3AF; margin-top:14px;">Chave API do Asaas ativa e validada</div>
                </div>
            </div>

            <!-- Tabela de Cobranças (Extrato) -->
            <div style="background:#141414; border:1px solid rgba(255,255,255,0.06); border-radius:20px; padding:24px; overflow:hidden;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="font-size:16px; font-weight:700; color:#FFFFFF; display:flex; align-items:center; gap:8px;">
                        <i data-lucide="list-ordered" style="width:18px; height:18px; color:#9CA3AF;"></i>
                        Cobranças Recentes no Asaas (Últimas 15)
                    </h3>
                </div>

                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                        <thead>
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.06); color:#9CA3AF; font-size:11px; text-transform:uppercase; font-weight:800;">
                                <th style="padding:16px 12px;">ID Cobrança</th>
                                <th style="padding:16px 12px;">Cliente</th>
                                <th style="padding:16px 12px;">Descrição</th>
                                <th style="padding:16px 12px;">Vencimento</th>
                                <th style="padding:16px 12px; text-align:right;">Valor</th>
                                <th style="padding:16px 12px;">Meio</th>
                                <th style="padding:16px 12px;">Status</th>
                                <th style="padding:16px 12px; text-align:right;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($cobrancasAsaas)): ?>
                                <tr>
                                    <td colspan="8" style="padding:40px; text-align:center; color:#6b7280; font-style:italic;">
                                        Nenhuma cobrança encontrada no painel do Asaas.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($cobrancasAsaas as $cob): ?>
                                    <?php
                                    $cStatus = strtolower($cob['status'] ?? '');
                                    $statusLabel = 'Pendente';
                                    $statusColor = '#60a5fa';
                                    $statusBg = 'rgba(96,165,250,0.1)';
                                    
                                    if (in_array($cStatus, ['received', 'confirmed'])) {
                                        $statusLabel = 'Pago';
                                        $statusColor = '#34d399';
                                        $statusBg = 'rgba(52,211,153,0.1)';
                                    } elseif ($cStatus === 'overdue') {
                                        $statusLabel = 'Vencido';
                                        $statusColor = '#f87171';
                                        $statusBg = 'rgba(248,113,113,0.1)';
                                    } elseif ($cStatus === 'deleted') {
                                        $statusLabel = 'Cancelado';
                                        $statusColor = '#9ca3af';
                                        $statusBg = 'rgba(156,163,175,0.1)';
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
                                    <tr style="border-bottom:1px solid rgba(255,255,255,0.02);">
                                        <td style="padding:16px 12px; font-weight:700; color:#a78bfa;"><?= $cob['id'] ?></td>
                                        <td style="padding:16px 12px; color:#FFFFFF; font-weight:600;"><?= sanitizar($cob['customerName'] ?? 'Cliente sem nome') ?></td>
                                        <td style="padding:16px 12px; color:#9CA3AF; max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= sanitizar($cob['description'] ?? 'Sem descrição') ?></td>
                                        <td style="padding:16px 12px; color:#9CA3AF;"><?= formatarData($cob['dueDate']) ?></td>
                                        <td style="padding:16px 12px; text-align:right; color:#FFFFFF; font-weight:700;"><?= formatarMoeda((float)$cob['value']) ?></td>
                                        <td style="padding:16px 12px; color:#9CA3AF;"><span style="font-size:11px; font-weight:800; border:1px solid rgba(255,255,255,0.08); padding:3px 8px; border-radius:6px;"><?= $meioLabel ?></span></td>
                                        <td style="padding:16px 12px;">
                                            <span style="font-size:11px; font-weight:800; color:<?= $statusColor ?>; background:<?= $statusBg ?>; padding:4px 10px; border-radius:99px; border:1px solid <?= str_replace('0.1', '0.2', $statusBg) ?>;">
                                                <?= $statusLabel ?>
                                            </span>
                                        </td>
                                        <td style="padding:16px 12px; text-align:right;">
                                            <div style="display:flex; justify-content:flex-end; gap:6px;">
                                                <?php if (!empty($cob['invoiceUrl'])): ?>
                                                    <a href="<?= $cob['invoiceUrl'] ?>" target="_blank" class="btn-secondary" style="background:rgba(255,255,255,0.03) !important; border:1px solid rgba(255,255,255,0.08); padding:6px 12px; font-size:11px; border-radius:8px; display:inline-flex; align-items:center; gap:4px;" title="Ver Fatura">
                                                        <i data-lucide="external-link" style="width:13px; height:13px;"></i> Fatura
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (!empty($cob['bankSlipUrl'])): ?>
                                                    <a href="<?= $cob['bankSlipUrl'] ?>" target="_blank" class="btn-secondary" style="background:rgba(255,255,255,0.03) !important; border:1px solid rgba(255,255,255,0.08); padding:6px 12px; font-size:11px; border-radius:8px; display:inline-flex; align-items:center; gap:4px;" title="Ver Boleto">
                                                        <i data-lucide="file-text" style="width:13px; height:13px;"></i> Boleto
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
        <div class="fixed inset-0 bg-black/80 backdrop-blur-md z-[9999] flex items-center justify-center p-4"
             x-show="modalNovaCobranca" x-cloak style="z-index: 9999;">
            <div class="bg-zinc-950 border border-white/10 rounded-[2rem] p-8 w-full max-w-lg shadow-2xl relative" @click.away="modalNovaCobranca = false">
                <button @click="modalNovaCobranca = false" class="absolute top-6 right-6 text-zinc-400 hover:text-white transition-colors cursor-pointer" style="background:none; border:none;">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
                
                <div class="mb-6">
                    <div class="w-12 h-12 rounded-2xl bg-zinc-900 flex items-center justify-center text-zinc-300 mb-4 border border-white/5">
                        <i data-lucide="wallet" class="w-6 h-6 text-a78bfa"></i>
                    </div>
                    <h3 class="text-xl font-black text-white">Nova Cobrança Manual Asaas</h3>
                    <p class="text-xs text-zinc-400 mt-1">Gere uma cobrança única ou parcelada para um cliente. A cobrança e os lançamentos do ERP serão criados ao mesmo tempo.</p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="acao" value="nova_cobranca">
                    <div class="space-y-4 mb-6">
                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-wider text-zinc-400 mb-2">Cliente *</label>
                            <select name="cliente_id" required class="w-full bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-white/20 transition-all cursor-pointer">
                                <option value="">Selecione um cliente...</option>
                                <?php foreach ($clientes as $cli): ?>
                                    <option value="<?= $cli['id'] ?>"><?= sanitizar($cli['nome']) ?> (<?= formatarCpfCnpj($cli['cpf_cnpj'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-black uppercase tracking-wider text-zinc-400 mb-2">Descrição / Serviço *</label>
                            <input type="text" name="descricao" required class="w-full bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-white/20 transition-all" placeholder="Ex: Cobertura Fotográfica Casamento">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-wider text-zinc-400 mb-2">Valor Total *</label>
                                <input type="text" name="valor_total" required class="w-full bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-white/20 transition-all" placeholder="R$ 0,00" oninput="this.value = formatarCampoMoeda(this.value)">
                            </div>
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-wider text-zinc-400 mb-2">Meio de Faturamento</label>
                                <select name="billing_type" class="w-full bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-white/20 transition-all cursor-pointer">
                                    <option value="UNDEFINED">Indefinido (Cliente Escolhe)</option>
                                    <option value="BOLETO">Boleto Bancário</option>
                                    <option value="PIX">Apenas Pix</option>
                                    <option value="CREDIT_CARD">Apenas Cartão</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-wider text-zinc-400 mb-2">Qtd. de Parcelas</label>
                                <input type="number" name="total_parcelas" value="1" min="1" max="60" class="w-full bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-white/20 transition-all">
                            </div>
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-wider text-zinc-400 mb-2">Vencimento da 1ª Parcela *</label>
                                <input type="date" name="vencimento" required class="w-full bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-white/20 transition-all">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-t border-white/5 pt-4">
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-wider text-zinc-400 mb-2">Valor do Sinal (Entrada)</label>
                                <input type="text" name="valor_sinal" class="w-full bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-white/20 transition-all" placeholder="R$ 0,00" oninput="this.value = formatarCampoMoeda(this.value)">
                            </div>
                            <div>
                                <label class="block text-[11px] font-black uppercase tracking-wider text-zinc-400 mb-2">Vencimento do Sinal</label>
                                <input type="date" name="sinal_vencimento" class="w-full bg-zinc-900 border border-white/5 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-white/20 transition-all">
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="button" @click="modalNovaCobranca = false" 
                                class="flex-1 py-3 bg-zinc-900 hover:bg-zinc-850 text-zinc-400 hover:text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="flex-1 py-3 bg-white hover:bg-zinc-200 text-black rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-1.5 cursor-pointer">
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
