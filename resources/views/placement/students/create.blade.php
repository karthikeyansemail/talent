@extends('layouts.app')
@section('title', 'Add Student')
@section('page-title', 'Add Student')
@section('content')
<div class="page-header"><h1>Add Student</h1></div>

<form method="POST" action="{{ route('placement.students.store') }}">
    @csrf
    <div class="card">
        <div class="card-header"><span class="card-header-icon">Student Details</span></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+91-9876543210">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Enrollment Number</label>
                    <input type="text" name="enrollment_number" class="form-control" value="{{ old('enrollment_number') }}" placeholder="CSE2026001">
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">— Select department —</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ old('department_id', $preselectDept) == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Course / Program</label>
                    <input type="text" name="course" class="form-control" value="{{ old('course') }}" placeholder="B.Tech CSE / B.Tech Mechatronics / etc.">
                </div>
                <div class="form-group">
                    <label>Batch Year</label>
                    <input type="number" name="batch_year" min="2000" max="2100" class="form-control" value="{{ old('batch_year') }}" placeholder="2026">
                </div>
            </div>
            <div class="form-group">
                <label>Skills</label>
                <input type="text" name="skills" class="form-control" value="{{ old('skills') }}" placeholder="Python, DSA, SQL">
                <small class="text-muted">Comma or semicolon separated</small>
            </div>
            <div class="flex gap-10" style="margin-top:20px">
                <button type="submit" class="btn btn-primary">Add Student</button>
                <a href="{{ route('placement.students.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>
@endsection
