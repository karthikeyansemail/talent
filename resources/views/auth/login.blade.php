@extends('layouts.auth')
@section('title', 'Sign In')
@section('content')
<h1 style="text-align:center">Welcome back</h1>
<p style="text-align:center">Sign in to your {{ $branding['app_name'] ?? 'Nalam Pulse' }} account</p>
<form method="POST" action="{{ url('/login') }}">
    @csrf
    <div class="form-group">
        <label>Email address</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
    </div>
    <div class="form-group">
        <label>Password</label>
        <div style="position:relative">
            <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter your password" required style="padding-right:40px">
            <button type="button" id="togglePassword" onclick="(function(){var i=document.getElementById('loginPassword'),b=document.getElementById('togglePassword');i.type=i.type==='password'?'text':'password';b.innerHTML=i.type==='password'?'<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'18\' height=\'18\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z\'/><circle cx=\'12\' cy=\'12\' r=\'3\'/></svg>':'<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'18\' height=\'18\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94\'/><path d=\'M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19\'/><line x1=\'1\' y1=\'1\' x2=\'23\' y2=\'23\'/></svg>'})()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--gray-400);display:flex;align-items:center;padding:0">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
        </div>
    </div>
    <div class="form-group" style="display:flex;align-items:center;justify-content:space-between">
        <label style="margin:0;display:flex;align-items:center;gap:6px;cursor:pointer"><input type="checkbox" name="remember" style="accent-color:var(--primary)"> Remember me</label>
        <a href="{{ url('/forgot-password') }}" style="font-size:13px;color:var(--primary);text-decoration:none">Forgot password?</a>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-full" style="margin-top:8px">Sign in</button>
</form>
@if(isset($ssoProviders) && $ssoProviders->isNotEmpty())
<div style="margin-top:24px">
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
        <div style="flex:1;height:1px;background:var(--gray-200)"></div>
        <span style="font-size:12px;color:var(--gray-400);white-space:nowrap;font-weight:500">OR CONTINUE WITH</span>
        <div style="flex:1;height:1px;background:var(--gray-200)"></div>
    </div>
    <div style="display:flex;flex-direction:column;gap:10px">
        @foreach($ssoProviders as $p)
        <a href="{{ route('sso.redirect', $p->provider) }}" style="display:flex;align-items:center;justify-content:center;gap:10px;padding:10px 16px;border:1px solid var(--gray-200);border-radius:8px;text-decoration:none;color:var(--gray-700);font-size:14px;font-weight:500;background:#fff;transition:background 0.15s,border-color 0.15s" onmouseover="this.style.background='var(--gray-50)';this.style.borderColor='var(--gray-300)'" onmouseout="this.style.background='#fff';this.style.borderColor='var(--gray-200)'">
            @if($p->provider === 'google')
            <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            @elseif($p->provider === 'microsoft')
            <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M11.4 11.4H0V0h11.4v11.4z" fill="#F25022"/><path d="M24 11.4H12.6V0H24v11.4z" fill="#7FBA00"/><path d="M11.4 24H0V12.6h11.4V24z" fill="#00A4EF"/><path d="M24 24H12.6V12.6H24V24z" fill="#FFB900"/></svg>
            @elseif($p->provider === 'okta')
            <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="11" fill="#007DC1"/><circle cx="12" cy="12" r="4.8" fill="#fff"/></svg>
            @endif
            Continue with {{ $p->provider_label }}
        </a>
        @endforeach
    </div>
</div>
@endif
@endsection
