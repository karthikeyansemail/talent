@extends('layouts.app')
@section('title', 'Edit Employee')
@section('page-title', 'Edit Employee')
@section('content')
<div class="page-header"><h1>Edit: {{ $employee->full_name }}</h1></div>
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Employee Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('employees.update', $employee) }}">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group"><label>First Name *</label><input type="text" name="first_name" class="form-control" value="{{ old('first_name', $employee->first_name) }}" required></div>
                <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" class="form-control" value="{{ old('last_name', $employee->last_name) }}" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="{{ old('email', $employee->email) }}" required></div>
                <div class="form-group"><label>Designation</label><input type="text" name="designation" class="form-control" value="{{ old('designation', $employee->designation) }}"></div>
            </div>
            <div class="form-group">
                <label>Department</label>
                <select name="department_id" class="form-control">
                    <option value="">Select Department</option>
                    @foreach($departments as $d)
                    <option value="{{ $d->id }}" {{ old('department_id',$employee->department_id)==$d->id?'selected':'' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Update Employee
                </button>
                <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
