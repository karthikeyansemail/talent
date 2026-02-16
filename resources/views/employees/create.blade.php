@extends('layouts.app')
@section('title', 'Add Employee')
@section('page-title', 'Add Employee')
@section('content')
<div class="page-header"><h1>Add Employee</h1></div>
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            Employee Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('employees.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group"><label>First Name *</label><input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required></div>
                <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
                <div class="form-group"><label>Designation</label><input type="text" name="designation" class="form-control" value="{{ old('designation') }}"></div>
            </div>
            <div class="form-group">
                <label>Department</label>
                <select name="department_id" class="form-control">
                    <option value="">Select Department</option>
                    @foreach($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create Employee
                </button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
