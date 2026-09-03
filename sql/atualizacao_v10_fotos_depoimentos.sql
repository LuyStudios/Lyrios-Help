-- ============================================================
-- ATUALIZAÇÃO v10: fotos nos depoimentos (para o novo design
-- com efeitos de espelho/reflexo e carrossel na página inicial)
-- Executa uma vez numa base de dados já existente.
-- ============================================================
USE lyrios;

ALTER TABLE depoimentos
    ADD COLUMN IF NOT EXISTS foto_url VARCHAR(500) DEFAULT NULL;
