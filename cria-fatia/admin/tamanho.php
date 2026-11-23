<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';
checarPerfil(['admin']);
require_once __DIR__ . '/../includes/header.php';

$msg = '';
$erro = '';
$csrfToken = gerarCsrfToken();

/**
 * Gerenciamento de tamanhos.
 * A tabela utilizada pelo restante do projeto é "tamanhos" (plural).
 * O preço do tamanho é "preco_base", consumido em cliente/montar.php.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_tamanho'])) {
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
        $erro = 'Ação inválida (CSRF).';
    } else {
        $id = (int) ($_POST['tamanho_id'] ?? 0);
        $acao = $_POST['acao_tamanho'];
        $stmt = $pdo->prepare("SELECT nome, disponivel FROM tamanhos WHERE id = ?");
        $stmt->execute([$id]);
        $tamanho = $stmt->fetch();

        if (!$tamanho) {
            $erro = 'Tamanho não encontrado.';
        } elseif ($acao === 'toggle') {
            $novoStatus = (int) !$tamanho['disponivel'];
            $update = $pdo->prepare("UPDATE tamanhos SET disponivel = ? WHERE id = ?");
            $update->execute([$novoStatus, $id]);

            registrarAuditoria(
                $pdo,
                'ALTERAR_DISPONIBILIDADE',
                'tamanhos',
                $id,
                (int) $tamanho['disponivel'],
                $novoStatus,
                'Alterou a disponibilidade do tamanho: ' . $tamanho['nome']
            );
            $msg = 'Disponibilidade do tamanho atualizada com sucesso!';
        } elseif ($acao === 'excluir') {
            try {
                $delete = $pdo->prepare("DELETE FROM tamanhos WHERE id = ?");
                $delete->execute([$id]);
                registrarAuditoria(
                    $pdo,
                    'EXCLUIR',
                    'tamanhos',
                    $id,
                    $tamanho['nome'],
                    null,
                    'Excluiu o tamanho: ' . $tamanho['nome']
                );
                $msg = 'Tamanho excluído com sucesso!';
            } catch (PDOException $e) {
                // Evita apagar um tamanho que já esteja associado a pedidos.
                $erro = 'Não foi possível excluir: este tamanho já está vinculado a pedidos. Desative sua disponibilidade.';
            }
        } else {
            $erro = 'Ação administrativa inválida.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_tamanho'])) {
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
        $erro = 'Ação inválida (CSRF).';
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $maxSabores = (int) ($_POST['max_sabores'] ?? 1);
        $quantidadeFatias = (int) ($_POST['quantidade_fatias'] ?? 0);
        $precoBase = (float) str_replace(',', '.', $_POST['preco_base'] ?? '0');
        $disponivel = isset($_POST['disponivel']) ? 1 : 0;

        if ($nome === '') {
            $erro = 'O nome do tamanho é obrigatório.';
        } elseif ($maxSabores < 1) {
            $erro = 'A quantidade máxima de sabores deve ser pelo menos 1.';
        } elseif ($quantidadeFatias < 0) {
            $erro = 'A quantidade de fatias não pode ser negativa.';
        } elseif ($precoBase < 0) {
            $erro = 'O preço-base não pode ser negativo.';
        } else {
            if ($id > 0) {
                $oldStmt = $pdo->prepare("SELECT * FROM tamanhos WHERE id = ?");
                $oldStmt->execute([$id]);
                $oldData = $oldStmt->fetch();

                if (!$oldData) {
                    $erro = 'Tamanho não encontrado para edição.';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE tamanhos
                        SET nome = ?, descricao = ?, max_sabores = ?, quantidade_fatias = ?, preco_base = ?, disponivel = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$nome, $descricao, $maxSabores, $quantidadeFatias ?: null, $precoBase, $disponivel, $id]);

                    registrarAuditoria(
                        $pdo,
                        'ATUALIZAR',
                        'tamanhos',
                        $id,
                        $oldData,
                        [
                            'nome' => $nome,
                            'descricao' => $descricao,
                            'max_sabores' => $maxSabores,
                            'preco_base' => $precoBase,
                            'disponivel' => $disponivel
                        ],
                        'Atualizou o tamanho: ' . $nome
                    );
                    $msg = 'Tamanho atualizado com sucesso!';
                }
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO tamanhos (nome, descricao, max_sabores, quantidade_fatias, preco_base, disponivel)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nome, $descricao, $maxSabores, $quantidadeFatias ?: null, $precoBase, $disponivel]);
                $novoId = (int) $pdo->lastInsertId();

                registrarAuditoria(
                    $pdo,
                    'CADASTRAR',
                    'tamanhos',
                    $novoId,
                    null,
                    [
                        'nome' => $nome,
                        'descricao' => $descricao,
                        'max_sabores' => $maxSabores,
                        'preco_base' => $precoBase,
                        'disponivel' => $disponivel
                    ],
                    'Cadastrou o tamanho: ' . $nome
                );
                $msg = 'Tamanho cadastrado com sucesso!';
            }
        }
    }
}

$editar = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM tamanhos WHERE id = ?");
    $stmt->execute([(int) $_GET['editar']]);
    $editar = $stmt->fetch();
}

$tamanhos = $pdo->query("SELECT * FROM tamanhos ORDER BY nome ASC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="bi bi-circle-fill text-warning me-2"></i>Gerenciamento de Tamanhos</h2>
        <p class="text-muted mb-0">Cadastre, edite e controle os tamanhos disponíveis para as pizzas.</p>
    </div>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Voltar ao Painel
    </a>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold">
                <?= $editar ? 'Editar Tamanho' : 'Cadastrar Novo Tamanho' ?>
            </div>
            <div class="card-body">
                <form action="tamanho.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="salvar_tamanho" value="1">
                    <input type="hidden" name="id" value="<?= (int) ($editar['id'] ?? 0) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome do tamanho:</label>
                        <input type="text" name="nome" class="form-control" required maxlength="100"
                               value="<?= htmlspecialchars($editar['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Ex.: Média, Grande ou Família">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição:</label>
                        <textarea name="descricao" class="form-control" rows="2"
                                  placeholder="Descreva o tamanho da pizza"><?= htmlspecialchars($editar['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantidade máxima de sabores:</label>
                        <input type="number" name="max_sabores" class="form-control" min="1" step="1" required
                               value="<?= (int) ($editar['max_sabores'] ?? 1) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Preço-base (R$):</label>
                        <input type="number" name="preco_base" class="form-control" min="0" step="0.01" required
                               value="<?= htmlspecialchars((string) ($editar['preco_base'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="disponivel" id="tamanhoDisponivel" value="1"
                            <?= !isset($editar['disponivel']) || (int) $editar['disponivel'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="tamanhoDisponivel">Disponível para pedidos</label>
                    </div>

                    <button type="submit" class="btn btn-warning fw-bold w-100">
                        <i class="bi bi-check-circle me-1"></i><?= $editar ? 'Atualizar Tamanho' : 'Salvar Tamanho' ?>
                    </button>

                    <?php if ($editar): ?>
                        <a href="tamanho.php" class="btn btn-link w-100 text-muted mt-2">Cancelar edição</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Tamanho</th>
                                <th>Sabores</th>
                                <th>Preço-base</th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$tamanhos): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Nenhum tamanho cadastrado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($tamanhos as $t): ?>
                                <tr>
                                    <td>#<?= (int) $t['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($t['nome'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php if (!empty($t['descricao'])): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($t['descricao'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $t['quantidade_fatias'] !== null ? (int) $t['quantidade_fatias'] : '—' ?></td>
                                    <td><?= (int) $t['max_sabores'] ?></td>
                                    <td class="fw-bold text-success">R$ <?= number_format((float) $t['preco_base'], 2, ',', '.') ?></td>
                                    <td>
                                        <?php if ((int) $t['disponivel'] === 1): ?>
                                            <span class="badge bg-success">Disponível</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Indisponível</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <form method="POST" action="tamanho.php" class="d-inline"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="acao_tamanho" value="toggle"><input type="hidden" name="tamanho_id" value="<?= (int)$t['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-<?= $t['disponivel'] ? 'warning' : 'success' ?>" title="Alternar disponibilidade"><i class="bi bi-power"></i></button></form>
                                        <a href="tamanho.php?editar=<?= (int) $t['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="tamanho.php" class="d-inline" onsubmit="return confirm('Deseja realmente excluir este tamanho?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="acao_tamanho" value="excluir"><input type="hidden" name="tamanho_id" value="<?= (int)$t['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="bi bi-trash"></i></button></form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
