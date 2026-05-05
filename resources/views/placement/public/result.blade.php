@extends('placement.public._layout')
@section('title', 'Result')
@section('content')

<div class="public-header">
    <h1>{{ $attempt->test->title }}</h1>
    <div class="sub">{{ $attempt->test->drive->company_name }} · {{ $attempt->student_name }}</div>
</div>

<div class="card" id="resultCard">
    <div class="card-body" style="padding:40px 30px;text-align:center" id="resultBody">
        @if($attempt->grading_status === 'grading' || $attempt->grading_status === 'pending')
            <div style="display:inline-flex;width:56px;height:56px;border-radius:50%;background:var(--primary-100);align-items:center;justify-content:center;margin-bottom:18px">
                <div style="width:36px;height:36px;border:3px solid var(--primary-200);border-top-color:var(--primary);border-radius:50%;animation:spin 0.9s linear infinite"></div>
            </div>
            <h3 style="margin:0 0 8px;font-size:18px;color:var(--text-strong)">Grading your answers…</h3>
            <p style="margin:0;color:var(--text-muted);font-size:13px;line-height:1.5">
                MCQ answers are scored instantly. Descriptive answers are evaluated by AI for understanding —
                this usually takes 30–90 seconds depending on how many you wrote.
            </p>
            <style>@keyframes spin { to { transform: rotate(360deg); } }</style>
        @else
            @include('placement.public._result_body', ['attempt' => $attempt])
        @endif
    </div>
</div>

<div style="margin-top:18px;padding:14px 18px;background:var(--bg-muted);border-radius:8px;text-align:center;font-size:13px;color:var(--text-muted)">
    Your detailed scorecard has been shared with the placement office. They will reach out about the next round.
</div>

@if($attempt->grading_status !== 'complete')
<script>
(function() {
    var statusUrl = "{{ route('placement.public.resultStatus', $attempt) }}";
    var poll = setInterval(function() {
        fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.status === 'complete') {
                clearInterval(poll);
                window.location.reload();
            }
        })
        .catch(function() { /* ignore */ });
    }, 3000);
})();
</script>
@endif
@endsection
