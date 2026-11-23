-- Banco de Dados Corrigido - Cria Fatia
CREATE DATABASE IF NOT EXISTS cria_fatia DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cria_fatia;

-- Tabela de Usuários com senha hash bcrypt correta para 'admin123'
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'atendimento', 'cozinha') NOT NULL DEFAULT 'atendimento',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuários padrão (senha: admin123)
INSERT INTO usuarios (id, nome, email, senha, perfil) VALUES
(1, 'Administrador', 'admin@briafatia.com', '$2y$12$zRz0VJfGX7x0lX6z9xQfQ.CrMVE6nGN/aV4psv/jBT7qL/nTdwAk6', 'admin'),
(2, 'Atendimento Bella Roza', 'atendimento@briafatia.com', '$2y$12$zRz0VJfGX7x0lX6z9xQfQ.CrMVE6nGN/aV4psv/jBT7qL/nTdwAk6', 'atendimento'),
(3, 'Cozinha Bella Roza', 'cozinha@briafatia.com', '$2y$12$zRz0VJfGX7x0lX6z9xQfQ.CrMVE6nGN/aV4psv/jBT7qL/nTdwAk6', 'cozinha')
ON DUPLICATE KEY UPDATE nome=VALUES(nome), senha=VALUES(senha), perfil=VALUES(perfil);

-- Tabela de Pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_acompanhamento VARCHAR(50) NOT NULL UNIQUE,
    cliente_nome VARCHAR(100),
    status VARCHAR(50) DEFAULT 'Recebido',
    total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO pedidos (codigo_acompanhamento, cliente_nome, status, total) VALUES
('CF-8K29P5', 'Cliente Teste', 'Em preparo', 45.00)
ON DUPLICATE KEY UPDATE status=VALUES(status);
