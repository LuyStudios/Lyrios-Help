-- ============================================================
-- ATUALIZAÇÃO v8: novos campos do registo do psicólogo em forma
-- de assistente/wizard (especialidades múltiplas, anos de
-- experiência, abordagens terapêuticas)
-- Executa uma vez numa base de dados já existente.
-- ============================================================
USE lyrios;

ALTER TABLE perfis_psicologos
    ADD COLUMN IF NOT EXISTS anos_experiencia VARCHAR(20) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS abordagens_terapeuticas VARCHAR(255) DEFAULT NULL;
