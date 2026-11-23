-- Colunas utilizadas pelas telas administrativas e pelo checkout.
ALTER TABLE tamanhos ADD COLUMN IF NOT EXISTS descricao TEXT DEFAULT NULL;
ALTER TABLE tamanhos ADD COLUMN IF NOT EXISTS quantidade_fatias INT NOT NULL DEFAULT 0;
ALTER TABLE tamanhos ADD COLUMN IF NOT EXISTS disponivel TINYINT(1) NOT NULL DEFAULT 1;

UPDATE tamanhos SET quantidade_fatias = 4 WHERE id = 1 AND quantidade_fatias = 0;
UPDATE tamanhos SET quantidade_fatias = 6 WHERE id = 2 AND quantidade_fatias = 0;
UPDATE tamanhos SET quantidade_fatias = 8 WHERE id = 3 AND quantidade_fatias = 0;

ALTER TABLE ingredientes ADD COLUMN IF NOT EXISTS descricao TEXT DEFAULT NULL;
ALTER TABLE ingredientes ADD COLUMN IF NOT EXISTS categoria VARCHAR(100) DEFAULT NULL;

ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS forma_pagamento ENUM('pix', 'cartao', 'dinheiro') NOT NULL DEFAULT 'pix';
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS taxa_entrega DECIMAL(10, 2) NOT NULL DEFAULT 0.00;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS troco_para DECIMAL(10, 2) DEFAULT NULL;

-- Garante integridade referencial da massa utilizada em pedidos existentes.
SET @massa_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'pedido_itens'
      AND index_name = 'idx_pedido_itens_massa'
);
SET @massa_index_sql = IF(
    @massa_index_exists = 0,
    'ALTER TABLE pedido_itens ADD INDEX idx_pedido_itens_massa (massa_id)',
    'SELECT 1'
);
PREPARE massa_index_stmt FROM @massa_index_sql;
EXECUTE massa_index_stmt;
DEALLOCATE PREPARE massa_index_stmt;

SET @massa_fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'pedido_itens'
      AND constraint_name = 'fk_pedido_itens_massa'
      AND constraint_type = 'FOREIGN KEY'
);
SET @massa_fk_sql = IF(
    @massa_fk_exists = 0,
    'ALTER TABLE pedido_itens ADD CONSTRAINT fk_pedido_itens_massa FOREIGN KEY (massa_id) REFERENCES tipos_massas(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE massa_fk_stmt FROM @massa_fk_sql;
EXECUTE massa_fk_stmt;
DEALLOCATE PREPARE massa_fk_stmt;
