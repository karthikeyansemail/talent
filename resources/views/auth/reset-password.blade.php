@extends('layouts.auth')
@section('title', 'Set New Password')
@section('content')
<h1 style="text-align:center">Set new password</h1>
<p style="text-align:center;color:var(--gray-500)">Choose a strong password of at least 8 characters.</p>

@if($errors->any())
<div class="alert alert-error" style="margin-bottom:20px">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ url('/reset-password') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="form-group">
        <label>Email address</label>
        <input type="email" name="email" class="form-control"
               value="{{ old('email', $email) }}" required>
    </div>

    <div class="form-group">
        <label>New Password</label>
        <input type="password" name="password" class="form-control"
               placeholder="At least 8 characters" required autofocus>
    </div>

    <div class="form-group">
        <label>Confirm New Password</label>
        <input type="password" name="password_confirmation" class="form-control"
               placeholder="Repeat the password" required>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-full" style="margin-top:8px">
        Reset Password
    </button>
</form>

<div style="text-align:center;margin-top:20px">
    <a href="{{ url('/login') }}" style="font-size:14px;color:var(--primary);text-decoration:none">
        &larr; Back to Sign In
    </a>
</div>
@endsection
