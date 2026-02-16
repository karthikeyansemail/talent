@extends('layouts.app')
@section('title', 'Jira Connections')
@section('page-title', 'Jira Connections')
@section('content')
<div class="page-header">
    <h1>Jira Connections</h1>
    <a href="{{ route('jira-connections.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Connection
    </a>
</div>
<div class="card">
    <table>
        <thead><tr><th>Base URL</th><th>Email</th><th>Status</th><th>Last Synced</th><th></th></tr></thead>
        <tbody>
        @forelse($connections as $conn)
        <tr>
            <td>{{ $conn->jira_base_url }}</td>
            <td>{{ $conn->jira_email }}</td>
            <td>@include('components.stage-badge', ['stage' => $conn->is_active ? 'active' : 'closed'])</td>
            <td class="text-sm text-muted">{{ $conn->last_synced_at?->diffForHumans() ?? 'Never' }}</td>
            <td>
                <div class="table-actions">
                    <form method="POST" action="{{ route('jira-connections.test', $conn) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            Test
                        </button>
                    </form>
                    <form method="POST" action="{{ route('jira-connections.sync', $conn) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                            Sync
                        </button>
                    </form>
                    <form method="POST" action="{{ route('jira-connections.destroy', $conn) }}" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this connection?')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            Remove
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5">
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    <p>No Jira connections</p>
                    <p class="empty-hint">Add a connection to start syncing employee tasks</p>
                </div>
            </td>
        </tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
