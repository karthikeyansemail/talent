@extends('layouts.app')
@section('title', 'Add Candidate')
@section('page-title', 'Add Candidate')
@section('content')
<div class="page-header"><h1>Add Candidate</h1></div>
<div class="card">
    <form method="POST" action="{{ route('candidates.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group"><label>First Name *</label><input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required></div>
            <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" value="{{ old('phone') }}"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Current Company</label><input type="text" name="current_company" class="form-control" value="{{ old('current_company') }}"></div>
            <div class="form-group"><label>Current Title</label><input type="text" name="current_title" class="form-control" value="{{ old('current_title') }}"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Experience (years)</label><input type="number" name="experience_years" class="form-control" value="{{ old('experience_years') }}" step="0.5" min="0"></div>
            <div class="form-group"><label>Source *</label>
                <select name="source" class="form-control">
                    <option value="upload">Upload</option><option value="referral">Referral</option><option value="direct">Direct</option>
                </select>
            </div>
        </div>
        <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea></div>
        <div class="flex gap-10">
            <button type="submit" class="btn btn-primary">Create Candidate</button>
            <a href="{{ route('candidates.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
