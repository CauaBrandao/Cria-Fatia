
<?php
require_once __DIR__ . '/../config/conexao.php';

// Segurança e funções auxiliares
function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function moeda($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

$codigo = trim($_GET['codigo'] ?? $_POST['codigo'] ?? '');
$pedido = null;
$itens = [];
$erro = '';

if (empty($_SESSION['csrf_pedido'])) {
    $_SESSION['csrf_pedido'] = bin2hex(random_bytes(32));
}

// Mensagens após confirmação
$mensagem = $_SESSION['flash_pedido'] ?? '';
unset($_SESSION['flash_pedido']);

// =====================================================
// CONFIRMAR RECEBIMENTO
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_recebimento'])) {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_pedido'], $token)) {
        $erro = 'Sessão inválida. Atualize a página.';

    } elseif (
        empty($_SESSION['ultimo_pedido_codigo']) ||
        empty($_SESSION['ultimo_pedido_token']) ||
        !hash_equals((string)$_SESSION['ultimo_pedido_codigo'], $codigo)
    ) {
        $erro = 'Não foi possível validar o acesso a este pedido.';

    } else {

        $stmt = $pdo->prepare("
            SELECT id, status, token_acesso
            FROM pedidos
            WHERE codigo_acompanhamento = ?
        ");

        $stmt->execute([$codigo]);
        $pedidoConfirmacao = $stmt->fetch();

        if (!$pedidoConfirmacao) {
            $erro = 'Pedido não encontrado.';

        } elseif (
            !hash_equals(
                (string)$pedidoConfirmacao['token_acesso'],
                (string)$_SESSION['ultimo_pedido_token']
            )
        ) {
            $erro = 'Token de acesso inválido.';

        } elseif ($pedidoConfirmacao['status'] === 'entregue') {

            $_SESSION['flash_pedido'] = 'O recebimento deste pedido já foi confirmado!';

            header('Location: pedido.php?codigo=' . urlencode($codigo));
            exit;

        } elseif (!in_array($pedidoConfirmacao['status'], ['pronto', 'finalizado'], true)) {

            $erro = 'Seu pedido ainda não está disponível para confirmação.';

        } else {

            $stmt = $pdo->prepare("
                UPDATE pedidos
                SET status = 'entregue'
                WHERE id = ?
                AND status IN ('pronto', 'finalizado')
                AND token_acesso = ?
            ");

            $stmt->execute([
                $pedidoConfirmacao['id'],
                $_SESSION['ultimo_pedido_token']
            ]);

            if ($stmt->rowCount() > 0) {

                // Registrar histórico
                $stmtHist = $pdo->prepare("
                    INSERT INTO historico_status_pedidos
                    (pedido_id, status_anterior, novo_status, observacao)
                    VALUES (?, ?, 'entregue', ?)
                ");

                $stmtHist->execute([
                    $pedidoConfirmacao['id'],
                    $pedidoConfirmacao['status'],
                    'Recebimento confirmado pelo cliente.'
                ]);

                // Registrar venda no caixa aberto, se houver
                try {
                    $caixaAberto = $pdo->query("SELECT id FROM caixas WHERE status = 'aberto' ORDER BY id DESC LIMIT 1")->fetch();
                    if ($caixaAberto) {
                        $pedInfo = $pdo->prepare("SELECT valor_total, cliente_nome FROM pedidos WHERE id = ?");
                        $pedInfo->execute([$pedidoConfirmacao['id']]);
                        $pedDados = $pedInfo->fetch();
                        if ($pedDados) {
                            $stmtVenda = $pdo->prepare("INSERT INTO caixa_movimentacoes (caixa_id, pedido_id, usuario_id, tipo, origem, valor, justificativa) VALUES (?, ?, 1, 'venda', 'Entrega Confirmada pelo Cliente', ?, ?)");
                            $stmtVenda->execute([
                                $caixaAberto['id'],
                                $pedidoConfirmacao['id'],
                                $pedDados['valor_total'],
                                "Venda do Pedido #{$pedidoConfirmacao['id']} - Cliente: {$pedDados['cliente_nome']}"
                            ]);
                        }
                    }
                } catch (Exception $e) {
                    // Silenciar
                }

                $_SESSION['flash_pedido'] =
                    'Recebimento confirmado! Seu pedido agora está marcado como entregue.';

                header('Location: pedido.php?codigo=' . urlencode($codigo));
                exit;

            } else {
                $erro = 'Não foi possível confirmar o recebimento. Atualize a página.';
            }
        }
    }
}

// =====================================================
// BUSCAR PEDIDO
// =====================================================

if (!empty($codigo)) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM pedidos
        WHERE codigo_acompanhamento = ?
    ");

    $stmt->execute([$codigo]);
    $pedido = $stmt->fetch();

}

// =====================================================
// BUSCAR ITENS E DETALHES DA PIZZA
// =====================================================

if ($pedido) {

    $stmt = $pdo->prepare("
        SELECT
            pi.*,
            t.nome AS tamanho_nome,
            b.nome AS borda_nome,
            tm.nome AS massa_nome,
            s1.nome AS sabor1_nome,
            s2.nome AS sabor2_nome

        FROM pedido_itens pi

        LEFT JOIN tamanhos t ON t.id = pi.tamanho_id
        LEFT JOIN bordas b ON b.id = pi.borda_id
        LEFT JOIN tipos_massas tm ON tm.id = pi.massa_id
        LEFT JOIN sabores s1 ON s1.id = pi.sabor1_id
        LEFT JOIN sabores s2 ON s2.id = pi.sabor2_id

        WHERE pi.pedido_id = ?

        ORDER BY pi.id ASC
    ");

    $stmt->execute([$pedido['id']]);
    $itens = $stmt->fetchAll();

    // Buscar personalizações de cada pizza
    foreach ($itens as &$item) {

        $stmtCust = $pdo->prepare("
            SELECT
                pic.metade,
                pic.acao,
                i.nome,
                i.preco_adicional

            FROM pedido_item_customizacoes pic

            INNER JOIN ingredientes i
                ON i.id = pic.ingrediente_id

            WHERE pic.pedido_item_id = ?

            ORDER BY pic.metade, pic.acao, i.nome
        ");

        $stmtCust->execute([$item['id']]);
        $item['customizacoes'] = $stmtCust->fetchAll();

        // Molhos
        $stmtMolhos = $pdo->prepare("
            SELECT
                m.nome,
                m.preco_adicional

            FROM pedido_item_molhos pim

            INNER JOIN molhos m
                ON m.id = pim.molho_id

            WHERE pim.pedido_item_id = ?

            ORDER BY m.nome
        ");

        $stmtMolhos->execute([$item['id']]);
        $item['molhos'] = $stmtMolhos->fetchAll();
    }

    unset($item);
}

// =====================================================
// CONFIGURAÇÃO DOS STATUS
// =====================================================

$statusConfig = [

    'pendente' => [
        'nome' => 'Pedido recebido',
        'cor' => 'warning',
        'icone' => 'bi-clock-history',
        'descricao' => 'Recebemos seu pedido e ele está aguardando preparação.'
    ],

    'em_producao' => [
        'nome' => 'Em produção',
        'cor' => 'info',
        'icone' => 'bi-fire',
        'descricao' => 'Estamos preparando seu pedido!'
    ],

    'pronto' => [
        'nome' => 'Pedido pronto',
        'cor' => 'primary',
        'icone' => 'bi-check-circle',
        'descricao' => 'Seu pedido está pronto! Assim que receber, confirme abaixo.'
    ],

    'finalizado' => [
        'nome' => 'Aguardando recebimento',
        'cor' => 'primary',
        'icone' => 'bi-box-seam',
        'descricao' => 'Seu pedido está pronto para ser recebido.'
    ],

    'entregue' => [
        'nome' => 'Pedido entregue',
        'cor' => 'success',
        'icone' => 'bi-check-circle-fill',
        'descricao' => 'Recebimento confirmado! Obrigado pela preferência.'
    ],

    'cancelado' => [
        'nome' => 'Pedido cancelado',
        'cor' => 'danger',
        'icone' => 'bi-x-circle',
        'descricao' => 'Este pedido foi cancelado.'
    ]
];

$statusAtual = $pedido['status'] ?? '';

$config = $statusConfig[$statusAtual] ?? [
    'nome' => ucfirst($statusAtual),
    'cor' => 'secondary',
    'icone' => 'bi-info-circle',
    'descricao' => 'Consulte as informações do seu pedido.'
];

$podeConfirmar =
    $pedido &&
    in_array($statusAtual, ['pronto', 'finalizado'], true) &&
    !empty($_SESSION['ultimo_pedido_codigo']) &&
    !empty($_SESSION['ultimo_pedido_token']) &&
    hash_equals((string)$_SESSION['ultimo_pedido_codigo'], $codigo) &&
    hash_equals(
        (string)($pedido['token_acesso'] ?? ''),
        (string)($_SESSION['ultimo_pedido_token'] ?? '')
    );

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meu Pedido - Cria Fatia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>

        .pedido-container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .pedido-card {
            border: none;
            border-radius: 18px;
            overflow: hidden;
        }

        .pedido-header {
            background: #181a20;
            color: white;
            padding: 25px;
        }

        .pedido-header h2 {
            font-weight: 800;
        }

        .secao-pedido {
            border: 1px solid #e9ecef;
            border-radius: 14px;
            padding: 22px;
            background: white;
            margin-bottom: 20px;
        }

        .secao-titulo {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 20px;
            color: #252525;
        }

        .item-pizza {
            border: 1px solid #e9ecef;
            border-radius: 13px;
            padding: 20px;
            margin-bottom: 15px;
            background: #fff;
        }

        .item-pizza:last-child {
            margin-bottom: 0;
        }

        .item-pizza-titulo {
            font-weight: 800;
            font-size: 18px;
        }

        .detalhe-linha {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .detalhe-linha:last-child {
            border-bottom: none;
        }

        .detalhe-label {
            color: #777;
            font-size: 14px;
        }

        .detalhe-valor {
            font-weight: 600;
            text-align: right;
            font-size: 14px;
        }

        .status-card {
            border-radius: 15px;
            padding: 25px;
            text-align: center;
        }

        .status-icone {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 34px;
            margin: 0 auto 15px;
        }

        .total-card {
            background: #181a20;
            color: white;
            padding: 22px;
            border-radius: 14px;
        }

        .btn-acao {
            min-height: 52px;
            border-radius: 11px;
            font-weight: 700;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 24px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 9px;
            width: 10px;
            height: 10px;
            background: #dee2e6;
            border-radius: 50%;
        }

        .timeline-item.ativo::before {
            background: #198754;
            box-shadow: 0 0 0 4px #d1e7dd;
        }

        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: -19px;
            top: 20px;
            width: 2px;
            height: calc(100% - 20px);
            background: #dee2e6;
        }

        .timeline-item.ativo:not(:last-child)::after {
            background: #198754;
        }

        @media (max-width: 767px) {

            .pedido-header {
                padding: 20px;
            }

            .secao-pedido {
                padding: 17px;
            }

            .item-pizza {
                padding: 15px;
            }

            .detalhe-linha {
                gap: 8px;
            }

            .detalhe-label,
            .detalhe-valor {
                font-size: 13px;
            }

        }

    </style>

</head>

<body class="bg-light">

<?php include("../includes/header.php"); ?>

<div class="container my-5 pedido-container">

    <?php if ($mensagem): ?>

        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i>

            <?= e($mensagem) ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

    <?php endif; ?>

    <?php if ($erro): ?>

        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle me-2"></i>
            <?= e($erro) ?>
        </div>

    <?php endif; ?>

    <?php if ($pedido): ?>

        <div class="card shadow pedido-card">

            <!-- CABEÇALHO -->

            <div class="pedido-header">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                    <div>

                        <span class="text-warning small fw-bold">
                            CRIA FATIA
                        </span>

                        <h2 class=" text-white mb-1 mt-1">
                            Meu Pedido
                        </h2>

                        <span class="text-white-50">
                            Acompanhe e confira os detalhes da sua compra.
                        </span>

                    </div>

                    <div class="text-md-end">

                        <div class="small text-white-50">
                            Código de acompanhamento
                        </div>

                        <h4 class="fw-bold text-warning mb-0">
                            <?= e($pedido['codigo_acompanhamento']) ?>
                        </h4>

                    </div>

                </div>

            </div>

            <div class="card-body p-3 p-md-4">

                <div class="row g-4">

                    <!-- COLUNA PRINCIPAL -->

                    <div class="col-lg-8">

                        <!-- DADOS DO CLIENTE -->

                        <div class="secao-pedido">

                            <h5 class="secao-titulo">
                                <i class="bi bi-person-circle text-warning me-2"></i>
                                Dados do pedido
                            </h5>

                            <div class="row g-3">

                                <div class="col-md-6">

                                    <div class="detalhe-label">
                                        Cliente
                                    </div>

                                    <strong>
                                        <?= e($pedido['cliente_nome']) ?>
                                    </strong>

                                </div>

                                <div class="col-md-6">

                                    <div class="detalhe-label">
                                        Telefone
                                    </div>

                                    <strong>
                                        <?= e($pedido['cliente_telefone']) ?>
                                    </strong>

                                </div>

                                <div class="col-md-6">

                                    <div class="detalhe-label">
                                        Tipo de pedido
                                    </div>

                                    <strong>
                                        <?= $pedido['tipo_entrega'] === 'retirada'
                                            ? 'Retirada no balcão'
                                            : 'Entrega em domicílio' ?>
                                    </strong>

                                </div>

                                <div class="col-md-6">

                                    <div class="detalhe-label">
                                        Data do pedido
                                    </div>

                                    <strong>
                                        <?= !empty($pedido['criado_em'])
                                            ? date('d/m/Y H:i', strtotime($pedido['criado_em']))
                                            : 'Não informado' ?>
                                    </strong>

                                </div>

                                <?php if (!empty($pedido['endereco']) && $pedido['tipo_entrega'] === 'entrega'): ?>

                                    <div class="col-12">

                                        <div class="detalhe-label">
                                            Endereço de entrega
                                        </div>

                                        <strong>
                                            <?= nl2br(e($pedido['endereco'])) ?>
                                        </strong>

                                    </div>

                                <?php endif; ?>

                                <?php if (!empty($pedido['observacoes'])): ?>

                                    <div class="col-12">

                                        <div class="detalhe-label">
                                            Observações
                                        </div>

                                        <strong>
                                            <?= nl2br(e($pedido['observacoes'])) ?>
                                        </strong>

                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                        <!-- ITENS DO PEDIDO -->

                        <div class="secao-pedido">

                            <h5 class="secao-titulo">
                                <i class="bi bi-receipt text-warning me-2"></i>
                                O que você pediu
                            </h5>

                            <?php if (empty($itens)): ?>

                                <div class="alert alert-warning">
                                    Não foram encontrados itens para este pedido.
                                </div>

                            <?php else: ?>

                                <?php foreach ($itens as $index => $item): ?>

                                    <div class="item-pizza">

                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">

                                            <div class="item-pizza-titulo">

                                                <i class="bi bi-pie-chart-fill text-warning me-2"></i>

                                                Pizza <?= $index + 1 ?>

                                            </div>

                                            <span class="badge bg-warning text-dark px-3 py-2">
                                                <?= e($item['tamanho_nome'] ?? 'Tamanho não informado') ?>
                                            </span>

                                        </div>

                                        <div class="detalhe-linha">

                                            <span class="detalhe-label">
                                                Tamanho
                                            </span>

                                            <span class="detalhe-valor">
                                                <?= e($item['tamanho_nome'] ?? 'Não informado') ?>
                                            </span>

                                        </div>

                                        <div class="detalhe-linha">
                                            <span class="detalhe-label">Tipo de massa</span>
                                            <span class="detalhe-valor"><?= e($item['massa_nome'] ?? 'Não informada') ?></span>
                                        </div>

                                        <div class="detalhe-linha">

                                            <span class="detalhe-label">
                                                Divisão
                                            </span>

                                            <span class="detalhe-valor">
                                                <?= $item['divisao'] === 'meio_a_meio'
                                                    ? 'Meio a meio'
                                                    : 'Inteira' ?>
                                            </span>

                                        </div>

                                        <div class="detalhe-linha">

                                            <span class="detalhe-label">
                                                Ingredientes da pizza
                                            </span>

                                            <span class="detalhe-valor">
                                                <?= e(implode(', ', array_map(function($c){ return $c['nome']; }, array_filter($item['customizacoes'] ?? [], function($c){ return $c['acao'] === 'adicionar'; }))) ?: 'Não informado') ?>
                                            </span>

                                        </div>

                                        <?php if (false): ?>

                                            <div class="detalhe-linha">

                                                <span class="detalhe-label">
                                                    Segundo sabor
                                                </span>

                                                <span class="detalhe-valor">
                                                    <?= e($item['sabor2_nome']) ?>
                                                </span>

                                            </div>

                                        <?php endif; ?>

                                        <div class="detalhe-linha">

                                            <span class="detalhe-label">
                                                Borda recheada
                                            </span>

                                            <span class="detalhe-valor">
                                                <?= e($item['borda_nome'] ?? 'Não informada') ?>
                                            </span>

                                        </div>

                                        <!-- INGREDIENTES PERSONALIZADOS -->

                                        <?php

                                        $adicionados = array_filter(
                                            $item['customizacoes'],
                                            function($c) {
                                                return $c['acao'] === 'adicionar';
                                            }
                                        );

                                        $removidos = array_filter(
                                            $item['customizacoes'],
                                            function($c) {
                                                return $c['acao'] === 'remover';
                                            }
                                        );

                                        ?>

                                        <?php if (!empty($adicionados)): ?>

                                            <div class="mt-3">

                                                <div class="small fw-bold text-success mb-2">

                                                    <i class="bi bi-plus-circle me-1"></i>
                                                    Ingredientes adicionais

                                                </div>

                                                <?php foreach ($adicionados as $extra): ?>

                                                    <div class="detalhe-linha">

                                                        <span class="detalhe-label">

                                                            <?= e($extra['nome']) ?>

                                                            <small>
                                                                (Metade <?= (int)$extra['metade'] ?>)
                                                            </small>

                                                        </span>

                                                        <span class="detalhe-valor text-success">
                                                            + <?= moeda($extra['preco_adicional']) ?>
                                                        </span>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        <?php endif; ?>

                                        <?php if (!empty($removidos)): ?>

                                            <div class="mt-3">

                                                <div class="small fw-bold text-danger mb-2">

                                                    <i class="bi bi-dash-circle me-1"></i>
                                                    Ingredientes removidos

                                                </div>

                                                <?php foreach ($removidos as $extra): ?>

                                                    <div class="detalhe-linha">

                                                        <span class="detalhe-label">
                                                            <?= e($extra['nome']) ?>
                                                            <small>(Metade <?= (int)$extra['metade'] ?>)</small>
                                                        </span>

                                                        <span class="detalhe-valor text-danger">
                                                            Removido
                                                        </span>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        <?php endif; ?>

                                        <!-- MOLHOS -->

                                        <?php if (!empty($item['molhos'])): ?>

                                            <div class="mt-3">

                                                <div class="small fw-bold text-primary mb-2">

                                                    <i class="bi bi-droplet-fill me-1"></i>
                                                    Molhos especiais

                                                </div>

                                                <?php foreach ($item['molhos'] as $molho): ?>

                                                    <div class="detalhe-linha">

                                                        <span class="detalhe-label">
                                                            <?= e($molho['nome']) ?>
                                                        </span>

                                                        <span class="detalhe-valor text-success">
                                                            + <?= moeda($molho['preco_adicional']) ?>
                                                        </span>

                                                    </div>

                                                <?php endforeach; ?>

                                            </div>

                                        <?php endif; ?>

                                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">

                                            <strong>
                                                Valor do item
                                            </strong>

                                            <strong class="text-success fs-5">
                                                <?= moeda($item['preco_item']) ?>
                                            </strong>

                                        </div>

                                    </div>

                                <?php endforeach; ?>

                            <?php endif; ?>

                        </div>

                        <!-- TOTAL DO PEDIDO -->

                        <div class="total-card mb-4">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <span class="text-white">
                                        Total do pedido
                                    </span>

                                    <h4 class="text-white fw-bold mb-0">
                                        Valor final
                                    </h4>

                                </div>

                                <h2 class="text-success fw-bold mb-0">
                                    <?= moeda($pedido['valor_total']) ?>
                                </h2>

                            </div>

                            <?php if ((float)($pedido['taxa_entrega'] ?? 0) > 0): ?>
                                <div class="mt-2 pt-2 border-top border-secondary d-flex justify-content-between small text-white-50">
                                    <span>Taxa de Entrega inclusa:</span>
                                    <span><?= moeda($pedido['taxa_entrega']) ?></span>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- DETALHES DO PAGAMENTO & PIX -->
                        <div class="secao-pedido mb-4">
                            <h5 class="secao-titulo d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-credit-card-2-front text-warning me-2"></i>Forma de Pagamento</span>
                                <?php
                                $forma = $pedido['forma_pagamento'] ?? 'pix';
                                $badg = ['pix' => 'bg-success', 'cartao' => 'bg-primary', 'dinheiro' => 'bg-dark'];
                                ?>
                                <span class="badge <?= $badg[$forma] ?? 'bg-secondary' ?> text-uppercase px-3 py-1">
                                    <?= htmlspecialchars(strtoupper($forma)) ?>
                                </span>
                            </h5>

                            <?php if (($pedido['forma_pagamento'] ?? 'pix') === 'pix'): ?>
                                <?php
                                $pixPayload = "00020126580014br.gov.bcb.pix0136bellaroza-pix-cf@cria-fatia.com5204000053039865405" . number_format($pedido['valor_total'], 2, '.', '') . "5802BR5919BELLA ROZA PIZZARIA6009SAO PAULO62070503" . $pedido['codigo_acompanhamento'] . "6304";
                                ?>
                                <div class="bg-light p-4 rounded-3 text-center border">
                                    <h6 class="fw-bold text-dark mb-1"><i class="bi bi-qr-code me-1 text-success"></i> Pague com Pix Instantâneo</h6>
                                    <p class="text-muted small mb-3">Aponte a câmera do aplicativo do seu banco ou use o Copia e Cola:</p>
                                    <div class="d-inline-block bg-white p-2 rounded border shadow-sm mb-3">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($pixPayload) ?>" alt="QR Code Pix" class="img-fluid" style="width: 170px; height: 170px;">
                                    </div>
                                    <div class="input-group">
                                        <input type="text" id="pixCodeInput" class="form-control form-control-sm text-center font-monospace" value="<?= htmlspecialchars($pixPayload) ?>" readonly>
                                        <button class="btn btn-warning btn-sm fw-bold px-3 text-dark" type="button" onclick="copiarPix()">
                                            <i class="bi bi-clipboard-check me-1"></i> <span id="pixBtnText">Copiar Pix</span>
                                        </button>
                                    </div>
                                    <div class="text-muted small mt-2"><i class="bi bi-shield-check text-success me-1"></i> Chave Pix cadastrada: <strong>bellaroza-pix-cf@cria-fatia.com</strong></div>
                                </div>
                            <?php elseif (($pedido['forma_pagamento'] ?? '') === 'dinheiro'): ?>
                                <div class="alert alert-warning mb-0 p-3">
                                    <div class="fw-bold mb-1"><i class="bi bi-cash me-1"></i> Pagamento em Dinheiro na Entrega / Retirada</div>
                                    <div class="small text-muted">
                                        <?php if (!empty($pedido['troco_para'])): ?>
                                            Troco solicitado para: <strong>R$ <?= number_format($pedido['troco_para'], 2, ',', '.') ?></strong>
                                            (Troco a devolver: <strong>R$ <?= number_format($pedido['troco_para'] - $pedido['valor_total'], 2, ',', '.') ?></strong>)
                                        <?php else: ?>
                                            Não é necessário troco (valor exato em espécie).
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0 p-3">
                                    <div class="fw-bold mb-1"><i class="bi bi-credit-card me-1"></i> Cartão de Débito ou Crédito</div>
                                    <div class="small text-muted">A maquininha de cartão será apresentada no momento da entrega ou no balcão.</div>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <!-- COLUNA DIREITA: STATUS E AÇÕES -->

                    <div class="col-lg-4">

                        <div class="position-sticky" style="top: 20px;">

                            <!-- STATUS -->

                            <div class="secao-pedido">

                                <h5 class="secao-titulo">
                                    <i class="bi bi-activity text-warning me-2"></i>
                                    Situação do pedido
                                </h5>

                                <div class="status-card bg-<?= e($config['cor']) ?> bg-opacity-10">

                                    <div class="status-icone bg-<?= e($config['cor']) ?> bg-opacity-10 text-<?= e($config['cor']) ?>">

                                        <i class="bi <?= e($config['icone']) ?>"></i>

                                    </div>

                                    <h5 class="fw-bold">
                                        <?= e($config['nome']) ?>
                                    </h5>

                                    <p class="text-muted small mb-0">
                                        <?= e($config['descricao']) ?>
                                    </p>

                                </div>

                                <hr>

                                <div class="timeline">

                                    <?php

                                    $etapas = [
                                        'pendente' => 'Pedido recebido',
                                        'em_producao' => 'Em produção',
                                        'pronto' => 'Pedido pronto',
                                        'entregue' => 'Pedido entregue'
                                    ];

                                    $ordem = [
                                        'pendente' => 1,
                                        'em_producao' => 2,
                                        'pronto' => 3,
                                        'finalizado' => 3,
                                        'entregue' => 4
                                    ];

                                    $posicao = $ordem[$statusAtual] ?? 0;

                                    ?>

                                    <?php if ($statusAtual === 'cancelado'): ?>

                                        <div class="timeline-item ativo">
                                            <strong class="text-danger">Pedido cancelado</strong>
                                        </div>

                                    <?php else: ?>

                                        <?php foreach ($etapas as $chave => $nomeEtapa): ?>

                                            <div class="timeline-item <?= $posicao >= $ordem[$chave] ? 'ativo' : '' ?>">

                                                <div class="fw-bold small">
                                                    <?= e($nomeEtapa) ?>
                                                </div>

                                                <small class="text-muted">

                                                    <?= $posicao >= $ordem[$chave]
                                                        ? 'Etapa concluída'
                                                        : 'Aguardando' ?>

                                                </small>

                                            </div>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                </div>

                            </div>

                            <!-- AÇÕES DO CLIENTE -->

                            <div class="secao-pedido">

                                <h5 class="secao-titulo">
                                    <i class="bi bi-gear text-warning me-2"></i>
                                    Ações do pedido
                                </h5>

                                <!-- ATUALIZAR STATUS -->

                                <a href="pedido.php?codigo=<?= urlencode($codigo) ?>"
                                   class="btn btn-outline-primary btn-lg w-100 btn-acao mb-3">

                                    <i class="bi bi-arrow-clockwise me-2"></i>

                                    Atualizar Status

                                </a>

                                <!-- CONFIRMAR RECEBIMENTO -->

                                <?php if ($podeConfirmar): ?>

                                    <div class="alert alert-warning small">

                                        <i class="bi bi-info-circle-fill me-1"></i>

                                        Você recebeu seu pedido? Confirme abaixo para finalizar a compra.

                                    </div>

                                    <form method="POST"
                                          action="pedido.php?codigo=<?= urlencode($codigo) ?>"
                                          onsubmit="return confirm('Você confirma que recebeu seu pedido? Ele será marcado como entregue.');">

                                        <input type="hidden"
                                               name="codigo"
                                               value="<?= e($codigo) ?>">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= e($_SESSION['csrf_pedido']) ?>">

                                        <button type="submit"
                                                name="confirmar_recebimento"
                                                value="1"
                                                class="btn btn-success btn-lg w-100 btn-acao">

                                            <i class="bi bi-check-circle-fill me-2"></i>

                                            Confirmar que Recebi

                                        </button>

                                    </form>

                                <?php elseif ($statusAtual === 'entregue'): ?>

                                    <div class="alert alert-success text-center mb-0">

                                        <i class="bi bi-check-circle-fill fs-2 d-block mb-2"></i>

                                        <strong>
                                            Recebimento confirmado!
                                        </strong>

                                        <p class="small mb-0 mt-2">
                                            Seu pedido já consta como entregue.
                                        </p>

                                    </div>

                                <?php elseif ($statusAtual === 'cancelado'): ?>

                                    <div class="alert alert-danger text-center mb-0">
                                        Este pedido foi cancelado.
                                    </div>

                                <?php elseif (in_array($statusAtual, ['pronto', 'finalizado'], true)): ?>

                                    <div class="alert alert-warning small mb-0">
                                        Para confirmar o recebimento, acesse o pedido pelo mesmo navegador utilizado na compra.
                                    </div>

                                <?php else: ?>

                                    <div class="alert alert-info text-center small mb-0">

                                        <i class="bi bi-hourglass-split me-1"></i>

                                        A confirmação de recebimento estará disponível quando seu pedido estiver pronto.

                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    <?php else: ?>

        <!-- BUSCA DO PEDIDO -->

        <div class="card pedido-card shadow-sm p-4 p-md-5 text-center">

            <div class="mb-3">

                <i class="bi bi-search text-warning" style="font-size: 55px;"></i>

            </div>

            <h2 class="fw-bold">
                Acompanhe seu pedido
            </h2>

            <p class="text-muted">
                Informe o código de acompanhamento para visualizar os detalhes da sua compra.
            </p>

            <form method="GET" action="pedido.php" class="mt-3">

                <div class="input-group input-group-lg">

                    <input type="text"
                           name="codigo"
                           class="form-control"
                           placeholder="Ex: CF-8K29P5"
                           value="<?= e($codigo) ?>"
                           required>

                    <button type="submit" class="btn btn-warning fw-bold">
                        Buscar
                    </button>

                </div>

            </form>

            <?php if (!empty($codigo)): ?>

                <div class="alert alert-danger mt-3 mb-0">
                    Não encontramos um pedido com esse código. Verifique e tente novamente.
                </div>

            <?php endif; ?>

            <div class="mt-4">

                <a href="../index.php" class="btn btn-outline-secondary">
                    Voltar à página inicial
                </a>

            </div>

        </div>

    <?php endif; ?>

</div>

<script>
function copiarPix() {
    const input = document.getElementById('pixCodeInput');
    if (!input) return;
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = document.getElementById('pixBtnText');
        if (btn) {
            btn.textContent = 'Copiado!';
            setTimeout(() => btn.textContent = 'Copiar Pix', 2500);
        }
    }).catch(() => {
        input.select();
        document.execCommand('copy');
    });
}

<?php if ($pedido && in_array($statusAtual, ['pendente', 'em_producao', 'pronto'])): ?>
let pollSec = 15;
setInterval(() => {
    pollSec--;
    if (pollSec <= 0) {
        window.location.reload();
    }
}, 1000);
<?php endif; ?>
</script>

<?php include("../includes/footer.php"); ?>
