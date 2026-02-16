@extends('layouts.app')
@section('title', 'Candidates')
@section('page-title', 'Candidates')
@section('content')
<div class="page-header">
    <h1>Candidates</h1>
    <div class="flex gap-10">
        <a href="{{ route('candidates.bulkCreate') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
            Add Multiple Candidates
        </a>
        <a href="{{ route('candidates.create') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Candidate
        </a>
    </div>
</div>
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap">
        <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
        <button type="submit" class="btn btn-secondary">Search</button>
    </form>
</div>
<div class="card">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Current Title</th><th>Experience</th><th>Source</th><th>Resumes</th><th></th></tr></thead>
        <tbody>
        @forelse($candidates as $c)
        <tr>
            <td><a href="{{ route('candidates.show', $c) }}">{{ $c->full_name }}</a></td>
            <td>{{ $c->email }}</td>
            <td>{{ $c->current_title ?? '-' }}</td>
            <td>{{ $c->experience_years ?? '-' }} yrs</td>
            <td><span class="badge badge-gray">{{ ucfirst($c->source) }}</span></td>
            <td>{{ $c->resumes->count() }}</td>
            <td>
                <div class="table-actions">
                    <a href="{{ route('candidates.show', $c) }}" class="btn btn-sm btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        View
                    </a>
                    <a href="{{ route('candidates.edit', $c) }}" class="btn btn-sm btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit
                    </a>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted">No candidates found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $candidates->withQueryString()->links() }}</div>
</div>
@endsection
