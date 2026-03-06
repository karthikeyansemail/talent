@extends('layouts.app')
@section('title', 'Add User')
@section('page-title', 'Add User')
@section('content')
<div class="page-header"><h1>Add User</h1></div>
<div class="card" style="overflow:visible">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            User Details
        </span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('settings.users.store') }}">
            @csrf
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            @php use App\Enums\RoleRegistry; @endphp
            <div class="form-group">
                <label>Roles *</label>
                <p style="font-size:12px;color:var(--gray-500);margin:0 0 8px">Select one or more roles for this user</p>
                @foreach(RoleRegistry::assignable() as $key => $meta)
                <label style="display:flex;align-items:center;gap:8px;padding:6px 0;cursor:pointer;margin:0">
                    <input type="checkbox" name="roles[]" value="{{ $key }}" {{ in_array($key, old('roles', [])) ? 'checked' : '' }}>
                    <span style="font-weight:500">{{ $meta['label'] }}</span>
                    <span style="font-size:11px;color:var(--gray-400)">— {{ $meta['description'] }}</span>
                </label>
                @endforeach
                @error('roles') <p style="color:var(--danger);font-size:12px;margin:4px 0 0">{{ $message }}</p> @enderror
            </div>
            <div class="flex gap-10">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Create User
                </button>
                <a href="{{ route('settings.users.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
