@extends('layouts.app')
@section('title', 'Projects')
@section('page-title', 'Projects')
@section('content')
<div class="page-header">
    <h1>Projects</h1>
    <a href="{{ route('projects.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Project
    </a>
</div>
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap">
        <select name="status" class="form-control" onchange="this.form.submit()">
            <option value="">All Status</option>
            @foreach(['planning','active','completed','on_hold'] as $s)
            <option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </form>
</div>
<div class="card">
    <table>
        <thead><tr><th>Name</th><th>Complexity</th><th>Status</th><th>Skills</th><th>Matches</th><th>Dates</th><th></th></tr></thead>
        <tbody>
        @forelse($projects as $p)
        <tr>
            <td><a href="{{ route('projects.show', $p) }}" class="name-link">{{ $p->name }}</a></td>
            <td>@include('components.stage-badge', ['stage' => $p->complexity_level])</td>
            <td>@include('components.stage-badge', ['stage' => $p->status])</td>
            <td><div class="tags">@foreach(array_slice($p->required_skills ?? [], 0, 3) as $s)<span class="tag">{{ $s }}</span>@endforeach</div></td>
            <td>{{ $p->resource_matches_count ?? 0 }}</td>
            <td class="text-sm text-muted">{{ $p->start_date?->format('M d') }} - {{ $p->end_date?->format('M d, Y') }}</td>
            <td>
                <div class="table-actions">
                    <a href="{{ route('projects.show', $p) }}" class="action-link">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                        View
                    </a>
                    <a href="{{ route('projects.edit', $p) }}" class="action-link action-link--secondary">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit
                    </a>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted">No projects found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $projects->withQueryString()->links() }}</div>
</div>
@endsection
