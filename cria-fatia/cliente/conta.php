<?php
require_once __DIR__ . '/../config/conexao.php'; require_once __DIR__ . '/../includes/funcoes.php';
if (empty($_SESSION['cliente_id'])) { header('Location: entrar.php'); exit; }
$stmt = $pdo->prepare('SELECT id, codigo_acompanhamento, token_acesso, criado_em, valor_total, status, forma_pagamento FROM pedidos WHERE cliente_id = ? ORDER BY id DESC');
$stmt->execute([$_SESSION['cliente_id']]); $pedidos = $stmt->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="fw-bold mb-1">Olá, <?= escapar($_SESSION['cliente_nome']) ?></h2><p class="text-muted mb-0"><?= escapar($_SESSION['cliente_email']) ?></p></div><a class="btn btn-outline-danger" href="sair.php">Sair da conta</a></div>
<div class="card shadow-sm border-0"><div class="card-header bg-dark text-white fw-bold">Histórico de pedidos</div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Pedido</th><th>Data</th><th>Pagamento</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($pedidos as $p): ?><tr><td><?= escapar($p['codigo_acompanhamento']) ?></td><td><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td><td><?= strtoupper(escapar($p['forma_pagamento'])) ?></td><td><?= formatarMoeda($p['valor_total']) ?></td><td><span class="badge bg-secondary"><?= escapar(strtoupper($p['status'])) ?></span></td><td><a class="btn btn-sm btn-outline-dark" href="abrir_pedido.php?id=<?= (int)$p['id'] ?>">Ver</a></td></tr><?php endforeach; ?>
<?php if (!$pedidos): ?><tr><td colspan="6" class="text-center text-muted py-5">Você ainda não fez pedidos. Pedidos antigos como visitante continuam acessíveis pelo código.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

