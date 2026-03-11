<?php
/**
 * Chat Close — admin closes a session
 * POST { session_id }
 * Requires admin session
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

$agent = admin();
if (!$agent) { http_response_code(401); exit; }
if (!has_role('admin', 'support')) { http_response_code(403); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$sessionId = (int)($_POST['session_id'] ?? 0);
if ($sessionId) {
    db()->prepare('UPDATE chat_sessions SET status = ? WHERE id = ?')
       ->execute(['closed', $sessionId]);
}

header('Location: ' . BASE . '/chat.php?session=' . $sessionId);
exit;
