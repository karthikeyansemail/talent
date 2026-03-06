{{-- Schedule Interview Modal — reusable partial for job show + application show pages --}}
<div class="modal-overlay" id="scheduleInterviewModal" data-search-url="{{ route('interviews.searchEmployees') }}">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            Schedule Interview
        </div>
        <form method="POST" action="{{ route('interviews.store') }}" id="scheduleInterviewForm">
            @csrf
            <input type="hidden" name="job_application_id" id="sim_application_id">
            <input type="hidden" name="interview_type" id="sim_interview_type">
            <input type="hidden" name="_redirect" id="sim_redirect" value="{{ url()->current() }}">

            <div class="modal-body">
                {{-- Context display --}}
                <div style="display:flex;gap:24px;padding:12px 14px;background:var(--primary-light);border-radius:var(--radius);margin-bottom:16px;">
                    <div>
                        <div class="text-muted" style="font-size:12px;margin-bottom:2px">Candidate</div>
                        <strong id="sim_candidate_name">—</strong>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px;margin-bottom:2px">Interview Stage</div>
                        <strong id="sim_stage_label">—</strong>
                    </div>
                </div>

                {{-- Interviewer search --}}
                <div class="form-group">
                    <label for="sim_interviewer_search">Assign Interviewer <span class="text-danger">*</span></label>
                    <div class="employee-search-wrapper">
                        <input type="text" id="sim_interviewer_search" class="form-control" placeholder="Search employee by name or email..." autocomplete="off">
                        <input type="hidden" name="employee_id" id="sim_employee_id" required>
                        <div id="sim_employee_results" class="employee-search-results" style="display:none"></div>
                    </div>
                    <div id="sim_selected_interviewer" class="selected-interviewer" style="display:none">
                        <div class="selected-interviewer__info">
                            <strong id="sim_sel_name"></strong>
                            <small id="sim_sel_email" class="text-muted"></small>
                        </div>
                        <div id="sim_sel_account_note" class="selected-interviewer__note" style="display:none">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                            <span>Account will be auto-created for this employee</span>
                        </div>
                        <button type="button" id="sim_clear_interviewer" class="btn btn-sm btn-outline">&times; Clear</button>
                    </div>
                </div>

                {{-- Schedule --}}
                <div class="form-group">
                    <label for="sim_scheduled_at">Scheduled Date &amp; Time</label>
                    <input type="datetime-local" name="scheduled_at" id="sim_scheduled_at" class="form-control">
                </div>

                {{-- Notes --}}
                <div class="form-group">
                    <label for="sim_notes">Notes for Interviewer</label>
                    <textarea name="notes" id="sim_notes" class="form-control" rows="3" placeholder="Optional instructions or focus areas..."></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" onclick="closeModal('scheduleInterviewModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="vertical-align:middle"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>
                    Assign Interview
                </button>
            </div>
        </form>
    </div>
</div>
