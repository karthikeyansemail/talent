@extends('layouts.app')
@section('title', 'Organization Settings')
@section('content')
<h1 class="mb-3">Organization Settings</h1>
<div class="card">
    <form method="POST" action="{{ route('settings.organization.update') }}">
        @csrf @method('PUT')
        <div class="form-group"><label>Organization Name</label><input type="text" name="name" class="form-control" value="{{ old('name', $organization->name) }}" required></div>
        <div class="form-group"><label>Domain</label><input type="text" name="domain" class="form-control" value="{{ old('domain', $organization->domain) }}" placeholder="example.com"></div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>
@endsection
