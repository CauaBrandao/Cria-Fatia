<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';
checarPerfil(['admin', 'cozinha']);
require_once __DIR__ . '/../includes/header.php';

$msg = '';
$erro = '';

// Concluir Produção com CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['concluir_id'])) {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validarCsrfToken($csrf)) {
        $erro = 'Ação não permitida (token CSRF inválido).';
    } else {
        $concluirId = (int)$_POST['concluir_id'];
        $stmt = $pdo->prepare("UPDATE pedidos SET status = 'pronto' WHERE id = ? AND status IN ('pendente','em_producao')");
        $stmt->execute([$concluirId]);
        if ($stmt->rowCount() > 0) {
            registrarStatusPedido($pdo, $concluirId, 'em_producao', 'pronto', 'Produção concluída pela cozinha.');
            $msg = "Pedido #{$concluirId} marcado como PRONTO!";
        } else {
            $erro = 'Pedido não encontrado ou já atualizado.';
        }
    }
}

// Buscar Pedidos Ativos na Cozinha (em_producao ou pendente)
$pedidos = $pdo->query("
    SELECT p.*, pi.id as item_id, pi.divisao, t.nome as tamanho_nome, b.nome as borda_nome, tm.nome AS massa_nome, s1.nome as s1_nome, s2.nome as s2_nome
    FROM pedidos p
    JOIN pedido_itens pi ON p.id = pi.pedido_id
    JOIN tamanhos t ON pi.tamanho_id = t.id
    JOIN bordas b ON pi.borda_id = b.id
    LEFT JOIN tipos_massas tm ON pi.massa_id = tm.id
    LEFT JOIN sabores s1 ON pi.sabor1_id = s1.id
    LEFT JOIN sabores s2 ON pi.sabor2_id = s2.id
    WHERE p.status IN ('pendente', 'em_producao')
    ORDER BY p.criado_em DESC, p.id DESC
")->fetchAll();

$token = gerarCsrfToken();
?>

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0"><i class="bi bi-fire me-2 text-danger"></i>Painel da Cozinha <small class="fs-6 text-muted fw-semibold">· <?php echo count(array_unique(array_column($pedidos, 'id'))); ?> pedido(s) na fila</small></h2>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-sm btn-outline-dark" onclick="tocarAlerta()" title="Testar o alerta sonoro"><i class="bi bi-bell-fill"></i></button>
        <a href="index.php" class="btn btn-outline-dark btn-sm" title="Atualizar agora"><i class="bi bi-arrow-repeat me-1"></i> Atualiza em <span id="kdsTimer">20s</span></a>
    </div>
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
    <?php if (empty($pedidos)): ?>
        <div class="col-12">
            <div class="alert alert-success text-center py-5 shadow-sm border-0 fs-5">
                <i class="bi bi-emoji-smile display-4 d-block mb-3 text-success"></i> Sem pedidos pendentes no momento! Nenhuma pizza em fila de produção.
            </div>
        </div>
    <?php else: ?>
        <?php foreach($pedidos as $p): 
            $stmtCust = $pdo->prepare("
                SELECT pic.*, i.nome as ingrediente_nome 
                FROM pedido_item_customizacoes pic
                JOIN ingredientes i ON pic.ingrediente_id = i.id
                WHERE pic.pedido_item_id = ?
            ");
            $stmtCust->execute([$p['item_id']]);
            $customs = $stmtCust->fetchAll();
            $stmtSauces = $pdo->prepare("SELECT m.nome FROM pedido_item_molhos pim JOIN molhos m ON m.id = pim.molho_id WHERE pim.pedido_item_id = ? ORDER BY m.nome");
            $stmtSauces->execute([$p['item_id']]);
            $molhosPedido = $stmtSauces->fetchAll();
        ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card ticket-kds shadow border-0 h-100">
                    <div class="card-header kds-header d-flex justify-content-between align-items-center py-3">
                        <span class="fs-5 fw-bold">Pedido #<?php echo $p['id']; ?></span>
                        <span class="badge <?php echo $p['status'] === 'em_producao' ? 'bg-info text-dark' : 'bg-warning text-dark'; ?>"><?php echo rotuloStatus($p['status']); ?></span>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between gap-2 align-items-start">
                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($p['tamanho_nome']); ?><?php echo $p['divisao'] === 'meio_a_meio' ? ' · Meio a meio' : ''; ?></h5>
                            <small class="text-muted text-nowrap"><?php echo date('d/m H:i', strtotime($p['criado_em'])); ?></small>
                        </div>
                        <p class="mb-2"><strong>Cliente:</strong> <?php echo htmlspecialchars($p['cliente_nome']); ?> · <?php echo htmlspecialchars($p['cliente_telefone']); ?></p>
                        <p class="mb-1"><strong>Massa:</strong> <?php echo htmlspecialchars($p['massa_nome'] ?: 'Não informada'); ?></p>
                        <p class="mb-1"><strong>Borda:</strong> <?php echo htmlspecialchars($p['borda_nome']); ?></p>
                        <hr>
                        <!-- Detalhamento por Metade -->
                        <div class="mb-3">
                            <strong class="text-dark d-block mb-1">Ingredientes da pizza:</strong>
                            <?php foreach($customs as $c): if ($c['metade'] == 1): ?>
                                <?php if ($c['acao'] === 'remover'): ?>
                                    <span class="badge badge-remover me-1 mb-1">SEM <?php echo htmlspecialchars($c['ingrediente_nome']); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-adicionar me-1 mb-1"><?php echo htmlspecialchars($c['ingrediente_nome']); ?></span>
                                <?php endif; ?>
                            <?php endif; endforeach; ?>
                            <?php if (empty($customs)): ?><div class="text-muted small">Nenhum ingrediente informado para esta pizza.</div><?php endif; ?>
                        </div>
                        <?php if (!empty($molhosPedido)): ?>
                            <div class="mb-3"><strong class="d-block mb-1">Molhos:</strong><?php echo htmlspecialchars(implode(', ', array_column($molhosPedido, 'nome'))); ?></div>
                        <?php endif; ?>

                        <?php if ($p['divisao'] === 'meio_a_meio' && !empty($p['s2_nome'])): ?>
                            <div class="mb-3">
                                <strong class="text-dark d-block mb-1">Metade 2: <?php echo htmlspecialchars($p['s2_nome']); ?></strong>
                                <?php foreach($customs as $c): if ($c['metade'] == 2): ?>
                                    <div class="my-1">
                                        <?php if ($c['acao'] === 'remover'): ?>
                                            <span class="badge badge-remover me-1">SEM <?php echo htmlspecialchars($c['ingrediente_nome']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-adicionar me-1">+ COM <?php echo htmlspecialchars($c['ingrediente_nome']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($p['observacoes']): ?>
                            <div class="alert alert-warning py-2 px-3 mt-3 small mb-0">
                                <strong>Obs:</strong> <?php echo htmlspecialchars($p['observacoes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-light border-0 text-center p-3">
                        <form action="index.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                            <input type="hidden" name="concluir_id" value="<?php echo $p['id']; ?>">
                            <div class="d-flex gap-2">
                                <a href="../atendimento/imprimir.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn btn-outline-dark btn-lg" title="Imprimir Comanda do Ticket">
                                    <i class="bi bi-printer"></i>
                                </a>
                                <button type="submit" class="btn btn-success btn-lg flex-grow-1 fw-bold shadow-sm"><i class="bi bi-check-lg me-1"></i> Concluir Produção</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function tocarAlerta() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
        osc.frequency.setValueAtTime(880, audioCtx.currentTime + 0.15); // A5
        gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.45);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.45);
    } catch (e) {}
}

let tempoRestante = 20;
const timerEl = document.getElementById('kdsTimer');
setInterval(() => {
    tempoRestante--;
    if (timerEl) timerEl.textContent = tempoRestante + 's';
    if (tempoRestante <= 0) {
        // Recarrega via GET (evita reenviar o formulário de "Concluir Produção")
        window.location.href = 'index.php';
    }
}, 1000);
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
