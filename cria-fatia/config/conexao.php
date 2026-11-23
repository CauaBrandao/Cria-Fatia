<?php

require_once __DIR__ . '/config.php';

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
    PDO::ATTR_EMULATE_PREPARES => false,
];

if (getenv('TIDB_HOST')) {
    $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/cert.pem';
    $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password, $pdoOptions);

    if (session_status() === PHP_SESSION_NONE) {
        require_once __DIR__ . '/database_session_handler.php';
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_set_save_handler(new DatabaseSessionHandler($pdo), true);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => str_starts_with(BASE_URL, 'https://'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
} catch (PDOException $e) {
    error_log('Falha de banco de dados no Cria Fatia: ' . $e->getMessage());
    http_response_code(503);
    exit('Serviço temporariamente indisponível. Tente novamente em alguns instantes.');
}

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}
