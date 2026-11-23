-- Migração Segura Etapa 2: Adição de Índices
ALTER TABLE pedidos ADD INDEX IF NOT EXISTS idx_status (status);
ALTER TABLE pedidos ADD INDEX IF NOT EXISTS idx_criado_em (criado_em);