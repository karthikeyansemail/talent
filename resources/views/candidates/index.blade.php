@extends('layouts.app')
@section('title', 'Candidates')
@section('page-title', 'Candidates')
@section('content')
<div class="page-header">
    <h1>Candidates</h1>
    <div class="flex gap-10">
        <a href="{{ route('candidates.bulkCreate') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
            Add Multiple
        </a>
        <a href="{{ route('candidates.create') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Candidate
        </a>
    </div>
</div>
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:nowrap;width:100%">
        <input type="text" name="search" class="form-control" placeholder="Search name / email..." value="{{ request('search') }}" style="flex:1 1 200px;height:40px;padding:0 12px;font-size:13px;box-sizing:border-box">
        <select name="job_id" class="form-control" onchange="this.form.submit()" style="width:130px;flex:0 0 130px;height:40px;padding:0 28px 0 10px;font-size:13px;box-sizing:border-box;background-position:right 8px center;background-size:14px">
            <option value="">All Jobs</option>
            @foreach($jobs as $j)
            <option value="{{ $j->id }}" {{ request('job_id') == $j->id ? 'selected' : '' }}>{{ Str::limit($j->title, 30) }}</option>
            @endforeach
        </select>
        <select name="experience" class="form-control" onchange="this.form.submit()" style="width:160px;flex:0 0 160px;height:40px;padding:0 28px 0 10px;font-size:13px;box-sizing:border-box;background-position:right 8px center;background-size:14px">
            <option value="">All Experience</option>
            @foreach(['0-1','1-2','1-3','2-3','2-4','3-5','4-6','5-8','6-10','8-12','10-15','15-20','20-50'] as $r)
            <option value="{{ $r }}" {{ request('experience') === $r ? 'selected' : '' }}>{{ $r }} yrs</option>
            @endforeach
        </select>
        <select name="title" class="form-control" onchange="this.form.submit()" style="width:130px;flex:0 0 130px;height:40px;padding:0 28px 0 10px;font-size:13px;box-sizing:border-box;background-position:right 8px center;background-size:14px">
            <option value="">All Titles</option>
            @foreach($titles as $t)
            <option value="{{ $t }}" {{ request('title') === $t ? 'selected' : '' }}>{{ Str::limit($t, 28) }}</option>
            @endforeach
        </select>
        <select name="skill" class="form-control" onchange="this.form.submit()" style="width:130px;flex:0 0 130px;height:40px;padding:0 28px 0 10px;font-size:13px;box-sizing:border-box;background-position:right 8px center;background-size:14px">
            <option value="">All Skills</option>
            @foreach($allSkills as $sk)
            <option value="{{ $sk }}" {{ request('skill') === $sk ? 'selected' : '' }}>{{ $sk }}</option>
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
$qs = request()->only(['search', 'job_id', 'experience', 'title', 'skill']);
@endphp

<div class="card">
    <table>
        <thead>
            <tr>
                <th class="sortable {{ $cs === 'first_name' ? 'sorted' : '' }}">
                    <a href="{{ route('candidates.index', array_merge($qs, ['sort' => 'first_name', 'direction' => $cs === 'first_name' && $cd === 'asc' ? 'desc' : 'asc'])) }}">Name @if($cs === 'first_name')<span class="sort-arrow">{{ $cd === 'asc' ? '▲' : '▼' }}</span>@endif</a>
                </th>
                <th class="sortable {{ $cs === 'current_title' ? 'sorted' : '' }}">
                    <a href="{{ route('candidates.index', array_merge($qs, ['sort' => 'current_title', 'direction' => $cs === 'current_title' && $cd === 'asc' ? 'desc' : 'asc'])) }}">Title @if($cs === 'current_title')<span class="sort-arrow">{{ $cd === 'asc' ? '▲' : '▼' }}</span>@endif</a>
                </th>
                <th class="sortable {{ $cs === 'experience_years' ? 'sorted' : '' }}">
                    <a href="{{ route('candidates.index', array_merge($qs, ['sort' => 'experience_years', 'direction' => $cs === 'experience_years' && $cd === 'asc' ? 'desc' : 'asc'])) }}">Exp @if($cs === 'experience_years')<span class="sort-arrow">{{ $cd === 'asc' ? '▲' : '▼' }}</span>@endif</a>
                </th>
                <th>Skills</th>
                <th>Applications</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($candidates as $c)
        <tr>
            <td>
                <a href="{{ route('candidates.show', $c) }}" style="display:block;line-height:1.3">{{ $c->full_name }}</a>
                <span style="font-size:12px;color:var(--gray-400)">{{ $c->email }}</span>
            </td>
            <td>{{ $c->current_title ?? '-' }}</td>
            <td style="white-space:nowrap">{{ $c->experience_years ?? '-' }} yrs</td>
            <td>
                @if($c->skills && count($c->skills))
                <div class="tags">
                    @foreach(array_slice($c->skills, 0, 3) as $skill)
                    <span class="tag">{{ $skill }}</span>
                    @endforeach
                    @if(count($c->skills) > 3)
                    <span class="tag" style="background:var(--gray-100);color:var(--gray-500)">+{{ count($c->skills) - 3 }}</span>
                    @endif
                </div>
                @else
                <span class="text-muted">-</span>
                @endif
            </td>
            <td>
                @if($c->applications->count())
                <div class="application-chips">
                    @foreach($c->applications as $app)
                    <a href="{{ route('applications.show', $app) }}" class="application-chip" title="{{ $app->jobPosting->title }}">
                        {{ Str::limit($app->jobPosting->title, 20) }}
                        @if($app->ai_score)
                        <span class="chip-score {{ $app->ai_score >= 70 ? 'high' : ($app->ai_score >= 40 ? 'medium' : 'low') }}">{{ number_format($app->ai_score, 0) }}</span>
                        @endif
                    </a>
                    @endforeach
                </div>
                @else
                <span class="text-muted">-</span>
                @endif
            </td>
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
        <tr><td colspan="6" class="text-center text-muted">No candidates found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $candidates->withQueryString()->links() }}</div>
</div>
@endsection
