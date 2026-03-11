<?php
/**
 * Nalam Pulse — Error Log Ingestion API
 *
 * POST /portal/api/error-log.php
 * Header: X-Api-Key: <instance api key>
 * Body: JSON { message, exception_class, file, line, stack_trace, level, url, method, user_info, request_data, environment, app_version, php_version }
 *
 * Fire-and-forget from Laravel apps. Always returns 200/400/401.
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Authenticate via API key
    $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if (!$apiKey || strlen($apiKey) < 32) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $db = db();

    $stmt = $db->prepare('SELECT id, domain, is_active FROM instances WHERE api_key = ?');
    $stmt->execute([$apiKey]);
    $instance = $stmt->fetch();

    if (!$instance || !$instance['is_active']) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or inactive instance']);
        exit;
    }

    $instanceId = (int)$instance['id'];

    // Parse JSON body
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload || !isset($payload['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid payload: message required']);
        exit;
    }

    // Sanitize + extract
    $level          = in_array($payload['level'] ?? '', ['error','warning','critical','notice'], true) ? $payload['level'] : 'error';
    $message        = mb_substr($payload['message'] ?? '', 0, 65535);
    $exceptionClass = mb_substr($payload['exception_class'] ?? '', 0, 255);
    $file           = mb_substr($payload['file'] ?? '', 0, 500);
    $line           = (int)($payload['line'] ?? 0);
    $stackTrace     = mb_substr($payload['stack_trace'] ?? '', 0, 65535);
    $url            = mb_substr($payload['url'] ?? '', 0, 2000);
    $method         = mb_substr($payload['method'] ?? '', 0, 10);
    $userInfo       = isset($payload['user_info']) ? json_encode($payload['user_info']) : null;
    $requestData    = isset($payload['request_data']) ? json_encode($payload['request_data']) : null;
    $environment    = mb_substr($payload['environment'] ?? '', 0, 50);
    $appVersion     = mb_substr($payload['app_version'] ?? '', 0, 50);
    $phpVersion     = mb_substr($payload['php_version'] ?? '', 0, 20);

    // Compute error hash for deduplication
    $errorHash = hash('sha256', $exceptionClass . '|' . $file . '|' . $line);

    // Check for existing unresolved error with same hash
    $existing = $db->prepare(
        'SELECT id, occurrence_count FROM error_logs WHERE instance_id = ? AND error_hash = ? AND is_resolved = 0'
    );
    $existing->execute([$instanceId, $errorHash]);
    $existingError = $existing->fetch();

    if ($existingError) {
        // Deduplicate: increment count, update context
        $db->prepare(
            'UPDATE error_logs SET
                occurrence_count = occurrence_count + 1,
                last_seen_at = NOW(),
                stack_trace = ?,
                url = COALESCE(?, url),
                method = COALESCE(?, method),
                user_info = COALESCE(?, user_info),
                request_data = COALESCE(?, request_data),
                message = ?
             WHERE id = ?'
        )->execute([$stackTrace, $url, $method, $userInfo, $requestData, $message, $existingError['id']]);
    } else {
        // New error
        $db->prepare(
            'INSERT INTO error_logs
                (instance_id, error_hash, level, message, exception_class, file, line,
                 stack_trace, url, method, user_info, request_data,
                 environment, app_version, php_version,
                 occurrence_count, first_seen_at, last_seen_at)
             VALUES (?,?,?,?,?,?,?, ?,?,?,?,?, ?,?,?, 1, NOW(), NOW())'
        )->execute([
            $instanceId, $errorHash, $level, $message, $exceptionClass, $file, $line,
            $stackTrace, $url, $method, $userInfo, $requestData,
            $environment, $appVersion, $phpVersion
        ]);
    }

    // Update instance heartbeat
    $db->prepare('UPDATE instances SET last_seen_at = NOW(), version = COALESCE(?, version) WHERE id = ?')
       ->execute([$appVersion, $instanceId]);

    http_response_code(200);
    echo json_encode(['status' => 'ok']);

} catch (\Throwable $e) {
    // Never let errors propagate — always return 200 to prevent retry storms
    error_log('Nalam Pulse error-log ingestion failed: ' . $e->getMessage());
    http_response_code(200);
    echo json_encode(['status' => 'accepted', 'warning' => 'processing_error']);
}
