<?php

if (!defined('BASE_URL')) {
    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    $protocol = ($forwardedProto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $configuredUrl = getenv('APP_URL');
    define('BASE_URL', $configuredUrl ? rtrim($configuredUrl, '/') . '/' : $protocol . $host . '/');
}

$databaseUrl = getenv('DATABASE_URL');
$databaseConfig = $databaseUrl ? parse_url($databaseUrl) : [];
$host = $databaseConfig['host'] ?? (getenv('TIDB_HOST') ?: getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
$port = $databaseConfig['port'] ?? (getenv('TIDB_PORT') ?: getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306');
$dbname = isset($databaseConfig['path']) ? ltrim($databaseConfig['path'], '/') : (getenv('TIDB_DATABASE') ?: getenv('DB_NAME') ?: 'cria_fatia');
$username = $databaseConfig['user'] ?? (getenv('TIDB_USER') ?: getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
$password = isset($databaseConfig['pass']) ? urldecode($databaseConfig['pass']) : (getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
$password = getenv('TIDB_PASSWORD') ?: $password;
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

if (getenv('TIDB_HOST')) {
    $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/cert.pem';
    $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, $pdoOptions);

    if (session_status() === PHP_SESSION_NONE) {
        require_once __DIR__ . '/database_session_handler.php';
        session_set_save_handler(new DatabaseSessionHandler($pdo), true);
        session_start();
    }
} catch (PDOException $e) {
    die("<div style='font-family:sans-serif; padding:20px; background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;'>
            <h3>Erro de Conexão com o Banco de Dados</h3>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
            <p>Por favor, verifique se o MySQL do WAMP está ligado e se o banco <b>cria_fatia</b> foi importado corretamente.</p>
         </div>");
}
?>
