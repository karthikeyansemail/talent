-- ═══════════════════════════════════════════════════════════════════════════
-- Nalam Pulse Portal — Migration v1.2
-- Cross-module workflow automation: orders↔instances, ticket routing,
-- appointment→lead progression
-- Run: mysql -u root nalampulse_portal < migration-v1.2.sql
-- ═══════════════════════════════════════════════════════════════════════════

-- ── 1. Link instances to customers + orders ───────────────────────────────
-- When a self_hosted order is activated, an instance is created.
-- customer_id lets us look up a customer's instances from support tickets.
ALTER TABLE instances
  ADD COLUMN IF NOT EXISTS customer_id INT UNSIGNED DEFAULT NULL AFTER name,
  ADD COLUMN IF NOT EXISTS order_id    INT UNSIGNED DEFAULT NULL AFTER customer_id,
  ADD INDEX IF NOT EXISTS idx_customer (customer_id);

-- ── 2. Link orders back to provisioned instance ───────────────────────────
ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS instance_id INT UNSIGNED DEFAULT NULL AFTER customer_id;

-- ── 3. Ticket type for routing (billing / software_bug / tech_support) ────
ALTER TABLE tickets
  ADD COLUMN IF NOT EXISTS ticket_type ENUM('general','billing','software_bug','tech_support')
    NOT NULL DEFAULT 'general' AFTER priority,
  ADD COLUMN IF NOT EXISTS billing_forwarded_at TIMESTAMP NULL DEFAULT NULL AFTER ticket_type;
