@extends('layouts.app')
@section('title', 'Projects')
@section('content')
<div class="page-header">
    <h1>Projects</h1>
    <a href="{{ route('projects.create') }}" class="btn btn-primary">+ New Project</a>
</div>
<div class="filter-bar">
    <form method="GET"><select name="status" class="form-control" onchange="this.form.submit()"><option value="">All Status</option>@foreach(['planning','active','completed','on_hold'] as $s)<option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach</select></form>
</div>
<div class="card">
    <table>
        <thead><tr><th>Name</th><th>Complexity</th><th>Status</th><th>Skills</th><th>Matches</th><th>Dates</th><th></th></tr></thead>
        <tbody>
        @forelse($projects as $p)
        <tr>
            <td><a href="{{ route('projects.show', $p) }}">{{ $p->name }}</a></td>
            <td>@include('components.stage-badge', ['stage' => $p->complexity_level])</td>
            <td>@include('components.stage-badge', ['stage' => $p->status])</td>
            <td><div class="tags">@foreach(array_slice($p->required_skills ?? [], 0, 3) as $s)<span class="tag">{{ $s }}</span>@endforeach</div></td>
            <td>{{ $p->resource_matches_count ?? 0 }}</td>
            <td class="text-sm text-muted">{{ $p->start_date?->format('M d') }} - {{ $p->end_date?->format('M d, Y') }}</td>
            <td><a href="{{ route('projects.edit', $p) }}" class="btn btn-sm btn-secondary">Edit</a></td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted">No projects found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $projects->withQueryString()->links() }}</div>
</div>
@endsection
