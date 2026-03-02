@extends('layouts.auth')
@section('title', 'Reset Password')
@section('content')
<h1 style="text-align:center">Forgot your password?</h1>
<p style="text-align:center;color:var(--gray-500)">Enter your email address and we'll send you a reset link.</p>

@if(session('status'))
<div class="alert alert-success" style="margin-bottom:20px">
    {{ session('status') }}
</div>
@endif

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:20px">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ url('/forgot-password') }}">
    @csrf
    <div class="form-group">
        <label>Email address</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}"
               placeholder="you@company.com" required autofocus>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-full" style="margin-top:8px">
        Send Reset Link
    </button>
</form>

<div style="text-align:center;margin-top:20px">
    <a href="{{ url('/login') }}" style="font-size:14px;color:var(--primary);text-decoration:none">
        &larr; Back to Sign In
    </a>
</div>
@endsection
