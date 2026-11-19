-- Migração para inclusão de Código de Acompanhamento e Token de Acesso
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS codigo_acompanhamento VARCHAR(20) UNIQUE DEFAULT NULL;
ALTER TABLE pedidos ADD COLUMN IF NOT EXISTS token_acesso VARCHAR(64) DEFAULT NULL;
ALTER TABLE pedidos ADD INDEX IF NOT EXISTS idx_codigo (codigo_acompanhamento);
