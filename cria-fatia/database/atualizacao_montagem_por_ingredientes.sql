-- Atualização para permitir pizzas montadas diretamente pelos ingredientes.
-- Execute uma única vez no banco cria_fatia.
ALTER TABLE pedido_itens DROP FOREIGN KEY pedido_itens_ibfk_4;
ALTER TABLE pedido_itens MODIFY sabor1_id INT NULL;
ALTER TABLE pedido_itens ADD CONSTRAINT fk_pedido_itens_sabor1 FOREIGN KEY (sabor1_id) REFERENCES sabores(id) ON DELETE SET NULL;
