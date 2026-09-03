-- ============================================================
-- ATUALIZAÇÃO v9: perguntas de registo configuráveis pelo admin,
-- mostradas dinamicamente no wizard de registo (paciente/psicólogo)
-- Executa uma vez numa base de dados já existente.
-- ============================================================
USE lyrios;

CREATE TABLE IF NOT EXISTS perguntas_registo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_utilizador ENUM('paciente','psicologo') NOT NULL,
    pergunta VARCHAR(255) NOT NULL,
    tipo_campo ENUM('chip_multipla','cartao_unica','texto') NOT NULL DEFAULT 'texto',
    opcoes VARCHAR(600) DEFAULT NULL, -- opções separadas por vírgula (chip_multipla / cartao_unica)
    obrigatorio TINYINT(1) NOT NULL DEFAULT 0,
    ordem INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS respostas_registo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT NOT NULL,
    pergunta_id INT NOT NULL,
    resposta VARCHAR(1000) NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (pergunta_id) REFERENCES perguntas_registo(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Foto no depoimento (mostrada com efeito espelho na página História)
-- ------------------------------------------------------------
ALTER TABLE depoimentos
    ADD COLUMN IF NOT EXISTS foto VARCHAR(255) DEFAULT NULL;
