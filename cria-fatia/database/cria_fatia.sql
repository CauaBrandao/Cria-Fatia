-- Database Schema for Cria Fatia - Bella Roza Pizzaria
CREATE DATABASE IF NOT EXISTS cria_fatia DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cria_fatia;

-- Table: tamanhos
CREATE TABLE IF NOT EXISTS tamanhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    max_sabores INT NOT NULL DEFAULT 1,
    preco_base DECIMAL(10, 2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tamanhos (id, nome, max_sabores, preco_base) VALUES
(1, 'Broto', 1, 25.00),
(2, 'Grande', 2, 45.00),
(3, 'Família', 2, 55.00)
ON DUPLICATE KEY UPDATE nome=VALUES(nome), max_sabores=VALUES(max_sabores), preco_base=VALUES(preco_base);

-- Table: sabores
CREATE TABLE IF NOT EXISTS sabores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    imagem VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO sabores (id, nome, descricao, preco, imagem) VALUES
(1, 'Calabresa', 'Calabresa fatiada, cebola e azeitonas', 40.00, 'calabresa.png'),
(2, 'Mussarela', 'Mussarela de primeira qualidade e rodelas de tomate', 38.00, 'mussarela.png'),
(3, 'Frango com Catupiry', 'Frango desfiado com autêntico Catupiry', 44.00, 'frango_catupiry.png'),
(4, 'Quatro Queijos', 'Mussarela, provolone, parmesão e gorgonzola', 46.00, 'quatro_queijos.png'),
(5, 'Portuguesa', 'Presunto, ovos, cebola, ervilha e mussarela', 42.00, 'portuguesa.png')
ON DUPLICATE KEY UPDATE nome=VALUES(nome), descricao=VALUES(descricao), preco=VALUES(preco);

-- Table: bordas
CREATE TABLE IF NOT EXISTS bordas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    preco DECIMAL(10, 2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO bordas (id, nome, preco) VALUES
(1, 'Tradicional (Sem recheio)', 0.00),
(2, 'Catupiry', 8.00),
(3, 'Cheddar', 8.00),
(4, 'Chocolate', 10.00)
ON DUPLICATE KEY UPDATE nome=VALUES(nome), preco=VALUES(preco);

-- Table: ingredientes
CREATE TABLE IF NOT EXISTS ingredientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    preco_adicional DECIMAL(10, 2) NOT NULL DEFAULT 3.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO ingredientes (id, nome, preco_adicional) VALUES
(1, 'Bacon', 4.00),
(2, 'Azeitona Extra', 2.50),
(3, 'Cebola Extra', 2.00),
(4, 'Milho', 2.50),
(5, 'Catupiry Extra', 5.00),
(6, 'Tomate', 2.00)
ON DUPLICATE KEY UPDATE nome=VALUES(nome), preco_adicional=VALUES(preco_adicional);

-- Table: sabor_ingredientes
CREATE TABLE IF NOT EXISTS sabor_ingredientes (
    sabor_id INT NOT NULL,
    ingrediente_id INT NOT NULL,
    PRIMARY KEY (sabor_id, ingrediente_id),
    FOREIGN KEY (sabor_id) REFERENCES sabores(id) ON DELETE CASCADE,
    FOREIGN KEY (ingrediente_id) REFERENCES ingredientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO sabor_ingredientes (sabor_id, ingrediente_id) VALUES
(1, 3), (1, 2),
(2, 6),
(3, 5);

-- Table: usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'atendimento', 'cozinha') NOT NULL DEFAULT 'atendimento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_acompanhamento VARCHAR(20) UNIQUE DEFAULT NULL,
    token_acesso VARCHAR(64) DEFAULT NULL,
    cliente_nome VARCHAR(100) NOT NULL,
    cliente_telefone VARCHAR(20) NOT NULL,
    tipo_entrega ENUM('entrega', 'retirada') NOT NULL DEFAULT 'entrega',
    endereco TEXT DEFAULT NULL,
    observacoes TEXT DEFAULT NULL,
    valor_total DECIMAL(10, 2) NOT NULL,
    status ENUM('pendente', 'em_producao', 'pronto', 'finalizado', 'entregue', 'cancelado') NOT NULL DEFAULT 'pendente',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_criado_em (criado_em),
    INDEX idx_codigo (codigo_acompanhamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: tipos_massas
CREATE TABLE IF NOT EXISTS tipos_massas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT DEFAULT NULL,
    preco_adicional DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    disponivel TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tipos_massas (nome, descricao, preco_adicional, disponivel) VALUES
('Tradicional', 'Massa tradicional da casa', 0.00, 1),
('Integral', 'Massa integral', 3.00, 1)
ON DUPLICATE KEY UPDATE descricao=VALUES(descricao);

-- Table: pedido_itens
CREATE TABLE IF NOT EXISTS pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    tamanho_id INT NOT NULL,
    borda_id INT NOT NULL,
    massa_id INT DEFAULT NULL,
    divisao ENUM('inteira', 'meio_a_meio') NOT NULL DEFAULT 'inteira',
    sabor1_id INT DEFAULT NULL,
    sabor2_id INT DEFAULT NULL,
    preco_item DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (tamanho_id) REFERENCES tamanhos(id),
    FOREIGN KEY (borda_id) REFERENCES bordas(id),
    FOREIGN KEY (sabor1_id) REFERENCES sabores(id) ON DELETE SET NULL,
    FOREIGN KEY (sabor2_id) REFERENCES sabores(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: pedido_item_customizacoes
CREATE TABLE IF NOT EXISTS pedido_item_customizacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_item_id INT NOT NULL,
    metade INT NOT NULL DEFAULT 1,
    ingrediente_id INT NOT NULL,
    acao ENUM('adicionar', 'remover') NOT NULL,
    FOREIGN KEY (pedido_item_id) REFERENCES pedido_itens(id) ON DELETE CASCADE,
    FOREIGN KEY (ingrediente_id) REFERENCES ingredientes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ========================================================
-- MELHORIAS E NOVAS FUNCIONALIDADES — CRIA FATIA
-- ========================================================

-- 1. Disponibilidade de Produtos (adicionar coluna disponivel onde aplicável)
ALTER TABLE sabores ADD COLUMN IF NOT EXISTS disponivel TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE bordas ADD COLUMN IF NOT EXISTS disponivel TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE ingredientes ADD COLUMN IF NOT EXISTS disponivel TINYINT(1) NOT NULL DEFAULT 1;

-- 2. Cadastro de Molhos
CREATE TABLE IF NOT EXISTS molhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT DEFAULT NULL,
    preco_adicional DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    disponivel TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO molhos (id, nome, descricao, preco_adicional, disponivel) VALUES
(1, 'Molho Barbecue', 'Molho barbecue defumado especial da casa', 3.00, 1),
(2, 'Molho de Alho', 'Molho cremoso à base de alho selecionado', 2.50, 1),
(3, 'Molho Especial da Casa', 'Receita secreta de ervas finas e especiarias', 3.50, 1),
(4, 'Molho Cheddar', 'Cheddar cremoso derretido', 4.00, 1),
(5, 'Molho Picante', 'Molho à base de pimenta dedo-de-moça', 3.00, 1)
ON DUPLICATE KEY UPDATE nome=VALUES(nome), preco_adicional=VALUES(preco_adicional), disponivel=VALUES(disponivel);

-- Tabela de relacionamento pedido_item_molhos
CREATE TABLE IF NOT EXISTS pedido_item_molhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_item_id INT NOT NULL,
    molho_id INT NOT NULL,
    FOREIGN KEY (pedido_item_id) REFERENCES pedido_itens(id) ON DELETE CASCADE,
    FOREIGN KEY (molho_id) REFERENCES molhos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Histórico de Status dos Pedidos (historico_status_pedidos)
CREATE TABLE IF NOT EXISTS historico_status_pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    status_anterior VARCHAR(30) DEFAULT NULL,
    novo_status VARCHAR(30) NOT NULL,
    usuario_id INT DEFAULT NULL,
    observacao TEXT DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_pedido_hist (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Histórico de Alterações / Auditoria (historico_alteracoes)
CREATE TABLE IF NOT EXISTS historico_alteracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    acao VARCHAR(100) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    registro_id INT DEFAULT NULL,
    valor_antigo TEXT DEFAULT NULL,
    valor_novo TEXT DEFAULT NULL,
    descricao TEXT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_modulo (modulo),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Controle de Caixa (Abertura, Fechamento e Movimentações)
CREATE TABLE IF NOT EXISTS caixas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_abertura_id INT NOT NULL,
    usuario_fechamento_id INT DEFAULT NULL,
    valor_inicial DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    valor_contado DECIMAL(10, 2) DEFAULT NULL,
    saldo_esperado DECIMAL(10, 2) DEFAULT NULL,
    diferenca DECIMAL(10, 2) DEFAULT NULL,
    status ENUM('aberto', 'fechado') NOT NULL DEFAULT 'aberto',
    observacoes_fechamento TEXT DEFAULT NULL,
    aberto_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    fechado_em DATETIME DEFAULT NULL,
    FOREIGN KEY (usuario_abertura_id) REFERENCES usuarios(id),
    FOREIGN KEY (usuario_fechamento_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS caixa_movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    caixa_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('entrada', 'saida', 'venda') NOT NULL,
    origem VARCHAR(100) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    justificativa TEXT DEFAULT NULL,
    pedido_id INT DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caixa_id) REFERENCES caixas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
