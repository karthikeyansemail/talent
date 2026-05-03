@extends('layouts.app')
@section('title', 'Aptitude Tests')
@section('page-title', 'Aptitude Tests')
@section('content')
<div class="page-header">
    <h1>Aptitude Tests</h1>
    <p style="margin:6px 0 0;color:var(--text-muted);font-size:13px">
        AI-generated tests linked to placement drives. Mix of MCQ + descriptive questions; descriptive answers are graded by AI for understanding, not just keyword matches.
    </p>
</div>
<div class="card">
    <div class="card-body" style="text-align:center;padding:60px 30px">
        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--text-subtle)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 16px;display:block"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        <h3 style="font-size:16px;color:var(--text-strong);margin:0 0 8px">{{ $tests->count() }} test{{ $tests->count() === 1 ? '' : 's' }} so far</h3>
        <p style="color:var(--text-muted);font-size:13px;margin:0;max-width:480px;margin-left:auto;margin-right:auto;line-height:1.5">
            Test generation UI + AI question creator + officer review editor ship in Commit C.
        </p>
    </div>
</div>
@endsection
