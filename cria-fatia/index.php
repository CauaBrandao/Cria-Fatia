<?php
require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO SECTION PREMIUM -->
<div class="row align-items-center py-5 my-3">
    <div class="col-lg-6 mb-4 mb-lg-0">
        <div class="d-inline-flex align-items-center px-3 py-2 rounded-pill cf-selo fw-bold mb-3">
            <i class="bi bi-fire me-2"></i> Pizzas artesanais, feitas com amor!
        </div>
        <h1 class="display-4 fw-bold mb-3" style="line-height: 1.15;">
            Monte do seu jeito,<br><span class="cf-destaque">peça sem erro!</span>
        </h1>
        <p class="text-muted lead fs-6 mb-4" style="max-width: 500px;">
            No Cria Fatia você personaliza sua pizza com os melhores ingredientes e recebe uma experiência única!
        </p>
        <div class="d-flex flex-wrap gap-3">
            <a href="cliente/montar.php" class="btn btn-warning btn-lg px-4 py-3 fw-bold shadow-lg d-inline-flex align-items-center">
                <i class="bi bi-sliders me-2"></i> Montar Minha Pizza &rarr;
            </a>
            <a href="#cardapio" class="btn btn-outline-dark btn-lg px-4 py-3 fw-bold d-inline-flex align-items-center">
                <i class="bi bi-book me-2"></i> Ver Cardápio
            </a>
        </div>
    </div>
    <div class="col-lg-6 text-center">
        <div class="position-relative">
            <img src="https://images.unsplash.com/photo-1513104890138-7c749659a591?q=80&w=1000&auto=format&fit=crop" alt="Pizza Gourmet Cria Fatia" class="img-fluid rounded-4 shadow-lg" style="max-height: 420px; object-fit: cover; width: 100%;">
        </div>
    </div>
</div>

<!-- FEATURES BAR -->
<div class="cf-features row g-3 py-4 px-2 mb-5">
    <?php
    $features = [
        ['bi-bicycle', 'Entrega rápida', 'e segura até você'],
        ['bi-shield-check', 'Pagamento fácil', 'Pix, cartão ou dinheiro'],
        ['bi-star-fill', 'Qualidade garantida', 'em cada fatia'],
        ['bi-headset', 'Atendimento', 'sempre que precisar'],
    ];
    foreach ($features as [$icone, $titulo, $texto]): ?>
        <div class="col-md-3 col-6 d-flex align-items-center gap-3">
            <div class="cf-feature-icon"><i class="bi <?php echo $icone; ?>"></i></div>
            <div>
                <div class="fw-bold text-dark small"><?php echo $titulo; ?></div>
                <div class="cf-feature-text"><?php echo $texto; ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ACOMPANHE SEU PEDIDO -->
<div class="card border-0 rounded-4 p-4 p-md-5 mb-5">
    <div class="row align-items-center g-4">
        <div class="col-lg-5">
            <h2 class="fw-bold mb-2">Acompanhe seu <span class="cf-destaque">pedido</span></h2>
            <p class="text-muted small mb-4">Digite o código que você recebeu ao finalizar a compra.</p>
            <form action="cliente/pedido.php" method="GET" class="d-flex gap-2">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-ticket-detailed"></i></span>
                    <input type="text" name="codigo" class="form-control" placeholder="Ex: CF-8K29P5" required>
                </div>
                <button type="submit" class="btn btn-warning fw-bold px-4">Consultar</button>
            </form>
            <div class="small text-muted mt-2"><i class="bi bi-shield-lock me-1"></i> Seus dados estão seguros conosco.</div>
        </div>
        <div class="col-lg-7">
            <div class="cf-timeline">
                <?php
                $etapas = [
                    ['bi-check-lg', 'Pedido recebido'],
                    ['bi-fire', 'Em preparo'],
                    ['bi-box-seam', 'Pizza pronta'],
                    ['bi-bicycle', 'Saiu para entrega'],
                    ['bi-house-check', 'Entregue'],
                ];
                foreach ($etapas as $i => [$icone, $nome]): ?>
                    <div class="cf-etapa<?php echo $i === 0 ? ' ativa' : ''; ?>">
                        <div class="cf-etapa-icone"><i class="bi <?php echo $icone; ?>"></i></div>
                        <div class="cf-etapa-nome"><?php echo $nome; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="text-center small text-muted mt-3 mb-0">Exemplo das etapas que você acompanha em tempo real.</p>
        </div>
    </div>
</div>

<?php
$saboresCardapio = $pdo->query("SELECT * FROM sabores ORDER BY preco ASC, nome ASC")->fetchAll();
$ingredientesCardapio = $pdo->query("SELECT id, nome, preco_adicional FROM ingredientes WHERE disponivel = 1")->fetchAll();
$ingredientesPorId = array_column($ingredientesCardapio, null, 'id');
$menorTamanho = $pdo->query("SELECT nome, preco_base FROM tamanhos WHERE disponivel = 1 ORDER BY preco_base ASC LIMIT 1")->fetch();
?>
<!-- CARDÁPIO -->
<div id="cardapio" class="mb-5">
    <div class="text-center mb-4">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2"><i class="bi bi-book me-1"></i> Sugestões da casa</span>
        <h2 class="display-6 fw-bold mb-2">Nosso cardápio</h2>
        <p class="text-muted mb-0">Escolha um sabor e ajuste do seu jeito: os ingredientes já vêm marcados no montador.</p>
    </div>
    <div class="row g-4">
        <?php foreach ($saboresCardapio as $s):
            $idsSabor = ingredientesDoSabor($pdo, $s, $ingredientesCardapio);
            $precoDesde = (float)($menorTamanho['preco_base'] ?? 0);
            foreach ($idsSabor as $idIng) { $precoDesde += (float)$ingredientesPorId[$idIng]['preco_adicional']; }
        ?>
            <div class="col-md-6 col-lg-4">
                <a href="cliente/montar.php?sabor=<?php echo (int)$s['id']; ?>" class="cf-sabor card h-100 border-0 rounded-4 overflow-hidden text-decoration-none">
                    <div class="cf-sabor-topo">
                        <i class="bi bi-pie-chart-fill"></i>
                        <span class="cf-sabor-nome"><?php echo htmlspecialchars($s['nome']); ?></span>
                    </div>
                    <div class="card-body p-4 d-flex flex-column">
                        <p class="text-muted small mb-3"><?php echo htmlspecialchars($s['descricao']); ?></p>
                        <?php if ($idsSabor): ?>
                            <div class="d-flex flex-wrap gap-1 mb-3">
                                <?php foreach ($idsSabor as $idIng): ?>
                                    <span class="cf-chip"><?php echo htmlspecialchars($ingredientesPorId[$idIng]['nome']); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="mt-auto d-flex justify-content-between align-items-end">
                            <div>
                                <div class="small text-muted">a partir de</div>
                                <div class="fs-4 fw-bold text-dark"><?php echo formatarMoeda($precoDesde); ?></div>
                            </div>
                            <span class="btn btn-warning fw-bold"><i class="bi bi-sliders me-1"></i> Montar esta</span>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
        <div class="col-md-6 col-lg-4">
            <a href="cliente/montar.php" class="cf-sabor cf-sabor-livre card h-100 border-0 rounded-4 text-decoration-none d-flex align-items-center justify-content-center text-center p-4">
                <div>
                    <i class="bi bi-stars fs-1 d-block mb-2"></i>
                    <div class="fs-5 fw-bold text-dark">Criar do zero</div>
                    <p class="text-muted small mb-0">Escolha cada ingrediente você mesmo.</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
