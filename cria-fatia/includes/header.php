<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/funcoes.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cria Fatia - Monte do seu jeito, peça sem erro!</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
  <div class="container py-2">
    <!-- LOGO MODERNO E PROFISSIONAL -->
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>">
        <div class="cf-logo-icon rounded-3 p-1 d-flex align-items-center justify-content-center" style="background: rgba(255, 183, 77, 0.15);">
            <i class="bi bi-pie-chart-fill text-warning fs-3"></i>
        </div>
        <div class="d-flex flex-column">
            <span class="fs-4 fw-extrabold text-warning lh-1 tracking-tight">Cria Fatia</span>
            <span class="text-white-50 fw-semibold" style="font-size: 0.68rem; letter-spacing: 1px;">PIZZA LAB & CO.</span>
        </div>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>"><i class="bi bi-house-door me-1"></i> Início</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>#cardapio"><i class="bi bi-book me-1"></i> Cardápio</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>cliente/montar.php"><i class="bi bi-sliders me-1"></i> Montar Pizza</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>cliente/pedido.php"><i class="bi bi-ticket-detailed me-1"></i> Acompanhar Pedido</a></li>

        <?php if (isset($_SESSION['usuario_id'])): ?>
            <?php if (in_array($_SESSION['usuario_perfil'], ['admin', 'atendimento'])): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>atendimento/index.php"><i class="bi bi-headset me-1"></i> Atendimento</a></li>
            <?php endif; ?>
            <?php if (in_array($_SESSION['usuario_perfil'], ['admin', 'cozinha'])): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>cozinha/index.php"><i class="bi bi-fire me-1"></i> Cozinha</a></li>
            <?php endif; ?>
            <?php if ($_SESSION['usuario_perfil'] === 'admin'): ?>
                <li class="nav-item"><a class="nav-link text-warning" href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Gestão</a></li>
            <?php endif; ?>
        <?php endif; ?>
      </ul>

      <!-- Ações Direita: Login / Usuário logado -->
      <ul class="navbar-nav align-items-lg-center gap-2">
        <?php if (isset($_SESSION['usuario_id'])): ?>
            <li class="nav-item"><span class="nav-link text-white-50 small">Olá, <strong class="text-white"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></span></li>
            <li class="nav-item"><a class="btn btn-outline-danger btn-sm rounded-pill px-3" href="<?php echo BASE_URL; ?>logout.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
        <?php else: ?>
            <li class="nav-item"><a class="btn btn-warning btn-sm rounded-pill px-3 fw-bold" href="<?php echo BASE_URL; ?>login.php"><i class="bi bi-person me-1"></i> Entrar</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container my-5">
<?php if (isset($_GET['erro']) && $_GET['erro'] === 'acesso_negado'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-shield-exclamation me-2"></i> Você não tem permissão para acessar esta área com o seu perfil atual.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
