@extends('layouts.app')
@section('title', 'Edit Drive')
@section('page-title', 'Edit Drive')
@section('content')
<div class="page-header">
    <h1>Edit: {{ $drive->company_name }}</h1>
</div>

<form method="POST" action="{{ route('placement.drives.update', $drive) }}">
    @csrf @method('PUT')
    <div class="card">
        <div class="card-header"><span class="card-header-icon">Drive Details</span></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Company Name *</label>
                    <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $drive->company_name) }}" required>
                </div>
                <div class="form-group">
                    <label>Role Title *</label>
                    <input type="text" name="role_title" class="form-control" value="{{ old('role_title', $drive->role_title) }}" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4">{{ old('description', $drive->description) }}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Eligible Courses</label>
                    <input type="text" name="eligible_courses" class="form-control" value="{{ old('eligible_courses', implode(', ', $drive->eligible_courses ?? [])) }}">
                </div>
                <div class="form-group">
                    <label>Eligible Batch Years</label>
                    <input type="text" name="eligible_batch_years" class="form-control" value="{{ old('eligible_batch_years', implode(', ', $drive->eligible_batch_years ?? [])) }}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Min CGPA</label>
                    <input type="number" name="min_cgpa" step="0.01" min="0" max="10" class="form-control" value="{{ old('min_cgpa', $drive->min_cgpa) }}">
                </div>
                <div class="form-group">
                    <label>Package (LPA)</label>
                    <input type="number" name="package_lpa" min="0" class="form-control" value="{{ old('package_lpa', $drive->package_lpa) }}">
                </div>
            </div>
            <div class="form-group">
                <label>Required Skills</label>
                <input type="text" name="required_skills" class="form-control" value="{{ old('required_skills', implode(', ', $drive->required_skills ?? [])) }}">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Drive Date</label>
                    <input type="date" name="drive_date" class="form-control" value="{{ old('drive_date', $drive->drive_date?->format('Y-m-d')) }}">
                </div>
                <div class="form-group">
                    <label>Test Format *</label>
                    <select name="test_format" class="form-control" required>
                        @foreach(['aptitude'=>'Aptitude Only','aptitude_plus_interview'=>'Aptitude + Interview','technical'=>'Technical Round'] as $v=>$l)
                            <option value="{{ $v }}" {{ $drive->test_format === $v ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Status *</label>
                <select name="status" class="form-control" style="max-width:280px" required>
                    @foreach(['draft'=>'Draft','open'=>'Open','closed'=>'Closed','completed'=>'Completed'] as $v=>$l)
                        <option value="{{ $v }}" {{ $drive->status === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-10" style="margin-top:20px">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('placement.drives.show', $drive) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>
@endsection
