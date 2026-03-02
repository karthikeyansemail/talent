<?php
/**
 * Nalam Pulse — Chat Widget → Telegram Endpoint
 * nalampulse.com/api/chat.php
 *
 * SETUP: Fill in TELEGRAM_BOT_TOKEN and TELEGRAM_CHAT_ID below.
 * Get bot token from @BotFather on Telegram.
 * Get chat ID by messaging your bot and visiting:
 *   https://api.telegram.org/bot<TOKEN>/getUpdates
 */

// ── CONFIG ──────────────────────────────────────────────────
define('TELEGRAM_BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');   // e.g. 7123456789:AAH...
define('TELEGRAM_CHAT_ID',   'YOUR_CHAT_ID_HERE');     // e.g. -1001234567890 or your user ID
// ─────────────────────────────────────────────────────────────

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://nalampulse.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Parse input
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$name    = trim(strip_tags($input['name']    ?? ''));
$email   = trim(strip_tags($input['email']   ?? ''));
$message = trim(strip_tags($input['message'] ?? ''));

// Validate
if (!$name || !$email || !$message) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address.']);
    exit;
}

// Rate limiting: simple IP-based (1 message per 60 seconds)
$ipFile = sys_get_temp_dir() . '/np_chat_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (file_exists($ipFile) && (time() - filemtime($ipFile)) < 60) {
    http_response_code(429);
    echo json_encode(['error' => 'Please wait before sending another message.']);
    exit;
}
touch($ipFile);

// Save to portal DB (nalampulse_portal) — best-effort, never blocks response
saveToPortalDb($name, $email, $message);

// Build Telegram message
$text = "🌐 *New Website Enquiry — Nalam Pulse*\n\n"
    . "👤 *Name:* " . escapeMarkdown($name) . "\n"
    . "📧 *Email:* " . escapeMarkdown($email) . "\n\n"
    . "💬 *Message:*\n" . escapeMarkdown($message) . "\n\n"
    . "_Sent from nalampulse\\.com chat widget_";

// Send to Telegram
$result = sendTelegramMessage($text);

if ($result === true) {
    echo json_encode(['success' => true, 'message' => 'Message sent! We\'ll be in touch soon.']);
} else {
    // Log error but still respond gracefully
    error_log('Nalam Pulse chat: Telegram error — ' . $result);
    // Fallback: try to store in file
    $logFile = __DIR__ . '/chat-fallback.log';
    $entry = date('Y-m-d H:i:s') . " | {$name} | {$email} | " . str_replace("\n", ' ', $message) . "\n";
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

    // Still return success to user — we have the fallback log
    echo json_encode(['success' => true, 'message' => 'Message received! We\'ll be in touch soon.']);
}

// ── HELPERS ──────────────────────────────────────────────────

function sendTelegramMessage(string $text): bool|string
{
    if (TELEGRAM_BOT_TOKEN === 'YOUR_BOT_TOKEN_HERE') {
        // Not configured yet — log to file
        error_log('Nalam Pulse chat: Telegram not configured. Message: ' . $text);
        return true; // return success so site works during dev
    }

    $url = 'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage';

    $payload = json_encode([
        'chat_id'    => TELEGRAM_CHAT_ID,
        'text'       => $text,
        'parse_mode' => 'MarkdownV2',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return 'cURL error: ' . $curlError;
    }

    $result = json_decode($response, true);

    if ($result && isset($result['ok']) && $result['ok'] === true) {
        return true;
    }

    return 'Telegram API error: ' . ($result['description'] ?? $response);
}

/**
 * Save chat message to nalampulse_portal DB for admin visibility.
 * Silently fails if portal DB is not yet configured — does not affect user experience.
 */
function saveToPortalDb(string $name, string $email, string $body): void
{
    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=nalampulse_portal;charset=utf8mb4',
            'root', '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2]
        );

        // Upsert session
        $stmt = $pdo->prepare('SELECT id FROM chat_sessions WHERE email=? ORDER BY created_at DESC LIMIT 1');
        $stmt->execute([$email]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            $pdo->prepare('INSERT INTO chat_sessions (name,email,ip) VALUES (?,?,?)')
               ->execute([$name, $email, $_SERVER['REMOTE_ADDR'] ?? null]);
            $sessionId = $pdo->lastInsertId();
        } else {
            $sessionId = $session['id'];
        }

        $pdo->prepare('INSERT INTO chat_messages (session_id,body) VALUES (?,?)')
           ->execute([$sessionId, $body]);
    } catch (Exception $e) {
        // Portal DB not available — log quietly and continue
        error_log('Nalam Pulse chat: portal DB error — ' . $e->getMessage());
    }
}

/**
 * Escape special characters for Telegram MarkdownV2
 */
function escapeMarkdown(string $text): string
{
    $special = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
    foreach ($special as $char) {
        $text = str_replace($char, '\\' . $char, $text);
    }
    return $text;
}
