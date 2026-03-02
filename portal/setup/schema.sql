-- Nalam Pulse — Customer Portal Database
-- Database: nalampulse_portal
-- Run: mysql -u root < schema.sql

CREATE DATABASE IF NOT EXISTS nalampulse_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nalampulse_portal;

-- ─────────────────────────────────────────────────────────────────────────────
-- Admin users (portal staff / Nalam Pulse team)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(191) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    is_active  TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────────────────────
-- Customers (org admins who purchased a plan)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS customers (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(191) NOT NULL UNIQUE,
    company       VARCHAR(255),
    country       VARCHAR(100),
    timezone      VARCHAR(100) DEFAULT 'UTC',
    magic_token   VARCHAR(64),   -- for magic-link login
    token_expires TIMESTAMP NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────────────────────
-- Subscriptions / orders
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT UNSIGNED NOT NULL,
    plan            ENUM('free','cloud_enterprise','self_hosted') NOT NULL DEFAULT 'free',
    currency        ENUM('USD','INR') NOT NULL DEFAULT 'USD',
    amount          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status          ENUM('pending','active','cancelled','expired','refunded') NOT NULL DEFAULT 'pending',
    payment_gateway ENUM('stripe','razorpay','manual') NOT NULL DEFAULT 'manual',
    gateway_txn_id  VARCHAR(255),          -- Stripe charge/subscription ID or Razorpay order_id
    gateway_sub_id  VARCHAR(255),          -- Stripe subscription ID (recurring)
    invoice_pdf_url VARCHAR(500),
    billing_period  ENUM('monthly','annual','one_time') DEFAULT 'monthly',
    starts_at       DATE,
    expires_at      DATE,                  -- NULL for self-hosted (perpetual)
    cancelled_at    TIMESTAMP NULL,
    notes           TEXT,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_gateway_txn (gateway_txn_id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────────────────────
-- Support tickets
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tickets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    subject     VARCHAR(255) NOT NULL,
    status      ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    priority    ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ticket_messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id   INT UNSIGNED NOT NULL,
    sender_type ENUM('customer','admin') NOT NULL,
    sender_id   INT UNSIGNED NOT NULL,   -- customer.id or admin_user.id
    body        TEXT NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────────────────────
-- Website chat sessions (from nalampulse.com chat widget)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chat_sessions (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(191) NOT NULL,
    ip         VARCHAR(45),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chat_messages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    body       TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE,
    INDEX idx_session (session_id)
) ENGINE=InnoDB;

-- ─────────────────────────────────────────────────────────────────────────────
-- Seed: default admin user (password: admin123 — CHANGE IN PRODUCTION)
-- bcrypt hash of 'admin123'
-- ─────────────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO admin_users (name, email, password) VALUES
('Nalam Admin', 'admin@nalampulse.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- default password above is 'password' (Laravel's default bcrypt) — set a real one!
