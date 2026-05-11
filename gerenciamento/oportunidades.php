<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

exigirAutenticacao();
$db = Database::get();

$statusMessage = '';
$errorMessage = '';
$editOportunidade = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = sanitizar($_POST['nome'] ?? '');
    $cliente_id = sanitizar($_POST['cliente_id'] ?? '');
    $valor_estimado = floatval($_POST['valor_estimado'] ?? 0);
    $etapa = sanitizar($_POST['etapa'] ?? 'novo');
    $previsao = sanitizar($_POST['previsao'] ?? '');
    $responsavel = sanitizar($_POST['responsavel'] ?? '');
    $descricao = sanitizar($_POST['descricao'] ?? '');

    if (!$nome) {
        $errorMessage = 'O nome da oportunidade é obrigatório.';
    } else {
        if ($id) {
            $stmt = $db->prepare("UPDATE oportunidades SET nome = ?, cliente_id = ?, valor_estimado = ?, etapa = ?, previsao = ?, responsavel = ?, descricao = ? WHERE id = ?");
            $stmt->execute([$nome, $cliente_id ?: null, $valor_estimado, $etapa, $previsao ?: null, $responsavel, $descricao, $id]);
            $statusMessage = 'Oportunidade atualizada com sucesso.';
        } else {
            $stmt = $db->prepare("INSERT INTO oportunidades (id, cliente_id, nome, valor_estimado, etapa, previsao, responsavel, descricao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([gerarId(), $cliente_id ?: null, $nome, $valor_estimado, $etapa, $previsao ?: null, $responsavel, $descricao]);
            $statusMessage = 'Oportunidade criada com sucesso.';
        }
    }
}

if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM oportunidades WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $editOportunidade = $stmt->fetch();
}

if (isset($_GET['deletar'])) {
    $stmt = $db->prepare("DELETE FROM oportunidades WHERE id = ?");
    $stmt->execute([$_GET['deletar']]);
    $statusMessage = 'Oportunidade excluída com sucesso.';
}

$clientes = $db->query("SELECT id, nome FROM clientes ORDER BY nome ASC")->fetchAll();
$oportunidades = $db->query("SELECT o.*, c.nome AS cliente_nome FROM oportunidades o LEFT JOIN clientes c ON c.id = o.cliente_id ORDER BY CASE o.etapa WHEN 'novo' THEN 1 WHEN 'qualificado' THEN 2 WHEN 'proposta' THEN 3 WHEN 'negociacao' THEN 4 WHEN 'ganha' THEN 5 WHEN 'perdida' THEN 6 ELSE 7 END, o.previsao ASC")->fetchAll();
$tituloPagina = 'CRM • Oportunidades';
require_once __DIR__ . '/../includes/layout/head.php';
?>
<div id="app-wrapper">
    <?php require_once __DIR__ . '/../includes/layout/sidebar.php'; ?>
    <main id="main-content">
        <div class="app-topbar">
            <div>
                <h1 class="page-title">Oportunidades</h1>
                <p class="page-subtitle">Acompanhe o pipeline comercial e saiba quais negociações estão mais próximas de fechar.</p>
            </div>
        </div>

        <?php if ($statusMessage): ?>
            <div class="toast toast-success" style="margin-bottom:20px;"><?= sanitizar($statusMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="toast toast-error" style="margin-bottom:20px;"><?= sanitizar($errorMessage) ?></div>
        <?php endif; ?>

        <div class="card" style="margin-bottom:24px;">
            <h2 class="card-title"><?= $editOportunidade ? 'Editar oportunidade' : 'Nova oportunidade' ?></h2>
            <form method="post" action="<?= raizUrl('/gerenciamento/oportunidades.php') ?>">
                <input type="hidden" name="id" value="<?= $editOportunidade['id'] ?? '' ?>">
                <div class="form-grid">
                    <div>
                        <label class="label">Nome *</label>
                        <input class="input" type="text" name="nome" value="<?= sanitizar($editOportunidade['nome'] ?? '') ?>" required>
                    </div>
                    <div>
                        <label class="label">Cliente</label>
                        <select class="select" name="cliente_id">
                            <option value="">— Nenhum cliente —</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?= $cliente['id'] ?>" <?= isset($editOportunidade['cliente_id']) && $editOportunidade['cliente_id'] === $cliente['id'] ? 'selected' : '' ?>><?= sanitizar($cliente['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Valor estimado</label>
                        <input class="input" type="number" step="0.01" name="valor_estimado" value="<?= sanitizar($editOportunidade['valor_estimado'] ?? '0.00') ?>">
                    </div>
                    <div>
                        <label class="label">Etapa</label>
                        <select class="select" name="etapa">
                            <?php foreach (['novo'=>'Novo','qualificado'=>'Qualificado','proposta'=>'Proposta enviada','negociacao'=>'Negociação','ganha'=>'Ganha','perdida'=>'Perdida'] as $value => $label): ?>
                                <option value="<?= $value ?>" <?= (isset($editOportunidade['etapa']) && $editOportunidade['etapa'] === $value) ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="label">Previsão</label>
                        <input class="input" type="date" name="previsao" value="<?= sanitizar($editOportunidade['previsao'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="label">Responsável</label>
                        <input class="input" type="text" name="responsavel" value="<?= sanitizar($editOportunidade['responsavel'] ?? '') ?>">
                    </div>
                    <div style="grid-column:1 / -1;">
                        <label class="label">Descrição</label>
                        <textarea class="input" name="descricao" rows="3"><?= sanitizar($editOportunidade['descricao'] ?? '') ?></textarea>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:16px;">
                    <?php if ($editOportunidade): ?>
                        <a class="btn-secondary" href="<?= raizUrl('/gerenciamento/oportunidades.php') ?>">Cancelar</a>
                    <?php endif; ?>
                    <button class="btn-primary" type="submit"><?= $editOportunidade ? 'Salvar alterações' : 'Cadastrar oportunidade' ?></button>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 class="card-title">Pipeline de oportunidades</h2>
            <div class="table-header" style="grid-template-columns: 1.5fr 1fr 1fr 1fr 1fr 120px;">
                <div>Oportunidade</div>
                <div>Cliente</div>
                <div>Valor</div>
                <div>Etapa</div>
                <div>Previsão</div>
                <div style="text-align:right;">Ações</div>
            </div>
            <?php foreach ($oportunidades as $oportunidade): ?>
                <div class="table-row" style="grid-template-columns: 1.5fr 1fr 1fr 1fr 1fr 120px;">
                    <div><?= sanitizar($oportunidade['nome']) ?></div>
                    <div><?= sanitizar($oportunidade['cliente_nome'] ?? '—') ?></div>
                    <div><?= formatarMoeda((float)$oportunidade['valor_estimado']) ?></div>
                    <div><?= sanitizar(ucfirst($oportunidade['etapa'])) ?></div>
                    <div><?= formatarData($oportunidade['previsao']) ?></div>
                    <div style="text-align:right;">
                        <a class="btn-link" href="<?= raizUrl('/gerenciamento/oportunidades.php?editar=' . $oportunidade['id']) ?>">Editar</a>
                        <a class="btn-link btn-link-danger" href="<?= raizUrl('/gerenciamento/oportunidades.php?deletar=' . $oportunidade['id']) ?>" onclick="return confirm('Excluir esta oportunidade?');">Excluir</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>
