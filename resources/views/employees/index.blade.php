@extends('layouts.app')
@section('title', 'Employees')
@section('page-title', 'Employees')
@section('content')
<div class="page-header">
    <h1>Employees</h1>
    <div style="display:flex;gap:10px">
        <a href="{{ route('employees.import') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Import
        </a>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Employee
        </a>
    </div>
</div>
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:nowrap;width:100%">
        <input type="text" name="search" class="form-control" placeholder="Search name / email..." value="{{ request('search') }}" style="flex:1 1 180px;height:40px;padding:0 12px;font-size:13px;box-sizing:border-box">
        <select name="department_id" class="form-control" onchange="this.form.submit()" style="width:140px;flex:0 0 140px;height:40px;padding:0 28px 0 10px;font-size:13px;box-sizing:border-box;background-position:right 8px center;background-size:14px">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ Str::limit($dept->name, 22) }}</option>
            @endforeach
        </select>
        <select name="designation" class="form-control" onchange="this.form.submit()" style="width:160px;flex:0 0 160px;height:40px;padding:0 28px 0 10px;font-size:13px;box-sizing:border-box;background-position:right 8px center;background-size:14px">
            <option value="">All Designations</option>
            @foreach($designations as $d)
            <option value="{{ $d }}" {{ request('designation') === $d ? 'selected' : '' }}>{{ Str::limit($d, 26) }}</option>
            @endforeach
        </select>
        <select name="skill" class="form-control" onchange="this.form.submit()" style="width:130px;flex:0 0 130px;height:40px;padding:0 28px 0 10px;font-size:13px;box-sizing:border-box;background-position:right 8px center;background-size:14px">
            <option value="">All Skills</option>
            @foreach($allSkills as $sk)
            <option value="{{ $sk }}" {{ request('skill') === $sk ? 'selected' : '' }}>{{ $sk }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary" style="height:40px;padding:0 16px;font-size:13px;flex:0 0 auto;white-space:nowrap">Filter</button>
        @if(request()->hasAny(['search','department_id','designation','skill']))
        <a href="{{ route('employees.index') }}" class="btn btn-secondary" style="height:40px;padding:0 12px;font-size:13px;flex:0 0 auto;white-space:nowrap;color:var(--gray-500)">Clear</a>
        @endif
    </form>
</div>
<div class="card">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Designation</th><th>Skills</th><th></th></tr></thead>
        <tbody>
        @forelse($employees as $emp)
        <tr>
            <td><a href="{{ route('employees.show', $emp) }}" style="font-weight:500">{{ $emp->full_name }}</a></td>
            <td>{{ $emp->email }}</td>
            <td>{{ $emp->department?->name ?? '-' }}</td>
            <td>{{ $emp->designation ?? '-' }}</td>
            <td>
                <div class="tags">
                @if($emp->skills_from_resume && is_array($emp->skills_from_resume))
                    @foreach(array_slice($emp->skills_from_resume, 0, 3) as $skill)
                    <span class="tag">{{ $skill }}</span>
                    @endforeach
                @elseif($emp->combined_skill_profile && isset($emp->combined_skill_profile['top_skills']))
                    @foreach(array_slice($emp->combined_skill_profile['top_skills'], 0, 3) as $skill)
                    <span class="tag">{{ $skill }}</span>
                    @endforeach
                @endif
                </div>
            </td>
            <td>
                <a href="{{ route('employees.show', $emp) }}" class="btn btn-sm btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    View
                </a>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted">No employees found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $employees->withQueryString()->links() }}</div>
</div>
@endsection
