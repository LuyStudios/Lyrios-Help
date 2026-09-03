-- ============================================================
-- ATUALIZAÇÃO v5: Sistema de estrelas, recuperação de password,
-- confirmação de email, data de nascimento (idade mínima)
-- Executa este ficheiro UMA VEZ numa base de dados já existente.
-- Instalações novas: usa apenas o schema.sql, que já inclui tudo.
-- ============================================================
USE mindcare;

ALTER TABLE utilizadores
    ADD COLUMN IF NOT EXISTS data_nascimento DATE NULL,
    ADD COLUMN IF NOT EXISTS email_verificado TINYINT(1) NOT NULL DEFAULT 0;

-- Contas já existentes ficam consideradas verificadas (não bloqueamos quem já usava a plataforma)
UPDATE utilizadores SET email_verificado = 1 WHERE email_verificado = 0;

CREATE TABLE IF NOT EXISTS avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consulta_id INT NOT NULL UNIQUE,
    psicologo_id INT NOT NULL,
    paciente_id INT NOT NULL,
    nota TINYINT NOT NULL,
    comentario VARCHAR(500) DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE CASCADE,
    FOREIGN KEY (psicologo_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (paciente_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    CHECK (nota BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS verificacoes_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;
