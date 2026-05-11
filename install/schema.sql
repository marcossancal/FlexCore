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

-- ── API Keys ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `name`         VARCHAR(120) NOT NULL,
  `key_hash`     VARCHAR(64)  NOT NULL UNIQUE,
  `key_preview`  VARCHAR(12)  NOT NULL,
  `permissions`  TEXT         NOT NULL DEFAULT '{"scope":"all"}',
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
  `action_config`       TEXT         NOT NULL DEFAULT '{}',
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
  `manifest`     TEXT         NOT NULL DEFAULT '{}',
  `settings`     TEXT         DEFAULT NULL,
  `active`       TINYINT(1)   DEFAULT 1,
  `installed_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
