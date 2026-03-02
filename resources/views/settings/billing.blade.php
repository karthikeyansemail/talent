@extends('layouts.app')
@section('title', 'Billing & Plan')
@section('page-title', 'Billing & Plan')

@section('content')
<div class="page-header">
    <h1>Billing & Plan</h1>
</div>

{{-- Current Plan --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Current Plan
        </span>
        @if($org->subscription_plan === 'free')
            <span class="badge badge-gray">Free Plan</span>
        @elseif($org->subscription_plan === 'self_hosted')
            <span class="badge badge-green">Self-Hosted · Active</span>
        @elseif($org->subscription_expires_at)
            <span class="badge badge-green">Active · Renews {{ $org->subscription_expires_at->format('M j, Y') }}</span>
        @else
            <span class="badge badge-green">Active</span>
        @endif
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px">
            <div style="background:var(--gray-50);border-radius:8px;padding:16px;text-align:center">
                <div style="font-size:20px;font-weight:700;color:var(--primary)">{{ $org->planLabel() }}</div>
                <div style="font-size:12px;color:var(--gray-500);margin-top:4px">Current Plan</div>
            </div>
            <div style="background:{{ $jobLimit && $jobsUsed >= $jobLimit ? '#fef2f2' : 'var(--gray-50)' }};border-radius:8px;padding:16px;text-align:center">
                <div style="font-size:20px;font-weight:700;color:{{ $jobLimit && $jobsUsed >= $jobLimit ? '#ef4444' : 'var(--gray-900)' }}">
                    {{ $jobsUsed }}{{ $jobLimit ? ' / ' . $jobLimit : '' }}
                </div>
                <div style="font-size:12px;color:var(--gray-500);margin-top:4px">
                    Jobs {{ $jobLimit ? "(limit: {$jobLimit})" : '(unlimited)' }}
                </div>
            </div>
            <div style="background:{{ $candidateLimit && $candidatesUsed >= $candidateLimit ? '#fef2f2' : 'var(--gray-50)' }};border-radius:8px;padding:16px;text-align:center">
                <div style="font-size:20px;font-weight:700;color:{{ $candidateLimit && $candidatesUsed >= $candidateLimit ? '#ef4444' : 'var(--gray-900)' }}">
                    {{ $candidatesUsed }}{{ $candidateLimit ? ' / ' . $candidateLimit : '' }}
                </div>
                <div style="font-size:12px;color:var(--gray-500);margin-top:4px">
                    Candidates {{ $candidateLimit ? "(limit: {$candidateLimit})" : '(unlimited)' }}
                </div>
            </div>
        </div>

        @if($org->subscription_plan === 'cloud_enterprise' && $org->stripe_subscription_id)
        <div style="display:flex;align-items:center;gap:16px;padding-top:4px">
            <form method="POST" action="{{ route('settings.billing.cancel-subscription') }}"
                  onsubmit="return confirm('Are you sure? Your account will be downgraded to Free at end of the current billing period.')">
                @csrf
                <button type="submit" class="btn btn-secondary" style="border-color:#ef4444;color:#ef4444">
                    Cancel Subscription
                </button>
            </form>
            <span style="font-size:13px;color:var(--gray-500)">
                Need help? <a href="mailto:support@nalampulse.com" style="color:var(--primary)">support@nalampulse.com</a>
            </span>
        </div>
        @endif
    </div>
</div>

@if($org->subscription_plan === 'free')

{{-- ── Cloud Enterprise Upgrade ──────────────────────────────── --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            Upgrade to Cloud Enterprise
        </span>
        <span id="billing-region-label" style="font-size:12px;color:var(--gray-400)">Detecting region…</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start">
            {{-- Left: price + pay --}}
            <div style="border:2px solid var(--primary);border-radius:12px;padding:28px;position:relative">
                <div style="position:absolute;top:-12px;left:20px;background:var(--primary);color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:0.05em">MOST POPULAR</div>

                <div style="font-size:17px;font-weight:700;color:var(--gray-900);margin-bottom:6px">Cloud Enterprise</div>
                <div style="font-size:13px;color:var(--gray-500);margin-bottom:16px">Full platform hosted by us. Pay monthly, cancel anytime.</div>

                {{-- USD price --}}
                <div class="billing-usd" style="display:none;margin-bottom:20px">
                    <span style="font-size:38px;font-weight:800;color:var(--primary)">$49</span>
                    <span style="font-size:14px;color:var(--gray-500)"> /month</span>
                </div>
                {{-- INR price --}}
                <div class="billing-inr" style="display:none;margin-bottom:20px">
                    <span style="font-size:38px;font-weight:800;color:var(--primary)">₹3,999</span>
                    <span style="font-size:14px;color:var(--gray-500)"> /month</span>
                </div>

                {{-- Stripe (USD) --}}
                <form method="POST" action="{{ route('settings.billing.stripe.checkout') }}"
                      class="billing-stripe-form" style="display:none">
                    @csrf
                    <input type="hidden" name="plan" value="cloud_enterprise">
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        Pay with Card (Stripe)
                    </button>
                </form>

                {{-- Razorpay (INR) --}}
                <button class="btn btn-primary billing-rzp-btn" data-plan="cloud_enterprise"
                        style="display:none;width:100%;justify-content:center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                    Pay with Razorpay ₹
                </button>

                @if(config('app.billing_dev_mode'))
                <form method="POST" action="{{ route('settings.billing.dev-activate') }}" style="margin-top:10px">
                    @csrf
                    <input type="hidden" name="plan" value="cloud_enterprise">
                    <button type="submit" class="btn btn-secondary" style="width:100%;justify-content:center;border-color:#f59e0b;color:#f59e0b;font-size:12px">
                        ⚡ DEV: Activate Without Payment
                    </button>
                </form>
                @endif

                <p style="margin-top:14px;font-size:11px;color:var(--gray-400);text-align:center">
                    Secure payment · Cancel anytime · Taxes may apply
                </p>
            </div>

            {{-- Right: feature list --}}
            <div>
                <div style="font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:12px">Everything you unlock:</div>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px">
                    @foreach(config('billing.plans.cloud_enterprise.features') as $feature)
                    <li style="display:flex;align-items:center;gap:8px;font-size:13.5px;color:var(--gray-600)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- ── Self-Hosted: separate CTA (no in-app payment) ──────────── --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"/><rect x="2" y="14" width="20" height="8" rx="2" ry="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/></svg>
            Self-Hosted Enterprise
        </span>
        <span class="badge badge-blue">Monthly subscription</span>
    </div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start">
            <div>
                <div style="font-size:28px;font-weight:800;color:var(--gray-900);margin-bottom:4px">$79<span style="font-size:15px;color:var(--gray-500);font-weight:400">/month</span> <span style="font-size:13px;color:var(--gray-400);font-weight:400">or ₹5,999/month</span></div>
                <div style="font-size:13px;color:var(--gray-500);margin-bottom:20px">Monthly subscription. Deploy on your own servers — AWS, GCP, or Azure. Monthly updates included. Cancel anytime.</div>

                <div style="display:flex;flex-direction:column;gap:10px">
                    <a href="https://nalampulse.com/#pricing" target="_blank" class="btn btn-primary" style="justify-content:center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        Subscribe on Website
                    </a>
                    <a href="mailto:sales@nalampulse.com?subject=Self-Hosted%20License%20Enquiry" class="btn btn-secondary" style="justify-content:center">
                        Contact Sales
                    </a>
                    <a href="{{ route('settings.billing.deploy-guide') }}" class="btn btn-secondary" style="justify-content:center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        View Deployment Guide (AWS / GCP / Azure)
                    </a>
                </div>
            </div>
            <div>
                <div style="font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:12px">Everything in Cloud Enterprise, plus:</div>
                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px">
                    @foreach(config('billing.plans.self_hosted.features') as $feature)
                    <li style="display:flex;align-items:center;gap:8px;font-size:13.5px;color:var(--gray-600)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- What you're missing on Free --}}
<div class="card" style="margin-top:4px">
    <div class="card-header"><span>What you're missing on Free</span></div>
    <div class="card-body">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
            @foreach([
                ['AI Resume Analysis', 'Automatically score & rank candidates against job requirements.'],
                ['Resource Allocation', 'Match employees to projects based on skills and availability.'],
                ['Work Pulse', 'Track team throughput, quality, cycle time, and Jira/GitHub signals.'],
                ['Unlimited Jobs', 'Post more than 3 jobs simultaneously.'],
                ['Unlimited Candidates', 'Track more than 50 candidates in your pipeline.'],
                ['All Integrations', 'Connect Jira, Azure DevOps, Slack, Teams, and more.'],
            ] as [$title, $desc])
            <div style="padding:14px;background:var(--gray-50);border-radius:8px">
                <div style="font-size:13px;font-weight:600;color:var(--gray-800);margin-bottom:4px">🔒 {{ $title }}</div>
                <div style="font-size:12px;color:var(--gray-500)">{{ $desc }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@elseif($org->subscription_plan === 'self_hosted')
{{-- Self-hosted user: show deployment guide link --}}
<div class="card">
    <div class="card-body" style="text-align:center;padding:40px 24px">
        <div style="font-size:16px;font-weight:600;color:var(--gray-800);margin-bottom:8px">You're on a Self-Hosted Subscription</div>
        <p style="font-size:13px;color:var(--gray-500);margin-bottom:20px">
            Your subscription is active. Deploy or migrate using the guide below.
        </p>
        <a href="{{ route('settings.billing.deploy-guide') }}" class="btn btn-primary">
            View Deployment Guide (AWS / GCP / Azure)
        </a>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const RZP_KEY = '{{ $razorpayKeyId }}';

    // ── Currency detection ────────────────────────────────
    fetch('https://ipapi.co/json/', { signal: AbortSignal.timeout(4000) })
        .then(r => r.json())
        .then(data => applyRegion(data.country_code === 'IN'))
        .catch(() => applyRegion(false));

    function applyRegion(isIndia) {
        document.querySelectorAll('.billing-usd').forEach(el => el.style.display = isIndia ? 'none' : 'block');
        document.querySelectorAll('.billing-inr').forEach(el => el.style.display = isIndia ? 'block' : 'none');
        document.querySelectorAll('.billing-stripe-form').forEach(el => el.style.display = isIndia ? 'none' : 'block');
        document.querySelectorAll('.billing-rzp-btn').forEach(el => el.style.display = isIndia ? 'inline-flex' : 'none');

        const label = document.getElementById('billing-region-label');
        if (label) label.textContent = isIndia ? 'Showing INR · India' : 'Showing USD · International';
    }

    // ── Razorpay flow ─────────────────────────────────────
    document.querySelectorAll('.billing-rzp-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const plan = this.dataset.plan;
            btn.disabled = true;
            btn.textContent = 'Creating order…';

            fetch('{{ route("settings.billing.razorpay.order") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ plan }),
            })
            .then(r => r.json())
            .then(order => {
                if (order.error) { alert(order.error); btn.disabled = false; btn.textContent = 'Pay with Razorpay ₹'; return; }

                new Razorpay({
                    key:         RZP_KEY,
                    amount:      order.amount,
                    currency:    order.currency,
                    name:        order.name,
                    description: order.description,
                    order_id:    order.order_id,
                    prefill:     order.prefill,
                    notes:       order.notes,
                    handler: function (resp) {
                        fetch('{{ route("settings.billing.razorpay.verify") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                            body: JSON.stringify({
                                razorpay_order_id:   resp.razorpay_order_id,
                                razorpay_payment_id: resp.razorpay_payment_id,
                                razorpay_signature:  resp.razorpay_signature,
                                plan,
                            }),
                        })
                        .then(r => r.json())
                        .then(res => { if (res.redirect) window.location.href = res.redirect; })
                        .catch(() => alert('Verification error. Contact support@nalampulse.com'));
                    },
                    modal: { ondismiss: function () { btn.disabled = false; btn.textContent = 'Pay with Razorpay ₹'; } }
                }).open();
            })
            .catch(() => { alert('Network error. Please try again.'); btn.disabled = false; btn.textContent = 'Pay with Razorpay ₹'; });
        });
    });
})();
</script>
@endpush
