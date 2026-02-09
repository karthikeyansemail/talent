@extends('layouts.app')
@section('title', 'Employees')
@section('page-title', 'Employees')
@section('content')
<div class="page-header">
    <h1>Employees</h1>
    <a href="{{ route('employees.create') }}" class="btn btn-primary">+ New Employee</a>
</div>
<div class="filter-bar">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap">
        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
        <select name="department_id" class="form-control" onchange="this.form.submit()">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary">Filter</button>
    </form>
</div>
<div class="card">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Designation</th><th>Skills</th><th></th></tr></thead>
        <tbody>
        @forelse($employees as $emp)
        <tr>
            <td><a href="{{ route('employees.show', $emp) }}">{{ $emp->full_name }}</a></td>
            <td>{{ $emp->email }}</td>
            <td>{{ $emp->department?->name ?? '-' }}</td>
            <td>{{ $emp->designation ?? '-' }}</td>
            <td>
                <div class="tags">
                @if($emp->combined_skill_profile && isset($emp->combined_skill_profile['top_skills']))
                    @foreach(array_slice($emp->combined_skill_profile['top_skills'], 0, 3) as $skill)
                    <span class="tag">{{ $skill }}</span>
                    @endforeach
                @endif
                </div>
            </td>
            <td><a href="{{ route('employees.show', $emp) }}" class="btn btn-sm btn-secondary">View</a></td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted">No employees found.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="pagination">{{ $employees->withQueryString()->links() }}</div>
</div>
@endsection
