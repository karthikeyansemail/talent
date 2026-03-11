<?php
/**
 * Chat Send — visitor sends a message
 * POST { session_id, token, body }
 * Returns { ok: true, message_id }
 */
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

try {
    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $sessionId = (int)($body['session_id'] ?? 0);
    $token     = trim($body['token'] ?? '');
    $message   = trim($body['body'] ?? '');

    if (!$sessionId || !$token) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
    if (!$message)              { http_response_code(400); echo json_encode(['error' => 'Message body required']); exit; }
    if (strlen($message) > 4000) { http_response_code(400); echo json_encode(['error' => 'Message too long']); exit; }

    $db = db();

    // Verify token
    $sess = $db->prepare('SELECT id, name, status FROM chat_sessions WHERE id = ? AND visitor_token = ?');
    $sess->execute([$sessionId, $token]);
    $session = $sess->fetch();

    if (!$session)               { http_response_code(401); echo json_encode(['error' => 'Invalid session or token']); exit; }
    if ($session['status'] === 'closed') { http_response_code(403); echo json_encode(['error' => 'Session is closed']); exit; }

    $db->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_name, body) VALUES (?,?,?,?)')
       ->execute([$sessionId, 'visitor', $session['name'], $message]);

    $msgId = (int)$db->lastInsertId();

    echo json_encode(['ok' => true, 'message_id' => $msgId]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
