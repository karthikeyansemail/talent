@extends('layouts.app')
@section('title', 'Edit Project')
@section('page-title', 'Edit Project')
@section('content')
<div class="page-header"><h1>Edit: {{ $project->name }}</h1></div>
<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Project Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('projects.update', $project) }}">
            @csrf @method('PUT')
            <div class="form-group"><label>Project Name *</label><input type="text" name="name" class="form-control" value="{{ old('name', $project->name) }}" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="3">{{ old('description', $project->description) }}</textarea></div>
            <div class="form-group"><label>Required Skills</label>
                <div class="tag-input-wrapper"><input type="hidden" name="required_skills" value="{{ old('required_skills', is_array($project->required_skills) ? implode(',', $project->required_skills) : '') }}"><input type="text" placeholder="Type skill and press Enter"></div>
            </div>
            <div class="form-group"><label>Required Technologies</label>
                <div class="tag-input-wrapper"><input type="hidden" name="required_technologies" value="{{ old('required_technologies', is_array($project->required_technologies) ? implode(',', $project->required_technologies) : '') }}"><input type="text" placeholder="Type technology and press Enter"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Complexity</label>
                    <select name="complexity_level" class="form-control">@foreach(['low','medium','high','critical'] as $c)<option value="{{ $c }}" {{ old('complexity_level',$project->complexity_level)===$c?'selected':'' }}>{{ ucfirst($c) }}</option>@endforeach</select>
                </div>
                <div class="form-group"><label>Status</label>
                    <select name="status" class="form-control">@foreach(['planning','active','completed','on_hold'] as $s)<option value="{{ $s }}" {{ old('status',$project->status)===$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach</select>
                </div>
            </div>
            <div class="form-group"><label>Domain Context</label><textarea name="domain_context" class="form-control" rows="2">{{ old('domain_context', $project->domain_context) }}</textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"></div>
                <div class="form-group"><label>End Date</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date', $project->end_date?->format('Y-m-d')) }}"></div>
            </div>
            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Update Project
                </button>
                <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
