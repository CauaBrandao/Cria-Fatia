<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';
checarPerfil(['admin']);
require_once __DIR__ . '/../includes/header.php';

$dataInicio = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['inicio'] ?? '') ? $_GET['inicio'] : date('Y-m-d');
$dataFim = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fim'] ?? '') ? $_GET['fim'] : date('Y-m-d');
if ($dataInicio > $dataFim) { [$dataInicio, $dataFim] = [$dataFim, $dataInicio]; }

// Indicadores Operacionais da Gestão
$totalPedidos = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$faturamentoTotal = $pdo->query("SELECT SUM(valor_total) FROM pedidos WHERE status != 'cancelado'")->fetchColumn() ?: 0;
$pedidosHoje = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE DATE(criado_em) = CURDATE()")->fetchColumn();
$pedidosFinalizados = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status IN ('finalizado', 'entregue')")->fetchColumn();

// Dados para os Gráficos Analíticos (Chart.js)
$vendasDias = $pdo->query("
    SELECT DATE_FORMAT(MIN(criado_em), '%d/%m') as dia, COUNT(id) as total_pedidos, COALESCE(SUM(valor_total), 0) as faturamento
    FROM pedidos
    WHERE status != 'cancelado'
    GROUP BY DATE(criado_em)
    ORDER BY MIN(criado_em) ASC
    LIMIT 7
")->fetchAll();

$diasLabels = array_column($vendasDias, 'dia');
$faturamentoData = array_map('floatval', array_column($vendasDias, 'faturamento'));
$pedidosData = array_map('intval', array_column($vendasDias, 'total_pedidos'));

$stmtIngredientes = $pdo->prepare("
    SELECT i.nome, i.tipo_item, COUNT(pic.id) as total
    FROM pedido_item_customizacoes pic
    JOIN ingredientes i ON pic.ingrediente_id = i.id
    JOIN pedido_itens pi ON pi.id = pic.pedido_item_id
    JOIN pedidos p ON p.id = pi.pedido_id
    WHERE p.status <> 'cancelado' AND p.criado_em >= ? AND p.criado_em < DATE_ADD(?, INTERVAL 1 DAY) AND pic.acao = 'adicionar'
    GROUP BY i.id, i.nome, i.tipo_item
    ORDER BY total DESC
    LIMIT 20
");
$stmtIngredientes->execute([$dataInicio, $dataFim]);
$topIngredientes = $stmtIngredientes->fetchAll();
$stmtResumo = $pdo->prepare("SELECT COUNT(DISTINCT p.id) pedidos, COUNT(pi.id) pizzas FROM pedidos p LEFT JOIN pedido_itens pi ON pi.pedido_id=p.id WHERE p.status <> 'cancelado' AND p.criado_em >= ? AND p.criado_em < DATE_ADD(?, INTERVAL 1 DAY)");
$stmtResumo->execute([$dataInicio, $dataFim]); $resumoPeriodo = $stmtResumo->fetch();

$tiposEntrega = $pdo->query("
    SELECT tipo_entrega, COUNT(*) as qtd
    FROM pedidos
    WHERE status != 'cancelado'
    GROUP BY tipo_entrega
")->fetchAll();
?>

<h2 class="fw-bold mb-3"><i class="bi bi-speedometer2 me-2 text-warning"></i>Painel da Gestão - Bella Roza Pizzaria</h2>
<form class="card card-body border-0 shadow-sm mb-4" method="get"><div class="row g-3 align-items-end"><div class="col-md-4"><label class="form-label fw-bold">Início</label><input class="form-control" type="date" name="inicio" value="<?= escapar($dataInicio) ?>"></div><div class="col-md-4"><label class="form-label fw-bold">Fim</label><input class="form-control" type="date" name="fim" value="<?= escapar($dataFim) ?>"></div><div class="col-md-4"><button class="btn btn-warning fw-bold w-100">Filtrar relatório</button></div></div></form>
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="dashboard.php" class="btn btn-dark fw-bold"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
    <a href="tamanho.php" class="btn btn-outline-dark fw-bold"><i class="bi bi-aspect-ratio me-1"></i> Tamanhos</a>
    <a href="massas.php" class="btn btn-outline-dark fw-bold"><i class="bi bi-circle-half me-1"></i> Massas</a>
    <a href="bordas.php" class="btn btn-outline-dark fw-bold"><i class="bi bi-circle me-1"></i> Bordas</a>
    <a href="ingredientes.php" class="btn btn-outline-dark fw-bold"><i class="bi bi-egg-fried me-1"></i> Ingredientes</a>
    <a href="molhos.php" class="btn btn-outline-dark fw-bold"><i class="bi bi-droplet-fill me-1"></i> Molhos</a>
    <a href="sabores.php" class="btn btn-outline-dark fw-bold"><i class="bi bi-list-nested me-1"></i> Sabores</a>
    <a href="caixa.php" class="btn btn-success fw-bold"><i class="bi bi-cash-coin me-1"></i> Controle de Caixa</a>
    <a href="pedidos.php" class="btn btn-outline-dark fw-bold"><i class="bi bi-receipt me-1"></i> Todos os Pedidos</a>
</div>


<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-dark-50">Total de Pedidos</h6>
                <h2 class="fw-bold mb-0"><?php echo $totalPedidos; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-dark-50">Faturamento Bruto</h6>
                <h2 class="fw-bold mb-0">R$ <?php echo number_format($faturamentoTotal, 2, ',', '.'); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-dark shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-dark-50">Pedidos Hoje</h6>
                <h2 class="fw-bold mb-0"><?php echo $pedidosHoje; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-dark-50">Finalizados com Sucesso</h6>
                <h2 class="fw-bold mb-0"><?php echo $pedidosFinalizados; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- GRÁFICOS ANALÍTICOS -->
<div class="row mb-4">
    <div class="col-lg-8 mb-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold"><i class="bi bi-graph-up text-primary me-2"></i>Faturamento e Volume de Pedidos</span>
                <span class="badge bg-light text-muted border">Performance Recente</span>
            </div>
            <div class="card-body p-3">
                <canvas id="graficoVendas" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <span class="fw-bold"><i class="bi bi-bar-chart text-warning me-2"></i>Ingredientes e acompanhamentos</span>
            </div>
            <div class="card-body p-3 d-flex flex-column justify-content-center align-items-center">
                <canvas id="graficoIngredientes" style="max-height: 280px;"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-dark text-white fw-bold py-3"><i class="bi bi-gear me-2"></i>Gestão do Cardápio & Parâmetros do Sistema</div>
            <div class="card-body p-4">
                <div class="d-flex flex-wrap gap-3">
                    <a href="tamanho.php" class="btn btn-outline-dark btn-lg px-4"><i class="bi bi-aspect-ratio me-2 text-warning"></i> Gerenciar Tamanhos</a>
                    <a href="massas.php" class="btn btn-outline-dark btn-lg px-4"><i class="bi bi-circle-half me-2 text-warning"></i> Gerenciar Tipos de Massa</a>
                    <a href="bordas.php" class="btn btn-outline-dark btn-lg px-4"><i class="bi bi-circle-square me-2 text-warning"></i> Gerenciar Bordas</a>
                    <a href="ingredientes.php" class="btn btn-outline-dark btn-lg px-4"><i class="bi bi-basket-fill me-2 text-warning"></i> Gerenciar Ingredientes</a>
                    <a href="molhos.php" class="btn btn-outline-dark btn-lg px-4"><i class="bi bi-droplet-fill me-2 text-warning"></i> Gerenciar Molhos</a>
                    <a href="sabores.php" class="btn btn-outline-dark btn-lg px-4"><i class="bi bi-pie-chart-fill me-2 text-warning"></i> Gerenciar Sabores</a>
                    <a href="caixa.php" class="btn btn-outline-success btn-lg px-4"><i class="bi bi-cash-coin me-2 text-success"></i> Controle de Caixa</a>
                    <a href="pedidos.php" class="btn btn-outline-dark btn-lg px-4"><i class="bi bi-receipt me-2 text-warning"></i> Histórico de Pedidos</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4"><div class="card-header bg-white fw-bold">Relatório detalhado — <?= (int)$resumoPeriodo['pedidos'] ?> pedidos, <?= (int)$resumoPeriodo['pizzas'] ?> pizzas</div><div class="table-responsive"><table class="table table-striped mb-0"><thead><tr><th>Item</th><th>Grupo</th><th>Quantidade</th></tr></thead><tbody><?php foreach($topIngredientes as $item): ?><tr><td><?= escapar($item['nome']) ?></td><td><?= $item['tipo_item'] === 'acompanhamento' ? 'Acompanhamento' : 'Ingrediente' ?></td><td><?= (int)$item['total'] ?> seleções</td></tr><?php endforeach; ?><?php if(!$topIngredientes): ?><tr><td colspan="3" class="text-center text-muted py-4">Sem itens no período.</td></tr><?php endif; ?></tbody></table></div><div class="card-footer small text-muted">Quantidade representa o número de seleções registradas nos itens dos pedidos; não representa peso.</div></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctxVendas = document.getElementById('graficoVendas');
if (ctxVendas) {
    new Chart(ctxVendas, {
        type: 'bar',
        data: {
            labels: <?= json_encode($diasLabels ?: [date('d/m')]) ?>,
            datasets: [
                {
                    label: 'Faturamento (R$)',
                    data: <?= json_encode($faturamentoData ?: [0]) ?>,
                    backgroundColor: 'rgba(230, 81, 0, 0.75)',
                    borderColor: '#e65100',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    yAxisID: 'y'
                },
                {
                    label: 'Qtd Pedidos',
                    data: <?= json_encode($pedidosData ?: [0]) ?>,
                    type: 'line',
                    borderColor: '#0d6efd',
                    backgroundColor: '#0d6efd',
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { type: 'linear', display: true, position: 'left', beginAtZero: true, title: { display: true, text: 'R$' } },
                y1: { type: 'linear', display: true, position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, title: { display: true, text: 'Pedidos' } }
            }
        }
    });
}

const ctxIng = document.getElementById('graficoIngredientes');
if (ctxIng) {
    new Chart(ctxIng, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($topIngredientes, 'nome') ?: ['Nenhum pedido']) ?>,
            datasets: [{
                data: <?= json_encode(array_map('intval', array_column($topIngredientes, 'total')) ?: [1]) ?>,
                backgroundColor: <?= json_encode(array_map(fn($i) => $i['tipo_item'] === 'acompanhamento' ? '#0dcaf0' : '#ffc107', $topIngredientes) ?: ['#adb5bd']) ?>
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { autoSkip: false, maxRotation: 45 } } }
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
