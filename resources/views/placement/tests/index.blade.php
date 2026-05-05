@extends('layouts.app')
@section('title', 'Aptitude Tests')
@section('page-title', 'Aptitude Tests')
@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1>Aptitude Tests</h1>
        <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
            AI-generated tests linked to placement drives. Mix of MCQ + descriptive (paragraph) questions; descriptive answers graded by AI for actual understanding.
        </p>
    </div>
    <a href="{{ route('placement.tests.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Generate Test
    </a>
</div>

<div class="card">
    <table>
        <thead>
            <tr><th>Test</th><th>Drive</th><th>Questions</th><th>Time</th><th>Pass</th><th>Status</th><th>Attempts</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($tests as $t)
            <tr>
                <td><a href="{{ route('placement.tests.show', $t) }}" class="name-link">{{ $t->title }}</a></td>
                <td class="text-sm text-muted">{{ $t->drive->company_name }} — {{ $t->drive->role_title }}</td>
                <td>{{ $t->questions->count() }}</td>
                <td class="text-sm">{{ $t->time_limit_minutes }} min</td>
                <td>{{ $t->passing_score_pct }}%</td>
                <td>@include('components.stage-badge', ['stage' => $t->status])</td>
                <td>{{ $t->attempts->count() }}</td>
                <td>
                    <div class="table-actions">
                        @if($t->status === 'published')
                            <a href="{{ url('placement/test/' . $t->public_token) }}" target="_blank" class="btn btn-sm btn-secondary" title="Public student URL">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                Link
                            </a>
                        @endif
                        <a href="{{ route('placement.tests.edit', $t) }}" class="btn btn-sm btn-secondary">Edit</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8"><div class="empty-state"><p>No tests yet</p><p class="empty-hint">Click "Generate Test" to create one with AI from a placement drive.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
