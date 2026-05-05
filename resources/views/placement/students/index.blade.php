@extends('layouts.app')
@section('title', 'Students')
@section('page-title', 'Students')
@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1>Students</h1>
        <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
            {{ $students->total() }} student{{ $students->total() === 1 ? '' : 's' }} across {{ $departments->count() }} department{{ $departments->count() === 1 ? '' : 's' }}.
        </p>
    </div>
    <div class="flex gap-10">
        <a href="{{ route('placement.students.bulkUpload') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Bulk Upload CSV
        </a>
        <a href="{{ route('placement.students.create') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add Student
        </a>
    </div>
</div>

{{-- Department-grouped summary panel --}}
@if($departments->count() > 0)
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><span class="card-header-icon">By Department</span></div>
    <div class="card-body" style="padding:14px 18px">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px">
            @foreach($departments as $d)
                @php $count = $deptCounts[$d->id] ?? 0; @endphp
                <a href="{{ route('placement.students.index', ['department' => $d->id]) }}"
                   style="display:flex;justify-content:space-between;align-items:center;padding:10px 12px;border:1px solid {{ request('department') == $d->id ? 'var(--primary)' : 'var(--border)' }};border-radius:8px;text-decoration:none;background:{{ request('department') == $d->id ? 'var(--primary-50)' : 'var(--bg-card)' }};transition:border-color .12s">
                    <span style="font-size:13px;font-weight:500;color:var(--text);line-height:1.3">{{ $d->name }}</span>
                    <span style="background:var(--bg-muted);color:var(--text-strong);font-weight:600;font-size:12px;padding:2px 8px;border-radius:10px">{{ $count }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Filters --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:14px 18px">
        <form method="GET" action="{{ route('placement.students.index') }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
            <div class="form-group" style="margin:0;flex:1;min-width:200px">
                <label>Search</label>
                <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Name, email, enrollment #">
            </div>
            <div class="form-group" style="margin:0;min-width:200px">
                <label>Department</label>
                <select name="department" class="form-control">
                    <option value="">All</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}" {{ request('department') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;min-width:150px">
                <label>Course</label>
                <select name="course" class="form-control">
                    <option value="">All</option>
                    @foreach($courses as $c)<option value="{{ $c }}" {{ request('course') === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach
                </select>
            </div>
            <div class="form-group" style="margin:0;min-width:120px">
                <label>Batch</label>
                <select name="batch" class="form-control">
                    <option value="">All</option>
                    @foreach($batches as $b)<option value="{{ $b }}" {{ request('batch') == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filter</button>
            @if(request('q') || request('course') || request('batch') || request('department'))
                <a href="{{ route('placement.students.index') }}" class="btn btn-secondary">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <table>
        <thead>
            <tr><th>Name</th><th>Email</th><th>Enrollment #</th><th>Department</th><th>Course / Batch</th><th>Skills</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($students as $s)
            <tr>
                <td><strong>{{ $s->first_name }} {{ $s->last_name }}</strong></td>
                <td class="text-sm">{{ $s->email }}</td>
                <td class="text-sm text-muted">{{ $s->enrollment_number ?? '—' }}</td>
                <td class="text-sm">{{ $s->department?->name ?? '—' }}</td>
                <td class="text-sm text-muted">{{ $s->course ?? '—' }}{{ $s->batch_year ? ' · ' . $s->batch_year : '' }}</td>
                <td>
                    @foreach(array_slice($s->skills ?? [], 0, 3) as $sk)<span class="tag" style="font-size:11px">{{ $sk }}</span>@endforeach
                    @if(count($s->skills ?? []) > 3)<span class="text-muted text-sm">+{{ count($s->skills) - 3 }}</span>@endif
                </td>
                <td>
                    <form method="POST" action="{{ route('placement.students.destroy', $s) }}" style="margin:0">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this student?')">Remove</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7"><div class="empty-state"><p>No students found</p><p class="empty-hint">Add a student or bulk upload via CSV.</p></div></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($students->hasPages())
    <div style="margin-top:16px">{{ $students->links() }}</div>
@endif
@endsection
