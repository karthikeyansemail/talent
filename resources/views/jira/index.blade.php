@extends('layouts.app')
@section('title', 'Jira Connections')
@section('content')
<div class="page-header">
    <h1>Jira Connections</h1>
    <a href="{{ route('jira-connections.create') }}" class="btn btn-primary">+ Add Connection</a>
</div>
<div class="card">
    <table>
        <thead><tr><th>Base URL</th><th>Email</th><th>Status</th><th>Last Synced</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($connections as $conn)
        <tr>
            <td>{{ $conn->jira_base_url }}</td>
            <td>{{ $conn->jira_email }}</td>
            <td>@include('components.stage-badge', ['stage' => $conn->is_active ? 'active' : 'closed'])</td>
            <td class="text-sm text-muted">{{ $conn->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
            <td class="flex gap-10">
                <form method="POST" action="{{ route('jira-connections.test', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-secondary">Test</button></form>
                <form method="POST" action="{{ route('jira-connections.sync', $conn) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sm btn-primary">Sync</button></form>
                <form method="POST" action="{{ route('jira-connections.destroy', $conn) }}" style="display:inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove?')">Remove</button></form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted">No Jira connections.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
