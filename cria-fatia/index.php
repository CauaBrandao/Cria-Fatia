<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/conexao.php';
?>

<!-- HERO SECTION PREMIUM -->
<div class="row align-items-center py-5 my-3">
    <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="d-inline-flex align-items-center px-3 py-1.5 rounded-pill bg-white bg-opacity-15 text-warning fw-bold mb-3 border border-warning border-opacity-25 shadow-sm">
            <i class="bi bi-fire me-2"></i> Pizzas artesanais, feitas com amor!
        </div>
        <h1 class="display-4 fw-extrabold text-warning mb-3 tracking-tight" style="line-height: 1.15;">
            Monte do seu jeito,<br><span class="text-warning">peça sem erro!</span>
        </h1>
        <p class="text-muted lead fs-6 mb-4" style="max-width: 500px;">
            No Cria Fatia você personaliza sua pizza com os melhores ingredientes e recebe uma experiência única!
        </p>
        <div class="d-flex flex-wrap gap-3">
            <a href="cliente/montar.php" class="btn btn-warning btn-lg px-4 py-3 fw-bold shadow-lg d-inline-flex align-items-center">
                <i class="bi bi-sliders me-2"></i> Montar Minha Pizza &rarr;
            </a>
            <a href="#cardapio" class="btn btn-outline-light btn-lg px-4 py-3 fw-bold d-inline-flex align-items-center">
                <i class="bi bi-book me-2"></i> Ver Cardápio
            </a>
        </div>
    </div>
    <div class="col-lg-6 text-center">
        <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1513104890138-7c749659a591?q=80&w=1000&auto=format&fit=crop" alt="Pizza Gourmet Cria Fatia" class="img-fluid rounded-4 shadow-2xl border border-secondary" style="max-height: 420px; object-fit: cover; width: 100%;">
        </div>
    </div>
</div>

<!-- FEATURES BAR -->
<div class="row g-4 py-4 mb-5 border-top border-bottom border-secondary bg-dark bg-opacity-50 rounded-4 px-3">
    <div class="col-md-3 col-6 text-center text-md-start d-flex align-items-center gap-3">
        <div class="fs-2 text-warning"><i class="bi bi-bicycle"></i></div>
        <div>
            <div class="fw-bold text-white small">Entrega Rápida</div>
            <div class="text-muted text-xs">e Segura</div>
        </div>
    </div>
    <div class="col-md-3 col-6 text-center text-md-start d-flex align-items-center gap-3">
        <div class="fs-2 text-warning"><i class="bi bi-shield-check"></i></div>
        <div>
            <div class="fw-bold text-white small">Pagamento Seguro</div>
            <div class="text-muted text-xs">(Pix, Cartão e Dinheiro)</div>
        </div>
    </div>
    <div class="col-md-3 col-6 text-center text-md-start d-flex align-items-center gap-3">
        <div class="fs-2 text-warning"><i class="bi bi-star-fill"></i></div>
        <div>
            <div class="fw-bold text-white small">Qualidade Garantida</div>
            <div class="text-muted text-xs">em cada fatia</div>
        </div>
    </div>
    <div class="col-md-3 col-6 text-center text-md-start d-flex align-items-center gap-3">
        <div class="fs-2 text-warning"><i class="bi bi-headset"></i></div>
        <div>
            <div class="fw-bold text-white small">Suporte Especializado</div>
            <div class="text-muted text-xs">sempre que precisar</div>
        </div>
    </div>
</div>

<!-- ACOMPANHE SEU PEDIDO SECTION -->
<div class="card border-0 shadow-lg rounded-4 p-4 p-md-5 mb-5 bg-gradient-card">
    <div class="row align-items-center">
        <div class="col-lg-5 mb-4 mb-lg-0">
            <h2 class="fw-bold text-white mb-2">Acompanhe seu <span class="text-warning">Pedido</span></h2>
            <p class="text-muted small mb-4">Digite o código do seu pedido para acompanhar o status em tempo real.</p>
            
            <form action="cliente/pedido.php" method="GET" class="d-flex gap-2">
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-muted"><i class="bi bi-ticket-detailed"></i></span>
                    <input type="text" name="codigo" class="form-control" placeholder="Ex: CF-8K29P5" required>
                </div>
                <button type="submit" class="btn btn-warning fw-bold px-4">Consultar</button>
            </form>
            <div class="text-xs text-muted mt-2"><i class="bi bi-shield-lock me-1"></i> Seus dados estão seguros conosco!</div>
        </div>
        
        <div class="col-lg-7">
            <div class="d-flex justify-content-between align-items-center text-center overflow-x-auto py-2">
                <div class="px-2">
                    <div class="rounded-circle bg-warning text-dark d-inline-flex align-items-center justify-content-center shadow mb-2" style="width: 48px; height: 48px;"><i class="bi bi-check-lg fs-5"></i></div>
                    <div class="text-xs fw-bold text-white">Pedido Recebido</div>
                    <div class="text-xs text-warning">19:42</div>
                </div>
                <div class="flex-grow-1 border-top border-secondary border-2 mx-1 d-none d-md-block"></div>
                <div class="px-2 opacity-50">
                    <div class="rounded-circle bg-dark border border-secondary text-muted d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;"><i class="bi bi-fire fs-5"></i></div>
                    <div class="text-xs fw-bold text-white">Em Preparação</div>
                </div>
                <div class="flex-grow-1 border-top border-secondary border-2 mx-1 d-none d-md-block"></div>
                <div class="px-2 opacity-50">
                    <div class="rounded-circle bg-dark border border-secondary text-muted d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;"><i class="bi bi-box-seam fs-5"></i></div>
                    <div class="text-xs fw-bold text-white">Pizza Pronta</div>
                </div>
                <div class="flex-grow-1 border-top border-secondary border-2 mx-1 d-none d-md-block"></div>
                <div class="px-2 opacity-50">
                    <div class="rounded-circle bg-dark border border-secondary text-muted d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;"><i class="bi bi-bicycle fs-5"></i></div>
                    <div class="text-xs fw-bold text-white">Saiu para Entrega</div>
                </div>
                <div class="flex-grow-1 border-top border-secondary border-2 mx-1 d-none d-md-block"></div>
                <div class="px-2 opacity-50">
                    <div class="rounded-circle bg-dark border border-secondary text-muted d-inline-flex align-items-center justify-content-center mb-2" style="width: 48px; height: 48px;"><i class="bi bi-house-check fs-5"></i></div>
                    <div class="text-xs fw-bold text-white">Entregue</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$saboresCardapio = $pdo->query("SELECT * FROM sabores ORDER BY preco ASC")->fetchAll();
?>
<!-- SEÇÃO CARDÁPIO DE SUGESTÕES -->
<div id="cardapio" class="mb-5">
    <div class="text-center mb-4">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2"><i class="bi bi-book me-1"></i> Sugestões da Casa</span>
        <h2 class="display-6 fw-bold text-dark mb-2">Cardápio de Sabores Tradicionais</h2>
        <p class="text-muted">Inspire-se em nossas combinações consagradas ou crie uma pizza 100% personalizada!</p>
    </div>
    <div class="row g-4">
        <?php foreach ($saboresCardapio as $s): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($s['nome']) ?></h5>
                                <span class="badge bg-warning text-dark fs-6">R$ <?= number_format($s['preco'], 2, ',', '.') ?></span>
                            </div>
                            <p class="text-muted small mb-4"><?= htmlspecialchars($s['descricao']) ?></p>
                        </div>
                        <a href="cliente/montar.php" class="btn btn-outline-warning text-dark fw-bold w-100">
                            <i class="bi bi-sliders me-1"></i> Montar Pizza
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
