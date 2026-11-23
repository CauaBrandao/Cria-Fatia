<?php
$databaseUrl = getenv('DATABASE_URL');
$databaseConfig = $databaseUrl ? parse_url($databaseUrl) : [];
$host = $databaseConfig['host'] ?? (getenv('TIDB_HOST') ?: getenv('MYSQLHOST'));
$port = $databaseConfig['port'] ?? (getenv('TIDB_PORT') ?: getenv('MYSQLPORT') ?: '3306');
$username = $databaseConfig['user'] ?? (getenv('TIDB_USER') ?: getenv('MYSQLUSER') ?: 'root');
$password = isset($databaseConfig['pass']) ? urldecode($databaseConfig['pass']) : getenv('MYSQLPASSWORD');
$password = getenv('TIDB_PASSWORD') ?: $password;
$database = isset($databaseConfig['path']) ? ltrim($databaseConfig['path'], '/') : (getenv('TIDB_DATABASE') ?: getenv('DB_NAME') ?: 'cria_fatia');
$pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

if (getenv('TIDB_HOST')) {
    $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/cert.pem';
    $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

if (!$host || $password === false) {
    exit(0);
}

$attempts = 20;
while ($attempts-- > 0) {
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password, $pdoOptions);
        break;
    } catch (PDOException $exception) {
        if ($attempts === 0) {
            throw $exception;
        }
        sleep(3);
    }
}

if (!getenv('TIDB_DATABASE') && !$databaseUrl) {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}
$pdo->exec("USE `$database`");
$tableExists = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetchColumn();

if (!$tableExists) {
    $schema = file_get_contents(__DIR__ . '/cria_fatia.sql');
    $schema = preg_replace('/^CREATE DATABASE.*?;\s*^USE\s+\w+\s*;/mi', '', $schema);
    $pdo->exec($schema);
    fwrite(STDOUT, "Banco Cria Fatia inicializado.\n");
}
