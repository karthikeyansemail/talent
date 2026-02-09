@extends('layouts.app')
@section('title', 'Manage Users')
@section('content')
<div class="page-header">
    <h1>Users</h1>
    <a href="{{ route('settings.users.create') }}" class="btn btn-primary">+ Add User</a>
</div>
<div class="card">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($users as $user)
        <tr>
            <td>{{ $user->name }}</td>
            <td>{{ $user->email }}</td>
            <td><span class="badge badge-blue">{{ ucwords(str_replace('_',' ',$user->role)) }}</span></td>
            <td>@if($user->is_active)<span class="badge badge-green">Active</span>@else<span class="badge badge-red">Inactive</span>@endif</td>
            <td class="flex gap-10">
                <a href="{{ route('settings.users.edit', $user) }}" class="btn btn-sm btn-secondary">Edit</a>
                <form method="POST" action="{{ route('settings.users.toggleActive', $user) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-warning' : 'btn-success' }}">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button></form>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div class="pagination">{{ $users->links() }}</div>
</div>
@endsection
