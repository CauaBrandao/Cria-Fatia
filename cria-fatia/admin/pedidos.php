<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';
checarPerfil(['admin']);
require_once __DIR__ . '/../includes/header.php';

$msg = '';
$erro = '';

// Atualizar status do pedido diretamente no admin/pedidos.php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedido_id'], $_POST['novo_status'])) {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validarCsrfToken($csrf)) {
        $erro = 'Ação não permitida (token CSRF inválido).';
    } else {
        $pedidoId = (int)$_POST['pedido_id'];
        $novoStatus = sanitizeInput($_POST['novo_status']);
        $statusPermitidos = ['pendente', 'em_producao', 'pronto', 'entregue', 'finalizado'];

        if (in_array($novoStatus, $statusPermitidos)) {
            $stmtOld = $pdo->prepare("SELECT status FROM pedidos WHERE id = ?");
            $stmtOld->execute([$pedidoId]);
            $statusAnterior = $stmtOld->fetchColumn();

            $stmt = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
            $stmt->execute([$novoStatus, $pedidoId]);

            registrarStatusPedido($pdo, $pedidoId, $statusAnterior, $novoStatus, 'Status alterado manualmente pelo administrador.');
            registrarAuditoria($pdo, 'ALTERAR_STATUS_PEDIDO', 'pedidos', $pedidoId, $statusAnterior, $novoStatus, "Admin alterou status do pedido #{$pedidoId} de {$statusAnterior} para {$novoStatus}");

            $msg = "Status do pedido #{$pedidoId} atualizado para " . strtoupper($novoStatus) . " com sucesso!";
        } else {
            $erro = "Status inválido selecionado.";
        }
    }
}

// Cancelamento de pedido somente via POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_pedido'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($csrf)) {
        $erro = 'Ação inválida (CSRF).';
    } else {
        $idCancel = (int)$_POST['cancelar_pedido'];
        $motivo = trim($_POST['motivo_cancelamento'] ?? '');
        if (mb_strlen($motivo) < 5) {
            $erro = 'Informe um motivo de cancelamento com pelo menos 5 caracteres.';
        } else {
        $stmtOld = $pdo->prepare("SELECT status FROM pedidos WHERE id = ?");
        $stmtOld->execute([$idCancel]);
        $statusAnterior = $stmtOld->fetchColumn();
        $stmt = $pdo->prepare("UPDATE pedidos SET status = 'cancelado' WHERE id = ? AND status <> 'cancelado'");
        $stmt->execute([$idCancel]);
        if ($stmt->rowCount() > 0) {
            $stmtCancel = $pdo->prepare('INSERT INTO cancelamentos_pedidos (pedido_id, usuario_id, status_original, motivo) VALUES (?, ?, ?, ?)');
            $stmtCancel->execute([$idCancel, $_SESSION['usuario_id'], $statusAnterior, $motivo]);
            registrarStatusPedido($pdo, $idCancel, $statusAnterior, 'cancelado', 'Cancelado pelo administrador. Motivo: ' . $motivo);
            registrarAuditoria($pdo, 'CANCELAR_PEDIDO', 'pedidos', $idCancel, $statusAnterior, 'cancelado', $motivo);
            $stmtLibera = $pdo->prepare("INSERT IGNORE INTO caixa_movimentacoes (caixa_id, pedido_id, usuario_id, tipo, origem, valor, justificativa) SELECT caixa_id, pedido_id, ?, 'liberacao_troco', 'Liberação de troco', valor, 'Pedido cancelado; sem estorno automático' FROM caixa_movimentacoes WHERE pedido_id = ? AND tipo = 'reserva_troco'");
            $stmtLibera->execute([$_SESSION['usuario_id'], $idCancel]);
            $msg = "Pedido #{$idCancel} cancelado.";
        } else {
            $erro = 'O pedido não existe ou não pode mais ser cancelado.';
        }
        }
    }
}

$pedidos = $pdo->query("SELECT * FROM pedidos ORDER BY id DESC")->fetchAll();
$token = gerarCsrfToken();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-warning"></i>Histórico Geral e Gerenciamento de Pedidos</h3>
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

<div class="table-responsive bg-white rounded shadow-sm p-3 border">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
            <tr>
                <th>#ID</th>
                <th>Data / Hora</th>
                <th>Cliente</th>
                <th>Telefone</th>
                <th>Tipo</th>
                <th>Pagamento</th>
                <th>Total</th>
                <th>Status Atual</th>
                <th>Alterar Status / Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($pedidos as $p): ?>
                <tr>
                    <td><strong>#<?php echo $p['id']; ?></strong></td>
                    <td class="small"><?php echo date('d/m/Y H:i', strtotime($p['criado_em'])); ?></td>
                    <td><?php echo htmlspecialchars($p['cliente_nome']); ?></td>
                    <td><?php echo htmlspecialchars($p['cliente_telefone']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo ucfirst($p['tipo_entrega']); ?></span></td>
                    <td><span class="badge bg-light text-dark border"><?php echo strtoupper($p['forma_pagamento'] ?? 'PIX'); ?></span></td>
                    <td class="fw-bold text-success">R$ <?php echo number_format($p['valor_total'], 2, ',', '.'); ?></td>
                    <td>
                        <span class="badge bg-<?php 
                            echo $p['status'] === 'pendente' ? 'warning text-dark' : ($p['status'] === 'em_producao' ? 'info text-white' : ($p['status'] === 'pronto' ? 'primary' : ($p['status'] === 'entregue' ? 'success' : ($p['status'] === 'cancelado' ? 'danger' : 'success'))));
                        ?>">
                            <?php echo strtoupper($p['status']); ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-1">
                            <!-- Formulário para alterar status diretamente para qualquer valor -->
                            <form action="pedidos.php" method="POST" class="d-flex align-items-center gap-1 mb-0">
                                <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                <input type="hidden" name="pedido_id" value="<?php echo $p['id']; ?>">
                                <select name="novo_status" class="form-select form-select-sm" style="width: 130px;" required>
                                    <option value="pendente" <?php echo $p['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="em_producao" <?php echo $p['status'] === 'em_producao' ? 'selected' : ''; ?>>Em Produção</option>
                                    <option value="pronto" <?php echo $p['status'] === 'pronto' ? 'selected' : ''; ?>>Pronto</option>
                                    <option value="entregue" <?php echo $p['status'] === 'entregue' ? 'selected' : ''; ?>>Entregue</option>
                                    <option value="finalizado" <?php echo $p['status'] === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-warning fw-bold text-dark" title="Salvar Status"><i class="bi bi-check-lg"></i></button>
                            </form>
                            <a href="<?php echo BASE_URL; ?>atendimento/imprimir.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-dark" target="_blank" title="Imprimir Cupom"><i class="bi bi-printer"></i></a>
                            <a href="<?php echo BASE_URL; ?>cliente/pedido.php?codigo=<?php echo urlencode($p['codigo_acompanhamento']); ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Ver no Site"><i class="bi bi-eye"></i></a>
                            <?php if ($p['status'] !== 'cancelado'): ?>
                                <form method="POST" action="pedidos.php" class="d-flex gap-1" onsubmit="return confirm('Confirma o cancelamento? O pagamento não será estornado automaticamente.');"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>"><input type="text" name="motivo_cancelamento" class="form-control form-control-sm" style="width:150px" minlength="5" required placeholder="Motivo obrigatório"><button type="submit" name="cancelar_pedido" value="<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-outline-danger" title="Cancelar sem estorno automático"><i class="bi bi-x-circle"></i></button></form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
