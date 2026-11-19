-- Execute uma única vez no banco cria_fatia (phpMyAdmin > SQL).
USE cria_fatia;

CREATE TABLE IF NOT EXISTS tipos_massas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NULL,
    preco_adicional DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    disponivel TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tipos_massas (nome, descricao, preco_adicional, disponivel)
SELECT 'Tradicional', 'Massa tradicional da casa', 0.00, 1
WHERE NOT EXISTS (SELECT 1 FROM tipos_massas WHERE nome = 'Tradicional');

INSERT INTO tipos_massas (nome, descricao, preco_adicional, disponivel)
SELECT 'Integral', 'Massa integral', 3.00, 1
WHERE NOT EXISTS (SELECT 1 FROM tipos_massas WHERE nome = 'Integral');

ALTER TABLE pedido_itens ADD COLUMN IF NOT EXISTS massa_id INT NULL;
ALTER TABLE pedidos MODIFY status ENUM('pendente','em_producao','pronto','finalizado','entregue','cancelado') NOT NULL DEFAULT 'pendente';

-- Pedidos antigos permanecem sem tipo de massa; novos pedidos exigirão uma escolha.
