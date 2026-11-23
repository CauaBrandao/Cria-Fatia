-- Retira os itens removidos do catálogo sem apagar o histórico de pedidos antigos.
UPDATE ingredientes
SET disponivel = 0
WHERE LOWER(TRIM(nome)) IN ('cereais', 'conhaque');

DELETE FROM ingredientes
WHERE LOWER(TRIM(nome)) IN ('cereais', 'conhaque')
  AND id NOT IN (SELECT ingrediente_id FROM pedido_item_customizacoes);

-- Reaponta pedidos para o primeiro cadastro de cada massa antes de remover duplicatas.
UPDATE pedido_itens pi
INNER JOIN tipos_massas atual ON atual.id = pi.massa_id
INNER JOIN (
    SELECT LOWER(TRIM(nome)) AS nome_normalizado, MIN(id) AS id_canonico
    FROM tipos_massas
    GROUP BY LOWER(TRIM(nome))
) canonica ON canonica.nome_normalizado = LOWER(TRIM(atual.nome))
SET pi.massa_id = canonica.id_canonico
WHERE pi.massa_id <> canonica.id_canonico;

DELETE duplicada
FROM tipos_massas duplicada
INNER JOIN (
    SELECT LOWER(TRIM(nome)) AS nome_normalizado, MIN(id) AS id_canonico
    FROM tipos_massas
    GROUP BY LOWER(TRIM(nome))
) canonica ON canonica.nome_normalizado = LOWER(TRIM(duplicada.nome))
WHERE duplicada.id <> canonica.id_canonico;

ALTER TABLE tipos_massas
ADD UNIQUE KEY uq_tipos_massas_nome (nome);
