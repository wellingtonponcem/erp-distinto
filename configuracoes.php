<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
exigirAdmin();

$tituloPagina = 'Configurações';
$db = Database::get();

// Auto-migração: adiciona colunas do Mercado Pago se ainda não existirem
foreach ([
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS openrouter_api_key            TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_access_token      TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_public_key         TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_webhook_secret     TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_client_id          TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_client_secret      TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_test_access_token  TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_test_public_key    TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_prod_access_token  TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_prod_public_key    TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS mercadopago_mode               VARCHAR(10) DEFAULT 'test'",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS assinafy_api_key              TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS assinafy_account_id           VARCHAR(64)",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS assinafy_mode                 VARCHAR(10) DEFAULT 'test'",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS asaas_api_key                 TEXT",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS asaas_mode                    VARCHAR(10) DEFAULT 'test'",
    "ALTER TABLE configuracao_empresa ADD COLUMN IF NOT EXISTS asaas_webhook_token           VARCHAR(255)",
] as $sql) {
    try { $db->exec($sql); } catch (Exception $e) { /* ignora */ }
}

$config = $db->query("SELECT id, nome, cnpj, telefone, email, endereco, groq_api_key, gemini_api_key, openrouter_api_key,
    mercadopago_access_token, mercadopago_public_key, mercadopago_webhook_secret,
    mercadopago_client_id, mercadopago_client_secret,
    mercadopago_test_access_token, mercadopago_test_public_key,
    mercadopago_prod_access_token, mercadopago_prod_public_key,
    mercadopago_mode,
    assinafy_api_key, assinafy_account_id, assinafy_mode,
    asaas_api_key, asaas_mode, asaas_webhook_token
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

    if (!empty($_POST['openrouter_api_key'])) {
        $campos[] = 'openrouter_api_key';
        $vals[] = trim($_POST['openrouter_api_key']);
    }

    if (!empty($_POST['assinafy_api_key'])) {
        $campos[] = 'assinafy_api_key';
        $vals[] = trim($_POST['assinafy_api_key']);
    }

    $campos[] = 'assinafy_account_id';
    $vals[] = trim($_POST['assinafy_account_id'] ?? '');

    $assinafyMode = in_array($_POST['assinafy_mode'] ?? '', ['test', 'prod']) ? $_POST['assinafy_mode'] : 'test';
    $campos[] = 'assinafy_mode';
    $vals[] = $assinafyMode;

    if (!empty($_POST['asaas_api_key'])) {
        $campos[] = 'asaas_api_key';
        $vals[] = trim($_POST['asaas_api_key']);
    }
    $asaasMode = in_array($_POST['asaas_mode'] ?? '', ['test', 'prod']) ? $_POST['asaas_mode'] : 'test';
    $campos[] = 'asaas_mode';
    $vals[] = $asaasMode;
    $campos[] = 'asaas_webhook_token';
    $vals[] = trim($_POST['asaas_webhook_token'] ?? '');

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
    if (!empty($_POST['mercadopago_client_id'])) {
        $campos[] = 'mercadopago_client_id';
        $vals[] = trim($_POST['mercadopago_client_id']);
    }
    if (!empty($_POST['mercadopago_client_secret'])) {
        $campos[] = 'mercadopago_client_secret';
        $vals[] = trim($_POST['mercadopago_client_secret']);
    }
    // Credenciais separadas teste / produção
    if (!empty($_POST['mercadopago_test_access_token'])) {
        $campos[] = 'mercadopago_test_access_token';
        $vals[] = trim($_POST['mercadopago_test_access_token']);
    }
    if (!empty($_POST['mercadopago_test_public_key'])) {
        $campos[] = 'mercadopago_test_public_key';
        $vals[] = trim($_POST['mercadopago_test_public_key']);
    }
    if (!empty($_POST['mercadopago_prod_access_token'])) {
        $campos[] = 'mercadopago_prod_access_token';
        $vals[] = trim($_POST['mercadopago_prod_access_token']);
    }
    if (!empty($_POST['mercadopago_prod_public_key'])) {
        $campos[] = 'mercadopago_prod_public_key';
        $vals[] = trim($_POST['mercadopago_prod_public_key']);
    }
    // Modo ativo — sempre salvo; sincroniza os campos legados automaticamente
    $modo = in_array($_POST['mercadopago_mode'] ?? '', ['test','prod']) ? $_POST['mercadopago_mode'] : 'test';
    $campos[] = 'mercadopago_mode';
    $vals[]   = $modo;
    // Sincronizar campos legados com base no modo ativo
    // (access_token e public_key são os que o resto do sistema usa)
    $cfg_atual = $db->query("SELECT mercadopago_test_access_token, mercadopago_test_public_key,
        mercadopago_prod_access_token, mercadopago_prod_public_key
        FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
    // Mescla: se vieram novos valores no POST, usa-os; caso contrário, usa o que estava salvo
    $testToken  = !empty($_POST['mercadopago_test_access_token']) ? trim($_POST['mercadopago_test_access_token']) : ($cfg_atual['mercadopago_test_access_token'] ?? '');
    $testKey    = !empty($_POST['mercadopago_test_public_key'])   ? trim($_POST['mercadopago_test_public_key'])   : ($cfg_atual['mercadopago_test_public_key']   ?? '');
    $prodToken  = !empty($_POST['mercadopago_prod_access_token']) ? trim($_POST['mercadopago_prod_access_token']) : ($cfg_atual['mercadopago_prod_access_token'] ?? '');
    $prodKey    = !empty($_POST['mercadopago_prod_public_key'])   ? trim($_POST['mercadopago_prod_public_key'])   : ($cfg_atual['mercadopago_prod_public_key']   ?? '');
    $activeToken = $modo === 'prod' ? $prodToken : $testToken;
    $activeKey   = $modo === 'prod' ? $prodKey   : $testKey;
    if ($activeToken) { $campos[] = 'mercadopago_access_token'; $vals[] = $activeToken; }
    if ($activeKey)   { $campos[] = 'mercadopago_public_key';   $vals[] = $activeKey; }

    $sets = implode(', ', array_map(fn($c) => "\"$c\" = ?", $campos));
    $vals[] = 'principal';
    $stmt = $db->prepare("UPDATE configuracao_empresa SET $sets WHERE id = ?");
    $stmt->execute($vals);
    $sucesso = 'Configurações salvas com sucesso!';
    $config = $db->query("SELECT id, nome, cnpj, telefone, email, endereco, groq_api_key, gemini_api_key, openrouter_api_key,
        mercadopago_access_token, mercadopago_public_key, mercadopago_webhook_secret,
        mercadopago_client_id, mercadopago_client_secret,
        mercadopago_test_access_token, mercadopago_test_public_key,
        mercadopago_prod_access_token, mercadopago_prod_public_key,
        mercadopago_mode,
        assinafy_api_key, assinafy_account_id, assinafy_mode,
        asaas_api_key, asaas_mode, asaas_webhook_token
        FROM configuracao_empresa WHERE id='principal' LIMIT 1")->fetch();
}

include __DIR__ . '/includes/layout/head.php';
?>

<div id="app-wrapper" class="flex min-h-screen">
    <?php include __DIR__ . '/includes/layout/sidebar.php'; ?>

    <main id="main-content" class="content-sheet !bg-background !p-6 flex flex-col flex-1">
        <?php include __DIR__ . '/includes/layout/top_nav.php'; ?>

        <!-- Topbar -->
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-8 mt-2">
            <div>
                <h1 class="font-display-lg text-on-surface mb-1">Configurações Gerais</h1>
                <p class="text-body-md text-on-surface-variant">Dados da agência e chaves de API das integrações do sistema.</p>
            </div>
        </div>

        <?php if ($sucesso): ?>
            <div class="bg-primary/10 border border-primary/20 rounded-xl p-4 mb-6 text-sm text-primary flex items-center gap-2 max-w-3xl">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <span><?= sanitizar($sucesso) ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl">
            <!-- Formulário Principal -->
            <div class="lg:col-span-2 glass-card p-6 rounded-xl border border-outline-variant/20 shadow-sm space-y-6">
                <form method="POST">
                    <!-- Dados da Empresa -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-on-surface flex items-center gap-2 mb-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-primary"></i>
                            Dados da Agência
                        </h3>
                        <div>
                            <label class="label">Nome da Agência *</label>
                            <input class="input w-full" name="nome" required value="<?= sanitizar($config['nome'] ?? '') ?>" placeholder="Nome da sua agência">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="label">CNPJ</label>
                                <input class="input w-full" name="cnpj" value="<?= sanitizar($config['cnpj'] ?? '') ?>" placeholder="00.000.000/0001-00">
                            </div>
                            <div>
                                <label class="label">Telefone</label>
                                <input class="input w-full" name="telefone" value="<?= sanitizar($config['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
                            </div>
                        </div>
                        <div>
                            <label class="label">E-mail da Agência</label>
                            <input class="input w-full" type="email" name="email" value="<?= sanitizar($config['email'] ?? '') ?>" placeholder="contato@agencia.com.br">
                        </div>
                        <div>
                            <label class="label">Endereço</label>
                            <textarea class="textarea w-full" name="endereco" rows="2" placeholder="Rua, número, cidade, estado"><?= sanitizar($config['endereco'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Credenciais de Inteligência Artificial -->
                    <div class="border-t border-outline-variant/10 pt-6 mt-6 space-y-4">
                        <h3 class="text-sm font-bold text-on-surface flex items-center gap-2 mb-2">
                            <i data-lucide="cpu" class="w-4 h-4 text-primary"></i>
                            Chaves de API das IAs
                        </h3>
                        <div>
                            <label class="label flex justify-between items-center">
                                <span>Groq API Key</span>
                                <?php if (!empty($config['groq_api_key'])): ?>
                                    <span class="text-[9px] font-label-caps text-primary">✓ Chave salva</span>
                                <?php endif; ?>
                            </label>
                            <input class="input w-full" type="password" name="groq_api_key" placeholder="<?= !empty($config['groq_api_key']) ? '••••••••••••••••••••••••••••••••' : 'gsk_...' ?>">
                            <p class="text-[10px] text-on-surface-variant mt-1.5">Utilizada para geração rápida de roteiros.</p>
                        </div>

                        <div>
                            <label class="label flex justify-between items-center">
                                <span>Gemini API Key (Google)</span>
                                <?php if (!empty($config['gemini_api_key'])): ?>
                                    <span class="text-[9px] font-label-caps text-primary">✓ Chave salva</span>
                                <?php endif; ?>
                            </label>
                            <input class="input w-full" type="password" name="gemini_api_key" placeholder="<?= !empty($config['gemini_api_key']) ? '••••••••••••••••••••••••••••••••' : 'AIza...' ?>">
                            <p class="text-[10px] text-on-surface-variant mt-1.5">Essencial para leitura inteligente de imagens e PDFs.</p>
                        </div>

                        <div>
                            <label class="label flex justify-between items-center">
                                <span>OpenRouter API Key</span>
                                <?php if (!empty($config['openrouter_api_key'])): ?>
                                    <span class="text-[9px] font-label-caps text-primary">✓ Chave salva</span>
                                <?php endif; ?>
                            </label>
                            <input class="input w-full" type="password" name="openrouter_api_key" placeholder="<?= !empty($config['openrouter_api_key']) ? '••••••••••••••••••••••••••••••••' : 'sk-or-v1-...' ?>">
                            <p class="text-[10px] text-on-surface-variant mt-1.5">Usada para roteiros com modelos Qwen via OpenRouter.</p>
                        </div>
                    </div>

                    <!-- Mercado Pago -->
                    <div class="border-t border-outline-variant/10 pt-6 mt-6 space-y-4">
                        <h3 class="text-sm font-bold text-on-surface flex items-center gap-2 mb-1">
                            <i data-lucide="wallet" class="w-4 h-4 text-primary"></i>
                            Mercado Pago
                        </h3>
                        <p class="text-xs text-on-surface-variant">O sistema usa automaticamente as credenciais do ambiente ativo selecionado.</p>

                        <?php
                        $mpToken  = $config['mercadopago_access_token'] ?? '';
                        $mpMode   = str_starts_with($mpToken, 'TEST-') ? 'test' : ($mpToken ? 'prod' : 'none');
                        $modeBadge = match($mpMode) {
                            'test' => ['🧪 Sandbox Ativo', 'text-amber-400', 'bg-amber-500/10 border-amber-500/20'],
                            'prod' => ['🟢 Produção Ativa', 'text-emerald-400', 'bg-emerald-500/10 border-emerald-500/20'],
                            default => ['⚪ Não Configurado', 'text-on-surface-variant', 'bg-surface-container border-outline-variant/10'],
                        };
                        ?>
                        <div class="px-3 py-2 rounded-lg border text-xs font-bold flex items-center gap-2 w-fit <?= $modeBadge[2] ?> <?= $modeBadge[1] ?>">
                            <?= $modeBadge[0] ?>
                        </div>

                        <!-- Credenciais de Teste -->
                        <div class="space-y-3">
                            <div class="text-[9px] font-label-caps text-amber-400 tracking-wider">🧪 Ambiente de Teste (Sandbox)</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="label">Access Token (Teste)</label>
                                    <input class="input w-full" type="password" name="mercadopago_test_access_token" placeholder="<?= !empty($config['mercadopago_test_access_token']) ? '••••••••••••••••••••••••••••••••' : 'TEST-...' ?>">
                                </div>
                                <div>
                                    <label class="label">Public Key (Teste)</label>
                                    <input class="input w-full" type="text" name="mercadopago_test_public_key" value="<?= sanitizar($config['mercadopago_test_public_key'] ?? '') ?>" placeholder="TEST-xxxxxxxx...">
                                </div>
                            </div>
                        </div>

                        <!-- Credenciais de Produção -->
                        <div class="space-y-3 pt-2">
                            <div class="text-[9px] font-label-caps text-emerald-400 tracking-wider">🟢 Ambiente de Produção</div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="label">Access Token (Produção)</label>
                                    <input class="input w-full" type="password" name="mercadopago_prod_access_token" placeholder="<?= !empty($config['mercadopago_prod_access_token']) ? '••••••••••••••••••••••••••••••••' : 'APP_USR-...' ?>">
                                </div>
                                <div>
                                    <label class="label">Public Key (Produção)</label>
                                    <input class="input w-full" type="text" name="mercadopago_prod_public_key" value="<?= sanitizar($config['mercadopago_prod_public_key'] ?? '') ?>" placeholder="APP_USR-xxxxxxxx...">
                                </div>
                            </div>
                        </div>

                        <!-- Ambiente Ativo -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Ambiente Ativo</label>
                                <select class="select w-full" name="mercadopago_mode">
                                    <option value="test" <?= ($config['mercadopago_mode'] ?? 'test') === 'test' ? 'selected' : '' ?>>🧪 Teste (Sandbox)</option>
                                    <option value="prod" <?= ($config['mercadopago_mode'] ?? 'test') === 'prod' ? 'selected' : '' ?>>🟢 Produção</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Webhook Secret (Assinatura)</label>
                                <input class="input w-full" type="password" name="mercadopago_webhook_secret" placeholder="<?= !empty($config['mercadopago_webhook_secret']) ? '••••••••••••••••••••••••••••••••' : 'Assinatura secreta do webhook' ?>">
                            </div>
                        </div>
                        <div class="text-[10px] text-on-surface-variant">
                            URL do Webhook MP para colar no painel de desenvolvedores:<br>
                            <code class="px-1.5 py-0.5 rounded bg-surface-container border border-outline-variant/15 text-primary text-[9px] font-mono break-all inline-block mt-1">
                                <?= APP_URL ?>/api/assinatura/webhook_mercadopago.php
                            </code>
                        </div>

                        <!-- OAuth Opcional -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="label">Client ID (OAuth)</label>
                                <input class="input w-full" type="text" name="mercadopago_client_id" value="<?= sanitizar($config['mercadopago_client_id'] ?? '') ?>" placeholder="Ex: 58142549...">
                            </div>
                            <div>
                                <label class="label">Client Secret (OAuth)</label>
                                <input class="input w-full" type="password" name="mercadopago_client_secret" placeholder="<?= !empty($config['mercadopago_client_secret']) ? '••••••••••••••••••••••••••••••••' : 'Secret do aplicativo' ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Assinafy -->
                    <div class="border-t border-outline-variant/10 pt-6 mt-6 space-y-4">
                        <h3 class="text-sm font-bold text-on-surface flex items-center gap-2 mb-2">
                            <i data-lucide="pen-tool" class="w-4 h-4 text-primary"></i>
                            Assinatura Eletrônica — Assinafy
                        </h3>
                        <div>
                            <label class="label flex justify-between items-center">
                                <span>API Key (Assinafy)</span>
                                <?php if (!empty($config['assinafy_api_key'])): ?>
                                    <span class="text-[9px] font-label-caps text-primary">✓ Chave salva</span>
                                <?php endif; ?>
                            </label>
                            <input class="input w-full" type="password" name="assinafy_api_key" placeholder="<?= !empty($config['assinafy_api_key']) ? '••••••••••••••••••••••••••••••••' : 'Cole a chave da API do Assinafy' ?>">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="label">ID da Conta (Account ID)</label>
                                <input class="input w-full" type="text" name="assinafy_account_id" value="<?= sanitizar($config['assinafy_account_id'] ?? '') ?>" placeholder="ID da Conta no painel">
                            </div>
                            <div>
                                <label class="label">Ambiente Ativo</label>
                                <select class="select w-full" name="assinafy_mode">
                                    <option value="test" <?= ($config['assinafy_mode'] ?? 'test') === 'test' ? 'selected' : '' ?>>🧪 Sandbox (Testes)</option>
                                    <option value="prod" <?= ($config['assinafy_mode'] ?? 'test') === 'prod' ? 'selected' : '' ?>>🟢 Produção (Real)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Asaas -->
                    <div class="border-t border-outline-variant/10 pt-6 mt-6 space-y-4">
                        <h3 class="text-sm font-bold text-on-surface flex items-center gap-2 mb-2">
                            <i data-lucide="banknote" class="w-4 h-4 text-primary"></i>
                            Gateway de Pagamentos — Asaas
                        </h3>
                        <div>
                            <label class="label flex justify-between items-center">
                                <span>API Key (Asaas)</span>
                                <?php if (!empty($config['asaas_api_key'])): ?>
                                    <span class="text-[9px] font-label-caps text-primary">✓ Chave salva</span>
                                <?php endif; ?>
                            </label>
                            <input class="input w-full" type="password" name="asaas_api_key" placeholder="<?= !empty($config['asaas_api_key']) ? '••••••••••••••••••••••••••••••••' : 'Cole a chave da API do Asaas' ?>">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Ambiente Ativo</label>
                                <select class="select w-full" name="asaas_mode">
                                    <option value="test" <?= ($config['asaas_mode'] ?? 'test') === 'test' ? 'selected' : '' ?>>🧪 Sandbox (Testes)</option>
                                    <option value="prod" <?= ($config['asaas_mode'] ?? 'test') === 'prod' ? 'selected' : '' ?>>🟢 Produção (Real)</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Token do Webhook</label>
                                <input class="input w-full" type="text" name="asaas_webhook_token" value="<?= sanitizar($config['asaas_webhook_token'] ?? '') ?>" placeholder="Token de segurança do webhook">
                            </div>
                        </div>
                        <div class="text-[10px] text-on-surface-variant">
                            URL do Webhook Asaas para colar no painel do Asaas (Integrações > Webhooks > Cobranças):<br>
                            <code class="px-1.5 py-0.5 rounded bg-surface-container border border-outline-variant/15 text-primary text-[9px] font-mono break-all inline-block mt-1">
                                <?= sanitizar(preg_replace('#/sistema/?$#', '', rtrim(APP_URL, '/')) . raizUrl('/api/financeiro/webhook_asaas.php')) ?>
                            </code>
                        </div>
                    </div>

                    <!-- Botão de Salvar -->
                    <div class="border-t border-outline-variant/10 pt-6 mt-6 flex justify-end">
                        <button type="submit" class="bg-primary hover:bg-primary-container text-on-primary-container font-bold px-8 py-3 rounded-lg text-body-md transition-all active:scale-95 duration-150 shadow-lg flex items-center gap-2 cursor-pointer">
                            <i data-lucide="save" class="w-4 h-4"></i> Salvar Todas as Configurações
                        </button>
                    </div>
                </form>
            </div>

            <!-- Painel Lateral de Informações Técnicas -->
            <div class="space-y-6">
                <div class="glass-card p-6 rounded-xl border border-outline-variant/20 shadow-sm">
                    <h3 class="text-sm font-bold text-on-surface mb-4 flex items-center gap-2">
                        <i data-lucide="info" class="w-4 h-4 text-primary"></i>
                        Informações do Sistema
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <div class="text-[9px] font-label-caps text-on-surface-variant">Banco de dados</div>
                            <div class="text-xs font-bold text-on-surface font-data-tabular mt-0.5"><?= DB_HOST ?></div>
                        </div>
                        <div>
                            <div class="text-[9px] font-label-caps text-on-surface-variant">Modelo de IA Padrão</div>
                            <div class="text-xs font-bold text-on-surface font-data-tabular mt-0.5"><?= GROQ_MODEL ?></div>
                        </div>
                        <div>
                            <div class="text-[9px] font-label-caps text-on-surface-variant">Groq API Key</div>
                            <?php $temGroq = !empty($config['groq_api_key']) || !empty(GROQ_API_KEY); ?>
                            <div class="text-xs font-bold mt-0.5 flex items-center gap-1 <?= $temGroq ? 'text-primary' : 'text-error' ?>">
                                <i data-lucide="<?= $temGroq ? 'check' : 'x' ?>" class="w-3.5 h-3.5"></i>
                                <?= $temGroq ? 'Configurada' : 'Não configurada' ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-[9px] font-label-caps text-on-surface-variant">Gemini API Key</div>
                            <?php $temGemini = !empty($config['gemini_api_key']) || !empty(GEMINI_API_KEY); ?>
                            <div class="text-xs font-bold mt-0.5 flex items-center gap-1 <?= $temGemini ? 'text-primary' : 'text-error' ?>">
                                <i data-lucide="<?= $temGemini ? 'check' : 'x' ?>" class="w-3.5 h-3.5"></i>
                                <?= $temGemini ? 'Configurada' : 'Não configurada' ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-[9px] font-label-caps text-on-surface-variant">OpenRouter API Key</div>
                            <?php $temOpenRouter = !empty($config['openrouter_api_key']) || (defined('OPENROUTER_API_KEY') && !empty(OPENROUTER_API_KEY)); ?>
                            <div class="text-xs font-bold mt-0.5 flex items-center gap-1 <?= $temOpenRouter ? 'text-primary' : 'text-error' ?>">
                                <i data-lucide="<?= $temOpenRouter ? 'check' : 'x' ?>" class="w-3.5 h-3.5"></i>
                                <?= $temOpenRouter ? 'Configurada' : 'Não configurada' ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-[9px] font-label-caps text-on-surface-variant">Mercado Pago</div>
                            <?php $temMP = !empty($config['mercadopago_access_token']); ?>
                            <?php $isMPTest = $temMP && str_starts_with($config['mercadopago_access_token'], 'TEST-'); ?>
                            <div class="text-xs font-bold mt-0.5 flex items-center gap-1 <?= $temMP ? 'text-primary' : 'text-error' ?>">
                                <i data-lucide="<?= $temMP ? 'check' : 'x' ?>" class="w-3.5 h-3.5"></i>
                                <?= $temMP ? ($isMPTest ? 'Sandbox Ativo' : 'Produção Ativa') : 'Não configurado' ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-[9px] font-label-caps text-on-surface-variant">Asaas</div>
                            <?php $temAsaas = !empty($config['asaas_api_key']); ?>
                            <div class="text-xs font-bold mt-0.5 flex items-center gap-1 <?= $temAsaas ? 'text-primary' : 'text-error' ?>">
                                <i data-lucide="<?= $temAsaas ? 'check' : 'x' ?>" class="w-3.5 h-3.5"></i>
                                <?= $temAsaas ? ((($config['asaas_mode'] ?? 'test') === 'test' ? 'Sandbox' : 'Produção') . ' Ativa') : 'Não configurado' ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-[9px] font-label-caps text-on-surface-variant">Versão do PHP</div>
                            <div class="text-xs font-bold text-on-surface font-data-tabular mt-0.5"><?= PHP_VERSION ?></div>
                        </div>
                    </div>
                </div>

                <?php if (!$temGroq): ?>
                    <div class="bg-amber-500/10 border border-amber-500/20 text-amber-400 p-4 rounded-xl text-xs leading-relaxed flex gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4 shrink-0 mt-0.5"></i>
                        <span>A Groq API Key não está configurada. Insira sua chave acima ou edite o arquivo <code>config/env.php</code> para habilitar IA.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/layout/footer.php'; ?>
