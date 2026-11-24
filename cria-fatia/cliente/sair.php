<?php
require_once __DIR__ . '/../config/conexao.php';
unset($_SESSION['cliente_id'], $_SESSION['cliente_nome'], $_SESSION['cliente_email'], $_SESSION['cliente_telefone']);
session_regenerate_id(true);
header('Location: entrar.php'); exit;

