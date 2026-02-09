@extends('layouts.app')
@section('title', 'Add User')
@section('content')
<div class="page-header"><h1>Add User</h1></div>
<div class="card">
    <form method="POST" action="{{ route('settings.users.store') }}">
        @csrf
        <div class="form-group"><label>Name *</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
        <div class="form-group"><label>Password *</label><input type="password" name="password" class="form-control" required></div>
        <div class="form-group"><label>Role *</label>
            <select name="role" class="form-control" required>
                @foreach(['hr_manager'=>'HR Manager','hiring_manager'=>'Hiring Manager','resource_manager'=>'Resource Manager','employee'=>'Employee','org_admin'=>'Org Admin'] as $val=>$lbl)
                <option value="{{ $val }}" {{ old('role')===$val?'selected':'' }}>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-10">
            <button type="submit" class="btn btn-primary">Create User</button>
            <a href="{{ route('settings.users.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
