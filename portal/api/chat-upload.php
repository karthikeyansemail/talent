<?php
/**
 * Chat Upload — upload image/video/file attachment
 * POST multipart: session_id, token (visitor) OR portal session (admin), file
 * Returns { url, attachment_type, filename }
 */
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Visitor-Token');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

try {
    $sessionId  = (int)($_POST['session_id'] ?? 0);
    $token      = trim($_POST['token'] ?? '');
    $isPortal   = !empty($_POST['_portal']); // set when called from portal (session auth)

    if (!$sessionId) { http_response_code(400); echo json_encode(['error' => 'session_id required']); exit; }

    $db = db();

    // Auth: visitor token OR portal session
    if ($isPortal) {
        session_name('np_portal');
        session_start();
        if (empty($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
        $sess = $db->prepare('SELECT id FROM chat_sessions WHERE id = ?');
        $sess->execute([$sessionId]);
    } else {
        if (!$token) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
        $sess = $db->prepare('SELECT id FROM chat_sessions WHERE id = ? AND visitor_token = ?');
        $sess->execute([$sessionId, $token]);
    }
    if (!$sess->fetch()) { http_response_code(401); echo json_encode(['error' => 'Invalid session']); exit; }

    // Validate file
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400); echo json_encode(['error' => 'No file uploaded']); exit;
    }

    $file     = $_FILES['file'];
    $origName = basename($file['name']);
    $mime     = mime_content_type($file['tmp_name']);
    $size     = $file['size'];

    // Allowed types
    $imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $videoTypes = ['video/mp4', 'video/webm', 'video/quicktime'];
    $fileTypes  = ['application/pdf', 'text/plain'];

    $allAllowed = array_merge($imageTypes, $videoTypes, $fileTypes);
    if (!in_array($mime, $allAllowed, true)) {
        http_response_code(400); echo json_encode(['error' => 'File type not allowed']); exit;
    }

    $maxSize = in_array($mime, $videoTypes) ? 50 * 1024 * 1024 : 10 * 1024 * 1024; // 50MB video, 10MB others
    if ($size > $maxSize) {
        http_response_code(400); echo json_encode(['error' => 'File too large']); exit;
    }

    // Determine attachment type
    if (in_array($mime, $imageTypes, true)) { $attachType = 'image'; }
    elseif (in_array($mime, $videoTypes, true)) { $attachType = 'video'; }
    else { $attachType = 'file'; }

    // Save file
    $dir = dirname(__DIR__) . '/uploads/chat/' . date('Y-m') . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $safeName = bin2hex(random_bytes(12)) . '.' . $ext;
    $dest     = $dir . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        http_response_code(500); echo json_encode(['error' => 'Upload failed']); exit;
    }

    $url = BASE . '/uploads/chat/' . date('Y-m') . '/' . $safeName;

    echo json_encode([
        'ok'              => true,
        'url'             => $url,
        'attachment_type' => $attachType,
        'filename'        => $origName,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
