-- ═══════════════════════════════════════════════════════════════════════════
-- Nalam Pulse Portal — Migration v1.3
-- Chat system enhancements: sender_type, visitor_token, session status
-- Run: mysql -u root nalampulse_portal < migration-v1.3.sql
-- ═══════════════════════════════════════════════════════════════════════════

-- ── 1. chat_messages: track who sent each message ─────────────────────────
ALTER TABLE chat_messages
  ADD COLUMN IF NOT EXISTS sender_type ENUM('visitor','agent') NOT NULL DEFAULT 'visitor' AFTER session_id,
  ADD COLUMN IF NOT EXISTS sender_name VARCHAR(100) DEFAULT NULL AFTER sender_type;

-- ── 2. chat_sessions: visitor auth token + status ─────────────────────────
ALTER TABLE chat_sessions
  ADD COLUMN IF NOT EXISTS status        ENUM('open','closed') NOT NULL DEFAULT 'open' AFTER ip,
  ADD COLUMN IF NOT EXISTS visitor_token VARCHAR(64)           NOT NULL DEFAULT '' AFTER status;

ALTER TABLE chat_sessions
  ADD INDEX IF NOT EXISTS idx_token (visitor_token);
