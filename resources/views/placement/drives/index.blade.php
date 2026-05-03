@extends('layouts.app')
@section('title', 'Placement Drives')
@section('page-title', 'Placement Drives')
@section('content')
<div class="page-header">
    <h1>Placement Drives</h1>
    <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
        Manage company recruitment drives at your institution. Create a drive from a hiring document and the system generates aptitude tests automatically.
    </p>
</div>

<div class="card">
    <div class="card-body" style="text-align:center;padding:60px 30px">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-subtle)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 16px;display:block"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 7V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v3"/></svg>
        <h3 style="font-size:16px;color:var(--text-strong);margin:0 0 8px">Drive management coming next</h3>
        <p style="color:var(--text-muted);font-size:13px;margin:0;max-width:480px;margin-left:auto;margin-right:auto;line-height:1.5">
            The placement drive scaffolding is in place ({{ $drives->count() }} drive{{ $drives->count() === 1 ? '' : 's' }} in the database). Full create/edit UI ships in the next commit.
        </p>
    </div>
</div>
@endsection
