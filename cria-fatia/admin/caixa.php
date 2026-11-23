<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/conexao.php';
checarPerfil(['admin', 'atendimento']);

$msg = '';
$erro = '';

// Ação de Abertura de Caixa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abrir_caixa'])) {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validarCsrfToken($csrf)) {
        $erro = 'Ação inválida (CSRF).';
    } else {
        $valorInicial = (float)str_replace(',', '.', $_POST['valor_inicial']);
        
        // Verificar se já existe caixa aberto
        $stmtCheck = $pdo->prepare("SELECT id FROM caixas WHERE status = 'aberto'");
        $stmtCheck->execute();
        if ($stmtCheck->fetch()) {
            $erro = 'Já existe um caixa aberto no momento. É necessário fechar o caixa atual antes de abrir um novo.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO caixas (usuario_abertura_id, valor_inicial, status) VALUES (?, ?, 'aberto')");
            $stmt->execute([$_SESSION['usuario_id'], $valorInicial]);
            $caixaId = $pdo->lastInsertId();
            
            // Registrar movimentação inicial de abertura
            $stmtMov = $pdo->prepare("INSERT INTO caixa_movimentacoes (caixa_id, usuario_id, tipo, origem, valor, justificativa) VALUES (?, ?, 'entrada', 'Abertura de Caixa', ?, 'Valor inicial de troco')");
            $stmtMov->execute([$caixaId, $_SESSION['usuario_id'], $valorInicial]);
            
            registrarAuditoria($pdo, 'ABRIR_CAIXA', 'caixa', $caixaId, null, $valorInicial, "Abriu o caixa com valor inicial de R$ " . number_format($valorInicial, 2, ',', '.'));
            $msg = "Caixa aberto com sucesso!";
        }
    }
}

// Ação de Fechamento de Caixa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fechar_caixa'])) {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validarCsrfToken($csrf)) {
        $erro = 'Ação inválida (CSRF).';
    } else {
        $caixaId = (int)$_POST['caixa_id'];
        $valorContado = (float)str_replace(',', '.', $_POST['valor_contado']);
        $obs = sanitizeInput($_POST['observacoes']);

        // Calcular saldo esperado (Soma de entradas - saídas + vendas do caixa)
        $stmtMov = $pdo->prepare("SELECT SUM(CASE WHEN tipo IN ('entrada', 'venda') THEN valor ELSE -valor END) as saldo FROM caixa_movimentacoes WHERE caixa_id = ?");
        $stmtMov->execute([$caixaId]);
        $resMov = $stmtMov->fetch();
        $saldoEsperado = $resMov ? (float)$resMov['saldo'] : 0.00;
        $diferenca = $valorContado - $saldoEsperado;

        $stmtClose = $pdo->prepare("UPDATE caixas SET usuario_fechamento_id = ?, valor_contado = ?, saldo_esperado = ?, diferenca = ?, status = 'fechado', observacoes_fechamento = ?, fechado_em = NOW() WHERE id = ?");
        $stmtClose->execute([$_SESSION['usuario_id'], $valorContado, $saldoEsperado, $diferenca, $obs, $caixaId]);

        registrarAuditoria($pdo, 'FECHAR_CAIXA', 'caixa', $caixaId, $saldoEsperado, $valorContado, "Fechou o caixa. Esperado: R$ $saldoEsperado, Contado: R$ $valorContado, Diferença: R$ $diferenca");
        $msg = "Caixa fechado com sucesso!";
    }
}

// Ação de Movimentação Manual (Entrada/Saída)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movimentacao_manual'])) {
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!validarCsrfToken($csrf)) {
        $erro = 'Ação inválida (CSRF).';
    } else {
        $caixaId = (int)$_POST['caixa_id'];
        $tipo = sanitizeInput($_POST['tipo']); // entrada ou saida
        $origem = sanitizeInput($_POST['origem']);
        $valor = (float)str_replace(',', '.', $_POST['valor']);
        $justificativa = sanitizeInput($_POST['justificativa']);

        if ($valor <= 0) {
            $erro = 'O valor deve ser maior que zero.';
        } elseif (empty($justificativa)) {
            $erro = 'A justificativa é obrigatória para movimentações manuais.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO caixa_movimentacoes (caixa_id, usuario_id, tipo, origem, valor, justificativa) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$caixaId, $_SESSION['usuario_id'], $tipo, $origem, $valor, $justificativa]);
            registrarAuditoria($pdo, 'MOVIMENTACAO_CAIXA', 'caixa', $caixaId, null, $valor, "Movimentação manual ($tipo): R$ $valor - $origem ($justificativa)");
            $msg = "Movimentação registrada com sucesso!";
        }
    }
}

// Caixa atualmente aberto
$stmtAberto = $pdo->query("SELECT c.*, u.nome as abriu_por FROM caixas c JOIN usuarios u ON c.usuario_abertura_id = u.id WHERE c.status = 'aberto' ORDER BY c.id DESC LIMIT 1");
$caixaAberto = $stmtAberto->fetch();

// Histórico de caixas fechados
$caixasFechados = $pdo->query("SELECT c.*, ua.nome as abriu_por, uf.nome as fechou_por FROM caixas c JOIN usuarios ua ON c.usuario_abertura_id = ua.id LEFT JOIN usuarios uf ON c.usuario_fechamento_id = uf.id WHERE c.status = 'fechado' ORDER BY c.id DESC LIMIT 10")->fetchAll();

$movimentacoesAberto = [];
$saldoAtual = 0.00;
if ($caixaAberto) {
    $stmtMovs = $pdo->prepare("SELECT cm.*, u.nome as usuario_nome FROM caixa_movimentacoes cm JOIN usuarios u ON cm.usuario_id = u.id WHERE cm.caixa_id = ? ORDER BY cm.id DESC");
    $stmtMovs->execute([$caixaAberto['id']]);
    $movimentacoesAberto = $stmtMovs->fetchAll();

    $stmtS = $pdo->prepare("SELECT SUM(CASE WHEN tipo IN ('entrada', 'venda') THEN valor ELSE -valor END) as saldo FROM caixa_movimentacoes WHERE caixa_id = ?");
    $stmtS->execute([$caixaAberto['id']]);
    $resS = $stmtS->fetch();
    $saldoAtual = $resS ? (float)$resS['saldo'] : 0.00;
}

$csrfToken = gerarCsrfToken();
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold"><i class="bi bi-cash-coin text-warning me-2"></i>Controle de Caixa</h2>
        <p class="text-muted">Abertura, fechamento e acompanhamento de entradas e saídas financeiras.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <?php if (!$caixaAberto): ?>
            <button class="btn btn-success fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalAbrirCaixa">
                <i class="bi bi-unlock-fill me-1"></i> Abrir Caixa
            </button>
        <?php else: ?>
            <button class="btn btn-danger fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalFecharCaixa">
                <i class="bi bi-lock-fill me-1"></i> Fechar Caixa
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle me-2"></i><?php echo $msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-triangle me-2"></i><?php echo $erro; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if ($caixaAberto): ?>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow border-0 bg-dark text-white rounded-4 p-3">
                <span class="text-uppercase small text-warning fw-bold">Status do Caixa</span>
                <h3 class="fw-bold mb-1">ABERTO</h3>
                <p class="text-muted small mb-0">Aberto por: <?php echo htmlspecialchars($caixaAberto['abriu_por']); ?> em <?php echo date('d/m/Y H:i', strtotime($caixaAberto['aberto_em'])); ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow border-0 bg-success text-white rounded-4 p-3">
                <span class="text-uppercase small text-white-50 fw-bold">Saldo Esperado em Caixa</span>
                <h2 class="fw-bold mb-0">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></h2>
                <small class="text-white-50">Inclui valor inicial, vendas e movimentações</small>
            </div>
        </div>
        <div class="col-md-4 text-md-end align-self-center">
            <button class="btn btn-warning fw-bold text-dark px-4 py-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMovimentacao">
                <i class="bi bi-plus-slash-minus me-1"></i> Nova Movimentação Manual
            </button>
        </div>
    </div>

    <!-- Movimentações do Caixa Aberto -->
    <div class="card shadow border-0 rounded-4 mb-5">
        <div class="card-header bg-dark text-white fw-bold py-3">
            <i class="bi bi-list-columns-reverse me-2"></i>Movimentações do Caixa Atual (#<?php echo $caixaAberto['id']; ?>)
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Horário</th>
                            <th>Tipo</th>
                            <th>Origem / Motivo</th>
                            <th>Usuário</th>
                            <th>Justificativa</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($movimentacoesAberto)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-3">Nenhuma movimentação registrada neste caixa ainda.</td></tr>
                        <?php else: ?>
                            <?php foreach($movimentacoesAberto as $m): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($m['criado_em'])); ?></td>
                                    <td>
                                        <?php if ($m['tipo'] === 'entrada' || $m['tipo'] === 'venda'): ?>
                                            <span class="badge bg-success text-uppercase"><?php echo $m['tipo']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger text-uppercase"><?php echo $m['tipo']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold">
                                        <?php echo htmlspecialchars($m['origem']); ?>
                                        <?php if (!empty($m['pedido_id'])): ?>
                                            <a href="../atendimento/imprimir.php?id=<?php echo $m['pedido_id']; ?>" target="_blank" class="badge bg-dark text-decoration-none ms-1" title="Ver Comanda do Pedido">#<?php echo $m['pedido_id']; ?></a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($m['usuario_nome']); ?></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($m['justificativa']); ?></td>
                                    <td class="text-end fw-bold <?php echo ($m['tipo'] === 'saida') ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo ($m['tipo'] === 'saida') ? '- ' : '+ '; ?>R$ <?php echo number_format($m['valor'], 2, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning shadow-sm border-0 p-4 rounded-4 mb-5 text-center">
        <i class="bi bi-exclamation-triangle display-4 text-warning mb-2 d-block"></i>
        <h4 class="fw-bold">Nenhum caixa aberto no momento.</h4>
        <p class="text-muted mb-3">Para registrar pedidos e movimentações financeiras, abra um novo caixa informando o valor inicial de troco.</p>
        <button class="btn btn-success fw-bold px-4" data-bs-toggle="modal" data-bs-target="#modalAbrirCaixa">
            <i class="bi bi-unlock-fill me-1"></i> Abrir Caixa Agora
        </button>
    </div>
<?php endif; ?>

<!-- Histórico de Caixas Fechados -->
<div class="card shadow border-0 rounded-4">
    <div class="card-header bg-secondary text-white fw-bold py-3">
        <i class="bi bi-archive me-2"></i>Histórico de Fechamentos de Caixa
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Abertura</th>
                        <th>Fechamento</th>
                        <th>Valor Inicial</th>
                        <th>Saldo Esperado</th>
                        <th>Valor Contado</th>
                        <th>Diferença</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($caixasFechados)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-3">Nenhum caixa fechado recentemente.</td></tr>
                    <?php else: ?>
                        <?php foreach($caixasFechados as $cf): ?>
                            <tr>
                                <td>#<?php echo $cf['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cf['aberto_em'])); ?><br><small class="text-muted">Por: <?php echo htmlspecialchars($cf['abriu_por']); ?></small></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cf['fechado_em'])); ?><br><small class="text-muted">Por: <?php echo htmlspecialchars($cf['fechou_por']); ?></small></td>
                                <td>R$ <?php echo number_format($cf['valor_inicial'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($cf['saldo_esperado'], 2, ',', '.'); ?></td>
                                <td class="fw-bold">R$ <?php echo number_format($cf['valor_contado'], 2, ',', '.'); ?></td>
                                <td class="fw-bold <?php echo ($cf['diferenca'] < 0) ? 'text-danger' : (($cf['diferenca'] > 0) ? 'text-success' : 'text-muted'); ?>">
                                    R$ <?php echo number_format($cf['diferenca'], 2, ',', '.'); ?>
                                </td>
                                <td><span class="badge bg-secondary">Fechado</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Abrir Caixa -->
<div class="modal fade" id="modalAbrirCaixa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="caixa.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-unlock me-2"></i>Abertura de Caixa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Valor Inicial em Caixa (Troco):</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" name="valor_inicial" class="form-control" value="100,00" required>
                        </div>
                        <small class="text-muted">Informe o valor em dinheiro disponível no caixa para troco ao iniciar o expediente.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="abrir_caixa" class="btn btn-success fw-bold px-4">Confirmar Abertura</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($caixaAberto): ?>
<!-- Modal Fechar Caixa -->
<div class="modal fade" id="modalFecharCaixa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="caixa.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="caixa_id" value="<?php echo $caixaAberto['id']; ?>">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-lock me-2"></i>Fechamento de Caixa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded mb-3">
                        <p class="mb-1 text-muted">Saldo Esperado pelo Sistema:</p>
                        <h3 class="fw-bold text-success mb-0">R$ <?php echo number_format($saldoAtual, 2, ',', '.'); ?></h3>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Valor Contado em Caixa (Dinheiro Real):</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="text" name="valor_contado" class="form-control" placeholder="0,00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Observações / Justificativa de Quebra (se houver):</label>
                        <textarea name="observacoes" class="form-control" rows="2" placeholder="Ex: Sobra de troco ou diferença explicada"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="fechar_caixa" class="btn btn-danger fw-bold px-4">Confirmar Fechamento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Movimentação Manual -->
<div class="modal fade" id="modalMovimentacao" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="caixa.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="caixa_id" value="<?php echo $caixaAberto['id']; ?>">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-slash-minus me-2"></i>Nova Movimentação Manual</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de Movimentação:</label>
                        <select name="tipo" class="form-select" required>
                            <option value="entrada">Entrada (Suprimento / Reforço de troco)</option>
                            <option value="saida">Saída (Sangria / Pagamento de despesa)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Origem / Descrição:</label>
                        <input type="text" name="origem" class="form-control" placeholder="Ex: Pagamento de Motoboy / Troco extra" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Valor (R$):</label>
                        <input type="text" name="valor" class="form-control" placeholder="0,00" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Justificativa Obrigatória:</label>
                        <textarea name="justificativa" class="form-control" rows="2" placeholder="Explique o motivo da retirada ou entrada" required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" name="movimentacao_manual" class="btn btn-warning fw-bold text-dark px-4">Salvar Movimentação</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
