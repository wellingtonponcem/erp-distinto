<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();
$db = Database::get();

$statusMessage = '';
$errorMessage = '';
$editCliente = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = sanitizar($_POST['nome'] ?? '');
    $cpf_cnpj = sanitizar($_POST['cpf_cnpj'] ?? '');
    $contato = sanitizar($_POST['contato'] ?? '');
    $segmento = sanitizar($_POST['segmento'] ?? '');

    if (!$nome) {
        $errorMessage = 'O nome do cliente é obrigatório.';
    } else {
        if ($id) {
            $stmt = $db->prepare("UPDATE clientes SET nome = ?, cpf_cnpj = ?, contato = ?, segmento = ? WHERE id = ?");
            $stmt->execute([$nome, $cpf_cnpj, $contato, $segmento, $id]);
            $statusMessage = 'Cliente atualizado com sucesso.';
        } else {
            $stmt = $db->prepare("INSERT INTO clientes (id, nome, cpf_cnpj, contato, segmento) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([gerarId(), $nome, $cpf_cnpj, $contato, $segmento]);
            $statusMessage = 'Cliente cadastrado com sucesso.';
        }
    }
}

if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $editCliente = $stmt->fetch();
}

if (isset($_GET['deletar'])) {
    $stmt = $db->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$_GET['deletar']]);
    $statusMessage = 'Cliente excluído com sucesso.';
}

$clientes = $db->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll();
$tituloPagina = 'CRM • Clientes';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content">
        <div class="app-topbar">
            <div>
                <h1 class="page-title">Clientes</h1>
                <p class="page-subtitle">Cadastre e acompanhe os clientes que alimentam seu pipeline.</p>
            </div>
        </div>

        <?php if ($statusMessage): ?>
            <div class="toast toast-success" style="margin-bottom:20px;"><?= sanitizar($statusMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="toast toast-error" style="margin-bottom:20px;"><?= sanitizar($errorMessage) ?></div>
        <?php endif; ?>

        <div class="card" style="margin-bottom:24px;">
            <h2 class="card-title"><?= $editCliente ? 'Editar Cliente' : 'Novo Cliente' ?></h2>
            <form method="post" action="<?= raizUrl('/gerenciamento/clientes.php') ?>">
                <input type="hidden" name="id" value="<?= $editCliente['id'] ?? '' ?>">
                <div class="form-grid">
                    <div>
                        <label class="label">Nome *</label>
                        <input class="input" type="text" name="nome" value="<?= sanitizar($editCliente['nome'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label class="label">CPF / CNPJ</label>
                        <input class="input" type="text" name="cpf_cnpj" value="<?= sanitizar($editCliente['cpf_cnpj'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="label">Contato</label>
                        <input class="input" type="text" name="contato" value="<?= sanitizar($editCliente['contato'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="label">Segmento</label>
                        <input class="input" type="text" name="segmento" value="<?= sanitizar($editCliente['segmento'] ?? '') ?>">
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:16px;">
                    <?php if ($editCliente): ?>
                        <a class="btn-secondary" href="<?= raizUrl('/gerenciamento/clientes.php') ?>">Cancelar</a>
                    <?php endif; ?>
                    <button class="btn-primary" type="submit"><?= $editCliente ? 'Salvar alterações' : 'Cadastrar cliente' ?></button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 class="card-title">Lista de clientes</h2>
            <div class="table-header" style="grid-template-columns: 1.8fr 1fr 1fr 1fr 110px;">
                <div>Nome</div>
                <div>CPF / CNPJ</div>
                <div>Contato</div>
                <div>Segmento</div>
                <div style="text-align:right;">Ações</div>
            </div>
            <?php foreach ($clientes as $cliente): ?>
                <div class="table-row" style="grid-template-columns: 1.8fr 1fr 1fr 1fr 110px;">
                    <div><?= sanitizar($cliente['nome']) ?></div>
                    <div><?= sanitizar($cliente['cpf_cnpj']) ?></div>
                    <div><?= sanitizar($cliente['contato']) ?></div>
                    <div><?= sanitizar($cliente['segmento']) ?></div>
                    <div style="text-align:right;">
                        <a class="btn-link" href="<?= raizUrl('/gerenciamento/clientes.php?editar=' . $cliente['id']) ?>">Editar</a>
                        <a class="btn-link btn-link-danger" href="<?= raizUrl('/gerenciamento/clientes.php?deletar=' . $cliente['id']) ?>" onclick="return confirm('Excluir este cliente?');">Excluir</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
