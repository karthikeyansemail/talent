@extends('layouts.auth')
@section('title', 'Login')
@section('content')
<h1>Sign In</h1>
<p>Welcome back to Talent Intelligence</p>
<form method="POST" action="{{ url('/login') }}">
    @csrf
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
    </div>
    <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="form-group">
        <label><input type="checkbox" name="remember"> Remember me</label>
    </div>
    <button type="submit" class="btn btn-primary w-full">Sign In</button>
</form>
<p class="mt-2 text-center text-sm">Don't have an account? <a href="{{ route('register') }}">Register</a></p>
@endsection
