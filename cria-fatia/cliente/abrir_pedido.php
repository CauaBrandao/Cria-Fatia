<?php
require_once __DIR__ . '/../config/conexao.php';
if (empty($_SESSION['cliente_id'])) { header('Location: entrar.php'); exit; }
$stmt = $pdo->prepare('SELECT codigo_acompanhamento, token_acesso FROM pedidos WHERE id = ? AND cliente_id = ?');
$stmt->execute([(int)($_GET['id'] ?? 0), (int)$_SESSION['cliente_id']]);
$pedido = $stmt->fetch();
if (!$pedido) { http_response_code(404); exit('Pedido não encontrado.'); }
$_SESSION['ultimo_pedido_codigo'] = $pedido['codigo_acompanhamento'];
$_SESSION['ultimo_pedido_token'] = $pedido['token_acesso'];
header('Location: pedido.php?codigo=' . urlencode($pedido['codigo_acompanhamento'])); exit;

