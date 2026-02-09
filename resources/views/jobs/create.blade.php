@extends('layouts.app')
@section('title', 'Create Job')
@section('page-title', 'Create Job Posting')
@section('content')
<div class="page-header"><h1>Create Job Posting</h1></div>

<div class="card">
    <form method="POST" action="{{ route('jobs.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Job Title *</label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
            </div>
            <div class="form-group">
                <label>Department</label>
                <select name="department_id" class="form-control">
                    <option value="">Select Department</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Job Description *</label>
            <textarea name="description" class="form-control" rows="5" required>{{ old('description') }}</textarea>
        </div>

        <div class="form-group">
            <label>Requirements</label>
            <textarea name="requirements" class="form-control" rows="3">{{ old('requirements') }}</textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Min Experience (years)</label>
                <input type="number" name="min_experience" class="form-control" value="{{ old('min_experience', 0) }}" min="0">
            </div>
            <div class="form-group">
                <label>Max Experience (years)</label>
                <input type="number" name="max_experience" class="form-control" value="{{ old('max_experience', 10) }}" min="0">
            </div>
        </div>

        <div class="form-group">
            <label>Required Skills</label>
            <div class="tag-input-wrapper">
                <input type="hidden" name="required_skills" value="{{ old('required_skills') }}">
                <input type="text" placeholder="Type skill and press Enter">
            </div>
        </div>

        <div class="form-group">
            <label>Nice-to-Have Skills</label>
            <div class="tag-input-wrapper">
                <input type="hidden" name="nice_to_have_skills" value="{{ old('nice_to_have_skills') }}">
                <input type="text" placeholder="Type skill and press Enter">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Employment Type *</label>
                <select name="employment_type" class="form-control" required>
                    <option value="full_time" {{ old('employment_type') === 'full_time' ? 'selected' : '' }}>Full Time</option>
                    <option value="part_time" {{ old('employment_type') === 'part_time' ? 'selected' : '' }}>Part Time</option>
                    <option value="contract" {{ old('employment_type') === 'contract' ? 'selected' : '' }}>Contract</option>
                    <option value="intern" {{ old('employment_type') === 'intern' ? 'selected' : '' }}>Intern</option>
                </select>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control" value="{{ old('location') }}">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Min Salary</label>
                <input type="number" name="salary_min" class="form-control" value="{{ old('salary_min') }}" step="0.01">
            </div>
            <div class="form-group">
                <label>Max Salary</label>
                <input type="number" name="salary_max" class="form-control" value="{{ old('salary_max') }}" step="0.01">
            </div>
        </div>

        <div class="form-group">
            <label>Status *</label>
            <select name="status" class="form-control" required>
                <option value="draft">Draft</option>
                <option value="open">Open</option>
            </select>
        </div>

        <div class="flex gap-10">
            <button type="submit" class="btn btn-primary">Create Job</button>
            <a href="{{ route('jobs.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
