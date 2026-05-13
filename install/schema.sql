SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(120) NOT NULL,
  email      VARCHAR(180) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  role       ENUM('admin','editor','viewer') DEFAULT 'editor',
  active     TINYINT(1) DEFAULT 1,
  last_login DATETIME DEFAULT NULL,
  lang       VARCHAR(10) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  skey  VARCHAR(80) NOT NULL UNIQUE,
  sval  TEXT,
  label VARCHAR(120) DEFAULT '',
  grp   VARCHAR(60)  DEFAULT 'geral'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS entities (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(80)  NOT NULL,
  slug        VARCHAR(80)  NOT NULL UNIQUE,
  icon        VARCHAR(10)  DEFAULT '📋',
  description TEXT,
  color       VARCHAR(7)   DEFAULT '#00d4ff',
  position    INT          DEFAULT 0,
  active        TINYINT(1)   DEFAULT 1,
  api_responses TEXT         DEFAULT NULL,
  created_by    INT          DEFAULT NULL,
  created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Entity Fields ────────────────────────────────────────────────────
-- field_type suportados (29 tipos):
--
-- TEXTO E COMUNICAÇÃO:
--   text        — Texto curto                     → val_text
--   textarea    — Texto longo (plain)              → val_text
--   richtext    — Texto rico (HTML sanitizado)     → val_text
--   email       — E-mail (input type=email)        → val_text
--   url         — URL (input type=url)             → val_text
--   phone       — Telefone                         → val_text
--   password    — Senha / dado mascarado            → val_text
--
-- NÚMEROS E VALORES:
--   number      — Número decimal                   → val_num
--   currency    — Moeda (R$)                       → val_num
--   percent     — Percentual (0–100)               → val_num
--   rating      — Avaliação 1–5 estrelas           → val_num
--   progress    — Progresso 0–100%                 → val_num
--   duration    — Duração em segundos              → val_num
--
-- DATA E TEMPO:
--   date        — Data (YYYY-MM-DD)                → val_date
--   datetime    — Data e hora                      → val_date
--   time        — Hora (HH:MM)                     → val_text
--   daterange   — Intervalo JSON {start,end}        → val_text
--
-- SELEÇÃO E LISTAS:
--   select      — Lista escolha única              → val_text
--   multiselect — Lista múltipla (JSON array)      → val_text
--   checkbox    — Booleano "0"/"1"                 → val_text
--   tags        — Tags livres (JSON array)         → val_text
--   user        — ID de usuário do sistema         → val_text
--   color       — Cor hexadecimal (#rrggbb)        → val_text
--
-- RELACIONAMENTOS:
--   relation    — ID de registro de outra entidade → val_text
--
-- DADOS ESPECIAIS:
--   uuid        — UUID v4 auto-gerado              → val_text
--   json        — Objeto JSON livre                → val_text
--   ip          — Endereço IP / hostname           → val_text
--
-- MÍDIA E ARQUIVOS (armazenados como base64 em val_text, MEDIUMTEXT ≈16MB):
--   image       — Imagem (PNG/JPG/WEBP/GIF)        → val_text (data:image/...)
--   file        — Arquivo genérico                 → val_text (data:mime/...)
--
-- options_json:
--   select/multiselect → ["Opção 1","Opção 2",...]
--   image/file         → {"max_size_mb": 5, "filename": "nome.pdf"}
--   demais tipos       → null
CREATE TABLE IF NOT EXISTS entity_fields (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  entity_id          INT NOT NULL,
  name               VARCHAR(80)  NOT NULL,
  slug               VARCHAR(80)  NOT NULL,
  field_type         VARCHAR(30)  DEFAULT 'text',
  options_json       TEXT,
  relation_entity_id INT          DEFAULT NULL,
  required           TINYINT(1)   DEFAULT 0,
  show_in_list       TINYINT(1)   DEFAULT 1,
  position           INT          DEFAULT 0,
  created_at         DATETIME     DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_entity_slug (entity_id, slug),
  FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS entity_records (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  entity_id  INT NOT NULL,
  created_by INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_entity (entity_id),
  FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS record_values (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  record_id INT NOT NULL,
  field_id  INT NOT NULL,
  val_text  MEDIUMTEXT,
  val_num   DECIMAL(18,4) DEFAULT NULL,
  val_date  DATETIME      DEFAULT NULL,
  UNIQUE KEY uq_record_field (record_id, field_id),
  INDEX idx_record (record_id),
  FOREIGN KEY (record_id) REFERENCES entity_records(id) ON DELETE CASCADE,
  FOREIGN KEY (field_id)  REFERENCES entity_fields(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_log (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT DEFAULT NULL,
  action      VARCHAR(40),
  entity_id   INT DEFAULT NULL,
  record_id   INT DEFAULT NULL,
  description TEXT,
  ip          VARCHAR(45),
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO settings (skey, sval, label, grp) VALUES
  ('app_name',      'FlexCore',   'Nome do sistema',      'geral'),
  ('app_url',       '',          'URL da aplicação',     'geral'),
  ('app_logo',      '',          'Logo (URL ou base64)', 'geral'),
  ('app_favicon',   '',          'Favicon URL',          'geral'),
  ('color_accent',  '#00d4ff',   'Cor de destaque',      'tema'),
  ('color_accent2', '#6c5ce7',   'Cor secundária',       'tema'),
  ('theme_mode',    'dark',      'Modo do tema',         'tema'),
  ('theme_preset',  'default',   'Preset de tema',       'tema'),
  ('app_lang',      'pt_BR',     'Idioma padrão',        'geral');

-- ── Entity Permissions ───────────────────────────────────────────────
-- Permissões granulares por entidade e papel de usuário.
-- Quando não há linha para uma entidade, o comportamento é irrestrito
-- (todos os papéis podem fazer tudo — retrocompatibilidade com instâncias
-- existentes que não configuraram permissões).
CREATE TABLE IF NOT EXISTS entity_permissions (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  entity_id  INT NOT NULL,
  role       ENUM('admin','editor','viewer') NOT NULL,
  can_create TINYINT(1) DEFAULT 1,
  can_edit   TINYINT(1) DEFAULT 1,
  can_delete TINYINT(1) DEFAULT 0,
  UNIQUE KEY uq_entity_role (entity_id, role),
  FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── API Keys ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(120) NOT NULL,
  `key_hash`     VARCHAR(64)  NOT NULL UNIQUE,
  `key_preview`  VARCHAR(12)  NOT NULL,
  `permissions`  TEXT         NOT NULL,
  `rate_limit`   INT          DEFAULT 60,
  `active`       TINYINT(1)   DEFAULT 1,
  `last_used_at` DATETIME     DEFAULT NULL,
  `created_by`   INT          DEFAULT NULL,
  `created_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `expires_at`   DATETIME     DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── API Key Hits (rate limiting sliding window) ───────────────────────
CREATE TABLE IF NOT EXISTS `api_key_hits` (
  `id`         BIGINT AUTO_INCREMENT PRIMARY KEY,
  `key_id`     INT          NOT NULL,
  `hit_at`     DATETIME(3)  NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  INDEX idx_key_hit (`key_id`, `hit_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `automations` (
  `id`                  INT AUTO_INCREMENT PRIMARY KEY,
  `name`                VARCHAR(120) NOT NULL,
  `description`         TEXT,
  `trigger_entity_id`   INT          DEFAULT NULL,
  `trigger_event`       VARCHAR(30)  NOT NULL DEFAULT 'on_create',
  `trigger_conditions`  TEXT         DEFAULT NULL,
  `action_type`         VARCHAR(30)  NOT NULL DEFAULT 'webhook',
  `action_config`       TEXT         NOT NULL,
  `active`              TINYINT(1)   DEFAULT 1,
  `run_count`           INT          DEFAULT 0,
  `last_run_at`         DATETIME     DEFAULT NULL,
  `created_by`          INT          DEFAULT NULL,
  `created_at`          DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_trigger (trigger_entity_id, trigger_event, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Automation Logs ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `automation_logs` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `automation_id` INT          NOT NULL,
  `record_id`     INT          DEFAULT NULL,
  `status`        VARCHAR(10)  NOT NULL DEFAULT 'success',
  `message`       TEXT         DEFAULT NULL,
  `created_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_auto (automation_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Plugins ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `plugins` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `plugin_id`    VARCHAR(80)  NOT NULL UNIQUE,
  `name`         VARCHAR(120) NOT NULL,
  `version`      VARCHAR(20)  NOT NULL DEFAULT '0.0.0',
  `description`  TEXT,
  `author`       VARCHAR(120) DEFAULT NULL,
  `manifest`     TEXT         NOT NULL,
  `settings`     TEXT         DEFAULT NULL,
  `active`       TINYINT(1)   DEFAULT 1,
  `installed_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;