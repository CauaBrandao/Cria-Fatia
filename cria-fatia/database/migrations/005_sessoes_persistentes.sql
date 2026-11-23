CREATE TABLE IF NOT EXISTS php_sessions (
    id VARCHAR(128) PRIMARY KEY,
    data MEDIUMBLOB NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    INDEX idx_php_sessions_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
