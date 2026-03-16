<?php
/**
 * Chat Reply — agent sends a message from the portal (session auth)
 * POST { session_id, body }
 * Requires admin session cookie (same as portal login)
 * Returns { ok: true, message_id }
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

// Must be logged in
$agent = admin();
if (!$agent) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }

// Role check
if (!has_role('admin', 'support')) { http_response_code(403); echo json_encode(['error' => 'Forbidden']); exit; }

try {
    $body           = json_decode(file_get_contents('php://input'), true) ?? [];
    $sessionId      = (int)($body['session_id'] ?? 0);
    $message        = trim($body['body'] ?? '');
    $attachUrl      = trim($body['attachment_url']  ?? '') ?: null;
    $attachType     = trim($body['attachment_type'] ?? '') ?: null;

    if (!$sessionId) { http_response_code(400); echo json_encode(['error' => 'session_id required']); exit; }
    if (!$message && !$attachUrl) { http_response_code(400); echo json_encode(['error' => 'body or attachment required']); exit; }
    if ($message && strlen($message) > 4000) { http_response_code(400); echo json_encode(['error' => 'Message too long']); exit; }

    $db = db();

    // Verify session exists
    $sess = $db->prepare('SELECT id FROM chat_sessions WHERE id = ?');
    $sess->execute([$sessionId]);
    if (!$sess->fetch()) { http_response_code(404); echo json_encode(['error' => 'Session not found']); exit; }

    $agentName = $agent['name'] ?? 'Support';

    $db->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_name, body, attachment_url, attachment_type) VALUES (?,?,?,?,?,?)')
       ->execute([$sessionId, 'agent', $agentName, $message, $attachUrl, $attachType]);

    $msgId = (int)$db->lastInsertId();

    echo json_encode(['ok' => true, 'message_id' => $msgId]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
