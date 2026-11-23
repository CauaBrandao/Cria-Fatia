<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

$erro = '';
$taxaEntregaPadrao = 5.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tamanho_id'])) {
    if (!isset($_POST['csrf_token']) || !validarCsrfToken($_POST['csrf_token'])) {
        $erro = 'Sessão inválida. Por favor, volte e monte sua pizza novamente.';
    } else {
        $_SESSION['dados_pedido'] = $_POST;
    }
}
if (!isset($_SESSION['dados_pedido'])) { header('Location: montar.php'); exit; }
$dados = $_SESSION['dados_pedido'];
$tamanho_id = (int)($dados['tamanho_id'] ?? 0);
$massa_id = (int)($dados['massa_id'] ?? 0);
$borda_id = (int)($dados['borda_id'] ?? 0);
$ingredientesEscolhidos = isset($dados['ingredientes']) && is_array($dados['ingredientes']) ? array_values(array_unique(array_map('intval', $dados['ingredientes']))) : [];
$molhosEscolhidos = isset($dados['molhos']) && is_array($dados['molhos']) ? array_values(array_unique(array_map('intval', $dados['molhos']))) : [];

$stmt = $pdo->prepare("SELECT * FROM tamanhos WHERE id = ? AND disponivel = 1"); $stmt->execute([$tamanho_id]); $tamanho = $stmt->fetch();
$stmt = $pdo->prepare("SELECT * FROM tipos_massas WHERE id = ? AND disponivel = 1"); $stmt->execute([$massa_id]); $massa = $stmt->fetch();
$stmt = $pdo->prepare("SELECT * FROM bordas WHERE id = ? AND disponivel = 1"); $stmt->execute([$borda_id]); $borda = $stmt->fetch();
if (!$tamanho || !$borda || !$massa) { unset($_SESSION['dados_pedido']); header('Location: montar.php'); exit; }
if (!$ingredientesEscolhidos) { $erro = 'Selecione pelo menos um ingrediente para montar sua pizza.'; }
if (count($ingredientesEscolhidos) > 8) { $erro = 'Selecione no máximo 8 ingredientes para montar sua pizza.'; }

$ingredientesValidos = [];
if ($ingredientesEscolhidos) {
    $marks = implode(',', array_fill(0, count($ingredientesEscolhidos), '?'));
    $stmt = $pdo->prepare("SELECT id, nome, preco_adicional FROM ingredientes WHERE disponivel = 1 AND id IN ($marks) ORDER BY nome");
    $stmt->execute($ingredientesEscolhidos); $ingredientesValidos = $stmt->fetchAll();
    if (count($ingredientesValidos) !== count($ingredientesEscolhidos)) $erro = 'Um ou mais ingredientes não estão disponíveis. Monte sua pizza novamente.';
}
$molhosValidos = [];
if ($molhosEscolhidos) {
    $marks = implode(',', array_fill(0, count($molhosEscolhidos), '?'));
    $stmt = $pdo->prepare("SELECT id, nome, preco_adicional FROM molhos WHERE disponivel = 1 AND id IN ($marks)");
    $stmt->execute($molhosEscolhidos); $molhosValidos = $stmt->fetchAll();
}

$subtotalPizza = (float)$tamanho['preco_base'] + (float)$borda['preco'] + (float)$massa['preco_adicional'];
foreach ($ingredientesValidos as $ing) $subtotalPizza += (float)$ing['preco_adicional'];
foreach ($molhosValidos as $molho) $subtotalPizza += (float)$molho['preco_adicional'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cliente_nome'])) {
    if (!isset($_POST['csrf_token']) || !validarCsrfToken($_POST['csrf_token'])) {
        $erro = 'Sessão inválida.';
    } else {
        $cliente_nome = trim($_POST['cliente_nome']);
        $cliente_telefone = trim($_POST['cliente_telefone']);
        $tipo_entrega = ($_POST['tipo_entrega'] ?? '') === 'retirada' ? 'retirada' : 'entrega';
        $taxaEntrega = ($tipo_entrega === 'entrega') ? $taxaEntregaPadrao : 0.00;
        $valorTotalValidado = $subtotalPizza + $taxaEntrega;

        $forma_pagamento = in_array($_POST['forma_pagamento'] ?? '', ['pix', 'cartao', 'dinheiro'], true) ? $_POST['forma_pagamento'] : 'pix';
        $troco_para = null;
        if ($forma_pagamento === 'dinheiro' && !empty($_POST['troco_para'])) {
            $troco_para = (float)str_replace(',', '.', $_POST['troco_para']);
            if ($troco_para < $valorTotalValidado) {
                $erro = 'O valor para troco (R$ ' . number_format($troco_para, 2, ',', '.') . ') deve ser maior ou igual ao total do pedido (R$ ' . number_format($valorTotalValidado, 2, ',', '.') . ').';
            }
        }

        $endereco = trim($_POST['endereco'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');
        if ($erro === '' && (empty($cliente_nome) || empty($cliente_telefone))) $erro = 'Preencha seu nome e telefone.';
        if ($erro === '' && $tipo_entrega === 'entrega' && $endereco === '') $erro = 'Informe o endereço de entrega.';

        if ($erro === '') {
            try {
                $pdo->beginTransaction();
                $codigo_acompanhamento = 'CF-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
                $token_acesso = bin2hex(random_bytes(32));

                $stmtPed = $pdo->prepare("INSERT INTO pedidos (codigo_acompanhamento, token_acesso, cliente_nome, cliente_telefone, tipo_entrega, forma_pagamento, taxa_entrega, troco_para, endereco, observacoes, valor_total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente')");
                $stmtPed->execute([$codigo_acompanhamento, $token_acesso, $cliente_nome, $cliente_telefone, $tipo_entrega, $forma_pagamento, $taxaEntrega, $troco_para, $endereco, $observacoes, $valorTotalValidado]);
                $pedido_id = $pdo->lastInsertId();

                $stmtItem = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, tamanho_id, borda_id, massa_id, divisao, sabor1_id, sabor2_id, preco_item) VALUES (?, ?, ?, ?, 'inteira', NULL, NULL, ?)");
                $stmtItem->execute([$pedido_id, $tamanho_id, $borda_id, $massa_id, $subtotalPizza]);
                $pedido_item_id = $pdo->lastInsertId();

                $stmtCust = $pdo->prepare("INSERT INTO pedido_item_customizacoes (pedido_item_id, metade, ingrediente_id, acao) VALUES (?, 1, ?, 'adicionar')");
                foreach ($ingredientesValidos as $ing) $stmtCust->execute([$pedido_item_id, (int)$ing['id']]);

                if ($molhosValidos) {
                    $stmtMolho = $pdo->prepare("INSERT INTO pedido_item_molhos (pedido_item_id, molho_id) VALUES (?, ?)");
                    foreach ($molhosValidos as $molho) $stmtMolho->execute([$pedido_item_id, (int)$molho['id']]);
                }

                $stmtHist = $pdo->prepare("INSERT INTO historico_status_pedidos (pedido_id, status_anterior, novo_status, observacao) VALUES (?, NULL, 'pendente', 'Pedido realizado pelo cliente via site')");
                $stmtHist->execute([$pedido_id]);

                $pdo->commit();
                unset($_SESSION['dados_pedido']);
                $_SESSION['ultimo_pedido_codigo'] = $codigo_acompanhamento;
                $_SESSION['ultimo_pedido_token'] = $token_acesso;
                header('Location: pedido.php?codigo=' . urlencode($codigo_acompanhamento)); exit;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Erro ao finalizar pedido no Cria Fatia: ' . $e->getMessage());
                $erro = 'Não foi possível finalizar o pedido. Tente novamente em alguns instantes.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-lg border-0 rounded-4">
            <div class="card-header bg-gradient-dark text-white p-4 text-center">
                <h3 class="fw-bold mb-1"><i class="bi bi-bag-check me-2 text-warning"></i>Finalizar Pedido</h3>
                <p class="text-muted small mb-0">Confira os detalhes da sua pizza e informe seus dados para entrega ou retirada</p>
            </div>
            <div class="card-body p-4 p-md-5">
                <?php if ($erro): ?>
                    <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>

                <div class="alert alert-dark border border-secondary mb-4 p-4 rounded-3">
                    <h5 class="fw-bold text-warning mb-3"><i class="bi bi-receipt me-2"></i>Resumo do Pedido:</h5>
                    <div class="mb-2"><strong>Tamanho:</strong> <?php echo htmlspecialchars($tamanho['nome']); ?> · <strong>Massa:</strong> <?php echo htmlspecialchars($massa['nome']); ?> · <strong>Borda:</strong> <?php echo htmlspecialchars($borda['nome']); ?></div>
                    <div class="mb-2"><strong>Ingredientes:</strong> <?php echo htmlspecialchars(implode(', ', array_column($ingredientesValidos, 'nome'))); ?></div>
                    <?php if ($molhosValidos): ?><div class="mb-2"><strong>Molhos:</strong> <?php echo htmlspecialchars(implode(', ', array_column($molhosValidos, 'nome'))); ?></div><?php endif; ?>
                    <hr class="border-secondary my-2">
                    <div class="d-flex justify-content-between mb-1 small">
                        <span>Subtotal da Pizza:</span>
                        <strong>R$ <?php echo number_format($subtotalPizza, 2, ',', '.'); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span id="taxa_entrega_label">Taxa de Entrega:</span>
                        <strong id="taxa_entrega_display" class="text-warning">+ R$ <?php echo number_format($taxaEntregaPadrao, 2, ',', '.'); ?></strong>
                    </div>
                    <hr class="border-secondary my-2">
                    <div class="d-flex justify-content-between fs-4">
                        <span class="fw-bold text-white">Total a Pagar:</span>
                        <span class="fw-bold text-success" id="total_final_display">R$ <?php echo number_format($subtotalPizza + $taxaEntregaPadrao, 2, ',', '.'); ?></span>
                    </div>
                </div>

                <form action="finalizar.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo gerarCsrfToken(); ?>">
                    
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-fill text-warning me-2"></i>1. Dados do Cliente</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Seu Nome Completo *</label>
                            <input type="text" name="cliente_nome" class="form-control form-control-lg" required placeholder="Ex: Carlos Eduardo Silva" value="<?php echo htmlspecialchars($_POST['cliente_nome'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Telefone / WhatsApp *</label>
                            <input type="text" name="cliente_telefone" class="form-control form-control-lg" required placeholder="Ex: (11) 98765-4321" value="<?php echo htmlspecialchars($_POST['cliente_telefone'] ?? ''); ?>">
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-truck text-warning me-2"></i>2. Forma de Recebimento</h5>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tipo de Entrega *</label>
                        <select name="tipo_entrega" id="tipo_entrega" class="form-select form-select-lg" required onchange="atualizarTipoEntrega(this.value)">
                            <option value="entrega" <?php echo (!isset($_POST['tipo_entrega']) || $_POST['tipo_entrega'] === 'entrega') ? 'selected' : ''; ?>>Entrega em Domicílio (+ R$ 5,00 taxa fixa)</option>
                            <option value="retirada" <?php echo (isset($_POST['tipo_entrega']) && $_POST['tipo_entrega'] === 'retirada') ? 'selected' : ''; ?>>Retirar no Balcão (Sem taxa de entrega)</option>
                        </select>
                    </div>

                    <div class="mb-4" id="group_endereco">
                        <label class="form-label fw-bold">Endereço de Entrega (Rua, Número, Bairro, Complemento) *</label>
                        <textarea name="endereco" id="input_endereco" class="form-control" rows="3" placeholder="Ex: Rua das Palmeiras, 450, Apto 22 - Centro"><?php echo htmlspecialchars($_POST['endereco'] ?? ''); ?></textarea>
                    </div>

                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-credit-card-2-front text-warning me-2"></i>3. Forma de Pagamento</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="d-flex align-items-center gap-2 p-3 border rounded-3 cursor-pointer h-100 bg-light" for="pag_pix">
                                <input type="radio" name="forma_pagamento" id="pag_pix" value="pix" class="form-check-input mt-0" checked onchange="atualizarFormaPagamento('pix')">
                                <div>
                                    <div class="fw-bold text-dark"><i class="bi bi-qr-code text-success me-1"></i> Pix</div>
                                    <small class="text-muted">Aprovação Imediata</small>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="d-flex align-items-center gap-2 p-3 border rounded-3 cursor-pointer h-100 bg-light" for="pag_cartao">
                                <input type="radio" name="forma_pagamento" id="pag_cartao" value="cartao" class="form-check-input mt-0" onchange="atualizarFormaPagamento('cartao')">
                                <div>
                                    <div class="fw-bold text-dark"><i class="bi bi-credit-card text-primary me-1"></i> Cartão</div>
                                    <small class="text-muted">Crédito / Débito</small>
                                </div>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="d-flex align-items-center gap-2 p-3 border rounded-3 cursor-pointer h-100 bg-light" for="pag_dinheiro">
                                <input type="radio" name="forma_pagamento" id="pag_dinheiro" value="dinheiro" class="form-check-input mt-0" onchange="atualizarFormaPagamento('dinheiro')">
                                <div>
                                    <div class="fw-bold text-dark"><i class="bi bi-cash-stack text-success me-1"></i> Dinheiro</div>
                                    <small class="text-muted">No ato da entrega</small>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div id="painel_pix" class="mb-4 p-4 rounded-4 border border-success bg-light">
                        <h6 class="fw-bold text-success mb-3"><i class="bi bi-qr-code-scan me-2"></i>Pagamento via Pix</h6>
                        <div class="alert alert-warning mb-0" role="status">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            O Pix não é processado nem confirmado por este sistema. Antes de pagar, confirme a chave e o valor diretamente com a equipe da pizzaria. Não use QR Codes ou chaves recebidos fora dessa confirmação.
                        </div>
                    </div>

                    <!-- Painel Cartão: instruções de maquininha -->
                    <div id="painel_cartao" class="mb-4 p-4 rounded-4 border border-primary bg-light d-none">
                        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-credit-card-2-front me-2"></i>Pagamento com Cartão</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px;height:38px;font-weight:800;">1</div>
                                    <div>
                                        <div class="fw-semibold">Aceito na Entrega</div>
                                        <small class="text-muted">O motoboy ou atendente levará a maquininha até você.</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px;height:38px;font-weight:800;">2</div>
                                    <div>
                                        <div class="fw-semibold">Crédito ou Débito</div>
                                        <small class="text-muted">Aceitamos todas as bandeiras: Visa, Mastercard, Elo e Hipercard.</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:38px;height:38px;font-weight:800;">3</div>
                                    <div>
                                        <div class="fw-semibold">Parcelamento</div>
                                        <small class="text-muted">Crédito em até 2x sem juros.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-center justify-content-center">
                                <div class="text-center p-4 bg-white rounded-4 shadow-sm border">
                                    <i class="bi bi-credit-card-fill text-primary" style="font-size: 3rem;"></i>
                                    <div class="mt-2">
                                        <div class="d-flex gap-2 justify-content-center mt-2">
                                            <span class="badge bg-primary px-3 py-2">VISA</span>
                                            <span class="badge bg-danger px-3 py-2">Master</span>
                                            <span class="badge bg-warning text-dark px-3 py-2">Elo</span>
                                            <span class="badge bg-dark px-3 py-2">Hiper</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Painel Dinheiro: troco -->
                    <div id="painel_dinheiro" class="mb-4 p-4 rounded-4 border border-success bg-light d-none">
                        <h6 class="fw-bold text-success mb-3"><i class="bi bi-cash-stack me-2"></i>Pagamento em Dinheiro</h6>
                        <p class="text-muted small mb-3">O pagamento será feito no ato da entrega ou retirada. Se precisar de troco, informe o valor abaixo:</p>
                        <label class="form-label fw-bold">Precisa de troco para quanto? <span class="text-muted fw-normal">(Opcional)</span></label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-success text-white"><i class="bi bi-cash"></i></span>
                            <input type="number" step="0.50" min="0" name="troco_para" id="troco_input" class="form-control form-control-lg" placeholder="Ex: 100,00" value="<?php echo htmlspecialchars($_POST['troco_para'] ?? ''); ?>">
                        </div>
                        <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Se não precisar de troco, deixe em branco.</small>
                    </div>

                    <div id="group_troco" class="d-none">
                        <!-- Campo oculto de troco (mantido para compatibilidade) -->
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Observações do Pedido (Opcional)</label>
                        <textarea name="observacoes" class="form-control" rows="2" placeholder="Ex: Caprichar no orégano, tocar o interfone 22..."><?php echo htmlspecialchars($_POST['observacoes'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex gap-3">
                        <a href="montar.php" class="btn btn-outline-secondary btn-lg flex-fill py-3 fw-bold">Voltar e Ajustar</a>
                        <button type="submit" class="btn btn-warning btn-lg flex-fill py-3 fw-bold shadow text-dark">
                            <i class="bi bi-check-circle-fill me-2"></i> Confirmar Pedido
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const subtotalPizza = <?php echo json_encode($subtotalPizza); ?>;
const taxaPadrao = <?php echo json_encode($taxaEntregaPadrao); ?>;

function atualizarTipoEntrega(val) {
    const grp = document.getElementById('group_endereco');
    const labelTaxa = document.getElementById('taxa_entrega_label');
    const displayTaxa = document.getElementById('taxa_entrega_display');
    const displayTotal = document.getElementById('total_final_display');

    if (val === 'retirada') {
        grp.style.display = 'none';
        labelTaxa.textContent = 'Retirada no Balcão:';
        displayTaxa.className = 'text-success';
        displayTaxa.textContent = 'Grátis (R$ 0,00)';
        const total = subtotalPizza;
        displayTotal.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
    } else {
        grp.style.display = 'block';
        labelTaxa.textContent = 'Taxa de Entrega:';
        displayTaxa.className = 'text-warning';
        displayTaxa.textContent = '+ R$ ' + taxaPadrao.toFixed(2).replace('.', ',');
        const total = subtotalPizza + taxaPadrao;
        displayTotal.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
    }
}

function atualizarFormaPagamento(tipo) {
    // Esconder todos os painéis
    document.getElementById('painel_pix').classList.add('d-none');
    document.getElementById('painel_cartao').classList.add('d-none');
    document.getElementById('painel_dinheiro').classList.add('d-none');

    // Exibir o painel correspondente
    if (tipo === 'pix')      document.getElementById('painel_pix').classList.remove('d-none');
    if (tipo === 'cartao')   document.getElementById('painel_cartao').classList.remove('d-none');
    if (tipo === 'dinheiro') document.getElementById('painel_dinheiro').classList.remove('d-none');

    // Limpar troco se não for dinheiro
    const trocoInput = document.getElementById('troco_input');
    if (trocoInput && tipo !== 'dinheiro') trocoInput.value = '';
}

document.addEventListener('DOMContentLoaded', () => {
    const selEntrega = document.getElementById('tipo_entrega');
    if (selEntrega) atualizarTipoEntrega(selEntrega.value);

    // Exibir painel Pix por padrão e marcar o radio
    const radPix = document.getElementById('pag_pix');
    if (radPix) { radPix.checked = true; }
    atualizarFormaPagamento('pix');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
