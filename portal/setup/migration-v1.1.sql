-- ═══════════════════════════════════════════════════════════════════════════
-- Nalam Pulse Portal — Migration v1.1
-- Role-based access, Error logging, Marketing, Sales pipeline, Dev tools
-- Run: mysql -u root nalampulse_portal < migration-v1.1.sql
-- ═══════════════════════════════════════════════════════════════════════════

-- ── 1. Add role to admin_users ───────────────────────────────────────────
ALTER TABLE admin_users
  ADD COLUMN IF NOT EXISTS role ENUM('admin','sales','support','dev') NOT NULL DEFAULT 'admin' AFTER password;

-- ── 2. Add escalation columns to tickets ─────────────────────────────────
ALTER TABLE tickets
  ADD COLUMN IF NOT EXISTS dev_ticket_id INT UNSIGNED DEFAULT NULL AFTER priority,
  ADD COLUMN IF NOT EXISTS escalated_at TIMESTAMP NULL DEFAULT NULL AFTER dev_ticket_id;

-- ── 3. Instances registry ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS instances (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    domain       VARCHAR(255) NOT NULL UNIQUE,
    name         VARCHAR(255) NOT NULL,
    version      VARCHAR(50)  DEFAULT NULL,
    api_key      VARCHAR(64)  NOT NULL UNIQUE,
    environment  ENUM('production','staging','local') NOT NULL DEFAULT 'production',
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    last_seen_at TIMESTAMP NULL DEFAULT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key)
) ENGINE=InnoDB;

-- ── 4. Error logs ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS error_logs (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instance_id      INT UNSIGNED NOT NULL,
    error_hash       VARCHAR(64)  NOT NULL,
    level            ENUM('error','warning','critical','notice') NOT NULL DEFAULT 'error',
    message          TEXT NOT NULL,
    exception_class  VARCHAR(255) DEFAULT NULL,
    file             VARCHAR(500) DEFAULT NULL,
    line             INT UNSIGNED DEFAULT NULL,
    stack_trace      TEXT DEFAULT NULL,
    url              VARCHAR(2000) DEFAULT NULL,
    method           VARCHAR(10) DEFAULT NULL,
    user_info        JSON DEFAULT NULL,
    request_data     JSON DEFAULT NULL,
    environment      VARCHAR(50) DEFAULT NULL,
    app_version      VARCHAR(50) DEFAULT NULL,
    php_version      VARCHAR(20) DEFAULT NULL,
    occurrence_count INT UNSIGNED NOT NULL DEFAULT 1,
    first_seen_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_resolved      TINYINT(1) NOT NULL DEFAULT 0,
    resolved_by      INT UNSIGNED DEFAULT NULL,
    resolved_at      TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (instance_id) REFERENCES instances(id) ON DELETE CASCADE,
    INDEX idx_hash (instance_id, error_hash),
    INDEX idx_level (level),
    INDEX idx_resolved (is_resolved),
    INDEX idx_last_seen (last_seen_at)
) ENGINE=InnoDB;

-- ── 5. Dev tickets ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS dev_tickets (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instance_id        INT UNSIGNED DEFAULT NULL,
    error_log_id       INT UNSIGNED DEFAULT NULL,
    support_ticket_id  INT UNSIGNED DEFAULT NULL,
    title              VARCHAR(255) NOT NULL,
    description        TEXT,
    status             ENUM('open','investigating','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    priority           ENUM('low','normal','high','critical') NOT NULL DEFAULT 'normal',
    assigned_to        INT UNSIGNED DEFAULT NULL,
    created_by         INT UNSIGNED DEFAULT NULL,
    resolved_at        TIMESTAMP NULL DEFAULT NULL,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instance_id) REFERENCES instances(id) ON DELETE SET NULL,
    FOREIGN KEY (error_log_id) REFERENCES error_logs(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ── 6. Integrations (API credentials) ───────────────────────────────────
CREATE TABLE IF NOT EXISTS integrations (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider   VARCHAR(50) NOT NULL UNIQUE,
    config     TEXT NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── 7. Campaigns ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS campaigns (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(255) NOT NULL,
    platform      ENUM('google_ads','meta_ads','linkedin','manual','other') NOT NULL DEFAULT 'manual',
    external_id   VARCHAR(255) DEFAULT NULL,
    status        ENUM('draft','active','paused','completed') NOT NULL DEFAULT 'draft',
    budget        DECIMAL(10,2) DEFAULT NULL,
    currency      ENUM('USD','INR') NOT NULL DEFAULT 'USD',
    start_date    DATE DEFAULT NULL,
    end_date      DATE DEFAULT NULL,
    notes         TEXT,
    created_by    INT UNSIGNED DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_platform (platform),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ── 8. Ad spend tracking ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ad_spend (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id     INT UNSIGNED NOT NULL,
    period_start    DATE NOT NULL,
    period_end      DATE NOT NULL,
    amount          DECIMAL(10,2) NOT NULL DEFAULT 0,
    currency        ENUM('USD','INR') NOT NULL DEFAULT 'USD',
    impressions     INT UNSIGNED DEFAULT 0,
    clicks          INT UNSIGNED DEFAULT 0,
    leads_generated INT UNSIGNED DEFAULT 0,
    notes           TEXT,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign (campaign_id),
    UNIQUE KEY uq_campaign_period (campaign_id, period_start)
) ENGINE=InnoDB;

-- ── 9. Leads ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS leads (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(255) NOT NULL,
    email         VARCHAR(255) DEFAULT NULL,
    phone         VARCHAR(50) DEFAULT NULL,
    company       VARCHAR(255) DEFAULT NULL,
    source        ENUM('google_ads','meta_ads','linkedin','website','referral','manual') NOT NULL DEFAULT 'manual',
    campaign_id   INT UNSIGNED DEFAULT NULL,
    status        ENUM('new','contacted','qualified','proposal','negotiation','won','lost') NOT NULL DEFAULT 'new',
    assigned_to   INT UNSIGNED DEFAULT NULL,
    customer_id   INT UNSIGNED DEFAULT NULL,
    notes         TEXT,
    converted_at  TIMESTAMP NULL DEFAULT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_source (source),
    INDEX idx_assigned (assigned_to)
) ENGINE=InnoDB;

-- ── 10. Lead activities ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS lead_activities (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id     INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED DEFAULT NULL,
    type        ENUM('call','email','meeting','note','status_change') NOT NULL,
    description TEXT,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_lead (lead_id)
) ENGINE=InnoDB;

-- ── 11. Appointments ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS appointments (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lead_id      INT UNSIGNED NOT NULL,
    assigned_to  INT UNSIGNED DEFAULT NULL,
    scheduled_at DATETIME NOT NULL,
    duration_min INT UNSIGNED DEFAULT 30,
    type         ENUM('discovery','demo','follow_up','closing') NOT NULL DEFAULT 'discovery',
    status       ENUM('scheduled','completed','cancelled','no_show') NOT NULL DEFAULT 'scheduled',
    notes        TEXT,
    outcome      TEXT,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_lead (lead_id),
    INDEX idx_assigned (assigned_to),
    INDEX idx_scheduled (scheduled_at)
) ENGINE=InnoDB;

-- ── 12. Seed default SaaS instance ──────────────────────────────────────
INSERT IGNORE INTO instances (domain, name, api_key, environment) VALUES
('localhost', 'Local Development', CONCAT(MD5(RAND()), MD5(RAND())), 'local');
