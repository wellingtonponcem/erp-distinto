<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
exigirAdmin();

$tituloPagina = 'Configurações';
$db = Database::get();

$config = $db->query("SELECT id, nome, cnpj, telefone, email, endereco, groq_api_key, gemini_api_key,
    mercadopago_access_token, mercadopago_public_key, mercadopago_webhook_secret
    FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();

$sucesso = '';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = ['nome','cnpj','telefone','email','endereco'];
    $vals = array_map(fn($c) => trim($_POST[$c] ?? ''), $campos);

    if (!empty($_POST['groq_api_key'])) {
        $campos[] = 'groq_api_key';
        $vals[] = trim($_POST['groq_api_key']);
    }

    if (!empty($_POST['gemini_api_key'])) {
        $campos[] = 'gemini_api_key';
        $vals[] = trim($_POST['gemini_api_key']);
    }

    // Mercado Pago — campos sensíveis: só atualiza se enviado
    if (!empty($_POST['mercadopago_access_token'])) {
        $campos[] = 'mercadopago_access_token';
        $vals[] = trim($_POST['mercadopago_access_token']);
    }
    if (!empty($_POST['mercadopago_public_key'])) {
        $campos[] = 'mercadopago_public_key';
        $vals[] = trim($_POST['mercadopago_public_key']);
    }
    if (!empty($_POST['mercadopago_webhook_secret'])) {
        $campos[] = 'mercadopago_webhook_secret';
        $vals[] = trim($_POST['mercadopago_webhook_secret']);
    }

    $sets = implode(', ', array_map(fn($c) => "\"$c\" = ?", $campos));
    $vals[] = 'principal';
    $stmt = $db->prepare("UPDATE configuracao_empresa SET $sets WHERE id = ?");
    $stmt->execute($vals);
    $sucesso = 'Configurações salvas com sucesso!';
    $config = $db->query("SELECT id, nome, cnpj, telefone, email, endereco, groq_api_key, gemini_api_key,
        mercadopago_access_token, mercadopago_public_key, mercadopago_webhook_secret
        FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
}

include __DIR__ . '/includes/layout/head.php';
?>

<div id="app-wrapper" style="display:flex; min-height:100vh;">
    <?php include __DIR__ . '/includes/layout/sidebar.php'; ?>

    <main id="main-content" style="flex:1; padding:28px 32px; overflow-y:auto; max-width:calc(100vw - 240px);">

        <div style="margin-bottom:28px;">
            <h1 style="font-size:22px; font-weight:700; color:#f1f5f9;">Configurações</h1>
            <p style="font-size:14px; color:#6b7280; margin-top:2px;">Dados da empresa usados nas propostas em PDF</p>
        </div>

        <?php if ($sucesso): ?>
        <div style="background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:8px; padding:12px 16px; margin-bottom:20px; font-size:14px; color:#34d399;">
            <?= sanitizar($sucesso) ?>
        </div>
        <?php endif; ?>

        <div class="card" style="padding:28px; max-width:600px;">
            <h3 style="font-size:15px; font-weight:600; color:#e2e8f0; margin-bottom:20px;">Dados da Empresa</h3>
            <form method="POST">
                <div style="margin-bottom:16px;">
                    <label class="label">Nome da Agência *</label>
                    <input class="input" name="nome" required value="<?= sanitizar($config['nome'] ?? '') ?>" placeholder="Nome da sua agência">
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                    <div>
                        <label class="label">CNPJ</label>
                        <input class="input" name="cnpj" value="<?= sanitizar($config['cnpj'] ?? '') ?>" placeholder="00.000.000/0001-00">
                    </div>
                    <div>
                        <label class="label">Telefone</label>
                        <input class="input" name="telefone" value="<?= sanitizar($config['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
                    </div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="label">E-mail da Agência</label>
                    <input class="input" type="email" name="email" value="<?= sanitizar($config['email'] ?? '') ?>" placeholder="contato@agencia.com.br">
                </div>
                <div style="margin-bottom:24px;">
                    <label class="label">Endereço</label>
                    <textarea class="input" name="endereco" rows="2" placeholder="Rua, número, cidade, estado" style="resize:vertical;"><?= sanitizar($config['endereco'] ?? '') ?></textarea>
                </div>
                <div style="margin-bottom:24px; border-top:1px solid #334155; padding-top:20px;">
                    <label class="label" style="display:flex; justify-content:space-between; align-items:center;">
                        <span>Groq API Key</span>
                        <?php if (!empty($config['groq_api_key'])): ?>
                            <span style="font-size:12px; color:#10b981; font-weight:normal;">✓ Chave salva</span>
                        <?php endif; ?>
                    </label>
                    <input class="input" type="password" name="groq_api_key" placeholder="<?= !empty($config['groq_api_key']) ? '••••••••••••••••••••••••••••••••' : 'gsk_...' ?>">
                    <p style="font-size:12px; color:#6b7280; margin-top:6px;">Utilizada para geração de roteiros ultra-rápida.</p>
                </div>

                <div style="margin-bottom:24px;">
                    <label class="label" style="display:flex; justify-content:space-between; align-items:center;">
                        <span>Gemini API Key (Google)</span>
                        <?php if (!empty($config['gemini_api_key'])): ?>
                            <span style="font-size:12px; color:#10b981; font-weight:normal;">✓ Chave salva</span>
                        <?php endif; ?>
                    </label>
                    <input class="input" type="password" name="gemini_api_key" placeholder="<?= !empty($config['gemini_api_key']) ? '••••••••••••••••••••••••••••••••' : 'AIza...' ?>">
                    <p style="font-size:12px; color:#6b7280; margin-top:6px;">Essencial para Visão (OCR), leitura de imagens e PDFs.</p>
                </div>

                <!-- ── Seção Pagamento (Mercado Pago) ─────────────────────────── -->
                <div style="margin-bottom:8px; border-top:1px solid #334155; padding-top:20px;">
                    <h3 style="font-size:15px; font-weight:600; color:#e2e8f0; margin-bottom:4px;">Pagamento — Mercado Pago</h3>
                    <p style="font-size:12px; color:#6b7280; margin-bottom:20px;">
                        Credenciais para o Checkout Pro (PIX, cartão, boleto). Nunca aparecem no código.<br>
                        Use tokens de teste (<code style="color:#94a3b8;">TEST-...</code>) para sandbox, ou produção (<code style="color:#94a3b8;">APP_USR-...</code>) ao subir.
                    </p>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label" style="display:flex; justify-content:space-between; align-items:center;">
                        <span>Access Token</span>
                        <?php if (!empty($config['mercadopago_access_token'])): ?>
                            <span style="font-size:12px; color:#10b981; font-weight:normal;">✓ Salvo</span>
                        <?php endif; ?>
                    </label>
                    <input class="input" type="password" name="mercadopago_access_token"
                           placeholder="<?= !empty($config['mercadopago_access_token']) ? '••••••••••••••••••••••••••••••••' : 'TEST-... ou APP_USR-...' ?>">
                    <p style="font-size:12px; color:#6b7280; margin-top:6px;">
                        Encontrado em <strong>MP Dashboard → Credenciais → Access Token</strong>. Obrigatório para criar preferências.
                    </p>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="label" style="display:flex; justify-content:space-between; align-items:center;">
                        <span>Public Key <span style="font-weight:400; font-size:11px;">(não é sensível)</span></span>
                        <?php if (!empty($config['mercadopago_public_key'])): ?>
                            <span style="font-size:12px; color:#10b981; font-weight:normal;">✓ Salva</span>
                        <?php endif; ?>
                    </label>
                    <input class="input" type="text" name="mercadopago_public_key"
                           value="<?= sanitizar($config['mercadopago_public_key'] ?? '') ?>"
                           placeholder="APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                    <p style="font-size:12px; color:#6b7280; margin-top:6px;">Encontrada em <strong>MP Dashboard → Credenciais → Public Key</strong>. Usada para o SDK JS (futuro Checkout Transparente).</p>
                </div>

                <div style="margin-bottom:24px;">
                    <label class="label" style="display:flex; justify-content:space-between; align-items:center;">
                        <span>Webhook Secret <span style="font-weight:400; font-size:11px;">(opcional, mas recomendado)</span></span>
                        <?php if (!empty($config['mercadopago_webhook_secret'])): ?>
                            <span style="font-size:12px; color:#10b981; font-weight:normal;">✓ Salvo</span>
                        <?php endif; ?>
                    </label>
                    <input class="input" type="password" name="mercadopago_webhook_secret"
                           placeholder="<?= !empty($config['mercadopago_webhook_secret']) ? '••••••••••••••••••••••••••••••••' : 'Secret do painel de webhooks' ?>">
                    <p style="font-size:12px; color:#6b7280; margin-top:6px;">
                        Configure no <strong>MP Dashboard → Webhooks → Adicionar</strong>, tipo <em>Pagamentos</em>, apontando para:<br>
                        <code style="color:#94a3b8; background:#1e293b; padding:2px 6px; border-radius:4px; font-size:11px; word-break:break-all;">
                            <?= APP_URL ?>/api/assinatura/webhook_mercadopago.php
                        </code>
                    </p>
                </div>

                <button type="submit" class="btn-primary">
                    <i data-lucide="save" style="width:15px;height:15px;"></i> Salvar Configurações
                </button>
            </form>
        </div>

        <!-- Informações técnicas -->
        <div class="card" style="padding:24px; margin-top:20px; max-width:600px;">
            <h3 style="font-size:15px; font-weight:600; color:#e2e8f0; margin-bottom:16px;">Informações do Sistema</h3>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:13px;">
                <div>
                    <div style="color:#6b7280; margin-bottom:2px;">Banco de dados</div>
                    <div style="color:#94a3b8;"><?= DB_HOST ?></div>
                </div>
                <div>
                    <div style="color:#6b7280; margin-bottom:2px;">Modelo IA</div>
                    <div style="color:#94a3b8;"><?= GROQ_MODEL ?></div>
                </div>
                <div>
                    <div style="color:#6b7280; margin-bottom:2px;">Groq API Key</div>
                    <?php $temGroq = !empty($config['groq_api_key']) || !empty(GROQ_API_KEY); ?>
                    <div style="color:<?= $temGroq ? '#10b981' : '#ef4444' ?>;">
                        <?= $temGroq ? '✓ Configurada' : '✗ Não configurada' ?>
                    </div>
                </div>
                <div>
                    <div style="color:#6b7280; margin-bottom:2px;">Gemini API Key</div>
                    <?php $temGemini = !empty($config['gemini_api_key']) || !empty(GEMINI_API_KEY); ?>
                    <div style="color:<?= $temGemini ? '#10b981' : '#ef4444' ?>;">
                        <?= $temGemini ? '✓ Configurada' : '✗ Não configurada' ?>
                    </div>
                </div>
                <div>
                    <div style="color:#6b7280; margin-bottom:2px;">Mercado Pago</div>
                    <?php $temMP = !empty($config['mercadopago_access_token']); ?>
                    <?php $isMPTest = $temMP && str_starts_with($config['mercadopago_access_token'], 'TEST-'); ?>
                    <div style="color:<?= $temMP ? '#10b981' : '#ef4444' ?>;">
                        <?= $temMP ? ('✓ ' . ($isMPTest ? 'Sandbox' : 'Produção')) : '✗ Não configurado' ?>
                    </div>
                </div>
                <div>
                    <div style="color:#6b7280; margin-bottom:2px;">Versão PHP</div>
                    <div style="color:#94a3b8;"><?= PHP_VERSION ?></div>
                </div>
            </div>
            <?php if (!$temGroq): ?>
            <div style="background:rgba(245,158,11,0.1); border:1px solid rgba(245,158,11,0.3); border-radius:8px; padding:12px; margin-top:16px; font-size:13px; color:#fbbf24;">
                ⚠️ A Groq API Key não está configurada. Insira sua chave acima ou edite o arquivo <code>config/env.php</code>.
            </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<?php include __DIR__ . '/includes/layout/footer.php'; ?>
