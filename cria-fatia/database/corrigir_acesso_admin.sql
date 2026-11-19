-- ============================================================
-- CORREÇÃO DE ACESSO - CRIA FATIA
-- Execute uma vez no banco cria_fatia se os usuários já existirem.
-- Senha temporária dos três usuários: admin123
-- Altere as senhas após entrar no sistema, quando houver tela própria.
-- ============================================================
USE cria_fatia;

UPDATE usuarios
SET senha = '$2y$12$zRz0VJfGX7x0lX6z9xQfQ.CrMVE6nGN/aV4psv/jBT7qL/nTdwAk6'
WHERE email IN (
    'admin@briafatia.com',
    'atendimento@briafatia.com',
    'cozinha@briafatia.com'
);

-- Conferência: deve retornar os três usuários.
SELECT id, nome, email, perfil, LENGTH(senha) AS tamanho_hash
FROM usuarios
WHERE email IN (
    'admin@briafatia.com',
    'atendimento@briafatia.com',
    'cozinha@briafatia.com'
)
ORDER BY id;
