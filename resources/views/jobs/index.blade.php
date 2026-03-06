@extends('layouts.app')
@section('title', 'Jobs')
@section('page-title', 'Job Postings')
@section('content')
<div class="page-header">
    <h1>Job Postings</h1>
    <a href="{{ route('jobs.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Job
    </a>
</div>

<div class="filter-bar">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:nowrap;width:100%">
        <input type="text" name="search" class="form-control" placeholder="Search jobs..." value="{{ request('search') }}" style="flex:1 1 200px;height:40px;padding:0 12px;font-size:13px;box-sizing:border-box">
        <select name="status" class="form-control" onchange="this.form.submit()" style="width:130px;flex:0 0 130px;height:40px;padding:0 28px 0 10px;font-size:13px;box-sizing:border-box;background-position:right 8px center;background-size:14px">
            <option value="">All Status</option>
            @foreach(['draft','open','on_hold','closed'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <select name="department_id" class="form-control" onchange="this.form.submit()" style="width:180px;flex:0 0 180px;height:40px;padding:0 28px 0 10px;font-size:13px;box-sizing:border-box;background-position:right 8px center;background-size:14px">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
            @endforeach
        </select>
        @if(request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
        @if(request('direction'))<input type="hidden" name="direction" value="{{ request('direction') }}">@endif
        <button type="submit" class="btn btn-secondary" style="height:40px;padding:0 16px;font-size:13px;box-sizing:border-box;flex:0 0 auto;white-space:nowrap">Filter</button>
    </form>
</div>

@php
$cs = $sort ?? 'created_at';
$cd = $direction ?? 'desc';
$qs = request()->only(['search', 'status', 'department_id']);
@endphp

<div class="card">
    <table>
        <thead>
            <tr>
                <th class="sortable {{ $cs === 'title' ? 'sorted' : '' }}">
                    <a href="{{ route('jobs.index', array_merge($qs, ['sort' => 'title', 'direction' => $cs === 'title' && $cd === 'asc' ? 'desc' : 'asc'])) }}">Title @if($cs === 'title')<span class="sort-arrow">{{ $cd === 'asc' ? '▲' : '▼' }}</span>@endif</a>
                </th>
                <th>Department</th>
                <th class="sortable {{ $cs === 'employment_type' ? 'sorted' : '' }}">
                    <a href="{{ route('jobs.index', array_merge($qs, ['sort' => 'employment_type', 'direction' => $cs === 'employment_type' && $cd === 'asc' ? 'desc' : 'asc'])) }}">Type @if($cs === 'employment_type')<span class="sort-arrow">{{ $cd === 'asc' ? '▲' : '▼' }}</span>@endif</a>
                </th>
                <th class="sortable {{ $cs === 'min_experience' ? 'sorted' : '' }}">
                    <a href="{{ route('jobs.index', array_merge($qs, ['sort' => 'min_experience', 'direction' => $cs === 'min_experience' && $cd === 'asc' ? 'desc' : 'asc'])) }}">Experience @if($cs === 'min_experience')<span class="sort-arrow">{{ $cd === 'asc' ? '▲' : '▼' }}</span>@endif</a>
                </th>
                <th class="sortable {{ $cs === 'status' ? 'sorted' : '' }}">
                    <a href="{{ route('jobs.index', array_merge($qs, ['sort' => 'status', 'direction' => $cs === 'status' && $cd === 'asc' ? 'desc' : 'asc'])) }}">Status @if($cs === 'status')<span class="sort-arrow">{{ $cd === 'asc' ? '▲' : '▼' }}</span>@endif</a>
                </th>
                <th class="sortable {{ $cs === 'applications_count' ? 'sorted' : '' }}">
                    <a href="{{ route('jobs.index', array_merge($qs, ['sort' => 'applications_count', 'direction' => $cs === 'applications_count' && $cd === 'asc' ? 'desc' : 'asc'])) }}">Applications @if($cs === 'applications_count')<span class="sort-arrow">{{ $cd === 'asc' ? '▲' : '▼' }}</span>@endif</a>
                </th>
                <th class="sortable {{ $cs === 'created_at' ? 'sorted' : '' }}">
                    <a href="{{ route('jobs.index', array_merge($qs, ['sort' => 'created_at', 'direction' => $cs === 'created_at' && $cd === 'asc' ? 'desc' : 'asc'])) }}">Created @if($cs === 'created_at')<span class="sort-arrow">{{ $cd === 'asc' ? '▲' : '▼' }}</span>@endif</a>
                </th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($jobs as $job)
        <tr>
            <td><a href="{{ route('jobs.show', $job) }}" class="name-link">{{ $job->title }}</a></td>
            <td>{{ $job->department?->name ?? '-' }}</td>
            <td>{{ ucwords(str_replace('_', ' ', $job->employment_type)) }}</td>
            <td>{{ $job->min_experience }}-{{ $job->max_experience }} yrs</td>
            <td>@include('components.stage-badge', ['stage' => $job->status])</td>
            <td>{{ $job->applications_count }}</td>
            <td class="text-sm text-muted">{{ $job->created_at->format('M d, Y') }}</td>
            <td>
                <div class="table-actions">
                    <a href="{{ route('jobs.show', $job) }}" class="action-link">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        View
                    </a>
                    <a href="{{ route('jobs.edit', $job) }}" class="action-link action-link--secondary">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit
                    </a>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="text-center text-muted">No jobs found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $jobs->withQueryString()->links() }}</div>
</div>
@endsection
