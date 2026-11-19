<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $protocol . $host . '/cria-fatia/');
}

$host = 'localhost';
$dbname = 'cria_fatia';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif; padding:20px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;'>
            <h3>Erro de Conexão com o Banco de Dados</h3>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <p>Por favor, verifique se o MySQL do WAMP está ligado e se o banco <b>cria_fatia</b> foi importado corretamente.</p>
         </div>");
}
?>