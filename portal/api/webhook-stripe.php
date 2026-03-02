<?php
/**
 * Nalam Pulse — Stripe Webhook Handler
 * POST https://portal.nalampulse.com/api/webhook-stripe.php
 *
 * Configure in Stripe Dashboard → Webhooks:
 *   URL: https://portal.nalampulse.com/api/webhook-stripe.php
 *   Events: checkout.session.completed, customer.subscription.updated,
 *           customer.subscription.deleted, invoice.payment_failed
 */

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Stripe requires raw body for signature verification
$payload   = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Verify signature
$event = verifyStripeSignature($payload, $sigHeader, STRIPE_WEBHOOK_SECRET);
if ($event === null) {
    http_response_code(400);
    exit;
}

$db = db();

switch ($event['type']) {

    /**
     * Checkout session completed = new subscription or one-time purchase
     */
    case 'checkout.session.completed':
        $session   = $event['data']['object'];
        $email     = $session['customer_details']['email'] ?? null;
        $name      = $session['customer_details']['name']  ?? 'Customer';
        $amount    = ($session['amount_total'] ?? 0) / 100;  // cents → dollars
        $currency  = strtoupper($session['currency'] ?? 'USD');
        $mode      = $session['mode'];  // 'subscription' or 'payment'
        $txnId     = $session['id'];
        $subId     = $session['subscription'] ?? null;
        $priceId   = $session['metadata']['price_id'] ?? '';
        $plan      = stripePrice2Plan($priceId);
        $billing   = $mode === 'subscription' ? 'monthly' : 'one_time';

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

        // Calculate expiry for cloud_enterprise monthly (30 days from now)
        $expiresAt = null;
        if ($plan === 'cloud_enterprise' && $billing === 'monthly') {
            $expiresAt = date('Y-m-d', strtotime('+30 days'));
        } elseif ($plan === 'cloud_enterprise' && $billing === 'annual') {
            $expiresAt = date('Y-m-d', strtotime('+1 year'));
        }
        // self_hosted: no expiry (perpetual license)

        $db->prepare(
            'INSERT INTO orders
             (customer_id, plan, currency, amount, status, payment_gateway, gateway_txn_id, gateway_sub_id, billing_period, starts_at, expires_at)
             VALUES (?,?,?,?,?,?,?,?,?,CURDATE(),?)'
        )->execute([$customerId, $plan, $currency, $amount, 'active', 'stripe', $txnId, $subId, $billing, $expiresAt]);

        break;

    /**
     * Subscription updated (plan change, renewal)
     */
    case 'customer.subscription.updated':
        $sub   = $event['data']['object'];
        $subId = $sub['id'];
        $status = $sub['status'] === 'active' ? 'active' : 'cancelled';

        $db->prepare('UPDATE orders SET status=? WHERE gateway_sub_id=?')->execute([$status, $subId]);
        break;

    /**
     * Subscription cancelled / deleted
     */
    case 'customer.subscription.deleted':
        $sub   = $event['data']['object'];
        $subId = $sub['id'];
        $db->prepare('UPDATE orders SET status=?,cancelled_at=NOW() WHERE gateway_sub_id=?')->execute(['cancelled', $subId]);
        break;

    /**
     * Payment failed (dunning)
     */
    case 'invoice.payment_failed':
        $invoice = $event['data']['object'];
        $subId   = $invoice['subscription'] ?? null;
        if ($subId) {
            $db->prepare('UPDATE orders SET status=? WHERE gateway_sub_id=? AND status=?')
               ->execute(['pending', $subId, 'active']);
        }
        break;

    default:
        // Unhandled event — ignore
        break;
}

http_response_code(200);
echo json_encode(['received' => true]);

// ── Helpers ──────────────────────────────────────────────────────────────────

function verifyStripeSignature(string $payload, string $sigHeader, string $secret): ?array
{
    if (!$sigHeader || !$secret || $secret === 'whsec_YOUR_SECRET_HERE') {
        // Not configured — skip verification in dev
        return json_decode($payload, true);
    }

    $parts = [];
    foreach (explode(',', $sigHeader) as $part) {
        [$k, $v] = explode('=', $part, 2) + ['', ''];
        $parts[$k] = $v;
    }
    $timestamp  = $parts['t'] ?? '';
    $signature  = $parts['v1'] ?? '';
    $signedPayload = $timestamp . '.' . $payload;
    $expected  = hash_hmac('sha256', $signedPayload, $secret);

    if (!hash_equals($expected, $signature)) {
        error_log('Stripe webhook: signature mismatch');
        return null;
    }

    // Reject stale webhooks (>5 min)
    if (abs(time() - (int)$timestamp) > 300) {
        error_log('Stripe webhook: stale timestamp');
        return null;
    }

    return json_decode($payload, true);
}

function stripePrice2Plan(string $priceId): string
{
    // Map Stripe Price IDs to plan names
    return match ($priceId) {
        STRIPE_PRICE_CLOUD_MONTHLY, STRIPE_PRICE_CLOUD_ANNUAL => 'cloud_enterprise',
        STRIPE_PRICE_SELF_HOSTED                              => 'self_hosted',
        default                                               => 'free',
    };
}
