-- Migração aditiva: clientes, classificação de itens, reserva de troco e dados demo.
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clientes_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS cliente_id INT DEFAULT NULL;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS demo TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS troco_reservado DECIMAL(10,2) NOT NULL DEFAULT 0.00;
ALTER TABLE pedidos ADD INDEX IF NOT EXISTS idx_pedidos_cliente (cliente_id);
ALTER TABLE pedidos ADD INDEX IF NOT EXISTS idx_pedidos_demo (demo);

SET @cliente_fk_exists = (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE table_schema = DATABASE()
      AND table_name = 'pedidos'
      AND constraint_name = 'fk_pedidos_cliente'
      AND constraint_type = 'FOREIGN KEY'
);
SET @cliente_fk_sql = IF(
    @cliente_fk_exists = 0,
    'ALTER TABLE pedidos ADD CONSTRAINT fk_pedidos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE cliente_fk_stmt FROM @cliente_fk_sql;
EXECUTE cliente_fk_stmt;
DEALLOCATE PREPARE cliente_fk_stmt;

ALTER TABLE ingredientes ADD COLUMN IF NOT EXISTS tipo_item ENUM('ingrediente','acompanhamento') NOT NULL DEFAULT 'ingrediente';

SET @ingrediente_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'ingredientes'
      AND index_name = 'uq_ingredientes_nome'
);
SET @ingrediente_index_sql = IF(
    @ingrediente_index_exists = 0,
    'ALTER TABLE ingredientes ADD UNIQUE KEY uq_ingredientes_nome (nome)',
    'SELECT 1'
);
PREPARE ingrediente_index_stmt FROM @ingrediente_index_sql;
EXECUTE ingrediente_index_stmt;
DEALLOCATE PREPARE ingrediente_index_stmt;

UPDATE ingredientes
SET tipo_item = 'acompanhamento'
WHERE LOWER(nome) LIKE '%azeitona%' OR LOWER(nome) LIKE '%orégano%' OR LOWER(nome) LIKE '%oregano%';

ALTER TABLE caixa_movimentacoes MODIFY tipo ENUM('entrada','saida','venda','reserva_troco','liberacao_troco') NOT NULL;

CREATE TABLE IF NOT EXISTS cancelamentos_pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    usuario_id INT NOT NULL,
    status_original VARCHAR(30) NOT NULL,
    motivo TEXT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_cancelamento_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

