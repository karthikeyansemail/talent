@extends('layouts.app')
@section('title', 'Edit Candidate')
@section('page-title', 'Edit Candidate')
@section('content')
<div class="page-header"><h1>Edit: {{ $candidate->full_name }}</h1></div>
<div class="card">
    <form method="POST" action="{{ route('candidates.update', $candidate) }}">
        @csrf @method('PUT')
        <div class="form-row">
            <div class="form-group"><label>First Name *</label><input type="text" name="first_name" class="form-control" value="{{ old('first_name', $candidate->first_name) }}" required></div>
            <div class="form-group"><label>Last Name *</label><input type="text" name="last_name" class="form-control" value="{{ old('last_name', $candidate->last_name) }}" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Email *</label><input type="email" name="email" class="form-control" value="{{ old('email', $candidate->email) }}" required></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $candidate->phone) }}"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Current Company</label><input type="text" name="current_company" class="form-control" value="{{ old('current_company', $candidate->current_company) }}"></div>
            <div class="form-group"><label>Current Title</label><input type="text" name="current_title" class="form-control" value="{{ old('current_title', $candidate->current_title) }}"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Experience (years)</label><input type="number" name="experience_years" class="form-control" value="{{ old('experience_years', $candidate->experience_years) }}" step="0.5" min="0"></div>
            <div class="form-group"><label>Source</label>
                <select name="source" class="form-control">
                    @foreach(['upload','referral','direct'] as $s)
                    <option value="{{ $s }}" {{ old('source', $candidate->source) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ old('notes', $candidate->notes) }}</textarea></div>
        <div class="flex gap-10">
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('candidates.show', $candidate) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
