CREATE TABLE departamentos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'ATIVO'
);

CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('ADMIN_GERAL','ADMIN_DEPARTAMENTO','USUARIO','VISUALIZADOR') NOT NULL,
    departamento_id BIGINT UNSIGNED NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'ATIVO',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
);

CREATE TABLE pastas (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    departamento_id BIGINT UNSIGNED NOT NULL,
    nivel INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pastas_parent FOREIGN KEY (parent_id) REFERENCES pastas(id),
    CONSTRAINT fk_pastas_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
);

CREATE TABLE documentos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pasta_id BIGINT UNSIGNED NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    tipo VARCHAR(100) NOT NULL,
    status ENUM('EM_EDICAO','PENDENTE_ASSINATURA','ASSINADO') NOT NULL DEFAULT 'EM_EDICAO',
    versao_atual INT UNSIGNED NOT NULL DEFAULT 1,
    criado_por BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documentos_pasta FOREIGN KEY (pasta_id) REFERENCES pastas(id),
    CONSTRAINT fk_documentos_usuario FOREIGN KEY (criado_por) REFERENCES users(id)
);

CREATE TABLE documentos_arquivos (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    documento_id BIGINT UNSIGNED NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    versao INT UNSIGNED NOT NULL,
    hash_sha256 CHAR(64) NULL,
    criado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_arquivos_documento FOREIGN KEY (documento_id) REFERENCES documentos(id)
);

CREATE TABLE documentos_metadados (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    documento_id BIGINT UNSIGNED NOT NULL,
    chave VARCHAR(100) NOT NULL,
    valor TEXT NOT NULL,
    CONSTRAINT fk_doc_metadados_documento FOREIGN KEY (documento_id) REFERENCES documentos(id)
);

CREATE TABLE assinaturas (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    documento_id BIGINT UNSIGNED NOT NULL,
    usuario_id BIGINT UNSIGNED NOT NULL,
    ordem INT UNSIGNED NOT NULL,
    status ENUM('PENDENTE','ASSINADO') NOT NULL DEFAULT 'PENDENTE',
    assinatura_imagem VARCHAR(500) NULL,
    ip VARCHAR(50) NULL,
    assinado_em TIMESTAMP NULL,
    CONSTRAINT fk_assinaturas_documento FOREIGN KEY (documento_id) REFERENCES documentos(id),
    CONSTRAINT fk_assinaturas_usuario FOREIGN KEY (usuario_id) REFERENCES users(id)
);

CREATE TABLE permissoes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    pasta_id BIGINT UNSIGNED NOT NULL,
    pode_ver TINYINT(1) NOT NULL DEFAULT 0,
    pode_enviar TINYINT(1) NOT NULL DEFAULT 0,
    pode_editar TINYINT(1) NOT NULL DEFAULT 0,
    pode_assinar TINYINT(1) NOT NULL DEFAULT 0,
    pode_excluir TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_permissoes_usuario FOREIGN KEY (usuario_id) REFERENCES users(id),
    CONSTRAINT fk_permissoes_pasta FOREIGN KEY (pasta_id) REFERENCES pastas(id)
);

CREATE TABLE logs_auditoria (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NULL,
    acao VARCHAR(255) NOT NULL,
    entidade VARCHAR(100) NOT NULL,
    entidade_id BIGINT UNSIGNED NULL,
    ip VARCHAR(50) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_usuario FOREIGN KEY (usuario_id) REFERENCES users(id)
);

CREATE INDEX idx_documentos_pasta ON documentos (pasta_id);
CREATE INDEX idx_documentos_status ON documentos (status);
CREATE INDEX idx_documentos_created_at ON documentos (created_at);
CREATE INDEX idx_docs_meta_chave_valor ON documentos_metadados (chave, valor(100));
CREATE INDEX idx_logs_created_at ON logs_auditoria (created_at);

CREATE TABLE documentos_ocr (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    documento_id BIGINT UNSIGNED NOT NULL,
    documento_arquivo_id BIGINT UNSIGNED NOT NULL,
    idioma VARCHAR(10) NOT NULL,
    texto_extraido LONGTEXT NOT NULL,
    paginas_processadas INT UNSIGNED NULL,
    engine VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_ocr_documento FOREIGN KEY (documento_id) REFERENCES documentos(id),
    CONSTRAINT fk_doc_ocr_arquivo FOREIGN KEY (documento_arquivo_id) REFERENCES documentos_arquivos(id)
);

CREATE FULLTEXT INDEX idx_doc_ocr_texto ON documentos_ocr (texto_extraido);
CREATE INDEX idx_doc_ocr_documento ON documentos_ocr (documento_id);
CREATE INDEX idx_doc_ocr_arquivo ON documentos_ocr (documento_arquivo_id);
