<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';
checarPerfil(['admin']);
require_once __DIR__ . '/../includes/header.php';

$msg = '';
$erro = '';
$token = gerarCsrfToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
        $erro = 'Token de segurança inválido.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = (float)str_replace(',', '.', $_POST['preco_adicional'] ?? '0');
        $disponivel = isset($_POST['disponivel']) ? 1 : 0;
        if ($nome === '' || $preco < 0) {
            $erro = 'Informe um nome e um preço adicional válido.';
        } else {
            $duplicada = $pdo->prepare('SELECT COUNT(*) FROM tipos_massas WHERE LOWER(TRIM(nome)) = LOWER(TRIM(?)) AND id <> ?');
            $duplicada->execute([$nome, $id]);
            if ((int)$duplicada->fetchColumn() > 0) {
                $erro = 'Já existe um tipo de massa com esse nome.';
            } elseif ($id > 0) {
                $st = $pdo->prepare('UPDATE tipos_massas SET nome=?, descricao=?, preco_adicional=?, disponivel=? WHERE id=?');
                $st->execute([$nome, $descricao, $preco, $disponivel, $id]);
                $msg = 'Tipo de massa atualizado.';
            } else {
                $st = $pdo->prepare('INSERT INTO tipos_massas (nome, descricao, preco_adicional, disponivel) VALUES (?,?,?,?)');
                $st->execute([$nome, $descricao, $preco, $disponivel]);
                $msg = 'Tipo de massa cadastrado.';
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_massa'])) {
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) $erro = 'Token de segurança inválido.';
    else {
        $st = $pdo->prepare('DELETE FROM tipos_massas WHERE id=?');
        try { $st->execute([(int)$_POST['excluir_massa']]); $msg = 'Tipo de massa excluído.'; }
        catch (PDOException $e) { $erro = 'Não foi possível excluir: esta massa já está vinculada a pedidos.'; }
    }
}
$editar = null;
if (isset($_GET['editar'])) {
    $st=$pdo->prepare('SELECT * FROM tipos_massas WHERE id=?');
    $st->execute([(int)$_GET['editar']]); $editar=$st->fetch();
}
$lista=$pdo->query('SELECT * FROM tipos_massas ORDER BY nome')->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h2 class="fw-bold mb-1"><i class="bi bi-circle-half text-warning me-2"></i>Tipos de Massa</h2><p class="text-muted mb-0">Cadastre as massas disponíveis para montagem das pizzas.</p></div>
    <a href="dashboard.php" class="btn btn-outline-secondary">Voltar</a>
</div>
<?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
<?php if($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
<div class="row g-4">
<div class="col-lg-4"><div class="card border-0 shadow-sm"><div class="card-header bg-dark text-white fw-bold"><?= $editar ? 'Editar massa' : 'Nova massa' ?></div><div class="card-body">
<form method="post">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
<input type="hidden" name="id" value="<?= (int)($editar['id'] ?? 0) ?>">
<div class="mb-3"><label class="form-label fw-bold">Nome</label><input class="form-control" name="nome" required value="<?= htmlspecialchars($editar['nome'] ?? '') ?>" placeholder="Ex.: Massa tradicional"></div>
<div class="mb-3"><label class="form-label fw-bold">Descrição</label><textarea class="form-control" name="descricao" rows="2"><?= htmlspecialchars($editar['descricao'] ?? '') ?></textarea></div>
<div class="mb-3"><label class="form-label fw-bold">Preço adicional (R$)</label><input class="form-control" type="number" min="0" step="0.01" name="preco_adicional" value="<?= htmlspecialchars($editar['preco_adicional'] ?? '0.00') ?>" required></div>
<div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="disponivel" id="disponivel" <?= !isset($editar['disponivel']) || $editar['disponivel'] ? 'checked' : '' ?>><label class="form-check-label" for="disponivel">Disponível para pedidos</label></div>
<button class="btn btn-warning fw-bold w-100">Salvar tipo de massa</button>
<?php if($editar): ?><a href="massas.php" class="btn btn-link w-100">Cancelar edição</a><?php endif; ?>
</form></div></div></div>
<div class="col-lg-8"><div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-dark"><tr><th>Massa</th><th>Adicional</th><th>Status</th><th>Ações</th></tr></thead><tbody>
<?php foreach($lista as $m): ?><tr><td><strong><?= htmlspecialchars($m['nome']) ?></strong><div class="small text-muted"><?= htmlspecialchars($m['descricao'] ?? '') ?></div></td><td>R$ <?= number_format($m['preco_adicional'],2,',','.') ?></td><td><span class="badge bg-<?= $m['disponivel'] ? 'success' : 'secondary' ?>"><?= $m['disponivel'] ? 'Disponível' : 'Indisponível' ?></span></td><td><a class="btn btn-sm btn-outline-primary" href="massas.php?editar=<?= (int)$m['id'] ?>"><i class="bi bi-pencil"></i></a> <form method="POST" action="massas.php" class="d-inline" onsubmit="return confirm('Excluir este tipo de massa?')"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>"><button type="submit" name="excluir_massa" value="<?= (int)$m['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr><?php endforeach; ?>
<?php if(!$lista): ?><tr><td colspan="4" class="text-center text-muted p-4">Nenhuma massa cadastrada.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
