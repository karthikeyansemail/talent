@extends('layouts.app')
@section('title', 'Add User')
@section('page-title', 'Add User')
@section('content')
<div class="page-header"><h1>Add User</h1></div>
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            User Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('settings.users.store') }}">
            @csrf
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role" class="form-control" required>
                    @foreach(['hr_manager'=>'HR Manager','hiring_manager'=>'Hiring Manager','resource_manager'=>'Resource Manager','employee'=>'Employee','org_admin'=>'Org Admin'] as $val=>$lbl)
                    <option value="{{ $val }}" {{ old('role')===$val?'selected':'' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create User
                </button>
                <a href="{{ route('settings.users.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
