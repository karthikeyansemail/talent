-- ═══════════════════════════════════════════════════════════════════════════
-- Nalam Pulse Portal — Migration v1.4
-- Chat: attachment support (image/video/file)
-- Run: mysql -u root nalampulse_portal < migration-v1.4.sql
-- ═══════════════════════════════════════════════════════════════════════════

ALTER TABLE chat_messages
  ADD COLUMN IF NOT EXISTS attachment_url  VARCHAR(500) DEFAULT NULL AFTER body,
  ADD COLUMN IF NOT EXISTS attachment_type ENUM('image','video','file') DEFAULT NULL AFTER attachment_url;
