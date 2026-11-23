<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';
checarPerfil(['admin', 'atendimento', 'cozinha']);

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$id]);
$pedido = $stmt->fetch();

if (!$pedido) {
    die("Pedido não encontrado.");
}

// Buscar itens do pedido
$stmtItens = $pdo->prepare("
    SELECT pi.*, t.nome as tamanho_nome, b.nome as borda_nome, tm.nome as massa_nome
    FROM pedido_itens pi
    LEFT JOIN tamanhos t ON pi.tamanho_id = t.id
    LEFT JOIN bordas b ON pi.borda_id = b.id
    LEFT JOIN tipos_massas tm ON pi.massa_id = tm.id
    WHERE pi.pedido_id = ?
");
$stmtItens->execute([$id]);
$itens = $stmtItens->fetchAll();

foreach ($itens as &$item) {
    $stmtCust = $pdo->prepare("
        SELECT pic.*, i.nome as ingrediente_nome
        FROM pedido_item_customizacoes pic
        JOIN ingredientes i ON pic.ingrediente_id = i.id
        WHERE pic.pedido_item_id = ?
    ");
    $stmtCust->execute([$item['id']]);
    $item['ingredientes'] = $stmtCust->fetchAll();

    $stmtMolhos = $pdo->prepare("
        SELECT m.nome
        FROM pedido_item_molhos pim
        JOIN molhos m ON pim.molho_id = m.id
        WHERE pim.pedido_item_id = ?
    ");
    $stmtMolhos->execute([$item['id']]);
    $item['molhos'] = $stmtMolhos->fetchAll();
}
unset($item);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Comanda #<?= $pedido['id'] ?> - Bella Roza Pizzaria</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 76mm;
            margin: 0 auto;
            padding: 4mm;
            color: #000;
            background: #fff;
            font-size: 13px;
            line-height: 1.35;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .double-divider { border-top: 2px solid #000; margin: 6px 0; }
        .tag-remover { font-weight: bold; text-decoration: line-through; }
        .btn-imprimir {
            display: block;
            width: 100%;
            padding: 8px;
            background: #ffc107;
            color: #000;
            font-weight: bold;
            border: 1px solid #000;
            cursor: pointer;
            margin-bottom: 10px;
            font-family: sans-serif;
        }
        @media print {
            .no-print { display: none !important; }
            body { width: 100%; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-imprimir" onclick="window.print()">🖨️ IMPRIMIR COMANDA (CUPOM)</button>
    </div>

    <div class="text-center">
        <div class="fw-bold" style="font-size: 16px;">BELLA ROZA PIZZARIA</div>
        <div style="font-size: 11px;">CRIA FATIA - SISTEMA DE MONTAGEM</div>
        <div>Telefone: (11) 98888-7777</div>
    </div>

    <div class="double-divider"></div>

    <div><strong>PEDIDO:</strong> #<?= str_pad($pedido['id'], 5, '0', STR_PAD_LEFT) ?> (<?= htmlspecialchars($pedido['codigo_acompanhamento']) ?>)</div>
    <div><strong>DATA/HORA:</strong> <?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></div>
    <div><strong>TIPO:</strong> <?= strtoupper($pedido['tipo_entrega']) ?></div>
    <div><strong>STATUS:</strong> <?= strtoupper($pedido['status']) ?></div>

    <div class="divider"></div>

    <div class="fw-bold">DADOS DO CLIENTE</div>
    <div>Nome: <?= htmlspecialchars($pedido['cliente_nome']) ?></div>
    <div>Tel: <?= htmlspecialchars($pedido['cliente_telefone']) ?></div>
    <?php if ($pedido['tipo_entrega'] === 'entrega'): ?>
        <div>End: <?= htmlspecialchars($pedido['endereco']) ?></div>
    <?php else: ?>
        <div class="fw-bold">*** RETIRADA NO BALCÃO ***</div>
    <?php endif; ?>

    <div class="double-divider"></div>

    <div class="fw-bold">ITENS DO PEDIDO</div>
    <?php foreach ($itens as $i => $item): ?>
        <div style="margin-top: 4px;">
            <div class="fw-bold">1x PIZZA <?= strtoupper(htmlspecialchars($item['tamanho_nome'])) ?></div>
            <div>Massa: <?= htmlspecialchars($item['massa_nome']) ?></div>
            <div>Borda: <?= htmlspecialchars($item['borda_nome']) ?></div>
            <div style="margin-left: 8px;">
                <div class="fw-bold">Ingredientes:</div>
                <?php foreach ($item['ingredientes'] as $ing): ?>
                    <div>• <?= htmlspecialchars($ing['ingrediente_nome']) ?></div>
                <?php endforeach; ?>
                <?php if ($item['molhos']): ?>
                    <div class="fw-bold" style="margin-top:2px;">Molhos:</div>
                    <?php foreach ($item['molhos'] as $molho): ?>
                        <div>+ <?= htmlspecialchars($molho['nome']) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="text-right">R$ <?= number_format($item['preco_item'], 2, ',', '.') ?></div>
        </div>
        <div class="divider"></div>
    <?php endforeach; ?>

    <?php if (!empty($pedido['observacoes'])): ?>
        <div class="fw-bold">OBSERVAÇÕES:</div>
        <div><?= htmlspecialchars($pedido['observacoes']) ?></div>
        <div class="divider"></div>
    <?php endif; ?>

    <div style="margin-top: 4px;">
        <div style="display:flex; justify-content:space-between;">
            <span>Subtotal:</span>
            <span>R$ <?= number_format($pedido['valor_total'] - $pedido['taxa_entrega'], 2, ',', '.') ?></span>
        </div>
        <?php if ((float)$pedido['taxa_entrega'] > 0): ?>
            <div style="display:flex; justify-content:space-between;">
                <span>Taxa de Entrega:</span>
                <span>R$ <?= number_format($pedido['taxa_entrega'], 2, ',', '.') ?></span>
            </div>
        <?php endif; ?>
        <div class="double-divider"></div>
        <div class="fw-bold" style="display:flex; justify-content:space-between; font-size:15px;">
            <span>TOTAL:</span>
            <span>R$ <?= number_format($pedido['valor_total'], 2, ',', '.') ?></span>
        </div>
    </div>

    <div class="divider"></div>

    <div><strong>PAGAMENTO:</strong> <?= strtoupper($pedido['forma_pagamento'] ?? 'PIX') ?></div>
    <?php if (($pedido['forma_pagamento'] ?? '') === 'dinheiro' && !empty($pedido['troco_para'])): ?>
        <div>Troco para: R$ <?= number_format($pedido['troco_para'], 2, ',', '.') ?></div>
        <div>Troco a levar: R$ <?= number_format($pedido['troco_para'] - $pedido['valor_total'], 2, ',', '.') ?></div>
    <?php endif; ?>

    <div class="double-divider"></div>
    <div class="text-center" style="font-size: 11px; margin-top: 8px;">
        Agradecemos a preferência!<br>
        www.criafatia.com.br
    </div>
</body>
</html>

