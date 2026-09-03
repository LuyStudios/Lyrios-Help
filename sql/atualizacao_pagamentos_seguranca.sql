-- ============================================================
-- ATUALIZAÇÃO: Segurança + Gateways de Pagamento (Multicaixa
-- Express, RedoPay, Wesi)
-- Executa este ficheiro UMA VEZ numa base de dados Lyrios já
-- existente (via phpMyAdmin: Importar, ou linha de comando).
-- Instalações novas: usa apenas o schema.sql, que já inclui tudo.
-- ============================================================
USE mindcare;

-- ---- Segurança: bloqueio de conta por tentativas falhadas ----
ALTER TABLE utilizadores
    ADD COLUMN IF NOT EXISTS tentativas_login INT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS bloqueado_ate DATETIME NULL;

-- ---- Pagamentos: rastreio do gateway usado em cada transação ----
ALTER TABLE pagamentos
    ADD COLUMN IF NOT EXISTS gateway VARCHAR(30) NOT NULL DEFAULT 'simulado',
    ADD COLUMN IF NOT EXISTS referencia_gateway VARCHAR(150) NULL,
    ADD COLUMN IF NOT EXISTS payload_resposta TEXT NULL;

-- ---- Consultas passam a nascer "pendente" até o pagamento ser confirmado ----
ALTER TABLE consultas
    MODIFY estado ENUM('pendente','confirmada','concluida','cancelada') NOT NULL DEFAULT 'pendente';

-- ---- Configuração de cada gateway de pagamento (gerida pelo admin) ----
CREATE TABLE IF NOT EXISTS metodos_pagamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway VARCHAR(30) NOT NULL UNIQUE,
    nome_visivel VARCHAR(100) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 0,
    url_api VARCHAR(255) DEFAULT NULL,
    chave_publica VARCHAR(255) DEFAULT NULL,
    chave_privada VARCHAR(255) DEFAULT NULL,
    pos_id VARCHAR(100) DEFAULT NULL,
    supervisor_card VARCHAR(100) DEFAULT NULL,
    chave_webhook VARCHAR(255) DEFAULT NULL,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO metodos_pagamento (gateway, nome_visivel, ativo) VALUES
('simulado', 'Pagamento Simulado (testes)', 1),
('multicaixa', 'Multicaixa Express', 0),
('redopay', 'RedoPay', 0),
('wesi', 'Wesi', 0);

-- ---- Registo de eventos de segurança (logins falhados, webhooks inválidos, etc.) ----
CREATE TABLE IF NOT EXISTS logs_seguranca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    utilizador_id INT NULL,
    ip VARCHAR(45),
    descricao VARCHAR(255),
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
