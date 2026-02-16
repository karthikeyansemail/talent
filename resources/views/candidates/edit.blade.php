@extends('layouts.app')
@section('title', 'Edit Candidate')
@section('page-title', 'Edit Candidate')
@section('content')
<div class="page-header"><h1>Edit: {{ $candidate->full_name }}</h1></div>
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Candidate Details
        </span>
    </div>
    <div class="card-body">
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
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Update Candidate
                </button>
                <a href="{{ route('candidates.show', $candidate) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
