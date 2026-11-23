<?php
$databaseUrl = getenv('DATABASE_URL');
$databaseConfig = $databaseUrl ? parse_url($databaseUrl) : [];
$host = $databaseConfig['host'] ?? (getenv('TIDB_HOST') ?: getenv('MYSQLHOST') ?: getenv('DB_HOST'));
$port = $databaseConfig['port'] ?? (getenv('TIDB_PORT') ?: getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: '3306');
$username = $databaseConfig['user'] ?? (getenv('TIDB_USER') ?: getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
$password = isset($databaseConfig['pass']) ? urldecode($databaseConfig['pass']) : (getenv('TIDB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('DB_PASS'));
$password = getenv('TIDB_PASSWORD') ?: $password;
$database = isset($databaseConfig['path']) ? ltrim($databaseConfig['path'], '/') : (getenv('TIDB_DATABASE') ?: getenv('DB_NAME') ?: 'cria_fatia');
$pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

if (getenv('TIDB_HOST')) {
    $pdoOptions[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/cert.pem';
    $pdoOptions[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

if (!$host || $password === false || $password === null) {
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

// Aplica atualizações incrementais também em bancos que já estão em produção.
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (' .
    'nome VARCHAR(255) PRIMARY KEY, aplicado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP' .
    ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

$migrations = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($migrations, SORT_STRING);

foreach ($migrations as $migration) {
    $name = basename($migration);
    $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE nome = ?');
    $check->execute([$name]);
    if ($check->fetchColumn()) {
        continue;
    }

    $pdo->exec(file_get_contents($migration));
    $record = $pdo->prepare('INSERT INTO schema_migrations (nome) VALUES (?)');
    $record->execute([$name]);
    fwrite(STDOUT, "Migração aplicada: {$name}\n");
}

// Cria o primeiro administrador somente quando credenciais seguras são fornecidas.
$adminEmail = trim((string)getenv('ADMIN_INITIAL_EMAIL'));
$adminPassword = (string)getenv('ADMIN_INITIAL_PASSWORD');
$userCount = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();

if ($userCount === 0 && $adminEmail !== '' && $adminPassword !== '') {
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 12) {
        throw new RuntimeException('ADMIN_INITIAL_EMAIL inválido ou ADMIN_INITIAL_PASSWORD com menos de 12 caracteres.');
    }

    $createAdmin = $pdo->prepare(
        "INSERT INTO usuarios (nome, email, senha, perfil) VALUES ('Administrador', ?, ?, 'admin')"
    );
    $createAdmin->execute([
        strtolower($adminEmail),
        password_hash($adminPassword, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Administrador inicial criado a partir das variáveis de ambiente.\n");
}
