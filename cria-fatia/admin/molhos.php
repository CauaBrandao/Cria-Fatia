<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/conexao.php';
checarPerfil(['admin']);

$msg = '';
$erro = '';

// Toggle disponibilidade
if (isset($_GET['toggle'])) {
    $csrf = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';
    if (!validarCsrfToken($csrf)) {
        $erro = 'Ação inválida (CSRF).';
    } else {
        $idToggle = (int)$_GET['toggle'];
        $stmt = $pdo->prepare("SELECT nome, disponivel FROM molhos WHERE id = ?");
        $stmt->execute([$idToggle]);
        $molho = $stmt->fetch();
        if ($molho) {
            $novoStatus = $molho['disponivel'] ? 0 : 1;
            $stmtUp = $pdo->prepare("UPDATE molhos SET disponivel = ? WHERE id = ?");
            $stmtUp->execute([$novoStatus, $idToggle]);
            registrarAuditoria($pdo, 'ALTERAR_DISPONIBILIDADE', 'molhos', $idToggle, $molho['disponivel'], $novoStatus, "Alterou disponibilidade do molho: " . $molho['nome']);
            $msg = "Disponibilidade do molho atualizada com sucesso!";
        }
    }
}

// Exclusão
if (isset($_GET['excluir'])) {
    $csrf = isset($_GET['csrf_token']) ? $_GET['csrf_token'] : '';
    if (!validarCsrfToken($csrf)) {
        $erro = 'Ação inválida (CSRF).';
    } else {
        $idExcluir = (int)$_GET['excluir'];
        $stmt = $pdo->prepare("SELECT nome FROM molhos WHERE id = ?");
        $stmt->execute([$idExcluir]);
        $molho = $stmt->fetch();
        if ($molho) {
            $stmtDel = $pdo->prepare("DELETE FROM molhos WHERE id = ?");
            $stmtDel->execute([$idExcluir]);
            registrarAuditoria($pdo, 'EXCLUIR', 'molhos', $idExcluir, $molho['nome'], null, "Excluiu o molho: " . $molho['nome']);
            $msg = "Molho excluído com sucesso!";
        }
    }
}

// Cadastro / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_molho'])) {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validarCsrfToken($csrf)) {
        $erro = 'Ação inválida (CSRF).';
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nome = sanitizeInput($_POST['nome']);
        $descricao = sanitizeInput($_POST['descricao']);
        $preco = str_replace(',', '.', $_POST['preco_adicional']);
        $disponivel = isset($_POST['disponivel']) ? 1 : 0;

        if (empty($nome)) {
            $erro = 'O nome do molho é obrigatório.';
        } else {
            if ($id > 0) {
                // Editar
                $stmtOld = $pdo->prepare("SELECT * FROM molhos WHERE id = ?");
                $stmtOld->execute([$id]);
                $oldData = $stmtOld->fetch();

                $stmt = $pdo->prepare("UPDATE molhos SET nome = ?, descricao = ?, preco_adicional = ?, disponivel = ? WHERE id = ?");
                $stmt->execute([$nome, $descricao, $preco, $disponivel, $id]);
                registrarAuditoria($pdo, 'ATUALIZAR', 'molhos', $id, $oldData, ['nome' => $nome, 'preco_adicional' => $preco], "Atualizou o molho: $nome");
                $msg = "Molho atualizado com sucesso!";
            } else {
                // Inserir
                $stmt = $pdo->prepare("INSERT INTO molhos (nome, descricao, preco_adicional, disponivel) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $descricao, $preco, $disponivel]);
                $novoId = $pdo->lastInsertId();
                registrarAuditoria($pdo, 'CADASTRAR', 'molhos', $novoId, null, $nome, "Cadastrou o novo molho: $nome");
                $msg = "Molho cadastrado com sucesso!";
            }
        }
    }
}

$molhos = $pdo->query("SELECT * FROM molhos ORDER BY nome")->fetchAll();
$csrfToken = gerarCsrfToken();
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold"><i class="bi bi-droplet-fill text-warning me-2"></i>Gerenciamento de Molhos</h2>
        <p class="text-muted">Cadastre, edite e controle a disponibilidade dos molhos para as pizzas.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <button class="btn btn-warning fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#modalMolho" onclick="limparModal()">
            <i class="bi bi-plus-circle me-1"></i> Novo Molho
        </button>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle me-2"></i><?php echo $msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-triangle me-2"></i><?php echo $erro; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow border-0 rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Descrição</th>
                        <th>Preço Adicional</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($molhos)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum molho cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach($molhos as $m): ?>
                            <tr>
                                <td>#<?php echo $m['id']; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($m['nome']); ?></td>
                                <td class="text-muted small"><?php echo htmlspecialchars($m['descricao']); ?></td>
                                <td>R$ <?php echo number_format($m['preco_adicional'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php if ($m['disponivel']): ?>
                                        <span class="badge bg-success">Disponível</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Indisponível</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="molhos.php?toggle=<?php echo $m['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" class="btn btn-sm btn-outline-<?php echo $m['disponivel'] ? 'warning' : 'success'; ?>" title="Alternar Disponibilidade">
                                        <i class="bi bi-power"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-primary" onclick='editarMolho(<?php echo json_encode($m); ?>)' title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="molhos.php?excluir=<?php echo $m['id']; ?>&csrf_token=<?php echo $csrfToken; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deseja realmente excluir este molho?');" title="Excluir">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Cadastro/Edição -->
<div class="modal fade" id="modalMolho" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="molhos.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="id" id="molhoId" value="0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="modalTitulo">Cadastrar Novo Molho</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do Molho:</label>
                        <input type="text" name="nome" id="molhoNome" class="form-control" required placeholder="Ex: Molho Barbecue">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição:</label>
                        <textarea name="descricao" id="molhoDescricao" class="form-control" rows="2" placeholder="Ex: Molho defumado especial"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Preço Adicional (R$):</label>
                        <input type="text" name="preco_adicional" id="molhoPreco" class="form-control" value="3,00" required>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="disponivel" id="molhoDisponivel" value="1" checked>
                        <label class="form-check-label fw-bold" for="molhoDisponivel">Produto Disponível para Pedidos</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="salvar_molho" class="btn btn-warning fw-bold text-dark px-4">Salvar Molho</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function limparModal() {
    document.getElementById('molhoId').value = '0';
    document.getElementById('molhoNome').value = '';
    document.getElementById('molhoDescricao').value = '';
    document.getElementById('molhoPreco').value = '3,00';
    document.getElementById('molhoDisponivel').checked = true;
    document.getElementById('modalTitulo').innerText = 'Cadastrar Novo Molho';
}

function editarMolho(m) {
    document.getElementById('molhoId').value = m.id;
    document.getElementById('molhoNome').value = m.nome;
    document.getElementById('molhoDescricao').value = m.descricao || '';
    document.getElementById('molhoPreco').value = parseFloat(m.preco_adicional).toFixed(2).replace('.', ',');
    document.getElementById('molhoDisponivel').checked = (m.disponivel == 1);
    document.getElementById('modalTitulo').innerText = 'Editar Molho';
    new bootstrap.Modal(document.getElementById('modalMolho')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
