@extends('layouts.app')
@section('title', 'Add Employee')
@section('page-title', 'Add Employee')
@section('content')
<div class="page-header"><h1>Add Employee</h1></div>
<div class="card">
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
            <select name="department_id" class="form-control"><option value="">Select</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
        </div>
        <div class="flex gap-10">
            <button type="submit" class="btn btn-primary">Create</button>
            <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
