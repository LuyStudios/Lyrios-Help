-- ============================================================
-- ATUALIZAÇÃO: Certificados/verificação de psicólogos + Chat
-- (mensagens de texto e áudio entre paciente e psicólogo)
-- Executa este ficheiro UMA VEZ numa base de dados já existente.
-- Instalações novas: usa apenas o schema.sql, que já inclui tudo.
-- ============================================================
USE mindcare;

-- ------------------------------------------------------------
-- Certificados / documentos de verificação profissional
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS certificados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    psicologo_id INT NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    caminho VARCHAR(255) NOT NULL,
    tipo VARCHAR(100),
    estado ENUM('pendente','aprovado','rejeitado') NOT NULL DEFAULT 'pendente',
    motivo_rejeicao VARCHAR(255) DEFAULT NULL,
    enviado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    revisto_em DATETIME NULL,
    FOREIGN KEY (psicologo_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Conversas (uma por cada par paciente + psicólogo)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conversas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    psicologo_id INT NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY par_unico (paciente_id, psicologo_id),
    FOREIGN KEY (paciente_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (psicologo_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Mensagens de texto e de áudio
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mensagens_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversa_id INT NOT NULL,
    remetente_id INT NOT NULL,
    tipo ENUM('texto','audio') NOT NULL DEFAULT 'texto',
    conteudo TEXT NULL,
    ficheiro_audio VARCHAR(255) NULL,
    duracao_segundos INT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversa_id) REFERENCES conversas(id) ON DELETE CASCADE,
    FOREIGN KEY (remetente_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;
