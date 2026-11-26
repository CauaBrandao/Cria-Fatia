<?php
// Processa a autenticação antes de qualquer saída HTML.
// Isso é essencial para que os redirecionamentos header('Location: ...') funcionem.
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $senha = $_POST['senha'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!validarCsrfToken($csrf)) {
        $erro = 'Sua sessão expirou ou o formulário é inválido. Atualize a página e tente novamente.';
    } elseif ($email === '' || $senha === '') {
        $erro = 'Preencha todos os campos!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Informe um endereço de e-mail válido.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, nome, email, senha, perfil FROM usuarios WHERE email = :email LIMIT 1'
            );
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($senha, $user['senha'])) {
                // Regenera o identificador para evitar session fixation.
                session_regenerate_id(true);

                $_SESSION['usuario_id'] = (int) $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                $_SESSION['usuario_perfil'] = $user['perfil'];

                // Gera um novo token CSRF após autenticar.
                unset($_SESSION['csrf_token']);

                if ($user['perfil'] === 'admin') {
                    header('Location: ' . BASE_URL . 'admin/dashboard.php');
                } elseif ($user['perfil'] === 'cozinha') {
                    header('Location: ' . BASE_URL . 'cozinha/index.php');
                } else {
                    header('Location: ' . BASE_URL . 'atendimento/index.php');
                }
                exit;
            }

            $erro = 'E-mail ou senha incorretos!';
        } catch (PDOException $e) {
            error_log('Erro no login Cria Fatia: ' . $e->getMessage());
            $erro = 'Não foi possível realizar o login. Verifique a conexão com o banco de dados.';
        }
    }
}

$token = gerarCsrfToken();

// O cabeçalho só pode ser incluído depois do processamento do POST.
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center py-4 py-md-5">
    <div class="col-lg-5 col-md-7">
        <div class="card cf-login-card border-0 overflow-hidden">
            <div class="card-header cf-login-header text-center py-4">
                <div class="cf-login-icon mx-auto mb-2"><i class="bi bi-shield-lock-fill"></i></div>
                <h1 class="h4 mb-1 fw-bold">Acesso da equipe</h1>
                <p class="mb-0">Entre para gerenciar o Cria Fatia</p>
            </div>
            <div class="card-body p-4 p-md-5">
                <?php if ($erro !== ''): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" autocomplete="on">
                    <input type="hidden" name="csrf_token"
                           value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label fw-bold">E-mail Corporativo:</label>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="admin@briafatia.com" autocomplete="username"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label fw-bold">Senha:</label>
                        <input type="password" id="senha" name="senha" class="form-control"
                               placeholder="Digite sua senha" autocomplete="current-password" required>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 fw-bold py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Entrar no Sistema
                    </button>
                </form>

                <div class="cf-login-access mt-4 p-3 rounded text-center small">
                    <strong class="d-block mb-2">
                        <i class="bi bi-shield-lock me-1"></i>Acessos do Sistema:
                    </strong>
                    <strong>Administrador:</strong> admin@briafatia.com<br>
                    <strong>Atendimento:</strong> atendimento@briafatia.com<br>
                    <strong>Cozinha:</strong> cozinha@briafatia.com
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
