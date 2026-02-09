@extends('layouts.app')
@section('title', 'Edit Project')
@section('content')
<div class="page-header"><h1>Edit: {{ $project->name }}</h1></div>
<div class="card">
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
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
