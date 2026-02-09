@extends('layouts.app')
@section('title', 'Candidates')
@section('page-title', 'Candidates')
@section('content')
<div class="page-header">
    <h1>Candidates</h1>
    <a href="{{ route('candidates.create') }}" class="btn btn-primary">+ New Candidate</a>
</div>
<div class="filter-bar">
    <form method="GET"><input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}"><button type="submit" class="btn btn-secondary">Search</button></form>
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
            <td><a href="{{ route('candidates.edit', $c) }}" class="btn btn-sm btn-secondary">Edit</a></td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted">No candidates found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $candidates->withQueryString()->links() }}</div>
</div>
@endsection
