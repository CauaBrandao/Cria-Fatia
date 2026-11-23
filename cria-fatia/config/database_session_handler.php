<?php

final class DatabaseSessionHandler implements SessionHandlerInterface
{
    public function __construct(private PDO $pdo)
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS php_sessions (' .
            'id VARCHAR(128) PRIMARY KEY, data MEDIUMBLOB NOT NULL, updated_at INT UNSIGNED NOT NULL' .
            ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        $statement = $this->pdo->prepare('SELECT data FROM php_sessions WHERE id = ?');
        $statement->execute([$id]);
        $data = $statement->fetchColumn();
        return $data === false ? '' : $data;
    }

    public function write(string $id, string $data): bool
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO php_sessions (id, data, updated_at) VALUES (?, ?, ?) ' .
            'ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = VALUES(updated_at)'
        );
        return $statement->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM php_sessions WHERE id = ?');
        return $statement->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $statement = $this->pdo->prepare('DELETE FROM php_sessions WHERE updated_at < ?');
        $statement->execute([time() - $max_lifetime]);
        return $statement->rowCount();
    }
}
