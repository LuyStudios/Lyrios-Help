-- ============================================================
-- ATUALIZAÇÃO: Chamadas iniciadas a partir do chat
-- Executa este ficheiro UMA VEZ numa base de dados já existente.
-- Instalações novas: usa apenas o schema.sql, que já inclui tudo.
-- ============================================================
USE mindcare;

ALTER TABLE conversas
    ADD COLUMN IF NOT EXISTS sala_codigo VARCHAR(100) NULL;

ALTER TABLE mensagens_chat
    MODIFY tipo ENUM('texto','audio','chamada') NOT NULL DEFAULT 'texto';
