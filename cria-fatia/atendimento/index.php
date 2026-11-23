<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/conexao.php';

checarPerfil(['admin', 'atendimento']);

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

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold">
        <i class="bi bi-headset me-2 text-warning"></i>
        Painel do Atendimento
    </h2>

    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-light text-dark border"><i class="bi bi-arrow-repeat me-1"></i> Auto-atualização: <span id="refreshTimer">25s</span></span>
        <a href="<?php echo BASE_URL; ?>atendimento/index.php"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-clockwise"></i>
            Atualizar Lista
        </a>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo htmlspecialchars($msg); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($erro); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm p-3 mb-3 bg-white rounded-3">
    <div class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="filtroTexto" class="form-control border-start-0" placeholder="Buscar por cliente, telefone ou ID..." onkeyup="filtrarTabela()">
            </div>
        </div>
        <div class="col-md-7 d-flex flex-wrap gap-1 justify-content-md-end">
            <button type="button" class="btn btn-sm btn-dark btn-filtro active" onclick="filtrarStatus('todos', this)">Todos</button>
            <button type="button" class="btn btn-sm btn-outline-warning text-dark btn-filtro" onclick="filtrarStatus('pendente', this)"><i class="bi bi-clock me-1"></i>Pendentes</button>
            <button type="button" class="btn btn-sm btn-outline-info text-dark btn-filtro" onclick="filtrarStatus('em_producao', this)"><i class="bi bi-fire me-1"></i>Em Produção</button>
            <button type="button" class="btn btn-sm btn-outline-primary btn-filtro" onclick="filtrarStatus('pronto', this)"><i class="bi bi-check me-1"></i>Prontos</button>
            <button type="button" class="btn btn-sm btn-outline-success btn-filtro" onclick="filtrarStatus('entregue', this)"><i class="bi bi-check-all me-1"></i>Entregues</button>
        </div>
    </div>
</div>

<div class="table-responsive bg-white rounded shadow-sm p-3 border">

    <table class="table table-hover align-middle mb-0" id="tabelaPedidos">

        <thead class="table-dark">
            <tr>
                <th>#ID</th>
                <th>Cliente</th>
                <th>Telefone</th>
                <th>Tipo</th>
                <th>Pagamento</th>
                <th>Total</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>

        <tbody>

        <?php if (empty($pedidos)): ?>

            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    Nenhum pedido cadastrado no momento.
                </td>
            </tr>

        <?php else: ?>

            <?php foreach ($pedidos as $p): ?>

                <?php
                    $cores = [
                        'pendente' => 'warning text-dark',
                        'em_producao' => 'info text-white',
                        'pronto' => 'primary',
                        'entregue' => 'success',
                        'finalizado' => 'success',
                        'cancelado' => 'danger'
                    ];

                    $corStatus = $cores[$p['status']] ?? 'secondary';
                ?>

                <tr class="pedido-linha" data-status="<?php echo htmlspecialchars($p['status']); ?>" data-search="<?php echo strtolower(htmlspecialchars($p['id'] . ' ' . $p['cliente_nome'] . ' ' . $p['cliente_telefone'] . ' ' . $p['codigo_acompanhamento'])); ?>">

                    <td>
                        <strong>#<?php echo $p['id']; ?></strong>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($p['cliente_nome']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($p['cliente_telefone']); ?>
                        <?php 
                        $telLimpo = preg_replace('/\D/', '', $p['cliente_telefone']);
                        if (strlen($telLimpo) >= 10): 
                            $linkWhats = "https://wa.me/55" . $telLimpo . "?text=" . urlencode("Olá " . $p['cliente_nome'] . "! Aqui é da Bella Roza Pizzaria referente ao seu pedido #" . $p['id'] . " (" . $p['codigo_acompanhamento'] . "). Status atual: " . strtoupper($p['status']));
                        ?>
                            <a href="<?php echo $linkWhats; ?>" target="_blank" class="btn btn-sm btn-outline-success ms-1 py-0 px-1" title="Notificar via WhatsApp">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                        <?php endif; ?>
                    </td>

                    <td>
                        <span class="badge bg-secondary">
                            <?php echo ucfirst($p['tipo_entrega']); ?>
                        </span>
                    </td>

                    <td>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-credit-card me-1 text-primary"></i><?php echo strtoupper($p['forma_pagamento'] ?? 'PIX'); ?>
                        </span>
                    </td>

                    <td class="fw-bold text-success">
                        R$ <?php echo number_format($p['valor_total'], 2, ',', '.'); ?>
                    </td>

                    <td>
                        <span class="badge bg-<?php echo $corStatus; ?>">
                            <?php echo strtoupper($p['status']); ?>
                        </span>
                    </td>

                    <td>

                        <div class="d-flex align-items-center flex-wrap gap-2">

                            <?php if (
                                !in_array($p['status'], ['entregue', 'cancelado', 'finalizado'], true)
                            ): ?>

                                <!-- BOTÃO 1: ATUALIZAR STATUS -->

                                <form action="index.php"
                                      method="POST"
                                      class="d-flex align-items-center gap-1">

                                    <input type="hidden"
                                           name="csrf_token"
                                           value="<?php echo $token; ?>">

                                    <input type="hidden"
                                           name="pedido_id"
                                           value="<?php echo $p['id']; ?>">

                                    <input type="hidden"
                                           name="acao"
                                           value="atualizar_status">

                                    <select name="novo_status"
                                            class="form-select form-select-sm"
                                            required>

                                        <option value="pendente"
                                            <?php echo $p['status'] === 'pendente' ? 'selected' : ''; ?>>
                                            Pendente
                                        </option>

                                        <option value="em_producao"
                                            <?php echo $p['status'] === 'em_producao' ? 'selected' : ''; ?>>
                                            Em Produção
                                        </option>

                                        <option value="pronto"
                                            <?php echo $p['status'] === 'pronto' ? 'selected' : ''; ?>>
                                            Pronto
                                        </option>

                                        <option value="cancelado">
                                            Cancelado
                                        </option>

                                    </select>

                                    <button type="submit" class="btn btn-sm btn-warning fw-bold text-dark" title="Salvar novo status do pedido"> <i class="bi bi-check-lg"></i> </button>

                                </form>

                                

                                <!-- BOTÃO 2: CONFIRMAR RECEBIMENTO -->

                                <?php if ($p['status'] === 'pronto'): ?>

                                    <form action="index.php"
                                          method="POST"
                                          onsubmit="return confirm('Confirma que o cliente recebeu o pedido #<?php echo $p['id']; ?>? Após confirmar, o pedido será marcado como ENTREGUE.');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?php echo $token; ?>">

                                        <input type="hidden"
                                               name="pedido_id"
                                               value="<?php echo $p['id']; ?>">

                                        <input type="hidden"
                                               name="acao"
                                               value="confirmar_recebimento">

                                        <button type="submit"
                                                class="btn btn-sm btn-success fw-bold">

                                            <i class="bi bi-check-circle-fill me-1"></i>
                                            Confirmar Recebimento

                                        </button>

                                    </form>

                                <?php endif; ?>

                            <?php elseif ($p['status'] === 'entregue'): ?>

                                <span class="badge bg-success p-2">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    Recebimento Confirmado
                                </span>

                            <?php elseif ($p['status'] === 'cancelado'): ?>

                                <span class="badge bg-danger p-2">
                                    Pedido Cancelado
                                </span>

                            <?php else: ?>

                                <span class="badge bg-success p-2">
                                    Pedido Finalizado
                                </span>

                            <?php endif; ?>

                            <!-- IMPRIMIR CUPOM / COMANDA -->
                            <a href="<?php echo BASE_URL; ?>atendimento/imprimir.php?id=<?php echo $p['id']; ?>"
                               class="btn btn-sm btn-outline-dark"
                               target="_blank"
                               title="Imprimir Comanda / Cupom">
                                <i class="bi bi-printer"></i>
                            </a>

                            <!-- VER DETALHES NO SITE -->
                            <a href="<?php echo BASE_URL; ?>cliente/pedido.php?codigo=<?php echo urlencode($p['codigo_acompanhamento']); ?>"
                               class="btn btn-sm btn-outline-secondary"
                               target="_blank"
                               title="Ver Página de Rastreamento">
                                <i class="bi bi-eye"></i>
                            </a>

                        </div>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>

<script>
let statusFiltroAtual = 'todos';

function filtrarStatus(status, btn) {
    statusFiltroAtual = status;
    document.querySelectorAll('.btn-filtro').forEach(b => {
        b.classList.remove('btn-dark');
        b.classList.add('btn-outline-dark');
    });
    btn.classList.remove('btn-outline-dark', 'btn-outline-warning', 'btn-outline-info', 'btn-outline-primary', 'btn-outline-success');
    btn.classList.add('btn-dark');
    filtrarTabela();
}

function filtrarTabela() {
    const texto = (document.getElementById('filtroTexto')?.value || '').toLowerCase().trim();
    const linhas = document.querySelectorAll('.pedido-linha');

    linhas.forEach(linha => {
        const status = linha.dataset.status;
        const search = linha.dataset.search || '';

        const matchStatus = (statusFiltroAtual === 'todos' || status === statusFiltroAtual);
        const matchTexto = (texto === '' || search.includes(texto));

        linha.style.display = (matchStatus && matchTexto) ? '' : 'none';
    });
}

let tempoRestante = 25;
const timerEl = document.getElementById('refreshTimer');
setInterval(() => {
    tempoRestante--;
    if (timerEl) timerEl.textContent = tempoRestante + 's';
    if (tempoRestante <= 0) {
        window.location.reload();
    }
}, 1000);
</script>
    }
}, 1000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
