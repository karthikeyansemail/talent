@extends('layouts.app')
@section('title', 'Assign Interview')

@section('content')
<div class="page-header" style="display:flex; align-items:center; gap:12px; margin-bottom:24px;">
    <a href="{{ route('interviews.index') }}" class="btn btn-outline btn-sm">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Back
    </a>
    <h1 class="page-title">Assign Interview</h1>
</div>

<form action="{{ route('interviews.store') }}" method="POST">
    @csrf

    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <h3>Interview Details</h3>
        </div>
        <div class="card-body">
            {{-- Select Application (Candidate + Job) --}}
            <div class="form-group">
                <label for="job_application_id">Candidate Application <span class="text-danger">*</span></label>
                <select name="job_application_id" id="job_application_id" class="form-control" required>
                    <option value="">-- Select candidate &amp; position --</option>
                    @foreach($applications as $app)
                        <option value="{{ $app->id }}"
                            data-candidate-id="{{ $app->candidate_id }}"
                            data-skills="{{ json_encode($app->jobPosting->required_skills ?? []) }}"
                            {{ old('job_application_id') == $app->id ? 'selected' : '' }}>
                            {{ $app->candidate->first_name }} {{ $app->candidate->last_name }}
                            &mdash; {{ $app->jobPosting->title }}
                            ({{ str_replace('_', ' ', ucfirst($app->current_stage)) }})
                        </option>
                    @endforeach
                </select>
                @error('job_application_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Interview Type --}}
            <div class="form-group">
                <label for="interview_type">Interview Stage <span class="text-danger">*</span></label>
                <select name="interview_type" id="interview_type" class="form-control" required>
                    <option value="hr_screening" {{ old('interview_type') == 'hr_screening' ? 'selected' : '' }}>HR Screening</option>
                    <option value="technical_round_1" {{ old('interview_type') == 'technical_round_1' ? 'selected' : '' }}>Technical Round 1</option>
                    <option value="technical_round_2" {{ old('interview_type') == 'technical_round_2' ? 'selected' : '' }}>Technical Round 2</option>
                </select>
                @error('interview_type') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Interviewer Search --}}
            <div class="form-group">
                <label for="interviewer_search">Assign Interviewer <span class="text-danger">*</span></label>
                <div class="employee-search-wrapper">
                    <input type="text" id="interviewer_search" class="form-control" placeholder="Search employee by name or email..." autocomplete="off">
                    <input type="hidden" name="employee_id" id="employee_id" value="{{ old('employee_id') }}" required>
                    <div id="employee_results" class="employee-search-results" style="display:none;"></div>
                </div>
                <div id="selected_interviewer" class="selected-interviewer" style="display:none;">
                    <div class="selected-interviewer__info">
                        <strong id="sel_name"></strong>
                        <small id="sel_email" class="text-muted"></small>
                    </div>
                    <div id="sel_account_note" class="selected-interviewer__note" style="display:none;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        <span>Account will be auto-created for this employee</span>
                    </div>
                    <button type="button" id="clear_interviewer" class="btn btn-sm btn-outline">&times; Clear</button>
                </div>
                @error('employee_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Scheduled At --}}
            <div class="form-group">
                <label for="scheduled_at">Scheduled Date &amp; Time</label>
                <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}">
                @error('scheduled_at') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- Notes --}}
            <div class="form-group">
                <label for="notes">Notes for Interviewer</label>
                <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Optional instructions or focus areas...">{{ old('notes') }}</textarea>
                @error('notes') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        </div>
    </div>

    <div style="display:flex; gap:12px;">
        <button type="submit" class="btn btn-primary">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
            Assign Interview
        </button>
        <a href="{{ route('interviews.index') }}" class="btn btn-outline">Cancel</a>
    </div>
</form>

<script>
(function() {
    const searchInput = document.getElementById('interviewer_search');
    const resultsDiv = document.getElementById('employee_results');
    const hiddenInput = document.getElementById('employee_id');
    const selectedDiv = document.getElementById('selected_interviewer');
    let debounce = null;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounce);
        const q = this.value.trim();
        if (q.length < 2) { resultsDiv.style.display = 'none'; return; }
        debounce = setTimeout(() => {
            fetch('{{ route("interviews.searchEmployees") }}?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    resultsDiv.innerHTML = '<div class="employee-result-item text-muted">No employees found</div>';
                } else {
                    resultsDiv.innerHTML = data.map(e =>
                        '<div class="employee-result-item" data-id="' + e.id + '" data-name="' + e.name + '" data-email="' + e.email + '" data-has-account="' + (e.has_account ? '1' : '0') + '">' +
                        '<strong>' + e.name + '</strong> <small class="text-muted">' + e.email + '</small>' +
                        (e.has_account ? '' : ' <span class="badge badge-warning" style="font-size:10px;">No account</span>') +
                        '</div>'
                    ).join('');
                }
                resultsDiv.style.display = 'block';
            });
        }, 300);
    });

    resultsDiv.addEventListener('click', function(e) {
        const item = e.target.closest('.employee-result-item');
        if (!item || !item.dataset.id) return;
        hiddenInput.value = item.dataset.id;
        document.getElementById('sel_name').textContent = item.dataset.name;
        document.getElementById('sel_email').textContent = item.dataset.email;
        const noteDiv = document.getElementById('sel_account_note');
        noteDiv.style.display = item.dataset.hasAccount === '0' ? 'flex' : 'none';
        selectedDiv.style.display = 'flex';
        searchInput.style.display = 'none';
        resultsDiv.style.display = 'none';
    });

    document.getElementById('clear_interviewer').addEventListener('click', function() {
        hiddenInput.value = '';
        selectedDiv.style.display = 'none';
        searchInput.style.display = '';
        searchInput.value = '';
        searchInput.focus();
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.employee-search-wrapper')) {
            resultsDiv.style.display = 'none';
        }
    });
})();
</script>
@endsection
