@extends('layouts.app')
@section('title', 'Students')
@section('page-title', 'Students')
@section('content')
<div class="page-header">
    <h1>Students</h1>
    <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
        {{ $students->total() }} student{{ $students->total() === 1 ? '' : 's' }} on file. CSV bulk upload + onboarding ships in the next commit.
    </p>
</div>

<div class="card">
    <table>
        <thead>
            <tr><th>Name</th><th>Email</th><th>Enrollment #</th><th>Course</th><th>Batch</th></tr>
        </thead>
        <tbody>
            @forelse($students as $s)
            <tr>
                <td>{{ $s->first_name }} {{ $s->last_name }}</td>
                <td class="text-sm">{{ $s->email }}</td>
                <td class="text-sm text-muted">{{ $s->enrollment_number ?? '—' }}</td>
                <td class="text-sm text-muted">{{ $s->course ?? '—' }}</td>
                <td class="text-sm text-muted">{{ $s->batch_year ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5"><div class="empty-state"><p>No students yet</p><p class="empty-hint">Bulk upload via CSV will be available soon.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
