<?php
/**
 * Chat Poll — visitor or admin polls for new messages since a given message id
 * Visitor:  GET ?session_id=X&token=Y&since=Z
 * Admin:    GET ?session_id=X&since=Z&_admin=1  (requires PHP session auth via bootstrap)
 * Returns { messages: [...], session_status: 'open'|'closed' }
 */
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

try {
    $sessionId = (int)($_GET['session_id'] ?? 0);
    $token     = trim($_GET['token'] ?? '');
    $since     = (int)($_GET['since'] ?? 0);
    $isAdmin   = !empty($_GET['_admin']);

    if (!$sessionId) { http_response_code(400); echo json_encode(['error' => 'session_id required']); exit; }

    $db = db();

    if ($isAdmin) {
        // Admin path: verify portal session
        session_start();
        if (empty($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
        $sess = $db->prepare('SELECT id, status FROM chat_sessions WHERE id = ?');
        $sess->execute([$sessionId]);
        $session = $sess->fetch();
    } else {
        // Visitor path: verify token
        if (!$token) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
        $sess = $db->prepare('SELECT id, status FROM chat_sessions WHERE id = ? AND visitor_token = ?');
        $sess->execute([$sessionId, $token]);
        $session = $sess->fetch();
    }

    if (!$session) { http_response_code(401); echo json_encode(['error' => 'Invalid session or token']); exit; }

    // Fetch messages newer than $since
    $msgs = $db->prepare(
        'SELECT id, sender_type, sender_name, body, created_at
         FROM chat_messages
         WHERE session_id = ? AND id > ?
         ORDER BY id ASC
         LIMIT 50'
    );
    $msgs->execute([$sessionId, $since]);
    $messages = $msgs->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'messages'       => $messages,
        'session_status' => $session['status'],
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
