<?php
/**
 * Chat Suggest — AI-powered reply suggestions for agents
 * POST { session_id }
 * Returns { suggestions: [...] }
 */
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

$agent = admin();
if (!$agent || !has_role('admin', 'support')) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }

try {
    $body      = json_decode(file_get_contents('php://input'), true) ?? [];
    $sessionId = (int)($body['session_id'] ?? 0);

    if (!$sessionId) { http_response_code(400); echo json_encode(['error' => 'session_id required']); exit; }

    $db = db();

    // Get session info
    $sess = $db->prepare('SELECT name FROM chat_sessions WHERE id = ?');
    $sess->execute([$sessionId]);
    $session = $sess->fetch();
    if (!$session) { http_response_code(404); echo json_encode(['error' => 'Session not found']); exit; }

    // Get last 12 messages for context
    $msgs = $db->prepare(
        'SELECT sender_type, sender_name, body FROM chat_messages WHERE session_id = ? ORDER BY id DESC LIMIT 12'
    );
    $msgs->execute([$sessionId]);
    $messages = array_reverse($msgs->fetchAll());

    // Build conversation text
    $conversationLines = [];
    foreach ($messages as $m) {
        $role = $m['sender_type'] === 'visitor' ? 'Customer' : 'Agent';
        $conversationLines[] = $role . ': ' . $m['body'];
    }
    $conversationText = implode("\n", $conversationLines);

    $apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';

    if ($apiKey) {
        $suggestions = getSuggestionsFromClaude($apiKey, $conversationText, $session['name']);
    } else {
        $suggestions = getTemplateSuggestions($conversationText, $session['name']);
    }

    echo json_encode(['suggestions' => $suggestions]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}

// ── Claude API ────────────────────────────────────────────────────────────────
function getSuggestionsFromClaude(string $apiKey, string $conversation, string $visitorName): array
{
    $systemPrompt = <<<PROMPT
You are helping a customer support agent for Nalam Pulse — an AI-powered Hiring & Talent Intelligence Platform.

Product summary:
- Nalam Pulse has two pillars: (1) AI Hiring/ATS (job postings, candidate tracking, AI resume analysis, interview scheduling) and (2) Resource Allocation (employee skill mapping, project matching, Jira integration)
- Plans: Cloud (hosted SaaS), Self-Hosted (one-time license), Custom Enterprise
- Cloud pricing starts at $49/month; Self-hosted is $999 one-time
- 14-day free trial available
- Supports multi-tenant organizations with role-based access

Based on the conversation below, generate EXACTLY 3 short, helpful reply suggestions for the support agent.
Each suggestion should be professional, direct, and under 80 words.
Return ONLY a valid JSON array of 3 strings, nothing else. Example: ["Reply 1", "Reply 2", "Reply 3"]
PROMPT;

    $payload = json_encode([
        'model'      => 'claude-haiku-4-5-20251001',
        'max_tokens' => 400,
        'system'     => $systemPrompt,
        'messages'   => [['role' => 'user', 'content' => "Conversation:\n" . $conversation . "\n\nGenerate 3 reply suggestions."]],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);

    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError || !$response) return getTemplateSuggestions('', '');

    $data = json_decode($response, true);
    $text = $data['content'][0]['text'] ?? '';

    // Extract JSON array from response
    if (preg_match('/\[.*\]/s', $text, $m)) {
        $arr = json_decode($m[0], true);
        if (is_array($arr) && count($arr) >= 1) {
            return array_slice(array_values($arr), 0, 3);
        }
    }

    return getTemplateSuggestions($text, '');
}

// ── Template fallback ─────────────────────────────────────────────────────────
function getTemplateSuggestions(string $conversation, string $name): array
{
    $conv = strtolower($conversation);
    $greeting = $name ? "Hi {$name}!" : 'Hi there!';

    // Pricing / plans
    if (preg_match('/price|cost|plan|pricing|subscription|how much|fee/', $conv)) {
        return [
            "{$greeting} Our Cloud plan starts at \$49/month for up to 5 users. We also offer a Self-Hosted plan at \$999 one-time with unlimited users. Would you like a detailed breakdown?",
            "We offer a 14-day free trial on our Cloud plan — no credit card required. Would you like me to set that up for you?",
            "Our pricing depends on your team size and needs. Could you share how many users you're planning to onboard? I can suggest the best plan for you.",
        ];
    }

    // Trial / demo
    if (preg_match('/trial|demo|test|try|evaluate|pilot/', $conv)) {
        return [
            "{$greeting} Absolutely! You can start a free 14-day trial at app.nalampulse.com — full access, no credit card needed.",
            "I'd be happy to schedule a live demo for you. Could you share your availability and timezone? It usually takes about 30 minutes.",
            "Our trial includes full access to both the Hiring ATS and Resource Allocation features. Would you like me to walk you through what's included?",
        ];
    }

    // Technical issue / error / bug
    if (preg_match('/error|bug|issue|problem|not working|broken|fail|crash|slow/', $conv)) {
        return [
            "{$greeting} I'm sorry to hear you're experiencing this. Could you share a screenshot or describe what happens step by step? I'll escalate this to our dev team right away.",
            "Thank you for reporting this. Could you let me know which browser and OS you're using? This will help our team reproduce and fix the issue faster.",
            "I've noted the issue. Our team typically resolves critical bugs within 24 hours. I'll create a dev ticket and keep you updated by email.",
        ];
    }

    // Integration / API
    if (preg_match('/integrat|jira|api|webhook|connect|sync/', $conv)) {
        return [
            "{$greeting} Nalam Pulse integrates natively with Jira for resource tracking. Would you like documentation on setting up the integration?",
            "Our REST API is available on all paid plans. I can share the API docs and a sample Postman collection. What are you looking to integrate?",
            "We support Jira, webhooks, and custom integrations via our API. Could you describe your workflow? I'll point you to the right setup guide.",
        ];
    }

    // Feature question
    if (preg_match('/feature|how|can i|does it|support|what/', $conv)) {
        return [
            "{$greeting} Great question! Nalam Pulse covers AI resume screening, interview scheduling, and resource allocation. Which area are you most interested in exploring?",
            "Our AI analyzes resumes and matches candidates to job requirements automatically, saving hours of manual screening. Would you like to see a quick walkthrough?",
            "Nalam Pulse supports multi-organization setups with role-based access. Admins, HR managers, hiring managers, and resource managers each get a tailored view.",
        ];
    }

    // Default
    return [
        "{$greeting} Thanks for reaching out to Nalam Pulse support. I'm here to help — could you tell me a bit more about what you're looking to achieve?",
        "Happy to assist! Could you share more details so I can point you in the right direction?",
        "Thank you for contacting us. I'll look into this for you right away. In the meantime, our documentation at docs.nalampulse.com covers most common questions.",
    ];
}
