<?php
/**
 * Chat Sessions — AJAX endpoint returns session list as JSON
 * GET ?search=X&status=Y
 * Requires portal session auth
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

$agent = admin();
if (!$agent || !has_role('admin', 'support')) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }

$search = trim($_GET['search'] ?? '');
$status = $_GET['status']  ?? '';

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[] = '(name LIKE ? OR email LIKE ?)';
    $s = '%' . $search . '%';
    $params[] = $s; $params[] = $s;
}
if ($status) { $where[] = 'status = ?'; $params[] = $status; }

$whereStr = implode(' AND ', $where);

$stmt = db()->prepare(
    "SELECT s.id, s.name, s.email, s.status, s.created_at,
        (SELECT COUNT(*) FROM chat_messages WHERE session_id=s.id) as msg_count,
        (SELECT MAX(created_at) FROM chat_messages WHERE session_id=s.id) as last_msg_at
     FROM chat_sessions s WHERE {$whereStr}
     ORDER BY COALESCE((SELECT MAX(created_at) FROM chat_messages WHERE session_id=s.id), s.created_at) DESC
     LIMIT 50"
);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

echo json_encode(['sessions' => $sessions]);
