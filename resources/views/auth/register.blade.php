@extends('layouts.auth')
@section('title', 'Register')
@section('content')
<h1>Create Account</h1>
<p>Set up your organization on Talent Intelligence</p>
<form method="POST" action="{{ url('/register') }}">
    @csrf
    <div class="form-group">
        <label>Organization Name</label>
        <input type="text" name="org_name" class="form-control" value="{{ old('org_name') }}" required>
    </div>
    <div class="form-group">
        <label>Your Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
    </div>
    <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
    </div>
    <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-full">Register</button>
</form>
<p class="mt-2 text-center text-sm">Already have an account? <a href="{{ route('login') }}">Sign In</a></p>
@endsection
