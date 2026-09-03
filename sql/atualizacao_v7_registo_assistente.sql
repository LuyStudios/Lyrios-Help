-- ============================================================
-- ATUALIZAÇÃO v7: campos do novo registo por etapas (estilo
-- assistente/wizard) — motivo de procura, experiência prévia,
-- preferência de género do psicólogo
-- Executa uma vez numa base de dados já existente.
-- ============================================================
USE mindcare;

ALTER TABLE utilizadores
    ADD COLUMN IF NOT EXISTS motivo_procura VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS experiencia_terapia_previa TINYINT(1) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS preferencia_genero_psicologo ENUM('sem_preferencia','feminino','masculino') NOT NULL DEFAULT 'sem_preferencia';
