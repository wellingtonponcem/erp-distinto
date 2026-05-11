<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();
$db = Database::get();

$statusMessage = '';
$errorMessage = '';
$editFornecedor = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = sanitizar($_POST['nome'] ?? '');
    $contato = sanitizar($_POST['contato'] ?? '');
    $telefone = sanitizar($_POST['telefone'] ?? '');
    $email = sanitizar($_POST['email'] ?? '');
    $categoria = sanitizar($_POST['categoria'] ?? '');
    $observacao = sanitizar($_POST['observacao'] ?? '');

    if (!$nome) {
        $errorMessage = 'O nome do fornecedor é obrigatório.';
    } else {
        if ($id) {
            $stmt = $db->prepare("UPDATE fornecedores SET nome = ?, contato = ?, telefone = ?, email = ?, categoria = ?, observacao = ? WHERE id = ?");
            $stmt->execute([$nome, $contato, $telefone, $email, $categoria, $observacao, $id]);
            $statusMessage = 'Fornecedor atualizado com sucesso.';
        } else {
            $stmt = $db->prepare("INSERT INTO fornecedores (id, nome, contato, telefone, email, categoria, observacao) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([gerarId(), $nome, $contato, $telefone, $email, $categoria, $observacao]);
            $statusMessage = 'Fornecedor cadastrado com sucesso.';
        }
    }
}

if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM fornecedores WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $editFornecedor = $stmt->fetch();
}

if (isset($_GET['deletar'])) {
    $stmt = $db->prepare("DELETE FROM fornecedores WHERE id = ?");
    $stmt->execute([$_GET['deletar']]);
    $statusMessage = 'Fornecedor excluído com sucesso.';
}

$fornecedores = $db->query("SELECT * FROM fornecedores ORDER BY nome ASC")->fetchAll();
$tituloPagina = 'CRM • Fornecedores';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content">
        <div class="app-topbar">
            <div>
                <h1 class="page-title">Fornecedores</h1>
                <p class="page-subtitle">Cadastre os fornecedores e mantenha controle do pagamento e relações de compra.</p>
            </div>
        </div>

        <?php if ($statusMessage): ?>
            <div class="toast toast-success" style="margin-bottom:20px;"><?= sanitizar($statusMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="toast toast-error" style="margin-bottom:20px;"><?= sanitizar($errorMessage) ?></div>
        <?php endif; ?>

        <div class="card" style="margin-bottom:24px;">
            <h2 class="card-title"><?= $editFornecedor ? 'Editar Fornecedor' : 'Novo Fornecedor' ?></h2>
            <form method="post" action="<?= raizUrl('/gerenciamento/fornecedores.php') ?>">
                <input type="hidden" name="id" value="<?= $editFornecedor['id'] ?? '' ?>">
                <div class="form-grid">
                    <div>
                        <label class="label">Nome *</label>
                        <input class="input" type="text" name="nome" value="<?= sanitizar($editFornecedor['nome'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label class="label">Contato</label>
                        <input class="input" type="text" name="contato" value="<?= sanitizar($editFornecedor['contato'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="label">Telefone</label>
                        <input class="input" type="text" name="telefone" value="<?= sanitizar($editFornecedor['telefone'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="label">E-mail</label>
                        <input class="input" type="email" name="email" value="<?= sanitizar($editFornecedor['email'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="label">Categoria</label>
                        <input class="input" type="text" name="categoria" value="<?= sanitizar($editFornecedor['categoria'] ?? '') ?>">
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label class="label">Observação</label>
                        <textarea class="input" name="observacao" rows="3"><?= sanitizar($editFornecedor['observacao'] ?? '') ?></textarea>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:16px;">
                    <?php if ($editFornecedor): ?>
                        <a class="btn-secondary" href="<?= raizUrl('/gerenciamento/fornecedores.php') ?>">Cancelar</a>
                    <?php endif; ?>
                    <button class="btn-primary" type="submit"><?= $editFornecedor ? 'Salvar alterações' : 'Cadastrar fornecedor' ?></button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 class="card-title">Lista de fornecedores</h2>
            <div class="table-header" style="grid-template-columns: 1.8fr 1fr 1fr 1fr 110px;">
                <div>Nome</div>
                <div>Contato</div>
                <div>Telefone</div>
                <div>Categoria</div>
                <div style="text-align:right;">Ações</div>
            </div>
            <?php foreach ($fornecedores as $fornecedor): ?>
                <div class="table-row" style="grid-template-columns: 1.8fr 1fr 1fr 1fr 110px;">
                    <div><?= sanitizar($fornecedor['nome']) ?></div>
                    <div><?= sanitizar($fornecedor['contato']) ?></div>
                    <div><?= sanitizar($fornecedor['telefone']) ?></div>
                    <div><?= sanitizar($fornecedor['categoria']) ?></div>
                    <div style="text-align:right;">
                        <a class="btn-link" href="<?= raizUrl('/gerenciamento/fornecedores.php?editar=' . $fornecedor['id']) ?>">Editar</a>
                        <a class="btn-link btn-link-danger" href="<?= raizUrl('/gerenciamento/fornecedores.php?deletar=' . $fornecedor['id']) ?>" onclick="return confirm('Excluir este fornecedor?');">Excluir</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
