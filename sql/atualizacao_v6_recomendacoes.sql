-- ============================================================
-- ATUALIZAÇÃO v6: restante da lista de recomendações
-- (questionário, disponibilidade semanal, depoimentos, status
-- personalizável, levantamentos, cupões de desconto)
-- Executa uma vez numa base de dados já existente.
-- ============================================================
USE mindcare;

ALTER TABLE perfis_psicologos
    ADD COLUMN IF NOT EXISTS status_personalizado VARCHAR(100) DEFAULT NULL;

-- ------------------------------------------------------------
-- Disponibilidade semanal de cada psicólogo
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS disponibilidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    psicologo_id INT NOT NULL,
    dia_semana TINYINT NOT NULL, -- 0=Domingo ... 6=Sábado
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    FOREIGN KEY (psicologo_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Questionário inicial do paciente
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS perguntas_questionario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    texto VARCHAR(300) NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS respostas_questionario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    pergunta_id INT NOT NULL,
    resposta VARCHAR(1000) NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY resposta_unica (paciente_id, pergunta_id),
    FOREIGN KEY (paciente_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (pergunta_id) REFERENCES perguntas_questionario(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO perguntas_questionario (texto, ordem) VALUES
('Já fizeste algum acompanhamento psicológico antes?', 1),
('O que te motiva a procurar apoio psicológico neste momento?', 2),
('Como descreverias o teu estado emocional geral nas últimas semanas?', 3),
('Tomas atualmente alguma medicação relacionada com saúde mental?', 4),
('Há algo em específico que gostarias que o teu psicólogo soubesse antes da primeira sessão?', 5);

-- ------------------------------------------------------------
-- Depoimentos de pacientes (mostrados na página História)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS depoimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_paciente VARCHAR(150) NOT NULL,
    texto VARCHAR(600) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO depoimentos (nome_paciente, texto) VALUES
('Marta S.', 'A Lyrios tornou muito mais fácil encontrar apoio psicológico sem ter de sair de casa. Recomendo!'),
('João P.', 'O acompanhamento por videochamada foi surpreendentemente próximo e humano. Sinto-me muito mais tranquilo.');

-- ------------------------------------------------------------
-- Levantamentos (psicólogos levantam o valor investido/ganho)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS levantamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    psicologo_id INT NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    estado ENUM('pendente','pago','rejeitado') NOT NULL DEFAULT 'pendente',
    referencia_bancaria VARCHAR(150) DEFAULT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    processado_em DATETIME NULL,
    FOREIGN KEY (psicologo_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Cupões / ofertas de consulta
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cupoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL UNIQUE,
    percentagem_desconto DECIMAL(5,2) NOT NULL,
    validade DATE DEFAULT NULL,
    usos_maximos INT DEFAULT NULL,
    usos_atuais INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE pagamentos
    ADD COLUMN IF NOT EXISTS cupao_id INT NULL,
    ADD COLUMN IF NOT EXISTS valor_desconto DECIMAL(10,2) NOT NULL DEFAULT 0;
