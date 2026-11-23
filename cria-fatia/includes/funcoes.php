<?php
// Funções auxiliares e de segurança do sistema Cria Fatia

function sanitizeInput($data) {
    if (is_null($data)) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function formatarMoeda($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function gerarCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function checarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function checarPerfil($perfisPermitidos = []) {
    checarLogin();
    if (!in_array($_SESSION['usuario_perfil'], $perfisPermitidos)) {
        header('Location: ' . BASE_URL . 'index.php?erro=acesso_negado');
        exit;
    }
}

// Funções de Auditoria e Histórico
function registrarAuditoria($pdo, $acao, $modulo, $registro_id = null, $valor_antigo = null, $valor_novo = null, $descricao = '') {
    if (!isset($_SESSION['usuario_id'])) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO historico_alteracoes (usuario_id, acao, modulo, registro_id, valor_antigo, valor_novo, descricao) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['usuario_id'],
            $acao,
            $modulo,
            $registro_id,
            is_array($valor_antigo) ? json_encode($valor_antigo) : $valor_antigo,
            is_array($valor_novo) ? json_encode($valor_novo) : $valor_novo,
            $descricao
        ]);
    } catch (Exception $e) {
        // Silenciar erro de auditoria para não quebrar fluxo principal
    }
}

function registrarStatusPedido($pdo, $pedido_id, $status_anterior, $novo_status, $observacao = '') {
    $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : null;
    try {
        $stmt = $pdo->prepare("INSERT INTO historico_status_pedidos (pedido_id, status_anterior, novo_status, usuario_id, observacao) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$pedido_id, $status_anterior, $novo_status, $usuario_id, $observacao]);
    } catch (Exception $e) {
        // Silenciar
    }
}
