@extends('layouts.app')
@section('title', 'Edit User')
@section('page-title', 'Edit User')
@section('content')
<div class="page-header"><h1>Edit: {{ $user->name }}</h1></div>
<div class="card" style="overflow:visible">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            User Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('settings.users.update', $user) }}">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                <div class="form-hint">Leave blank to keep current password</div>
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role" class="form-control" required>
                    @foreach(['hr_manager'=>'HR Manager','hiring_manager'=>'Hiring Manager','resource_manager'=>'Resource Manager','management'=>'Management','employee'=>'Employee','org_admin'=>'Org Admin'] as $val=>$lbl)
                    <option value="{{ $val }}" {{ old('role',$user->role)===$val?'selected':'' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Update User
                </button>
                <a href="{{ route('settings.users.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
