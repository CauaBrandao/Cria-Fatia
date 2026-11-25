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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
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
<?php
  $perfilLogado = $_SESSION['usuario_perfil'] ?? null;
  $paginaAtual = $_SERVER['SCRIPT_NAME'] ?? '';
  $ativo = fn($trecho) => strpos($paginaAtual, $trecho) !== false ? ' active' : '';
  $nomesPerfil = ['admin' => 'Administrador', 'atendimento' => 'Atendimento', 'cozinha' => 'Cozinha'];
?>
<?php if ($perfilLogado): ?>
      <!-- Menu da equipe: só os painéis que o perfil pode usar -->
      <ul class="navbar-nav mx-auto align-items-lg-center gap-lg-1">
        <?php if (in_array($perfilLogado, ['admin', 'atendimento'], true)): ?>
            <li class="nav-item"><a class="nav-link<?php echo $ativo('/atendimento/'); ?>" href="<?php echo BASE_URL; ?>atendimento/index.php"><i class="bi bi-headset me-1"></i> Atendimento</a></li>
        <?php endif; ?>
        <?php if (in_array($perfilLogado, ['admin', 'cozinha'], true)): ?>
            <li class="nav-item"><a class="nav-link<?php echo $ativo('/cozinha/'); ?>" href="<?php echo BASE_URL; ?>cozinha/index.php"><i class="bi bi-fire me-1"></i> Cozinha</a></li>
        <?php endif; ?>
        <?php if ($perfilLogado === 'admin'): ?>
            <li class="nav-item"><a class="nav-link<?php echo $ativo('/admin/'); ?>" href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Gestão</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav align-items-lg-center">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="cf-avatar"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['usuario_nome'] ?? '?', 0, 1, 'UTF-8'), 'UTF-8')); ?></span>
            <span class="d-flex flex-column lh-sm text-start">
              <span class="small fw-bold text-white"><?php echo htmlspecialchars($_SESSION['usuario_nome'] ?? ''); ?></span>
              <span class="cf-perfil"><?php echo htmlspecialchars($nomesPerfil[$perfilLogado] ?? $perfilLogado); ?></span>
            </span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow">
            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>" target="_blank"><i class="bi bi-shop me-2"></i>Ver site do cliente</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sair</a></li>
          </ul>
        </li>
      </ul>
<?php else: ?>
      <!-- Menu do cliente -->
      <ul class="navbar-nav mx-auto align-items-lg-center">
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>"><i class="bi bi-house-door me-1"></i> Início</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>#cardapio"><i class="bi bi-book me-1"></i> Cardápio</a></li>
        <li class="nav-item"><a class="nav-link<?php echo $ativo('/cliente/montar'); ?>" href="<?php echo BASE_URL; ?>cliente/montar.php"><i class="bi bi-sliders me-1"></i> Montar Pizza</a></li>
        <li class="nav-item"><a class="nav-link<?php echo $ativo('/cliente/pedido'); ?>" href="<?php echo BASE_URL; ?>cliente/pedido.php"><i class="bi bi-ticket-detailed me-1"></i> Acompanhar Pedido</a></li>
      </ul>
      <!-- Ações Direita: Login / Usuário logado -->
      <ul class="navbar-nav align-items-lg-center gap-2">
        <?php if (!empty($_SESSION['cliente_id'])): ?>
            <li class="nav-item"><a class="btn btn-warning btn-sm rounded-pill px-3 fw-bold" href="<?php echo BASE_URL; ?>cliente/conta.php"><i class="bi bi-person-check me-1"></i> Meus pedidos</a></li>
        <?php elseif (!isset($_SESSION['usuario_id'])): ?>
            <li class="nav-item"><a class="btn btn-outline-light btn-sm rounded-pill px-3" href="<?php echo BASE_URL; ?>cliente/entrar.php"><i class="bi bi-person me-1"></i> Cliente</a></li>
        <?php endif; ?>
        <li class="nav-item"><a class="btn btn-sm btn-outline-light rounded-pill px-3" href="<?php echo BASE_URL; ?>login.php"><i class="bi bi-person me-1"></i> Área da equipe</a></li>
      </ul>
<?php endif; ?>
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
