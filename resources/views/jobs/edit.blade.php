@extends('layouts.app')
@section('title', 'Edit Job')
@section('page-title', 'Edit Job')
@section('content')
<div class="page-header"><h1>Edit: {{ $job->title }}</h1></div>

<div class="card">
    <form method="POST" action="{{ route('jobs.update', $job) }}">
        @csrf @method('PUT')
        <div class="form-row">
            <div class="form-group">
                <label>Job Title *</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $job->title) }}" required>
            </div>
            <div class="form-group">
                <label>Department</label>
                <select name="department_id" class="form-control">
                    <option value="">Select Department</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ old('department_id', $job->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Job Description *</label>
            <textarea name="description" class="form-control" rows="5" required>{{ old('description', $job->description) }}</textarea>
        </div>
        <div class="form-group">
            <label>Requirements</label>
            <textarea name="requirements" class="form-control" rows="3">{{ old('requirements', $job->requirements) }}</textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Min Experience</label>
                <input type="number" name="min_experience" class="form-control" value="{{ old('min_experience', $job->min_experience) }}" min="0">
            </div>
            <div class="form-group">
                <label>Max Experience</label>
                <input type="number" name="max_experience" class="form-control" value="{{ old('max_experience', $job->max_experience) }}" min="0">
            </div>
        </div>
        <div class="form-group">
            <label>Required Skills</label>
            <div class="tag-input-wrapper">
                <input type="hidden" name="required_skills" value="{{ old('required_skills', is_array($job->required_skills) ? implode(',', $job->required_skills) : '') }}">
                <input type="text" placeholder="Type skill and press Enter">
            </div>
        </div>
        <div class="form-group">
            <label>Nice-to-Have Skills</label>
            <div class="tag-input-wrapper">
                <input type="hidden" name="nice_to_have_skills" value="{{ old('nice_to_have_skills', is_array($job->nice_to_have_skills) ? implode(',', $job->nice_to_have_skills) : '') }}">
                <input type="text" placeholder="Type skill and press Enter">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Employment Type</label>
                <select name="employment_type" class="form-control">
                    @foreach(['full_time'=>'Full Time','part_time'=>'Part Time','contract'=>'Contract','intern'=>'Intern'] as $val=>$lbl)
                    <option value="{{ $val }}" {{ old('employment_type', $job->employment_type) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" class="form-control" value="{{ old('location', $job->location) }}">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Min Salary</label>
                <input type="number" name="salary_min" class="form-control" value="{{ old('salary_min', $job->salary_min) }}" step="0.01">
            </div>
            <div class="form-group">
                <label>Max Salary</label>
                <input type="number" name="salary_max" class="form-control" value="{{ old('salary_max', $job->salary_max) }}" step="0.01">
            </div>
        </div>
        <div class="flex gap-10">
            <button type="submit" class="btn btn-primary">Update Job</button>
            <a href="{{ route('jobs.show', $job) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
