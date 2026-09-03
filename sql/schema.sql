-- ============================================================
-- MINDCARE - Plataforma de Consultas Online de Psicologia
-- Base de dados completa
-- ============================================================

CREATE DATABASE IF NOT EXISTS mindcare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mindcare;

-- ------------------------------------------------------------
-- Utilizadores (pacientes, psicólogos e admin)
-- ------------------------------------------------------------
CREATE TABLE utilizadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefone VARCHAR(30),
    tipo ENUM('paciente','psicologo','admin') NOT NULL DEFAULT 'paciente',
    foto VARCHAR(255) DEFAULT NULL,
    data_nascimento DATE NULL,
    email_verificado TINYINT(1) NOT NULL DEFAULT 0,
    motivo_procura VARCHAR(255) DEFAULT NULL,
    experiencia_terapia_previa TINYINT(1) DEFAULT NULL,
    preferencia_genero_psicologo ENUM('sem_preferencia','feminino','masculino') NOT NULL DEFAULT 'sem_preferencia',
    estado ENUM('ativo','pendente','bloqueado') NOT NULL DEFAULT 'ativo',
    tentativas_login INT NOT NULL DEFAULT 0,
    bloqueado_ate DATETIME NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Perfil adicional dos psicólogos
-- ------------------------------------------------------------
CREATE TABLE perfis_psicologos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT NOT NULL,
    especialidade VARCHAR(150),
    numero_cedula VARCHAR(100),
    biografia TEXT,
    preco_sessao DECIMAL(10,2) NOT NULL DEFAULT 5000.00,
    status_personalizado VARCHAR(100) DEFAULT NULL,
    anos_experiencia VARCHAR(20) DEFAULT NULL,
    abordagens_terapeuticas VARCHAR(255) DEFAULT NULL,
    aprovado TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Serviços da plataforma (o admin pode adicionar novos)
-- ------------------------------------------------------------
CREATE TABLE servicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    preco_base DECIMAL(10,2) NOT NULL DEFAULT 0,
    icone VARCHAR(100) DEFAULT 'fa-heart',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Consultas / Marcações
-- ------------------------------------------------------------
CREATE TABLE consultas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    psicologo_id INT NOT NULL,
    servico_id INT DEFAULT NULL,
    data_hora DATETIME NOT NULL,
    sala_codigo VARCHAR(100) NOT NULL,
    estado ENUM('pendente','confirmada','concluida','cancelada') NOT NULL DEFAULT 'pendente',
    observacoes TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (psicologo_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Pagamentos (a plataforma fica com uma percentagem)
-- ------------------------------------------------------------
CREATE TABLE pagamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consulta_id INT NOT NULL,
    valor_total DECIMAL(10,2) NOT NULL,
    percentagem_plataforma DECIMAL(5,2) NOT NULL,
    valor_plataforma DECIMAL(10,2) NOT NULL,
    valor_psicologo DECIMAL(10,2) NOT NULL,
    metodo ENUM('cartao','transferencia','multicaixa','simulado') NOT NULL DEFAULT 'simulado',
    gateway VARCHAR(30) NOT NULL DEFAULT 'simulado',
    referencia_gateway VARCHAR(150) DEFAULT NULL,
    payload_resposta TEXT DEFAULT NULL,
    cupao_id INT NULL,
    valor_desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado ENUM('pendente','pago','reembolsado','falhado') NOT NULL DEFAULT 'pendente',
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consulta_id) REFERENCES consultas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Configurações gerais da plataforma
-- ------------------------------------------------------------
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    percentagem_comissao DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    nome_plataforma VARCHAR(150) DEFAULT 'Lyrios'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Registo de atividades (para pacientes e psicólogos verem)
-- ------------------------------------------------------------
CREATE TABLE atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Parceiros (página "Parceiros")
-- ------------------------------------------------------------
CREATE TABLE parceiros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    logo VARCHAR(255),
    descricao TEXT,
    site VARCHAR(255),
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Mensagens de contacto (página "Contactos")
-- ------------------------------------------------------------
CREATE TABLE mensagens_contacto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    assunto VARCHAR(200),
    mensagem TEXT NOT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Métodos/gateways de pagamento (configuráveis pelo admin)
-- ------------------------------------------------------------
CREATE TABLE metodos_pagamento (
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

-- ------------------------------------------------------------
-- Registo de eventos de segurança
-- ------------------------------------------------------------
CREATE TABLE logs_seguranca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    utilizador_id INT NULL,
    ip VARCHAR(45),
    descricao VARCHAR(255),
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Avaliações dos pacientes aos psicólogos (sistema de estrelas)
-- ------------------------------------------------------------
CREATE TABLE avaliacoes (
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

-- ------------------------------------------------------------
-- Recuperação de password
-- ------------------------------------------------------------
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expira_em DATETIME NOT NULL,
    usado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Confirmação de email
-- ------------------------------------------------------------
CREATE TABLE verificacoes_email (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Certificados / documentos de verificação profissional dos psicólogos
-- ------------------------------------------------------------
CREATE TABLE certificados (
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
CREATE TABLE conversas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    psicologo_id INT NOT NULL,
    sala_codigo VARCHAR(100) NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY par_unico (paciente_id, psicologo_id),
    FOREIGN KEY (paciente_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (psicologo_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Mensagens de texto, áudio e avisos de chamada entre paciente e psicólogo
-- ------------------------------------------------------------
CREATE TABLE mensagens_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversa_id INT NOT NULL,
    remetente_id INT NOT NULL,
    tipo ENUM('texto','audio','chamada') NOT NULL DEFAULT 'texto',
    conteudo TEXT NULL,
    ficheiro_audio VARCHAR(255) NULL,
    duracao_segundos INT NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversa_id) REFERENCES conversas(id) ON DELETE CASCADE,
    FOREIGN KEY (remetente_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Disponibilidade semanal de cada psicólogo
-- ------------------------------------------------------------
CREATE TABLE disponibilidades (
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
CREATE TABLE perguntas_questionario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    texto VARCHAR(300) NOT NULL,
    ordem INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE respostas_questionario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    pergunta_id INT NOT NULL,
    resposta VARCHAR(1000) NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY resposta_unica (paciente_id, pergunta_id),
    FOREIGN KEY (paciente_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (pergunta_id) REFERENCES perguntas_questionario(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Depoimentos de pacientes (mostrados na página História)
-- ------------------------------------------------------------
CREATE TABLE depoimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_paciente VARCHAR(150) NOT NULL,
    texto VARCHAR(600) NOT NULL,
    foto_url VARCHAR(500) DEFAULT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Levantamentos (psicólogos levantam o valor investido/ganho)
-- ------------------------------------------------------------
CREATE TABLE levantamentos (
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
CREATE TABLE cupoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(40) NOT NULL UNIQUE,
    percentagem_desconto DECIMAL(5,2) NOT NULL,
    validade DATE DEFAULT NULL,
    usos_maximos INT DEFAULT NULL,
    usos_atuais INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Perguntas de registo configuráveis pelo admin (wizard dinâmico)
-- ------------------------------------------------------------
CREATE TABLE perguntas_registo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_utilizador ENUM('paciente','psicologo') NOT NULL,
    pergunta VARCHAR(255) NOT NULL,
    tipo_campo ENUM('chip_multipla','cartao_unica','texto') NOT NULL DEFAULT 'texto',
    opcoes VARCHAR(600) DEFAULT NULL,
    obrigatorio TINYINT(1) NOT NULL DEFAULT 0,
    ordem INT NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE respostas_registo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT NOT NULL,
    pergunta_id INT NOT NULL,
    resposta VARCHAR(1000) NOT NULL,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (pergunta_id) REFERENCES perguntas_registo(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

INSERT INTO configuracoes (percentagem_comissao, nome_plataforma) VALUES (20.00, 'Lyrios');

-- Admin padrão -> email: admin@lyrios.com | password: admin123
INSERT INTO utilizadores (nome, email, password, tipo, estado)
VALUES ('Administrador', 'admin@lyrios.com', '$2y$10$UewD7wJj9c6ZJmJqM5m0XOEwyOZKq9c4b5N1n7c3sVnUZfF1Z1ZlG', 'admin', 'ativo');
-- (a password real "admin123" é gerada no ficheiro criar_admin.php, ver instruções no README)

INSERT INTO servicos (nome, descricao, preco_base, icone) VALUES
('Consulta Individual', 'Sessão de acompanhamento psicológico individual, 50 minutos.', 6000.00, 'fa-user'),
('Terapia de Casal', 'Sessão para casais com foco na comunicação e relacionamento.', 9000.00, 'fa-heart'),
('Apoio a Adolescentes', 'Acompanhamento psicológico especializado para adolescentes.', 5500.00, 'fa-child'),
('Consulta de Urgência', 'Apoio psicológico rápido para momentos de crise.', 7500.00, 'fa-triangle-exclamation');

INSERT INTO parceiros (nome, descricao, site) VALUES
('Clínica Bem-Estar', 'Parceiro clínico de referência em saúde mental.', '#'),
('Instituto Meridian de Saúde', 'Parceria académica e de investigação em bem-estar psicológico.', '#');

INSERT INTO metodos_pagamento (gateway, nome_visivel, ativo) VALUES
('simulado', 'Pagamento Simulado (testes)', 1),
('multicaixa', 'Multicaixa Express', 0),
('redopay', 'RedoPay', 0),
('wesi', 'Wesi', 0);

INSERT INTO perguntas_questionario (texto, ordem) VALUES
('Já fizeste algum acompanhamento psicológico antes?', 1),
('O que te motiva a procurar apoio psicológico neste momento?', 2),
('Como descreverias o teu estado emocional geral nas últimas semanas?', 3),
('Tomas atualmente alguma medicação relacionada com saúde mental?', 4),
('Há algo em específico que gostarias que o teu psicólogo soubesse antes da primeira sessão?', 5);

-- Nota: as fotos abaixo são fotos de stock com licença Unsplash (uso comercial livre),
-- usadas apenas como exemplo/placeholder. Substitui por fotos e depoimentos reais dos
-- teus próprios pacientes (com consentimento deles) antes de lançares em produção.
INSERT INTO depoimentos (nome_paciente, texto, foto_url) VALUES
('Marta S.', 'A Lyrios tornou muito mais fácil encontrar apoio psicológico sem ter de sair de casa. Recomendo!', 'https://images.unsplash.com/photo-1565793244233-3d09028aad47?fm=jpg&q=80&w=400&auto=format&fit=crop'),
('João P.', 'O acompanhamento por videochamada foi surpreendentemente próximo e humano. Sinto-me muito mais tranquilo.', 'https://images.unsplash.com/photo-1624224416603-c908080780b1?fm=jpg&q=80&w=400&auto=format&fit=crop'),
('Carla N.', 'Gosto de poder mandar uma mensagem de áudio ao meu psicólogo entre sessões quando preciso. Faz toda a diferença.', 'https://images.unsplash.com/photo-1758518727888-ffa196002e59?fm=jpg&q=80&w=400&auto=format&fit=crop');
