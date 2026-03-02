<?php
/**
 * Nalam Pulse — Razorpay Webhook Handler
 * POST https://portal.nalampulse.com/api/webhook-razorpay.php
 *
 * Configure in Razorpay Dashboard → Webhooks:
 *   URL: https://portal.nalampulse.com/api/webhook-razorpay.php
 *   Events: payment.captured, subscription.activated, subscription.cancelled, subscription.completed
 *
 * Set Webhook Secret in Razorpay Dashboard and put it in config.php as RAZORPAY_KEY_SECRET.
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

// Verify webhook signature
if (!verifyRazorpaySignature($payload, $signature, RAZORPAY_KEY_SECRET)) {
    http_response_code(400);
    exit('Signature mismatch');
}

$event = json_decode($payload, true);
if (!$event) {
    http_response_code(400);
    exit('Invalid JSON');
}

$db        = db();
$eventType = $event['event'] ?? '';
$entity    = $event['payload']['payment']['entity'] ?? $event['payload']['subscription']['entity'] ?? [];

switch ($eventType) {

    /**
     * One-time payment captured (self-hosted purchase) or subscription first charge
     */
    case 'payment.captured':
        $paymentId = $entity['id']             ?? '';
        $orderId   = $entity['order_id']       ?? '';
        $amount    = ($entity['amount'] ?? 0) / 100;  // paise → rupees
        $email     = $entity['email']           ?? $entity['notes']['email'] ?? null;
        $name      = $entity['notes']['name']   ?? 'Customer';
        $plan      = razorpayOrder2Plan($entity['notes']['plan'] ?? '');

        if (!$email) break;

        // Upsert customer
        $stmt = $db->prepare('SELECT id FROM customers WHERE email=?');
        $stmt->execute([$email]);
        $customer = $stmt->fetch();
        if (!$customer) {
            $db->prepare('INSERT INTO customers (name,email) VALUES (?,?)')->execute([$name, $email]);
            $customerId = $db->lastInsertId();
        } else {
            $customerId = $customer['id'];
        }

        $expiresAt = $plan === 'cloud_enterprise' ? date('Y-m-d', strtotime('+30 days')) : null;

        $db->prepare(
            'INSERT INTO orders
             (customer_id, plan, currency, amount, status, payment_gateway, gateway_txn_id, billing_period, starts_at, expires_at)
             VALUES (?,?,?,?,?,?,?,?,CURDATE(),?)'
        )->execute([$customerId, $plan, 'INR', $amount, 'active', 'razorpay', $paymentId,
                    $plan === 'self_hosted' ? 'one_time' : 'monthly', $expiresAt]);
        break;

    /**
     * Subscription activated
     */
    case 'subscription.activated':
        $subId = $entity['id'] ?? '';
        // Already created on payment.captured; update status to active
        $db->prepare('UPDATE orders SET status=? WHERE gateway_txn_id=?')->execute(['active', $subId]);
        break;

    /**
     * Subscription cancelled
     */
    case 'subscription.cancelled':
    case 'subscription.completed':
        $subId = $entity['id'] ?? '';
        $db->prepare('UPDATE orders SET status=?,cancelled_at=NOW() WHERE gateway_txn_id=?')->execute(['cancelled', $subId]);
        break;

    default:
        break;
}

http_response_code(200);
echo json_encode(['status' => 'ok']);

// ── Helpers ──────────────────────────────────────────────────────────────────

function verifyRazorpaySignature(string $payload, string $signature, string $secret): bool
{
    if (!$signature || !$secret || $secret === 'YOUR_RAZORPAY_SECRET_HERE') {
        // Not configured — skip in dev
        return true;
    }
    $expected = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $signature);
}

function razorpayOrder2Plan(string $plan): string
{
    return match ($plan) {
        'cloud_enterprise', 'cloud' => 'cloud_enterprise',
        'self_hosted', 'selfhosted' => 'self_hosted',
        default                     => 'free',
    };
}
