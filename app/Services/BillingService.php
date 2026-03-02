<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class BillingService
{
    // ── Stripe ────────────────────────────────────────────────────────────────

    public function createStripeCheckoutSession(
        string $priceId,
        string $mode,
        int $orgId,
        string $email,
        string $successUrl,
        string $cancelUrl
    ): array {
        if (!config('billing.stripe.secret_key')) {
            return ['status' => 0, 'body' => [], 'error' => 'Stripe is not configured. Contact support.'];
        }

        $payload = http_build_query([
            'mode'                      => $mode,
            'line_items[0][price]'      => $priceId,
            'line_items[0][quantity]'   => 1,
            'customer_email'            => $email,
            'metadata[organization_id]' => $orgId,
            'metadata[plan]'            => $mode === 'payment' ? 'self_hosted' : 'cloud_enterprise',
            'success_url'               => $successUrl,
            'cancel_url'                => $cancelUrl,
        ]);

        $result = $this->stripeRequest('POST', '/v1/checkout/sessions', $payload);

        if ($result['status'] !== 200) {
            $apiError = $result['body']['error']['message'] ?? 'Unknown Stripe error';
            Log::error('Stripe checkout session failed', [
                'status'  => $result['status'],
                'message' => $apiError,
                'org_id'  => $orgId,
            ]);
            $result['error'] = $apiError;
        }

        return $result;
    }

    public function cancelStripeSubscription(string $subscriptionId): array
    {
        return $this->stripeRequest('DELETE', '/v1/subscriptions/' . $subscriptionId);
    }

    public function verifyStripeWebhookSignature(string $payload, string $sigHeader): bool
    {
        $secret = config('billing.stripe.webhook_secret');
        if (!$secret || !$sigHeader) return false;

        $parts = [];
        foreach (explode(',', $sigHeader) as $part) {
            $pair = explode('=', $part, 2);
            if (count($pair) === 2) $parts[$pair[0]] = $pair[1];
        }
        if (empty($parts['t']) || empty($parts['v1'])) return false;

        $expected = hash_hmac('sha256', $parts['t'] . '.' . $payload, $secret);
        return hash_equals($expected, $parts['v1']);
    }

    // ── Razorpay ──────────────────────────────────────────────────────────────

    public function createRazorpayOrder(int $amountPaise, string $receipt): array
    {
        if (!config('billing.razorpay.key_id') || !config('billing.razorpay.key_secret')) {
            return ['status' => 0, 'body' => [], 'error' => 'Razorpay is not configured. Contact support.'];
        }

        $payload = json_encode([
            'amount'   => $amountPaise,
            'currency' => 'INR',
            'receipt'  => $receipt,
        ]);

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_USERPWD        => config('billing.razorpay.key_id') . ':' . config('billing.razorpay.key_secret'),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $decoded = json_decode($body, true);

        if ($status !== 200) {
            $apiError = $decoded['error']['description'] ?? $decoded['error']['code'] ?? $curlError ?: 'Unknown Razorpay error';
            Log::error('Razorpay order creation failed', [
                'status'    => $status,
                'message'   => $apiError,
                'curl_err'  => $curlError,
                'receipt'   => $receipt,
            ]);
            return ['status' => $status, 'body' => $decoded ?? [], 'error' => $apiError];
        }

        return ['status' => $status, 'body' => $decoded];
    }

    public function verifyRazorpaySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $secret   = config('billing.razorpay.key_secret');
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $secret);
        return hash_equals($expected, $signature);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function stripeRequest(string $method, string $path, string $payload = ''): array
    {
        $ch = curl_init('https://api.stripe.com' . $path);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => config('billing.stripe.secret_key') . ':',
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = $payload;
            $opts[CURLOPT_HTTPHEADER] = ['Content-Type: application/x-www-form-urlencoded'];
        } elseif ($method === 'DELETE') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }
        curl_setopt_array($ch, $opts);
        $body      = curl_exec($ch);
        $status    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return ['status' => $status, 'body' => json_decode($body, true), 'curl_error' => $curlError];
    }
}
