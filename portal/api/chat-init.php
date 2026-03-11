<?php
/**
 * Chat Init — visitor starts a new session
 * POST { name, email }
 * Returns { session_id, token }
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
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $name  = trim($body['name']  ?? '');
    $email = trim($body['email'] ?? '');

    if (!$name)  { http_response_code(400); echo json_encode(['error' => 'name required']); exit; }
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['error' => 'valid email required']); exit; }

    $db    = db();
    $token = bin2hex(random_bytes(24)); // 48-char visitor token
    $ip    = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;

    $db->prepare('INSERT INTO chat_sessions (name, email, ip, visitor_token) VALUES (?,?,?,?)')
       ->execute([$name, $email, $ip, $token]);

    $sessionId = (int)$db->lastInsertId();

    // Auto-welcome message from system
    $db->prepare('INSERT INTO chat_messages (session_id, sender_type, sender_name, body) VALUES (?,?,?,?)')
       ->execute([$sessionId, 'agent', 'Support', 'Hi ' . $name . '! 👋 Thanks for reaching out. How can we help you today?']);

    echo json_encode(['session_id' => $sessionId, 'token' => $token]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
