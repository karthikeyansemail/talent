@extends('layouts.app')
@section('title', 'Placement Drives')
@section('page-title', 'Placement Drives')
@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1>Placement Drives</h1>
        <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
            Companies recruiting at your institution. Each drive can have an aptitude test, interview round, and cleared-students list.
        </p>
    </div>
    <a href="{{ route('placement.drives.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Drive
    </a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Company</th>
                <th>Role</th>
                <th>Drive Date</th>
                <th>Package</th>
                <th>Status</th>
                <th>Attempts</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($drives as $d)
            <tr>
                <td><strong>{{ $d->company_name }}</strong></td>
                <td class="text-sm">{{ $d->role_title }}</td>
                <td class="text-sm text-muted">{{ $d->drive_date?->format('d M Y') ?? '—' }}</td>
                <td class="text-sm">{{ $d->package_lpa ? '₹' . $d->package_lpa . ' LPA' : '—' }}</td>
                <td>@include('components.stage-badge', ['stage' => $d->status])</td>
                <td>{{ $d->attempts_count ?? 0 }}</td>
                <td>
                    <div class="table-actions">
                        <a href="{{ route('placement.drives.show', $d) }}" class="btn btn-sm btn-secondary">View</a>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="7"><div class="empty-state"><p>No drives yet</p><p class="empty-hint">Click "New Drive" to add your first placement drive.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
