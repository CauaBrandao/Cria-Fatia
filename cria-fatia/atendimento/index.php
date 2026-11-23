<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';
checarPerfil(['admin', 'atendimento']);
require_once __DIR__ . '/../includes/header.php';

$msg = '';
$erro = '';

/**
 * PROCESSAR AÇÕES DO ATENDIMENTO
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrf = $_POST['csrf_token'] ?? '';

    if (!validarCsrfToken($csrf)) {
        $erro = 'Ação não permitida. Token de segurança inválido.';

    } else {

        $pedidoId = (int)($_POST['pedido_id'] ?? 0);
        $acao = $_POST['acao'] ?? '';

        // Buscar pedido
        $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
        $stmt->execute([$pedidoId]);
        $pedido = $stmt->fetch();

        if (!$pedido) {
            $erro = 'Pedido não encontrado.';

        } else {

            /**
             * AÇÃO 1: ATUALIZAR STATUS
             */
            if ($acao === 'atualizar_status') {

                $novoStatus = $_POST['novo_status'] ?? '';

                $statusPermitidos = [
                    'pendente',
                    'em_producao',
                    'pronto',
                    'cancelado'
                ];

                if (!in_array($novoStatus, $statusPermitidos, true)) {
                    $erro = 'Status inválido.';

                } elseif (
                    $pedido['status'] === 'entregue' ||
                    $pedido['status'] === 'cancelado'
                ) {
                    $erro = 'Não é possível alterar o status deste pedido.';

                } else {

                    $statusAnterior = $pedido['status'];

                    $stmt = $pdo->prepare("
                        UPDATE pedidos
                        SET status = ?
                        WHERE id = ?
                    ");

                    $stmt->execute([$novoStatus, $pedidoId]);

                    registrarStatusPedido(
                        $pdo,
                        $pedidoId,
                        $statusAnterior,
                        $novoStatus,
                        'Status atualizado pelo atendimento.'
                    );

                    $msg = "Status do pedido #{$pedidoId} atualizado com sucesso!";
                }
            }

            /**
             * AÇÃO 2: CONFIRMAR RECEBIMENTO
             */
            elseif ($acao === 'confirmar_recebimento') {

                if ($pedido['status'] === 'entregue') {

                    $erro = 'Este pedido já foi marcado como entregue.';

                } elseif ($pedido['status'] === 'cancelado') {

                    $erro = 'Não é possível confirmar um pedido cancelado.';

                } elseif ($pedido['status'] !== 'pronto') {

                    $erro = 'O pedido precisa estar pronto antes da confirmação de recebimento.';

                } else {

                    $statusAnterior = $pedido['status'];

                    $stmt = $pdo->prepare("
                        UPDATE pedidos
                        SET status = 'entregue'
                        WHERE id = ?
                        AND status = 'pronto'
                    ");

                    $stmt->execute([$pedidoId]);

                    if ($stmt->rowCount() > 0) {

                        registrarStatusPedido(
                            $pdo,
                            $pedidoId,
                            $statusAnterior,
                            'entregue',
                            'Recebimento confirmado pelo atendimento.'
                        );

                        // Registrar venda no caixa aberto, se houver
                        try {
                            $caixaAberto = $pdo->query("SELECT id FROM caixas WHERE status = 'aberto' ORDER BY id DESC LIMIT 1")->fetch();
                            if ($caixaAberto) {
                                $stmtVenda = $pdo->prepare("INSERT INTO caixa_movimentacoes (caixa_id, pedido_id, usuario_id, tipo, origem, valor, justificativa) VALUES (?, ?, ?, 'venda', 'Pedido Entregue', ?, ?)");
                                $stmtVenda->execute([
                                    $caixaAberto['id'],
                                    $pedidoId,
                                    $_SESSION['usuario_id'] ?? 1,
                                    $pedido['valor_total'],
                                    "Venda do Pedido #{$pedidoId} - Cliente: {$pedido['cliente_nome']}"
                                ]);
                            }
                        } catch (Exception $e) {
                            // Silenciar erro de caixa para não bloquear fluxo
                        }

                        $msg = "Recebimento do pedido #{$pedidoId} confirmado! Pedido marcado como ENTREGUE.";

                    } else {
                        $erro = 'Não foi possível confirmar o recebimento. Atualize a página e tente novamente.';
                    }
                }
            }
        }
    }
}

// Listar pedidos
$pedidos = $pdo->query("
    SELECT *
    FROM pedidos
    ORDER BY id DESC
")->fetchAll();

$token = gerarCsrfToken();
?>

<?php
$contagem = ['todos' => count($pedidos), 'pendente' => 0, 'em_producao' => 0, 'pronto' => 0, 'entregue' => 0, 'cancelado' => 0];
foreach ($pedidos as $p) {
    $chave = $p['status'] === 'finalizado' ? 'entregue' : $p['status'];
    if (isset($contagem[$chave])) $contagem[$chave]++;
}
$coresStatus = [
    'pendente'    => 'bg-warning text-dark',
    'em_producao' => 'bg-info text-dark',
    'pronto'      => 'bg-primary',
    'entregue'    => 'bg-success',
    'finalizado'  => 'bg-success',
    'cancelado'   => 'bg-danger',
];
$nomesPagamento = ['pix' => 'Pix', 'cartao' => 'Cartão', 'dinheiro' => 'Dinheiro'];
?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">
        <i class="bi bi-headset me-2 text-warning"></i>
        Painel do Atendimento
    </h2>
    <a href="<?php echo BASE_URL; ?>atendimento/index.php" class="btn btn-outline-dark btn-sm" title="Atualizar agora">
        <i class="bi bi-arrow-clockwise me-1"></i> Atualiza em <span id="refreshTimer">25s</span>
    </a>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($msg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($erro); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm p-3 mb-3">
    <div class="row g-2 align-items-center">
        <div class="col-lg-4">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="filtroTexto" class="form-control" placeholder="Buscar cliente, telefone, nº ou código" oninput="filtrarTabela()">
            </div>
        </div>
        <div class="col-lg-8 d-flex flex-wrap gap-1 justify-content-lg-end">
            <?php
            $filtros = ['todos' => 'Todos', 'pendente' => 'Pendentes', 'em_producao' => 'Em produção', 'pronto' => 'Prontos', 'entregue' => 'Entregues', 'cancelado' => 'Cancelados'];
            foreach ($filtros as $valor => $rotulo): ?>
                <button type="button" class="btn btn-sm btn-filtro <?php echo $valor === 'todos' ? 'btn-dark' : 'btn-outline-dark'; ?>" onclick="filtrarStatus('<?php echo $valor; ?>', this)">
                    <?php echo $rotulo; ?> <span class="badge bg-secondary ms-1"><?php echo $contagem[$valor]; ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm p-0">
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="tabelaPedidos">
        <thead class="table-dark">
            <tr>
                <th>Pedido</th>
                <th>Cliente</th>
                <th>Entrega / Pagamento</th>
                <th>Total</th>
                <th>Status</th>
                <th class="text-end">Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($pedidos)): ?>
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">Nenhum pedido cadastrado no momento.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($pedidos as $p):
                $statusFiltro = $p['status'] === 'finalizado' ? 'entregue' : $p['status'];
                $telLimpo = preg_replace('/\D/', '', (string)$p['cliente_telefone']);
                $aberto = !in_array($p['status'], ['entregue', 'cancelado', 'finalizado'], true);
                // Próximo passo do pedido (um botão por etapa, sem repetir o status)
                $proximo = [
                    'pendente'    => ['em_producao', 'Enviar p/ cozinha', 'bi-fire', 'btn-warning'],
                    'em_producao' => ['pronto', 'Marcar pronto', 'bi-box-seam', 'btn-primary'],
                ][$p['status']] ?? null;
            ?>
                <tr class="pedido-linha" data-status="<?php echo htmlspecialchars($statusFiltro); ?>" data-search="<?php echo htmlspecialchars(mb_strtolower($p['id'] . ' ' . $p['cliente_nome'] . ' ' . $p['cliente_telefone'] . ' ' . $p['codigo_acompanhamento'], 'UTF-8')); ?>">
                    <td>
                        <strong>#<?php echo (int)$p['id']; ?></strong>
                        <div class="small text-muted font-monospace"><?php echo htmlspecialchars($p['codigo_acompanhamento'] ?? ''); ?></div>
                    </td>
                    <td>
                        <div class="fw-semibold"><?php echo htmlspecialchars($p['cliente_nome']); ?></div>
                        <div class="small text-muted">
                            <?php echo htmlspecialchars($p['cliente_telefone']); ?>
                            <?php if (strlen($telLimpo) >= 10):
                                $linkWhats = "https://wa.me/55" . $telLimpo . "?text=" . urlencode("Olá " . $p['cliente_nome'] . "! Aqui é da Bella Roza Pizzaria, sobre o seu pedido #" . $p['id'] . " (" . $p['codigo_acompanhamento'] . "). Status atual: " . rotuloStatus($p['status']) . ".");
                            ?>
                                <a href="<?php echo htmlspecialchars($linkWhats); ?>" target="_blank" rel="noopener" class="text-success ms-1" title="Falar com o cliente no WhatsApp"><i class="bi bi-whatsapp"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="small">
                        <div><i class="bi <?php echo $p['tipo_entrega'] === 'retirada' ? 'bi-shop' : 'bi-bicycle'; ?> me-1"></i><?php echo $p['tipo_entrega'] === 'retirada' ? 'Retirada' : 'Entrega'; ?></div>
                        <div class="text-muted"><i class="bi bi-wallet2 me-1"></i><?php echo $nomesPagamento[$p['forma_pagamento']] ?? 'Pix'; ?></div>
                    </td>
                    <td class="fw-bold text-success text-nowrap"><?php echo formatarMoeda($p['valor_total']); ?></td>
                    <td><span class="badge <?php echo $coresStatus[$p['status']] ?? 'bg-secondary'; ?>"><?php echo rotuloStatus($p['status']); ?></span></td>
                    <td>
                        <div class="d-flex align-items-center justify-content-end flex-wrap gap-1">
                            <?php if ($proximo): ?>
                                <form action="index.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                    <input type="hidden" name="pedido_id" value="<?php echo (int)$p['id']; ?>">
                                    <input type="hidden" name="acao" value="atualizar_status">
                                    <input type="hidden" name="novo_status" value="<?php echo $proximo[0]; ?>">
                                    <button type="submit" class="btn btn-sm <?php echo $proximo[3]; ?> fw-bold"><i class="bi <?php echo $proximo[2]; ?> me-1"></i><?php echo $proximo[1]; ?></button>
                                </form>
                            <?php elseif ($p['status'] === 'pronto'): ?>
                                <form action="index.php" method="POST" onsubmit="return confirm('Confirmar que o cliente recebeu o pedido #<?php echo (int)$p['id']; ?>?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                    <input type="hidden" name="pedido_id" value="<?php echo (int)$p['id']; ?>">
                                    <input type="hidden" name="acao" value="confirmar_recebimento">
                                    <button type="submit" class="btn btn-sm btn-success fw-bold"><i class="bi bi-check-circle-fill me-1"></i>Confirmar entrega</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($aberto): ?>
                                <form action="index.php" method="POST" onsubmit="return confirm('Cancelar o pedido #<?php echo (int)$p['id']; ?>? Essa ação não pode ser desfeita.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                                    <input type="hidden" name="pedido_id" value="<?php echo (int)$p['id']; ?>">
                                    <input type="hidden" name="acao" value="atualizar_status">
                                    <input type="hidden" name="novo_status" value="cancelado">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancelar pedido"><i class="bi bi-x-lg"></i></button>
                                </form>
                            <?php endif; ?>

                            <a href="<?php echo BASE_URL; ?>atendimento/imprimir.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-outline-dark" target="_blank" title="Imprimir comanda"><i class="bi bi-printer"></i></a>
                            <a href="<?php echo BASE_URL; ?>cliente/pedido.php?codigo=<?php echo urlencode($p['codigo_acompanhamento']); ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Ver página do pedido"><i class="bi bi-eye"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</div>

<script>
let statusFiltroAtual = 'todos';

function filtrarStatus(status, btn) {
    statusFiltroAtual = status;
    document.querySelectorAll('.btn-filtro').forEach(b => {
        b.classList.remove('btn-dark');
        b.classList.add('btn-outline-dark');
    });
    btn.classList.remove('btn-outline-dark');
    btn.classList.add('btn-dark');
    filtrarTabela();
}

function filtrarTabela() {
    const texto = (document.getElementById('filtroTexto')?.value || '').toLowerCase().trim();
    document.querySelectorAll('.pedido-linha').forEach(linha => {
        const matchStatus = statusFiltroAtual === 'todos' || linha.dataset.status === statusFiltroAtual;
        const matchTexto = texto === '' || (linha.dataset.search || '').includes(texto);
        linha.style.display = (matchStatus && matchTexto) ? '' : 'none';
    });
}

// Atualização automática (pausa enquanto alguém digita na busca)
let tempoRestante = 25;
const timerEl = document.getElementById('refreshTimer');
setInterval(() => {
    if (document.activeElement && document.activeElement.id === 'filtroTexto') return;
    tempoRestante--;
    if (timerEl) timerEl.textContent = tempoRestante + 's';
    if (tempoRestante <= 0) {
        // Recarrega via GET para não reenviar formulários
        window.location.href = 'index.php';
    }
}, 1000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
