CREATE DATABASE  IF NOT EXISTS `cria_fatia` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `cria_fatia`;
-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: cria_fatia
-- ------------------------------------------------------
-- Server version	8.0.31

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bordas`
--

DROP TABLE IF EXISTS `bordas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bordas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `disponivel` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bordas_disponivel` (`disponivel`),
  KEY `idx_bordas_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bordas`
--

LOCK TABLES `bordas` WRITE;
/*!40000 ALTER TABLE `bordas` DISABLE KEYS */;
INSERT INTO `bordas` VALUES (1,'Tradicional (Sem recheio)',0.00,'Sem borda recheada',1,'2026-11-13 15:36:26',NULL),(2,'Catupiry',8.00,'Borda recheada com Catupiry',1,'2026-11-13 15:36:26',NULL),(3,'Cheddar',8.00,'Borda recheada com cheddar',1,'2026-11-13 15:36:26',NULL),(4,'Chocolate',10.00,'Borda recheada de chocolate',1,'2026-11-13 15:36:26',NULL);
/*!40000 ALTER TABLE `bordas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caixa_movimentacoes`
--

DROP TABLE IF EXISTS `caixa_movimentacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `caixa_movimentacoes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `caixa_id` int unsigned NOT NULL,
  `usuario_id` int unsigned NOT NULL,
  `tipo` enum('entrada','saida','venda') COLLATE utf8mb4_unicode_ci NOT NULL,
  `origem` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `justificativa` text COLLATE utf8mb4_unicode_ci,
  `pedido_id` int unsigned DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_caixa_movimentacoes_caixa` (`caixa_id`,`criado_em`),
  KEY `idx_caixa_movimentacoes_usuario` (`usuario_id`),
  KEY `idx_caixa_movimentacoes_pedido` (`pedido_id`),
  CONSTRAINT `fk_caixa_movimentacoes_caixa` FOREIGN KEY (`caixa_id`) REFERENCES `caixas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_caixa_movimentacoes_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_caixa_movimentacoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caixa_movimentacoes`
--

LOCK TABLES `caixa_movimentacoes` WRITE;
/*!40000 ALTER TABLE `caixa_movimentacoes` DISABLE KEYS */;
/*!40000 ALTER TABLE `caixa_movimentacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `caixas`
--

DROP TABLE IF EXISTS `caixas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `caixas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_abertura_id` int unsigned NOT NULL,
  `usuario_fechamento_id` int unsigned DEFAULT NULL,
  `valor_inicial` decimal(10,2) NOT NULL DEFAULT '0.00',
  `valor_contado` decimal(10,2) DEFAULT NULL,
  `saldo_esperado` decimal(10,2) DEFAULT NULL,
  `diferenca` decimal(10,2) DEFAULT NULL,
  `status` enum('aberto','fechado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aberto',
  `observacoes_fechamento` text COLLATE utf8mb4_unicode_ci,
  `aberto_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fechado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_caixas_status` (`status`),
  KEY `idx_caixas_aberto_em` (`aberto_em`),
  KEY `fk_caixas_usuario_abertura` (`usuario_abertura_id`),
  KEY `fk_caixas_usuario_fechamento` (`usuario_fechamento_id`),
  CONSTRAINT `fk_caixas_usuario_abertura` FOREIGN KEY (`usuario_abertura_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_caixas_usuario_fechamento` FOREIGN KEY (`usuario_fechamento_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `caixas`
--

LOCK TABLES `caixas` WRITE;
/*!40000 ALTER TABLE `caixas` DISABLE KEYS */;
/*!40000 ALTER TABLE `caixas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historico_alteracoes`
--

DROP TABLE IF EXISTS `historico_alteracoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historico_alteracoes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int unsigned DEFAULT NULL,
  `acao` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modulo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registro_id` int unsigned DEFAULT NULL,
  `valor_antigo` longtext COLLATE utf8mb4_unicode_ci,
  `valor_novo` longtext COLLATE utf8mb4_unicode_ci,
  `descricao` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_historico_alteracoes_modulo` (`modulo`),
  KEY `idx_historico_alteracoes_usuario` (`usuario_id`),
  KEY `idx_historico_alteracoes_registro` (`modulo`,`registro_id`),
  CONSTRAINT `fk_historico_alteracoes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historico_alteracoes`
--

LOCK TABLES `historico_alteracoes` WRITE;
/*!40000 ALTER TABLE `historico_alteracoes` DISABLE KEYS */;
INSERT INTO `historico_alteracoes` VALUES (1,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:44:43'),(2,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:44:44'),(3,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:44:45'),(4,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:44:46'),(5,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:05'),(6,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:07'),(7,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:08'),(8,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:08'),(9,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:10'),(10,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:16'),(11,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:17'),(12,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:17'),(13,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:17'),(14,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:18'),(15,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:18'),(16,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:35'),(17,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:36'),(18,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:36'),(19,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:50'),(20,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:51'),(21,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:51'),(22,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:45:52'),(23,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:51:24'),(24,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:51:25'),(25,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:51:26'),(26,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:51:26'),(27,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'1','0','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:51:27'),(28,1,'ALTERAR_DISPONIBILIDADE','molhos',2,'0','1','Alterou disponibilidade do molho: Molho barbecue','2026-11-13 15:51:27');
/*!40000 ALTER TABLE `historico_alteracoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historico_status_pedidos`
--

DROP TABLE IF EXISTS `historico_status_pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historico_status_pedidos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pedido_id` int unsigned NOT NULL,
  `status_anterior` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `novo_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int unsigned DEFAULT NULL,
  `observacao` text COLLATE utf8mb4_unicode_ci,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_historico_status_pedido` (`pedido_id`,`criado_em`),
  KEY `idx_historico_status_usuario` (`usuario_id`),
  CONSTRAINT `fk_historico_status_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_historico_status_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historico_status_pedidos`
--

LOCK TABLES `historico_status_pedidos` WRITE;
/*!40000 ALTER TABLE `historico_status_pedidos` DISABLE KEYS */;
INSERT INTO `historico_status_pedidos` VALUES (1,1,NULL,'pendente',NULL,'Pedido realizado pelo cliente via site','2026-11-13 17:29:06'),(2,2,NULL,'pendente',NULL,'Pedido realizado pelo cliente via site','2026-11-16 16:18:46'),(3,1,'pendente','em_producao',1,'Status atualizado pelo atendimento.','2026-11-16 16:19:17'),(4,2,'pendente','em_producao',1,'Status atualizado pelo atendimento.','2026-11-16 16:19:25'),(5,1,'em_producao','pronto',1,'Status atualizado pelo atendimento.','2026-11-16 16:19:56'),(6,2,'em_producao','em_producao',1,'Status atualizado pelo atendimento.','2026-11-16 16:19:58'),(7,2,'em_producao','pronto',1,'Status atualizado pelo atendimento.','2026-11-16 16:20:00'),(8,1,'pronto','em_producao',1,'Status atualizado pelo atendimento.','2026-11-16 16:20:08'),(9,2,'pronto','pronto',1,'Status atualizado pelo atendimento.','2026-11-16 16:20:09'),(10,2,'pronto','em_producao',1,'Status atualizado pelo atendimento.','2026-11-16 16:20:11');
/*!40000 ALTER TABLE `historico_status_pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingredientes`
--

DROP TABLE IF EXISTS `ingredientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingredientes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `preco_adicional` decimal(10,2) NOT NULL DEFAULT '3.00',
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `categoria` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagem` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disponivel` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ingredientes_disponivel` (`disponivel`),
  KEY `idx_ingredientes_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingredientes`
--

LOCK TABLES `ingredientes` WRITE;
/*!40000 ALTER TABLE `ingredientes` DISABLE KEYS */;
INSERT INTO `ingredientes` VALUES (1,'Mussarela',4.00,'Queijo mussarela','Queijos',NULL,1,'2026-11-13 15:36:26',NULL),(2,'Calabresa',4.00,'Calabresa fatiada','Carnes',NULL,1,'2026-11-13 15:36:26',NULL),(3,'Bacon',4.50,'Bacon em cubos','Carnes',NULL,1,'2026-11-13 15:36:26',NULL),(4,'Frango',4.00,'Frango desfiado','Carnes',NULL,1,'2026-11-13 15:36:26',NULL),(5,'Catupiry',5.00,'Requeijão tipo Catupiry','Queijos',NULL,1,'2026-11-13 15:36:26',NULL),(6,'Tomate',2.00,'Tomate fresco','Vegetais',NULL,1,'2026-11-13 15:36:26',NULL),(7,'Cebola',2.00,'Cebola fatiada','Vegetais',NULL,1,'2026-11-13 15:36:26',NULL),(8,'Milho',2.50,'Milho verde','Vegetais',NULL,1,'2026-11-13 15:36:26',NULL),(9,'Azeitona',2.50,'Azeitonas','Vegetais',NULL,1,'2026-11-13 15:36:26',NULL),(10,'Orégano',1.00,'Orégano','Temperos',NULL,1,'2026-11-13 15:36:26',NULL),(11,'Presunto',3.50,'Presunto','Carnes',NULL,1,'2026-11-13 15:36:26',NULL),(12,'Champignon',4.00,'Champignon fatiado','Vegetais',NULL,1,'2026-11-13 15:36:26',NULL);
/*!40000 ALTER TABLE `ingredientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `molhos`
--

DROP TABLE IF EXISTS `molhos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `molhos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `preco_adicional` decimal(10,2) NOT NULL DEFAULT '0.00',
  `disponivel` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_molhos_disponivel` (`disponivel`),
  KEY `idx_molhos_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `molhos`
--

LOCK TABLES `molhos` WRITE;
/*!40000 ALTER TABLE `molhos` DISABLE KEYS */;
INSERT INTO `molhos` VALUES (1,'Molho tradicional','Molho tradicional da casa',0.00,1,'2026-11-13 15:36:26',NULL),(2,'Molho barbecue','Molho barbecue defumado',3.00,1,'2026-11-13 15:36:26','2026-11-13 15:51:27'),(3,'Molho branco','Molho branco cremoso',3.00,1,'2026-11-13 15:36:26',NULL),(4,'Molho especial','Molho especial da casa',3.50,1,'2026-11-13 15:36:26',NULL);
/*!40000 ALTER TABLE `molhos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_item_customizacoes`
--

DROP TABLE IF EXISTS `pedido_item_customizacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_item_customizacoes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pedido_item_id` int unsigned NOT NULL,
  `metade` tinyint unsigned NOT NULL DEFAULT '1',
  `ingrediente_id` int unsigned NOT NULL,
  `acao` enum('adicionar','remover') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ingrediente_nome_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ingrediente_preco_snapshot` decimal(10,2) DEFAULT NULL,
  `quantidade` decimal(8,2) NOT NULL DEFAULT '1.00',
  PRIMARY KEY (`id`),
  KEY `idx_customizacoes_item` (`pedido_item_id`),
  KEY `idx_customizacoes_ingrediente` (`ingrediente_id`),
  CONSTRAINT `fk_customizacoes_ingrediente` FOREIGN KEY (`ingrediente_id`) REFERENCES `ingredientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_customizacoes_item` FOREIGN KEY (`pedido_item_id`) REFERENCES `pedido_itens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_item_customizacoes`
--

LOCK TABLES `pedido_item_customizacoes` WRITE;
/*!40000 ALTER TABLE `pedido_item_customizacoes` DISABLE KEYS */;
INSERT INTO `pedido_item_customizacoes` VALUES (1,1,1,9,'adicionar','Azeitona',2.50,1.00),(2,1,1,3,'adicionar','Bacon',4.50,1.00),(3,1,1,2,'adicionar','Calabresa',4.00,1.00),(4,1,1,5,'adicionar','Catupiry',5.00,1.00),(5,1,1,7,'adicionar','Cebola',2.00,1.00),(6,1,1,12,'adicionar','Champignon',4.00,1.00),(7,1,1,4,'adicionar','Frango',4.00,1.00),(8,1,1,8,'adicionar','Milho',2.50,1.00),(9,1,1,1,'adicionar','Mussarela',4.00,1.00),(10,1,1,10,'adicionar','Orégano',1.00,1.00),(11,1,1,11,'adicionar','Presunto',3.50,1.00),(12,1,1,6,'adicionar','Tomate',2.00,1.00),(13,2,1,9,'adicionar','Azeitona',2.50,1.00),(14,2,1,3,'adicionar','Bacon',4.50,1.00),(15,2,1,2,'adicionar','Calabresa',4.00,1.00),(16,2,1,5,'adicionar','Catupiry',5.00,1.00),(17,2,1,7,'adicionar','Cebola',2.00,1.00),(18,2,1,12,'adicionar','Champignon',4.00,1.00);
/*!40000 ALTER TABLE `pedido_item_customizacoes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_item_molhos`
--

DROP TABLE IF EXISTS `pedido_item_molhos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_item_molhos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pedido_item_id` int unsigned NOT NULL,
  `molho_id` int unsigned NOT NULL,
  `molho_nome_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `molho_preco_snapshot` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pedido_item_molho` (`pedido_item_id`,`molho_id`),
  KEY `idx_pedido_item_molhos_molho` (`molho_id`),
  CONSTRAINT `fk_pedido_item_molhos_item` FOREIGN KEY (`pedido_item_id`) REFERENCES `pedido_itens` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_item_molhos_molho` FOREIGN KEY (`molho_id`) REFERENCES `molhos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_item_molhos`
--

LOCK TABLES `pedido_item_molhos` WRITE;
/*!40000 ALTER TABLE `pedido_item_molhos` DISABLE KEYS */;
/*!40000 ALTER TABLE `pedido_item_molhos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido_itens`
--

DROP TABLE IF EXISTS `pedido_itens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido_itens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pedido_id` int unsigned NOT NULL,
  `tamanho_id` int unsigned NOT NULL,
  `borda_id` int unsigned NOT NULL,
  `massa_id` int unsigned DEFAULT NULL,
  `divisao` enum('inteira','meio_a_meio') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inteira',
  `sabor1_id` int unsigned DEFAULT NULL,
  `sabor2_id` int unsigned DEFAULT NULL,
  `preco_item` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantidade` int unsigned NOT NULL DEFAULT '1',
  `preco_base_snapshot` decimal(10,2) DEFAULT NULL,
  `tamanho_nome_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `massa_nome_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `borda_nome_snapshot` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pedido_itens_pedido` (`pedido_id`),
  KEY `idx_pedido_itens_tamanho` (`tamanho_id`),
  KEY `idx_pedido_itens_massa` (`massa_id`),
  KEY `idx_pedido_itens_borda` (`borda_id`),
  KEY `fk_pedido_itens_sabor1` (`sabor1_id`),
  KEY `fk_pedido_itens_sabor2` (`sabor2_id`),
  CONSTRAINT `fk_pedido_itens_borda` FOREIGN KEY (`borda_id`) REFERENCES `bordas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_itens_massa` FOREIGN KEY (`massa_id`) REFERENCES `tipos_massas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_itens_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_itens_sabor1` FOREIGN KEY (`sabor1_id`) REFERENCES `sabores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_itens_sabor2` FOREIGN KEY (`sabor2_id`) REFERENCES `sabores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_pedido_itens_tamanho` FOREIGN KEY (`tamanho_id`) REFERENCES `tamanhos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido_itens`
--

LOCK TABLES `pedido_itens` WRITE;
/*!40000 ALTER TABLE `pedido_itens` DISABLE KEYS */;
INSERT INTO `pedido_itens` VALUES (1,1,1,1,4,'inteira',NULL,NULL,68.00,1,25.00,'Broto','Pan','Tradicional (Sem recheio)','2026-11-13 17:29:06'),(2,2,5,1,2,'inteira',NULL,NULL,90.00,1,65.00,'Família','Integral','Tradicional (Sem recheio)','2026-11-16 16:18:46');
/*!40000 ALTER TABLE `pedido_itens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedidos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `codigo_acompanhamento` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_acesso` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cliente_nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cliente_telefone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_entrega` enum('entrega','retirada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'entrega',
  `forma_pagamento` enum('pix','cartao','dinheiro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pix',
  `taxa_entrega` decimal(10,2) NOT NULL DEFAULT '0.00',
  `troco_para` decimal(10,2) DEFAULT NULL,
  `endereco` text COLLATE utf8mb4_unicode_ci,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `valor_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('pendente','em_producao','pronto','finalizado','entregue','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendente',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pedidos_codigo_acompanhamento` (`codigo_acompanhamento`),
  KEY `idx_pedidos_status` (`status`),
  KEY `idx_pedidos_criado_em` (`criado_em`),
  KEY `idx_pedidos_cliente_telefone` (`cliente_telefone`),
  KEY `idx_pedidos_token_acesso` (`token_acesso`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedidos`
--

LOCK TABLES `pedidos` WRITE;
/*!40000 ALTER TABLE `pedidos` DISABLE KEYS */;
INSERT INTO `pedidos` VALUES (1,'CF-846A30','7efb95cfda9a5391c42f483bb8ebf521a3a151f699366a855dac6364480a6239','dylan alvim','11 993944476','entrega','asdasdas','asdasd',68.00,'em_producao','2026-11-13 17:29:06','2026-11-16 16:20:08'),(2,'CF-96EF8B','0648ca82ea30ab421e18f5c7503ba6dd50a130a7ee2b86b90106938ad9ffa488','dylan teste','11 993944476','entrega','asdasd','sadasda',90.00,'em_producao','2026-11-16 16:18:46','2026-11-16 16:20:11');
/*!40000 ALTER TABLE `pedidos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sabor_ingredientes`
--

DROP TABLE IF EXISTS `sabor_ingredientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sabor_ingredientes` (
  `sabor_id` int unsigned NOT NULL,
  `ingrediente_id` int unsigned NOT NULL,
  PRIMARY KEY (`sabor_id`,`ingrediente_id`),
  KEY `idx_sabor_ingredientes_ingrediente` (`ingrediente_id`),
  CONSTRAINT `fk_sabor_ingredientes_ingrediente` FOREIGN KEY (`ingrediente_id`) REFERENCES `ingredientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_sabor_ingredientes_sabor` FOREIGN KEY (`sabor_id`) REFERENCES `sabores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sabor_ingredientes`
--

LOCK TABLES `sabor_ingredientes` WRITE;
/*!40000 ALTER TABLE `sabor_ingredientes` DISABLE KEYS */;
/*!40000 ALTER TABLE `sabor_ingredientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sabores`
--

DROP TABLE IF EXISTS `sabores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sabores` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `preco` decimal(10,2) NOT NULL DEFAULT '0.00',
  `imagem` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disponivel` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sabores_disponivel` (`disponivel`),
  KEY `idx_sabores_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sabores`
--

LOCK TABLES `sabores` WRITE;
/*!40000 ALTER TABLE `sabores` DISABLE KEYS */;
INSERT INTO `sabores` VALUES (1,'Calabresa','Calabresa fatiada, cebola e azeitonas',40.00,'calabresa.png',1,'2026-11-13 15:36:26',NULL),(2,'Mussarela','Mussarela e rodelas de tomate',38.00,'mussarela.png',1,'2026-11-13 15:36:26',NULL),(3,'Frango com Catupiry','Frango desfiado com Catupiry',44.00,'frango_catupiry.png',1,'2026-11-13 15:36:26',NULL),(4,'Quatro Queijos','Mussarela, provolone, parmesão e gorgonzola',46.00,'quatro_queijos.png',1,'2026-11-13 15:36:26',NULL),(5,'Portuguesa','Presunto, ovos, cebola, ervilha e mussarela',42.00,'portuguesa.png',1,'2026-11-13 15:36:26',NULL);
/*!40000 ALTER TABLE `sabores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tamanhos`
--

DROP TABLE IF EXISTS `tamanhos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tamanhos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_sabores` int NOT NULL DEFAULT '1',
  `preco_base` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `quantidade_fatias` tinyint unsigned DEFAULT NULL,
  `disponivel` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tamanhos_nome` (`nome`),
  KEY `idx_tamanhos_disponivel` (`disponivel`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tamanhos`
--

LOCK TABLES `tamanhos` WRITE;
/*!40000 ALTER TABLE `tamanhos` DISABLE KEYS */;
INSERT INTO `tamanhos` VALUES (1,'Broto',1,25.00,'Pizza individual',4,1,'2026-11-13 15:36:26',NULL),(2,'Pequena',1,35.00,'Pizza pequena',6,1,'2026-11-13 15:36:26',NULL),(3,'Média',2,45.00,'Pizza média',8,1,'2026-11-13 15:36:26',NULL),(4,'Grande',2,55.00,'Pizza grande',8,1,'2026-11-13 15:36:26',NULL),(5,'Família',3,65.00,'Pizza família',12,1,'2026-11-13 15:36:26',NULL);
/*!40000 ALTER TABLE `tamanhos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_massas`
--

DROP TABLE IF EXISTS `tipos_massas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_massas` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `preco_adicional` decimal(10,2) NOT NULL DEFAULT '0.00',
  `disponivel` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tipos_massas_nome` (`nome`),
  KEY `idx_tipos_massas_disponivel` (`disponivel`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_massas`
--

LOCK TABLES `tipos_massas` WRITE;
/*!40000 ALTER TABLE `tipos_massas` DISABLE KEYS */;
INSERT INTO `tipos_massas` VALUES (1,'Tradicional','Massa tradicional da casa',0.00,1,'2026-11-13 15:36:26',NULL),(2,'Integral','Massa integral',3.00,1,'2026-11-13 15:36:26',NULL),(3,'Fina','Massa fina e crocante',2.00,1,'2026-11-13 15:36:26',NULL),(4,'Pan','Massa alta e macia',4.00,1,'2026-11-13 15:36:26',NULL);
/*!40000 ALTER TABLE `tipos_massas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `perfil` enum('admin','atendimento','cozinha') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'atendimento',
  `status` enum('ativo','inativo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ativo',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`),
  KEY `idx_usuarios_perfil_status` (`perfil`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','admin@briafatia.com','$2y$12$zRz0VJfGX7x0lX6z9xQfQ.CrMVE6nGN/aV4psv/jBT7qL/nTdwAk6','admin','ativo','2026-11-13 15:36:26',NULL),(2,'Atendimento Bella Roza','atendimento@briafatia.com','$2y$12$zRz0VJfGX7x0lX6z9xQfQ.CrMVE6nGN/aV4psv/jBT7qL/nTdwAk6','atendimento','ativo','2026-11-13 15:36:26',NULL),(3,'Cozinha Bella Roza','cozinha@briafatia.com','$2y$12$zRz0VJfGX7x0lX6z9xQfQ.CrMVE6nGN/aV4psv/jBT7qL/nTdwAk6','cozinha','ativo','2026-11-13 15:36:26',NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-16 16:21:57
