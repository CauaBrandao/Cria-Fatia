<?php
require_once __DIR__ . '/../config/conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? ''); $email = strtolower(trim($_POST['email'] ?? ''));
    $telefone = trim($_POST['telefone'] ?? ''); $senha = $_POST['senha'] ?? '';
    if (!validarCsrfToken($_POST['csrf_token'] ?? '')) $erro = 'Sessão expirada. Atualize a página.';
    elseif (mb_strlen($nome) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erro = 'Informe nome e e-mail válidos.';
    elseif (strlen($senha) < 8) $erro = 'A senha precisa ter pelo menos 8 caracteres.';
    elseif ($senha !== ($_POST['confirmar_senha'] ?? '')) $erro = 'As senhas não coincidem.';
    if ($erro === '') {
        try {
            $stmt = $pdo->prepare('INSERT INTO clientes (nome, email, senha, telefone) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $telefone ?: null]);
            session_regenerate_id(true); $_SESSION['cliente_id'] = (int)$pdo->lastInsertId();
            $_SESSION['cliente_nome'] = $nome; $_SESSION['cliente_email'] = $email;
            $_SESSION['cliente_telefone'] = $telefone;
            header('Location: ' . (!empty($_SESSION['dados_pedido']) ? 'finalizar.php' : 'conta.php')); exit;
        } catch (PDOException $e) {
            $erro = $e->getCode() === '23000' ? 'Já existe uma conta com este e-mail.' : 'Não foi possível criar a conta.';
        }
    }
}
$token = gerarCsrfToken(); require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center"><div class="col-md-7 col-lg-6"><div class="card shadow border-0 rounded-4"><div class="card-body p-4 p-md-5">
<h2 class="fw-bold mb-1">Criar conta</h2><p class="text-muted mb-4">Seus próximos pedidos ficarão reunidos aqui.</p>
<?php if ($erro): ?><div class="alert alert-danger"><?= escapar($erro) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= escapar($token) ?>">
<div class="mb-3"><label class="form-label fw-bold">Nome</label><input class="form-control" name="nome" required value="<?= escapar($_POST['nome'] ?? '') ?>"></div>
<div class="row"><div class="col-md-6 mb-3"><label class="form-label fw-bold">E-mail</label><input class="form-control" type="email" name="email" required value="<?= escapar($_POST['email'] ?? '') ?>"></div><div class="col-md-6 mb-3"><label class="form-label fw-bold">Telefone</label><input class="form-control" name="telefone" value="<?= escapar($_POST['telefone'] ?? '') ?>"></div></div>
<div class="row"><div class="col-md-6 mb-4"><label class="form-label fw-bold">Senha</label><input class="form-control" type="password" name="senha" minlength="8" required></div><div class="col-md-6 mb-4"><label class="form-label fw-bold">Confirmar senha</label><input class="form-control" type="password" name="confirmar_senha" minlength="8" required></div></div>
<button class="btn btn-warning fw-bold w-100" type="submit">Cadastrar</button></form>
</div></div></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

