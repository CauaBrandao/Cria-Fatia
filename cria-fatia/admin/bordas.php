<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/conexao.php';
checarPerfil(['admin']);

$msg = '';
$erro = '';

// Exclusão Segura
if (isset($_GET['excluir'])) {
    $csrf = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';
    if (!validarCsrfToken($csrf)) {
        $erro = 'Ação inválida (CSRF).';
    } else {
        $idExcluir = (int)$_GET['excluir'];
        $stmt = $pdo->prepare("DELETE FROM bordas WHERE id = ?");
        $stmt->execute([$idExcluir]);
        $msg = "Borda excluída com sucesso!";
    }
}

// Cadastro / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_borda'])) {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validarCsrfToken($csrf)) {
        $erro = 'Requisição inválida (CSRF falhou).';
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nome = sanitizeInput($_POST['nome']);
        $preco = (float)$_POST['preco'];

        if (empty($nome) || $preco < 0) {
            $erro = "Preencha o nome da borda e um preço válido.";
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE bordas SET nome = ?, preco = ? WHERE id = ?");
                $stmt->execute([$nome, $preco, $id]);
                $msg = "Borda atualizada com sucesso!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO bordas (nome, preco) VALUES (?, ?)");
                $stmt->execute([$nome, $preco]);
                $msg = "Nova borda cadastrada com sucesso!";
            }
        }
    }
}

$bordaEditar = null;
if (isset($_GET['editar'])) {
    $idEdit = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM bordas WHERE id = ?");
    $stmt->execute([$idEdit]);
    $bordaEditar = $stmt->fetch();
}

$bordas = $pdo->query("SELECT * FROM bordas ORDER BY id DESC")->fetchAll();
$token = gerarCsrfToken();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0"><i class="bi bi-circle-square me-2 text-warning"></i>Gerenciar Bordas</h3>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Voltar ao Painel</a>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $erro; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold">
                <?php echo $bordaEditar ? 'Editar Borda' : 'Cadastrar Nova Borda'; ?>
            </div>
            <div class="card-body">
                <form action="bordas.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                    <input type="hidden" name="salvar_borda" value="1">
                    <?php if ($bordaEditar): ?>
                        <input type="hidden" name="id" value="<?php echo $bordaEditar['id']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome da Borda:</label>
                        <input type="text" name="nome" class="form-control" value="<?php echo $bordaEditar ? htmlspecialchars($bordaEditar['nome']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Preço Adicional (R$):</label>
                        <input type="number" step="0.01" name="preco" class="form-control" value="<?php echo $bordaEditar ? $bordaEditar['preco'] : ''; ?>" required>
                    </div>
                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2">
                        <?php echo $bordaEditar ? 'Atualizar Borda' : 'Salvar Borda'; ?>
                    </button>
                    <?php if ($bordaEditar): ?>
                        <a href="bordas.php" class="btn btn-link w-100 text-muted mt-2">Cancelar Edição</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="table-responsive bg-white rounded shadow-sm p-3 border">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Preço Adicional</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bordas as $b): ?>
                        <tr>
                            <td>#<?php echo $b['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($b['nome']); ?></strong></td>
                            <td class="fw-bold text-success">R$ <?php echo number_format($b['preco'], 2, ',', '.'); ?></td>
                            <td>
                                <a href="bordas.php?editar=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="bordas.php?excluir=<?php echo $b['id']; ?>&csrf_token=<?php echo $token; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir esta borda?');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>