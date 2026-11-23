-- Atualiza os tamanhos sem alterar seus IDs, preços ou vínculos existentes.
UPDATE tamanhos SET nome = 'Broto (4 pedaços)', max_sabores = 1 WHERE id = 1;
UPDATE tamanhos SET nome = 'Média (6 pedaços)', max_sabores = 2 WHERE id = 2;
UPDATE tamanhos SET nome = 'Grande (8 pedaços)', max_sabores = 2 WHERE id = 3;
