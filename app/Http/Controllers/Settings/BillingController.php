<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    public function __construct(private BillingService $billing) {}

    // GET /settings/billing
    public function index()
    {
        $org   = Auth::user()->currentOrganization();
        $orgId = $org->id;

        $jobsUsed       = JobPosting::where('organization_id', $orgId)->count();
        $candidatesUsed = Candidate::where('organization_id', $orgId)->count();

        return view('settings.billing', [
            'org'            => $org,
            'jobsUsed'       => $jobsUsed,
            'candidatesUsed' => $candidatesUsed,
            'jobLimit'       => $org->jobLimit(),
            'candidateLimit' => $org->candidateLimit(),
            'plans'          => config('billing.plans'),
            'razorpayKeyId'  => config('billing.razorpay.key_id'),
        ]);
    }

    // POST /settings/billing/dev-activate  (dev mode only)
    public function devActivate(Request $request)
    {
        abort_unless(config('app.billing_dev_mode'), 404);
        $request->validate(['plan' => 'required|in:cloud_enterprise,self_hosted']);

        $org = Auth::user()->currentOrganization();
        $this->applyPlan($org, $request->plan, 'dev_bypass_' . time(), 'dev');

        return redirect()->route('settings.billing.success', ['plan' => $request->plan])
            ->with('success', '[DEV] Plan activated without payment.');
    }

    // POST /settings/billing/checkout/stripe
    public function stripeCheckout(Request $request)
    {
        $request->validate(['plan' => 'required|in:cloud_enterprise,self_hosted']);

        $org     = Auth::user()->currentOrganization();
        $plan    = $request->plan;
        $priceId = $plan === 'self_hosted'
            ? config('billing.stripe.price_self_hosted')
            : config('billing.stripe.price_cloud');
        $mode    = $plan === 'self_hosted' ? 'payment' : 'subscription';

        $successUrl = route('settings.billing.success') . '?session_id={CHECKOUT_SESSION_ID}&plan=' . $plan;
        $cancelUrl  = route('settings.billing.index');

        $result = $this->billing->createStripeCheckoutSession(
            $priceId, $mode, $org->id, Auth::user()->email, $successUrl, $cancelUrl
        );

        if ($result['status'] !== 200 || empty($result['body']['url'])) {
            $msg = $result['error'] ?? 'Could not create Stripe checkout session. Please try again or contact support.';
            return back()->with('error', $msg);
        }

        return redirect($result['body']['url']);
    }

    // GET /settings/billing/success
    public function success(Request $request)
    {
        $plan = $request->query('plan', 'cloud_enterprise');
        return view('settings.billing-success', ['plan' => $plan]);
    }

    // GET /settings/billing/cancel
    public function cancel()
    {
        return redirect()->route('settings.billing.index')
            ->with('error', 'Payment was cancelled. No charge was made.');
    }

    // POST /settings/billing/razorpay/order  (AJAX → returns JSON)
    public function razorpayCreateOrder(Request $request)
    {
        $request->validate(['plan' => 'required|in:cloud_enterprise,self_hosted']);

        $org    = Auth::user()->currentOrganization();
        $plan   = $request->plan;
        $amount = $plan === 'self_hosted'
            ? config('billing.razorpay.amount_self_hosted_inr')
            : config('billing.razorpay.amount_cloud_inr');

        $receipt = 'org_' . $org->id . '_' . time();
        $result  = $this->billing->createRazorpayOrder($amount, $receipt);

        if ($result['status'] !== 200 || empty($result['body']['id'])) {
            $msg = $result['error'] ?? 'Could not create Razorpay order. Please try again.';
            return response()->json(['error' => $msg], 500);
        }

        $planConfig = config('billing.plans')[$plan];

        return response()->json([
            'order_id'    => $result['body']['id'],
            'amount'      => $amount,
            'currency'    => 'INR',
            'key'         => config('billing.razorpay.key_id'),
            'name'        => 'Nalam Pulse',
            'description' => $planConfig['label'],
            'prefill'     => [
                'email' => Auth::user()->email,
                'name'  => Auth::user()->name,
            ],
            'notes'       => [
                'organization_id' => $org->id,
                'plan'            => $plan,
            ],
        ]);
    }

    // POST /settings/billing/razorpay/verify  (AJAX → verify then redirect)
    public function razorpayVerify(Request $request)
    {
        $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
            'plan'                => 'required|in:cloud_enterprise,self_hosted',
        ]);

        if (!$this->billing->verifyRazorpaySignature(
            $request->razorpay_order_id,
            $request->razorpay_payment_id,
            $request->razorpay_signature
        )) {
            return response()->json(['error' => 'Payment signature is invalid. Contact support.'], 400);
        }

        $org = Auth::user()->currentOrganization();
        $this->applyPlan($org, $request->plan, $request->razorpay_payment_id, 'razorpay');

        return response()->json([
            'redirect' => route('settings.billing.success') . '?plan=' . $request->plan,
        ]);
    }

    // POST /settings/billing/cancel-subscription
    public function cancelSubscription()
    {
        $org = Auth::user()->currentOrganization();

        if ($org->stripe_subscription_id) {
            $this->billing->cancelStripeSubscription($org->stripe_subscription_id);
        }

        $org->update([
            'subscription_plan'       => 'free',
            'subscription_expires_at' => null,
            'is_premium'              => false,
            'stripe_subscription_id'  => null,
        ]);

        return back()->with('success', 'Subscription cancelled. Your account is now on the Free plan.');
    }

    // GET /settings/billing/deploy-guide
    public function deployGuide()
    {
        return view('settings.billing-deploy-guide');
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function applyPlan($org, string $plan, string $paymentRef, string $provider): void
    {
        $expiresAt = $plan === 'cloud_enterprise' ? Carbon::now()->addMonth() : null;

        $data = [
            'subscription_plan'       => $plan,
            'subscription_expires_at' => $expiresAt,
            'is_premium'              => true,
        ];

        if ($provider === 'razorpay') {
            $data['razorpay_subscription_id'] = $paymentRef;
        }

        $org->update($data);
    }
}
