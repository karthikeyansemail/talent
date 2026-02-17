@extends('layouts.app')
@section('title', 'Departments')
@section('page-title', 'Departments')
@section('content')
<div class="page-header">
    <h1>Departments</h1>
    <button onclick="openModal('addDepartmentModal')" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Department
    </button>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Job Postings</th>
                <th>Employees</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($departments as $dept)
        <tr>
            <td><strong>{{ $dept->name }}</strong></td>
            <td class="text-muted">{{ $dept->description ?? '-' }}</td>
            <td>{{ $dept->job_postings_count }}</td>
            <td>{{ $dept->employees_count }}</td>
            <td>
                <div class="table-actions">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="openEditDepartment({{ $dept->id }}, '{{ addslashes($dept->name) }}', '{{ addslashes($dept->description ?? '') }}')">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit
                    </button>
                    @if($dept->job_postings_count == 0 && $dept->employees_count == 0)
                    <form method="POST" action="{{ route('settings.departments.destroy', $dept) }}" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete department &quot;{{ $dept->name }}&quot;?')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Delete
                        </button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted">No departments yet. Add your first department above.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- Add Department Modal --}}
<div class="modal-overlay" id="addDepartmentModal">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">Add Department</div>
        <form method="POST" action="{{ route('settings.departments.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Engineering, Marketing...">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control" placeholder="Short description (optional)">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('addDepartmentModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Department</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Department Modal --}}
<div class="modal-overlay" id="editDepartmentModal">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">Edit Department</div>
        <form method="POST" id="editDepartmentForm">
            @csrf @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" id="editDeptName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" id="editDeptDescription" class="form-control" placeholder="Short description (optional)">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('editDepartmentModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditDepartment(id, name, description) {
    document.getElementById('editDepartmentForm').action = '{{ url("settings/departments") }}/' + id;
    document.getElementById('editDeptName').value = name;
    document.getElementById('editDeptDescription').value = description;
    openModal('editDepartmentModal');
}
</script>
@endsection
