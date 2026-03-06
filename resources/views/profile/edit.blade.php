@extends('layouts.app')
@section('title', 'My Profile')
@section('page-title', 'My Profile')

@section('content')
<div class="page-header">
    <h1>My Profile</h1>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div style="max-width:540px">

    {{-- Name --}}
    <div class="card" style="margin-bottom:20px">
        <div class="card-header"><span>Account Details</span></div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="form-group" style="margin-bottom:16px">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group" style="margin-bottom:20px">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="{{ $user->email }}" disabled
                           style="background:var(--gray-50);color:var(--gray-400)">
                    <div style="font-size:11px;color:var(--gray-400);margin-top:4px">Email cannot be changed. Contact your admin if needed.</div>
                </div>

                <div style="border-top:1px solid var(--gray-100);padding-top:20px;margin-bottom:16px">
                    <div style="font-size:13px;font-weight:600;color:var(--gray-700);margin-bottom:14px">Change Password</div>
                    <div style="font-size:12px;color:var(--gray-400);margin-bottom:14px">Leave blank to keep your current password.</div>

                    <div class="form-group" style="margin-bottom:14px">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password"
                               class="form-control @error('current_password') is-invalid @enderror"
                               autocomplete="current-password">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom:14px">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               autocomplete="new-password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation"
                               class="form-control" autocomplete="new-password">
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Read-only info --}}
    <div class="card">
        <div class="card-header"><span>Role &amp; Organization</span></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                    <div style="font-size:11px;font-weight:600;color:var(--gray-400);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">Role</div>
                    <div style="font-size:13px;color:var(--gray-800);font-weight:500">{{ ucwords(str_replace('_', ' ', $user->role)) }}</div>
                </div>
                <div>
                    <div style="font-size:11px;font-weight:600;color:var(--gray-400);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">Organization</div>
                    <div style="font-size:13px;color:var(--gray-800);font-weight:500">{{ $user->currentOrganization()?->name ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
