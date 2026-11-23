-- Colunas utilizadas pelas telas administrativas e pelo checkout.
ALTER TABLE tamanhos ADD COLUMN IF NOT EXISTS descricao TEXT DEFAULT NULL;
ALTER TABLE tamanhos ADD COLUMN IF NOT EXISTS quantidade_fatias INT NOT NULL DEFAULT 0;
ALTER TABLE tamanhos ADD COLUMN IF NOT EXISTS disponivel TINYINT(1) NOT NULL DEFAULT 1;

UPDATE tamanhos SET quantidade_fatias = 4 WHERE id = 1;
UPDATE tamanhos SET quantidade_fatias = 6 WHERE id = 2;
UPDATE tamanhos SET quantidade_fatias = 8 WHERE id = 3;

ALTER TABLE ingredientes ADD COLUMN IF NOT EXISTS descricao TEXT DEFAULT NULL;
ALTER TABLE ingredientes ADD COLUMN IF NOT EXISTS categoria VARCHAR(100) DEFAULT NULL;

ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS forma_pagamento ENUM('pix', 'cartao', 'dinheiro') NOT NULL DEFAULT 'pix';
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS taxa_entrega DECIMAL(10, 2) NOT NULL DEFAULT 0.00;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS troco_para DECIMAL(10, 2) DEFAULT NULL;
