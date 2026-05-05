@extends('placement.public._layout')
@section('title', 'Taking: ' . $test->title)
@section('content')

<form method="POST" action="{{ route('placement.public.submit', $attempt) }}" id="testForm">
    @csrf

    {{-- Sticky timer + progress --}}
    <div class="timer-bar">
        <div>
            <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Time Remaining</div>
            <div class="timer-clock" id="timerClock">--:--</div>
        </div>
        <div style="text-align:right">
            <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;font-weight:600">Progress</div>
            <div style="margin-top:4px"><span id="answeredCount">0</span> / {{ $test->questions->count() }} answered</div>
            <div id="autosaveStatus" style="font-size:11px;color:var(--text-subtle);margin-top:2px">All answers auto-saved</div>
        </div>
    </div>

    {{-- Test header --}}
    <div class="public-header">
        <h1>{{ $test->title }}</h1>
        <div class="sub">{{ $test->drive->company_name }} · {{ $attempt->student_name }} · {{ $attempt->student_email }}</div>
    </div>

    {{-- Questions --}}
    @foreach($test->questions as $i => $q)
        @php
            $existing = $existingAnswers->get($q->id);
            $selected = $existing?->selected_option;
            $textVal  = $existing?->answer_text;
            $isAnswered = $q->type === 'mcq' ? !is_null($selected) : !empty($textVal);
        @endphp
        <div class="test-question {{ $isAnswered ? 'answered' : 'unanswered' }}" data-question-id="{{ $q->id }}" data-question-type="{{ $q->type }}">
            <div>
                <span class="q-num">Q{{ $i + 1 }}</span>
                <span class="q-meta">
                    {{ strtoupper($q->type) }}
                    @if($q->topic) · {{ $q->topic }} @endif
                    · {{ $q->marks }} mark{{ $q->marks === 1 ? '' : 's' }}
                </span>
            </div>

            <div style="font-size:15px;line-height:1.55;margin-top:10px;color:var(--text)">{{ $q->question_text }}</div>

            @if($q->context)
                <div class="q-context">{{ $q->context }}</div>
            @endif

            @if($q->type === 'mcq')
                <div style="margin-top:14px">
                    @foreach(($q->options ?? []) as $idx => $opt)
                        <label class="mcq-option {{ (string) $idx === (string) $selected ? 'checked' : '' }}">
                            <input type="radio"
                                   name="answers[{{ $q->id }}][selected_option]"
                                   value="{{ $idx }}"
                                   {{ (string) $idx === (string) $selected ? 'checked' : '' }}
                                   data-q-id="{{ $q->id }}">
                            <span class="opt-letter">{{ chr(65 + $idx) }}.</span>
                            <span style="flex:1">{{ $opt }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <div style="margin-top:14px">
                    <textarea
                        name="answers[{{ $q->id }}][answer_text]"
                        class="desc-textarea"
                        data-q-id="{{ $q->id }}"
                        placeholder="Write your answer here. Aim for {{ $q->expected_word_count ?? 100 }} words. Explain your reasoning — AI grades for understanding, not keyword match."
                    >{{ $textVal }}</textarea>
                    <div style="font-size:11px;color:var(--text-subtle);margin-top:4px">
                        <span class="word-count" data-q-id="{{ $q->id }}">{{ $textVal ? str_word_count($textVal) : 0 }}</span> words ·
                        target ~{{ $q->expected_word_count ?? 100 }}
                    </div>
                </div>
            @endif
        </div>
    @endforeach

    <div style="display:flex;justify-content:space-between;align-items:center;padding:20px 0;background:var(--bg-card);border-radius:10px;border:1px solid var(--border);padding:20px">
        <div style="font-size:13px;color:var(--text-muted)">
            Click submit when you're done. You can also wait — the test will auto-submit when time runs out.
        </div>
        <button type="submit" class="btn btn-primary" id="submitBtn" style="font-size:15px;padding:12px 28px">
            Submit Test
        </button>
    </div>
</form>

<script>
(function() {
    var attemptId = {{ $attempt->id }};
    var saveUrl = "{{ route('placement.public.save', $attempt) }}";
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var secondsLeft = {{ $secondsLeft }};
    var clockEl = document.getElementById('timerClock');
    var autosaveEl = document.getElementById('autosaveStatus');
    var answeredCountEl = document.getElementById('answeredCount');
    var form = document.getElementById('testForm');
    var submitBtn = document.getElementById('submitBtn');
    var hasSubmitted = false;

    // ── Timer ─────────────────────────────────────
    function fmtTime(s) {
        var m = Math.floor(s / 60);
        var sec = s % 60;
        return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
    }
    function tick() {
        if (secondsLeft <= 0) {
            clockEl.textContent = '00:00';
            if (!hasSubmitted) {
                hasSubmitted = true;
                alert('Time is up! Your test will be submitted automatically.');
                form.submit();
            }
            return;
        }
        clockEl.textContent = fmtTime(secondsLeft);
        clockEl.className = 'timer-clock';
        if (secondsLeft <= 60) clockEl.className = 'timer-clock danger';
        else if (secondsLeft <= 300) clockEl.className = 'timer-clock warning';
        secondsLeft--;
        setTimeout(tick, 1000);
    }
    tick();

    // ── Autosave ───────────────────────────────────
    var saveTimers = {};
    function autosave(qid, payload) {
        if (saveTimers[qid]) clearTimeout(saveTimers[qid]);
        saveTimers[qid] = setTimeout(function() {
            autosaveEl.textContent = 'Saving...';
            fetch(saveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(Object.assign({ test_question_id: qid }, payload))
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                autosaveEl.textContent = d.ok ? 'All answers auto-saved' : 'Save failed — please retry';
                updateAnsweredCount();
            })
            .catch(function() { autosaveEl.textContent = 'Save failed — check connection'; });
        }, 600);  // debounce 600ms
    }

    // MCQ change handler
    document.querySelectorAll('input[type="radio"][data-q-id]').forEach(function(input) {
        input.addEventListener('change', function() {
            var qid = parseInt(input.getAttribute('data-q-id'), 10);
            // Visual update on the option labels
            input.closest('.test-question').querySelectorAll('.mcq-option').forEach(function(o) {
                o.classList.toggle('checked', o.querySelector('input[type="radio"]').checked);
            });
            input.closest('.test-question').classList.remove('unanswered');
            input.closest('.test-question').classList.add('answered');
            autosave(qid, { selected_option: parseInt(input.value, 10) });
        });
    });

    // Descriptive textarea handler
    document.querySelectorAll('textarea[data-q-id]').forEach(function(ta) {
        var wcEl = document.querySelector('.word-count[data-q-id="' + ta.getAttribute('data-q-id') + '"]');
        ta.addEventListener('input', function() {
            var qid = parseInt(ta.getAttribute('data-q-id'), 10);
            var text = ta.value.trim();
            if (wcEl) wcEl.textContent = text ? text.split(/\s+/).length : 0;
            var qBlock = ta.closest('.test-question');
            if (text.length > 0) {
                qBlock.classList.remove('unanswered');
                qBlock.classList.add('answered');
            } else {
                qBlock.classList.remove('answered');
                qBlock.classList.add('unanswered');
            }
            autosave(qid, { answer_text: text });
        });
    });

    function updateAnsweredCount() {
        var count = document.querySelectorAll('.test-question.answered').length;
        answeredCountEl.textContent = count;
    }
    updateAnsweredCount();

    // Confirm submit
    form.addEventListener('submit', function(ev) {
        if (hasSubmitted) return; // already auto-submitted by timer
        var unanswered = document.querySelectorAll('.test-question.unanswered').length;
        if (unanswered > 0) {
            if (!confirm(unanswered + ' question(s) are unanswered. Submit anyway?')) {
                ev.preventDefault();
                return;
            }
        }
        hasSubmitted = true;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';
    });

    // Warn before navigating away
    window.addEventListener('beforeunload', function(ev) {
        if (!hasSubmitted) {
            ev.preventDefault();
            ev.returnValue = '';
        }
    });
})();
</script>
@endsection
