@extends('layouts.app')
@section('title', 'Students')
@section('page-title', 'Students')
@section('content')
<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start">
    <div>
        <h1>Students</h1>
        <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
            {{ $students->total() }} student{{ $students->total() === 1 ? '' : 's' }} matching current filters · {{ $departments->count() }} department{{ $departments->count() === 1 ? '' : 's' }} configured.
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

{{-- All filters in a single auto-submitting form so chips + dropdowns work together --}}
<form method="GET" action="{{ route('placement.students.index') }}" id="studentFilters">

    {{-- Multi-select department chips --}}
    @if($departments->count() > 0)
    <div class="card" style="margin-bottom:16px">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
            <span class="card-header-icon">Filter by Department</span>
            @if($selectedDepts->isNotEmpty())
                <span class="text-sm text-muted">{{ $selectedDepts->count() }} selected · <a href="{{ route('placement.students.index', request()->except(['departments', 'department'])) }}">clear</a></span>
            @endif
        </div>
        <div class="card-body" style="padding:14px 18px">
            <div style="display:flex;flex-wrap:wrap;gap:8px">
                @foreach($departments as $d)
                    @php
                        $isSelected = $selectedDepts->contains($d->id);
                        $count = $deptCounts[$d->id] ?? 0;
                    @endphp
                    <label class="dept-chip {{ $isSelected ? 'selected' : '' }}"
                        style="display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border:1px solid {{ $isSelected ? 'var(--primary)' : 'var(--border)' }};border-radius:18px;cursor:pointer;background:{{ $isSelected ? 'var(--primary-50)' : 'var(--bg-card)' }};font-size:13px;transition:all .12s;user-select:none">
                        <input type="checkbox" name="departments[]" value="{{ $d->id }}"
                            {{ $isSelected ? 'checked' : '' }}
                            onchange="document.getElementById('studentFilters').submit()"
                            style="display:none">
                        <span style="font-weight:{{ $isSelected ? '600' : '500' }};color:{{ $isSelected ? 'var(--primary)' : 'var(--text)' }}">{{ $d->name }}</span>
                        <span style="background:{{ $isSelected ? 'var(--primary)' : 'var(--bg-muted)' }};color:{{ $isSelected ? '#fff' : 'var(--text-muted)' }};padding:1px 7px;border-radius:10px;font-size:11px;font-weight:600">{{ $count }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Other filters --}}
    <div class="card" style="margin-bottom:16px">
        <div class="card-body" style="padding:14px 18px">
            <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
                <div class="form-group" style="margin:0;flex:1;min-width:200px">
                    <label>Search</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Name, email, enrollment #">
                </div>
                <div class="form-group" style="margin:0;min-width:240px">
                    <label>Placement Drive</label>
                    <select name="drive" class="form-control" onchange="document.getElementById('studentFilters').submit()">
                        <option value="">All drives</option>
                        @foreach($drives as $dr)
                            <option value="{{ $dr->id }}" {{ request('drive') == $dr->id ? 'selected' : '' }}>{{ $dr->company_name }} — {{ $dr->role_title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;min-width:150px">
                    <label>Course</label>
                    <select name="course" class="form-control" onchange="document.getElementById('studentFilters').submit()">
                        <option value="">All</option>
                        @foreach($courses as $c)<option value="{{ $c }}" {{ request('course') === $c ? 'selected' : '' }}>{{ $c }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group" style="margin:0;min-width:120px">
                    <label>Batch</label>
                    <select name="batch" class="form-control" onchange="document.getElementById('studentFilters').submit()">
                        <option value="">All</option>
                        @foreach($batches as $b)<option value="{{ $b }}" {{ request('batch') == $b ? 'selected' : '' }}>{{ $b }}</option>@endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary">Apply</button>
                @if(request('q') || request('course') || request('batch') || request('drive') || $selectedDepts->isNotEmpty())
                    <a href="{{ route('placement.students.index') }}" class="btn btn-secondary">Clear all</a>
                @endif
            </div>
            @if(request('drive'))
                @php $selDrive = $drives->firstWhere('id', (int) request('drive')); @endphp
                @if($selDrive)
                    <div style="margin-top:10px;padding:8px 12px;background:var(--primary-50);border-radius:6px;font-size:12.5px;color:var(--text)">
                        Showing students enrolled in <strong>{{ $selDrive->company_name }} — {{ $selDrive->role_title }}</strong> drive.
                    </div>
                @endif
            @endif
        </div>
    </div>
</form>

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
            <tr><td colspan="7"><div class="empty-state"><p>No students match these filters</p><p class="empty-hint">Try clearing some filters above.</p></div></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($students->hasPages())
    <div style="margin-top:16px">{{ $students->links() }}</div>
@endif
@endsection
