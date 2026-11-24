<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (!empty($_SESSION['cliente_id'])) { header('Location: conta.php'); exit; }
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) {
        $erro = 'Sessão expirada. Atualize a página e tente novamente.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $stmt = $pdo->prepare('SELECT id, nome, email, telefone, senha FROM clientes WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $cliente = $stmt->fetch();
        if ($cliente && password_verify($_POST['senha'] ?? '', $cliente['senha'])) {
            session_regenerate_id(true);
            $_SESSION['cliente_id'] = (int)$cliente['id'];
            $_SESSION['cliente_nome'] = $cliente['nome'];
            $_SESSION['cliente_email'] = $cliente['email'];
            $_SESSION['cliente_telefone'] = $cliente['telefone'];
            header('Location: ' . (!empty($_SESSION['dados_pedido']) ? 'finalizar.php' : 'conta.php')); exit;
        }
        $erro = 'E-mail ou senha inválidos.';
    }
}
$token = gerarCsrfToken();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-6 col-lg-5"><div class="card shadow border-0 rounded-4"><div class="card-body p-4 p-md-5">
<h2 class="fw-bold mb-1">Área do cliente</h2><p class="text-muted mb-4">Entre para acompanhar todos os seus pedidos.</p>
<?php if ($erro): ?><div class="alert alert-danger"><?= escapar($erro) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
<div class="mb-3"><label class="form-label fw-bold">E-mail</label><input class="form-control" type="email" name="email" required autocomplete="email"></div>
<div class="mb-4"><label class="form-label fw-bold">Senha</label><input class="form-control" type="password" name="senha" required autocomplete="current-password"></div>
<button class="btn btn-warning fw-bold w-100" type="submit">Entrar</button></form>
<p class="text-center mt-4 mb-0">Ainda não tem conta? <a href="cadastrar.php">Cadastre-se</a></p>
</div></div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

