@extends('layouts.auth')
@section('title', 'Sign In')
@section('content')
<h1 style="text-align:center">Welcome back</h1>
<p style="text-align:center">Sign in to your {{ $branding['app_name'] ?? 'Nalam Compass' }} account</p>
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
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-full" style="margin-top:8px">Sign in</button>
</form>
<p class="mt-3 text-center text-sm text-muted">Don't have an account? <a href="{{ route('register') }}">Create one</a></p>
@endsection
