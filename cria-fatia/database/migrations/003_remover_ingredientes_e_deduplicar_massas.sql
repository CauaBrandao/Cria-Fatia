-- Retira os itens do catálogo preservando pedidos e relações históricas.
UPDATE ingredientes
SET disponivel = 0
WHERE LOWER(TRIM(nome)) IN ('cereais', 'conhaque');

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

SET @index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'tipos_massas'
      AND index_name = 'uq_tipos_massas_nome'
);
SET @index_sql = IF(
    @index_exists = 0,
    'ALTER TABLE tipos_massas ADD UNIQUE KEY uq_tipos_massas_nome (nome)',
    'SELECT 1'
);
PREPARE index_stmt FROM @index_sql;
EXECUTE index_stmt;
DEALLOCATE PREPARE index_stmt;
