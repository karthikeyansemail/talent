<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private BillingService $billing) {}

    // POST /webhooks/stripe
    public function stripe(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature', '');

        if (!$this->billing->verifyStripeWebhookSignature($payload, $sigHeader)) {
            Log::warning('Stripe webhook: invalid signature');
            return response('Signature mismatch', 401);
        }

        $event = json_decode($payload, true);
        $type  = $event['type'] ?? '';
        $obj   = $event['data']['object'] ?? [];

        match ($type) {
            'checkout.session.completed'    => $this->stripeCheckoutCompleted($obj),
            'invoice.paid'                  => $this->stripeInvoicePaid($obj),
            'customer.subscription.deleted' => $this->stripeSubscriptionDeleted($obj),
            'customer.subscription.updated' => $this->stripeSubscriptionUpdated($obj),
            default                         => null,
        };

        return response()->json(['received' => true]);
    }

    // POST /webhooks/razorpay
    public function razorpay(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature', '');
        $secret    = config('billing.razorpay.key_secret');

        if ($secret && $signature) {
            $expected = hash_hmac('sha256', $payload, $secret);
            if (!hash_equals($expected, $signature)) {
                Log::warning('Razorpay webhook: invalid signature');
                return response('Signature mismatch', 401);
            }
        }

        $event   = json_decode($payload, true);
        $payment = $event['payload']['payment']['entity'] ?? [];

        if (($event['event'] ?? '') === 'payment.captured' && !empty($payment['id'])) {
            $orgId = $payment['notes']['organization_id'] ?? null;
            $plan  = $payment['notes']['plan'] ?? 'cloud_enterprise';

            if ($orgId && $org = Organization::find((int) $orgId)) {
                $org->update([
                    'subscription_plan'        => $plan,
                    'subscription_expires_at'  => $plan === 'cloud_enterprise' ? Carbon::now()->addMonth() : null,
                    'is_premium'               => true,
                    'razorpay_subscription_id' => $payment['id'],
                ]);
            }
        }

        return response()->json(['received' => true]);
    }

    // ── Stripe event handlers ─────────────────────────────────────────────────

    private function stripeCheckoutCompleted(array $session): void
    {
        $orgId = (int) ($session['metadata']['organization_id'] ?? 0);
        $plan  = $session['metadata']['plan'] ?? 'cloud_enterprise';
        if (!$orgId || !$org = Organization::find($orgId)) return;

        $org->update([
            'subscription_plan'       => $plan,
            'subscription_expires_at' => $plan === 'cloud_enterprise' ? Carbon::now()->addMonth() : null,
            'is_premium'              => true,
            'stripe_customer_id'      => $session['customer'] ?? null,
            'stripe_subscription_id'  => $session['subscription'] ?? null,
        ]);
    }

    private function stripeInvoicePaid(array $invoice): void
    {
        $customerId = $invoice['customer'] ?? null;
        if (!$customerId) return;

        $org = Organization::where('stripe_customer_id', $customerId)->first();
        if ($org && $org->subscription_plan === 'cloud_enterprise') {
            $org->update(['subscription_expires_at' => Carbon::now()->addMonth()]);
        }
    }

    private function stripeSubscriptionDeleted(array $subscription): void
    {
        $org = Organization::where('stripe_subscription_id', $subscription['id'])->first();
        if (!$org) return;

        $org->update([
            'subscription_plan'       => 'free',
            'subscription_expires_at' => null,
            'is_premium'              => false,
            'stripe_subscription_id'  => null,
        ]);
    }

    private function stripeSubscriptionUpdated(array $subscription): void
    {
        $org = Organization::where('stripe_subscription_id', $subscription['id'])->first();
        if (!$org) return;

        if ($subscription['cancel_at_period_end'] ?? false) {
            $periodEnd = $subscription['current_period_end'] ?? null;
            if ($periodEnd) {
                $org->update(['subscription_expires_at' => Carbon::createFromTimestamp($periodEnd)]);
            }
        }
    }
}
