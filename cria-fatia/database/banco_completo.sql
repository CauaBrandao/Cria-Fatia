-- Criação e Carga Completa do Banco de Dados - Cria Fatia
CREATE DATABASE IF NOT EXISTS `cria_fatia` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cria_fatia`;

-- --------------------------------------------------------
-- Tabela de Usuários (Senha padrão 'admin123' com hash bcrypt válido)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `senha` varchar(255) NOT NULL,
  `perfil` enum('admin','atendimento','cozinha') NOT NULL DEFAULT 'atendimento',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `perfil`) VALUES
(1, 'Administrador', 'admin@briafatia.com', '$2y$12$zRz0VJfGX7x0lX6z9xQfQ.CrMVE6nGN/aV4psv/jBT7qL/nTdwAk6', 'admin'),
(2, 'Atendimento Bella Roza', 'atendimento@briafatia.com', '$2y$12$zRz0VJfGX7x0lX6z9xQfQ.CrMVE6nGN/aV4psv/jBT7qL/nTdwAk6', 'atendimento'),
(3, 'Cozinha Bella Roza', 'cozinha@briafatia.com', '$2y$12$zRz0VJfGX7x0lX6z9xQfQ.CrMVE6nGN/aV4psv/jBT7qL/nTdwAk6', 'cozinha')
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`), `senha`=VALUES(`senha`), `perfil`=VALUES(`perfil`);

-- --------------------------------------------------------
-- Demais tabelas do sistema
-- --------------------------------------------------------
DROP TABLE IF EXISTS `tamanhos`;
CREATE TABLE `tamanhos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) NOT NULL,
  `max_sabores` int NOT NULL DEFAULT 1,
  `preco_base` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tamanhos` (`id`, `nome`, `max_sabores`, `preco_base`) VALUES
(1, 'Broto', 1, 25.00),
(2, 'Grande', 2, 45.00),
(3, 'Família', 2, 55.00)
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`), `max_sabores`=VALUES(`max_sabores`), `preco_base`=VALUES(`preco_base`);

DROP TABLE IF EXISTS `sabores`;
CREATE TABLE `sabores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `imagem` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sabores` (`id`, `nome`, `descricao`, `preco`, `imagem`) VALUES
(1, 'Calabresa', 'Calabresa fatiada, cebola e azeitonas', 40.00, 'calabresa.png'),
(2, 'Mussarela', 'Mussarela de primeira qualidade e rodelas de tomate', 38.00, 'mussarela.png'),
(3, 'Frango com Catupiry', 'Frango desfiado com autêntico Catupiry', 44.00, 'frango_catupiry.png'),
(4, 'Quatro Queijos', 'Mussarela, provolone, parmesão e gorgonzola', 46.00, 'quatro_queijos.png'),
(5, 'Portuguesa', 'Presunto, ovos, cebola, ervilha e mussarela', 42.00, 'portuguesa.png')
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`), `preco`=VALUES(`preco`);

DROP TABLE IF EXISTS `bordas`;
CREATE TABLE `bordas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `bordas` (`id`, `nome`, `preco`) VALUES
(1, 'Tradicional (Sem recheio)', 0.00),
(2, 'Catupiry', 8.00),
(3, 'Cheddar', 8.00),
(4, 'Chocolate', 10.00)
ON DUPLICATE KEY UPDATE `nome`=VALUES(`nome`), `preco`=VALUES(`preco`);

DROP TABLE IF EXISTS `pedidos`;
CREATE TABLE `pedidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo_acompanhamento` varchar(20) DEFAULT NULL,
  `cliente_nome` varchar(100) NOT NULL,
  `cliente_telefone` varchar(20) DEFAULT NULL,
  `tipo_entrega` enum('entrega','retirada') NOT NULL DEFAULT 'entrega',
  `endereco` text,
  `observacoes` text,
  `valor_total` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pendente',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo_acompanhamento` (`codigo_acompanhamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `pedidos` (`id`, `codigo_acompanhamento`, `cliente_nome`, `cliente_telefone`, `tipo_entrega`, `valor_total`, `status`, `criado_em`) VALUES
(1, 'CF-8K29P5', 'Cliente Teste', '11999999999', 'entrega', 45.00, 'em_producao', NOW())
ON DUPLICATE KEY UPDATE `status`=VALUES(`status`);
