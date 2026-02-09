@extends('layouts.app')
@section('title', 'Jobs')
@section('page-title', 'Job Postings')
@section('content')
<div class="page-header">
    <h1>Job Postings</h1>
    <a href="{{ route('jobs.create') }}" class="btn btn-primary">+ New Job</a>
</div>

<div class="filter-bar">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap">
        <input type="text" name="search" class="form-control" placeholder="Search jobs..." value="{{ request('search') }}">
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All Status</option>
            @foreach(['draft','open','on_hold','closed'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <select name="department_id" class="form-control" onchange="this.form.submit()">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</div>

<div class="card">
    <table>
        <thead><tr><th>Title</th><th>Department</th><th>Type</th><th>Experience</th><th>Status</th><th>Applications</th><th>Created</th><th></th></tr></thead>
        <tbody>
        @forelse($jobs as $job)
        <tr>
            <td><a href="{{ route('jobs.show', $job) }}">{{ $job->title }}</a></td>
            <td>{{ $job->department?->name ?? '-' }}</td>
            <td>{{ ucwords(str_replace('_', ' ', $job->employment_type)) }}</td>
            <td>{{ $job->min_experience }}-{{ $job->max_experience }} yrs</td>
            <td>@include('components.stage-badge', ['stage' => $job->status])</td>
            <td>{{ $job->applications_count ?? $job->applications->count() }}</td>
            <td class="text-sm text-muted">{{ $job->created_at->format('M d, Y') }}</td>
            <td><a href="{{ route('jobs.edit', $job) }}" class="btn btn-sm btn-secondary">Edit</a></td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted">No jobs found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $jobs->withQueryString()->links() }}</div>
</div>
@endsection
