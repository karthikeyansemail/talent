@extends('layouts.app')
@section('title', 'Edit User')
@section('content')
<div class="page-header"><h1>Edit: {{ $user->name }}</h1></div>
<div class="card">
    <form method="POST" action="{{ route('settings.users.update', $user) }}">
        @csrf @method('PUT')
        <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
        <div class="form-group"><label>New Password</label><input type="password" name="password" class="form-control" placeholder="Leave blank to keep current"><div class="form-hint">Leave blank to keep current password</div></div>
        <div class="form-group"><label>Role *</label>
            <select name="role" class="form-control" required>
                @foreach(['hr_manager'=>'HR Manager','hiring_manager'=>'Hiring Manager','resource_manager'=>'Resource Manager','employee'=>'Employee','org_admin'=>'Org Admin'] as $val=>$lbl)
                <option value="{{ $val }}" {{ old('role',$user->role)===$val?'selected':'' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-10">
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('settings.users.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
