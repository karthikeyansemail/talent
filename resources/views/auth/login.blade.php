@extends('layouts.auth')
@section('title', 'Sign In')
@section('content')
<h1 style="text-align:center">Welcome back</h1>
<p style="text-align:center">Sign in to your Talent Intelligence account</p>
<form method="POST" action="{{ url('/login') }}">
    @csrf
    <div class="form-group">
        <label>Email address</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="you@company.com" required autofocus>
    </div>
    <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
    </div>
    <div class="form-group" style="display:flex;align-items:center;justify-content:space-between">
        <label style="margin:0;display:flex;align-items:center;gap:6px;cursor:pointer"><input type="checkbox" name="remember" style="accent-color:var(--primary)"> Remember me</label>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-full" style="margin-top:8px">Sign in</button>
</form>
<p class="mt-3 text-center text-sm text-muted">Don't have an account? <a href="{{ route('register') }}">Create one</a></p>
@endsection
