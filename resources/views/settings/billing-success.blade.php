@extends('layouts.app')
@section('title', 'Payment Successful')
@section('page-title', 'Billing & Plan')

@section('content')
<div class="page-header"><h1>Billing & Plan</h1></div>

<div class="card">
    <div class="card-body" style="text-align:center;padding:64px 24px">
        <div style="width:64px;height:64px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>

        <h2 style="font-size:24px;font-weight:700;color:var(--gray-900);margin-bottom:8px">
            Payment Successful!
        </h2>
        <p style="font-size:15px;color:var(--gray-500);margin-bottom:8px">
            Your account has been upgraded to
            <strong style="color:var(--primary)">
                {{ config('billing.plans')[$plan]['label'] ?? 'Enterprise' }}
            </strong>.
        </p>
        <p style="font-size:13px;color:var(--gray-400);margin-bottom:32px">
            All features are now unlocked. It may take a moment for changes to reflect if paying via bank transfer or UPI.
        </p>

        <div style="display:flex;gap:12px;justify-content:center">
            <a href="{{ route('dashboard') }}" class="btn btn-primary">Go to Dashboard</a>
            <a href="{{ route('settings.billing.index') }}" class="btn btn-secondary">View Billing</a>
        </div>
    </div>
</div>
@endsection
