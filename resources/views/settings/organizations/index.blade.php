@extends('layouts.app')
@section('title', 'All Workspaces')
@section('page-title', 'All Workspaces')
@section('content')
<div class="page-header">
    <h1>All Workspaces</h1>
    <a href="{{ route('settings.organizations.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Workspace
    </a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Workspace</th>
                <th>Domain</th>
                <th>Users</th>
                <th>Status</th>
                <th>Premium</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($organizations as $org)
        <tr>
            <td>
                <strong>{{ $org->name }}</strong>
                <br><span style="font-size:12px;color:var(--gray-400)">{{ $org->slug }}</span>
            </td>
            <td>{{ $org->domain ?? '-' }}</td>
            <td>{{ $org->users_count }}</td>
            <td>
                @if($org->is_active)
                <span class="badge badge-green">Active</span>
                @else
                <span class="badge badge-red">Inactive</span>
                @endif
            </td>
            <td>
                @if($org->is_premium)
                <span class="badge badge-purple">Premium</span>
                @else
                <span class="badge badge-gray">Free</span>
                @endif
            </td>
            <td>
                <div class="table-actions">
                    <a href="{{ route('settings.organizations.edit', $org) }}" class="btn btn-sm btn-secondary">Edit</a>
                    <form action="{{ route('org.switch') }}" method="POST" style="display:inline;margin:0">
                        @csrf
                        <input type="hidden" name="organization_id" value="{{ $org->id }}">
                        <button type="submit" class="btn btn-sm btn-secondary">Switch To</button>
                    </form>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
