<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';
checarPerfil(['admin']);
require_once __DIR__ . '/../includes/header.php';

$msg = '';
$erro = '';
$token = gerarCsrfToken();

// Ações administrativas que alteram dados são aceitas somente via POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_ingrediente'])) {
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
        $erro = 'Ação inválida (CSRF).';
    } else {
        $id = (int)($_POST['ingrediente_id'] ?? 0);
        $acao = $_POST['acao_ingrediente'];
        $stmt = $pdo->prepare("SELECT nome, disponivel FROM ingredientes WHERE id = ?");
        $stmt->execute([$id]);
        $ing = $stmt->fetch();
        if (!$ing) {
            $erro = 'Ingrediente não encontrado.';
        } elseif ($acao === 'toggle') {
            $novoStatus = (int)!$ing['disponivel'];
            $pdo->prepare("UPDATE ingredientes SET disponivel = ? WHERE id = ?")->execute([$novoStatus, $id]);
            registrarAuditoria($pdo, 'ALTERAR_DISPONIBILIDADE', 'ingredientes', $id, $ing['disponivel'], $novoStatus, "Alterou disponibilidade do ingrediente: " . $ing['nome']);
            $msg = 'Disponibilidade do ingrediente atualizada com sucesso!';
        } elseif ($acao === 'excluir') {
            try {
                $pdo->prepare("DELETE FROM ingredientes WHERE id = ?")->execute([$id]);
                registrarAuditoria($pdo, 'EXCLUIR', 'ingredientes', $id, $ing['nome'], null, "Excluiu o ingrediente: " . $ing['nome']);
                $msg = 'Ingrediente excluído com sucesso!';
            } catch (PDOException $e) {
                $erro = 'Não foi possível excluir: este ingrediente está vinculado a pedidos existentes. Desative a disponibilidade.';
            }
        } else {
            $erro = 'Ação administrativa inválida.';
        }
    }
}

// Cadastro / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_ingrediente'])) {
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
        $erro = 'Token de segurança inválido.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = (float)str_replace(',', '.', $_POST['preco_adicional'] ?? '0');
        $disponivel = isset($_POST['disponivel']) ? 1 : 0;
        $tipoItem = ($_POST['tipo_item'] ?? '') === 'acompanhamento' ? 'acompanhamento' : 'ingrediente';

        if ($nome === '' || $preco < 0) {
            $erro = 'Informe um nome válido e um preço não negativo.';
        } else {
            if ($id > 0) {
                $old = $pdo->prepare("SELECT * FROM ingredientes WHERE id = ?");
                $old->execute([$id]);
                $oldData = $old->fetch();

                $stmt = $pdo->prepare("UPDATE ingredientes SET nome = ?, categoria = ?, descricao = ?, preco_adicional = ?, disponivel = ?, tipo_item = ? WHERE id = ?");
                $stmt->execute([$nome, $categoria ?: null, $descricao ?: null, $preco, $disponivel, $tipoItem, $id]);
                registrarAuditoria($pdo, 'ATUALIZAR', 'ingredientes', $id, $oldData, ['nome' => $nome, 'preco_adicional' => $preco], "Atualizou ingrediente: $nome");
                $msg = 'Ingrediente atualizado com sucesso!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO ingredientes (nome, categoria, descricao, preco_adicional, disponivel, tipo_item) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $categoria ?: null, $descricao ?: null, $preco, $disponivel, $tipoItem]);
                $novoId = (int)$pdo->lastInsertId();
                registrarAuditoria($pdo, 'CADASTRAR', 'ingredientes', $novoId, null, $nome, "Cadastrou novo ingrediente: $nome");
                $msg = 'Ingrediente cadastrado com sucesso!';
            }
        }
    }
}

$editar = null;
if (isset($_GET['editar'])) {
    $st = $pdo->prepare("SELECT * FROM ingredientes WHERE id = ?");
    $st->execute([(int)$_GET['editar']]);
    $editar = $st->fetch();
}

$ingredientes = $pdo->query("SELECT * FROM ingredientes ORDER BY categoria ASC, nome ASC")->fetchAll();
$categoriasExistentes = $pdo->query("SELECT DISTINCT categoria FROM ingredientes WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-egg-fried text-warning me-2"></i>Gerenciar Ingredientes</h2>
        <p class="text-muted mb-0">Controle os ingredientes disponíveis para os clientes montarem suas pizzas.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Painel</a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($erro) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Formulário Cadastro/Edição -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-dark text-white fw-bold py-3">
                <i class="bi bi-pencil-square me-2 text-warning"></i><?= $editar ? 'Editar Ingrediente' : 'Novo Ingrediente' ?>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="ingredientes.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                    <input type="hidden" name="salvar_ingrediente" value="1">
                    <input type="hidden" name="id" value="<?= (int)($editar['id'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do Ingrediente *</label>
                        <input type="text" name="nome" class="form-control" required
                               placeholder="Ex: Bacon em cubos" value="<?= htmlspecialchars($editar['nome'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de item *</label>
                        <select name="tipo_item" class="form-select" required>
                            <option value="ingrediente" <?= ($editar['tipo_item'] ?? 'ingrediente') === 'ingrediente' ? 'selected' : '' ?>>Ingrediente principal</option>
                            <option value="acompanhamento" <?= ($editar['tipo_item'] ?? '') === 'acompanhamento' ? 'selected' : '' ?>>Acompanhamento</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Categoria</label>
                        <input type="text" name="categoria" class="form-control" list="listaCategorias"
                               placeholder="Ex: Queijos, Carnes, Vegetais" value="<?= htmlspecialchars($editar['categoria'] ?? '') ?>">
                        <datalist id="listaCategorias">
                            <?php foreach ($categoriasExistentes as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>">
                            <?php endforeach; ?>
                            <option value="Queijos">
                            <option value="Carnes">
                            <option value="Vegetais">
                            <option value="Temperos">
                            <option value="Doces">
                        </datalist>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Preço Adicional (R$) *</label>
                        <input type="number" step="0.01" min="0" name="preco_adicional" class="form-control" required
                               value="<?= htmlspecialchars($editar['preco_adicional'] ?? '0.00') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição</label>
                        <textarea name="descricao" class="form-control" rows="2"
                                  placeholder="Detalhes ou marca do ingrediente"><?= htmlspecialchars($editar['descricao'] ?? '') ?></textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="disponivel" id="disponivel"
                               <?= !isset($editar['disponivel']) || $editar['disponivel'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="disponivel">Disponível no Montador</label>
                    </div>

                    <button type="submit" class="btn btn-warning fw-bold w-100 py-2">
                        <i class="bi bi-check-lg me-1"></i> <?= $editar ? 'Salvar Alterações' : 'Cadastrar Ingrediente' ?>
                    </button>
                    <?php if ($editar): ?>
                        <a href="ingredientes.php" class="btn btn-link text-muted w-100 mt-2">Cancelar Edição</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabela de Listagem -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold fs-5">Ingredientes Cadastrados</span>
                <span class="badge bg-warning text-dark"><?= count($ingredientes) ?> itens</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Ingrediente</th>
                            <th>Tipo / Categoria</th>
                            <th>Preço Adic.</th>
                            <th>Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingredientes as $ing): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($ing['nome']) ?></strong>
                                    <?php if (!empty($ing['descricao'])): ?>
                                        <div class="small text-muted"><?= htmlspecialchars($ing['descricao']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= ($ing['tipo_item'] ?? 'ingrediente') === 'acompanhamento' ? 'info' : 'warning' ?> text-dark mb-1"><?= ($ing['tipo_item'] ?? 'ingrediente') === 'acompanhamento' ? 'Acompanhamento' : 'Ingrediente' ?></span><br>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($ing['categoria'] ?: 'Geral') ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-success">
                                    + R$ <?= number_format($ing['preco_adicional'], 2, ',', '.') ?>
                                </td>
                                <td>
                                    <form method="POST" action="ingredientes.php" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                                        <input type="hidden" name="acao_ingrediente" value="toggle">
                                        <input type="hidden" name="ingrediente_id" value="<?= (int)$ing['id'] ?>">
                                        <button type="submit" class="badge border-0 text-decoration-none bg-<?= $ing['disponivel'] ? 'success' : 'secondary' ?>">
                                        <?= $ing['disponivel'] ? 'Disponível' : 'Indisponível' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <a href="ingredientes.php?editar=<?= (int)$ing['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="ingredientes.php" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este ingrediente?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token) ?>">
                                        <input type="hidden" name="acao_ingrediente" value="excluir">
                                        <input type="hidden" name="ingrediente_id" value="<?= (int)$ing['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ingredientes)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Nenhum ingrediente cadastrado.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
