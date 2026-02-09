@extends('layouts.app')
@section('title', 'Application Details')
@section('page-title', 'Application Details')
@section('content')
<div class="page-header">
    <h1>{{ $application->candidate->full_name }} - {{ $application->jobPosting->title }}</h1>
    <div class="flex gap-10">
        @include('components.stage-badge', ['stage' => $application->stage])
        <form method="POST" action="{{ route('applications.analyze', $application) }}" style="display:inline">
            @csrf <button type="submit" class="btn btn-sm btn-primary">Run AI Analysis</button>
        </form>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">Application Info</div>
        <div class="detail-grid">
            <div class="detail-item"><label>Candidate</label><div class="value"><a href="{{ route('candidates.show', $application->candidate_id) }}">{{ $application->candidate->full_name }}</a></div></div>
            <div class="detail-item"><label>Job</label><div class="value"><a href="{{ route('jobs.show', $application->job_posting_id) }}">{{ $application->jobPosting->title }}</a></div></div>
            <div class="detail-item"><label>Applied</label><div class="value">{{ $application->applied_at?->format('M d, Y') }}</div></div>
            <div class="detail-item"><label>Resume</label><div class="value">{{ $application->resume?->file_name ?? 'N/A' }}</div></div>
        </div>

        <div class="mt-2">
            <label class="text-sm text-muted">Update Stage</label>
            <form method="POST" action="{{ route('applications.updateStage', $application) }}" class="mt-1">
                @csrf @method('PUT')
                <div class="flex gap-10">
                    <select name="stage" class="form-control">
                        @foreach(['applied','ai_shortlisted','hr_screening','technical_round_1','technical_round_2','offer','hired','rejected'] as $s)
                        <option value="{{ $s }}" {{ $application->stage === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                </div>
                <div class="form-group mt-1">
                    <input type="text" name="stage_notes" class="form-control" placeholder="Stage notes (optional)" value="{{ $application->stage_notes }}">
                </div>
                @if($application->stage === 'rejected' || request('stage') === 'rejected')
                <div class="form-group">
                    <input type="text" name="rejection_reason" class="form-control" placeholder="Rejection reason" value="{{ $application->rejection_reason }}">
                </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">AI Analysis</div>
        @if($application->ai_analysis)
        @php $ai = $application->ai_analysis; @endphp
        <div class="text-center mb-2">
            <div class="score {{ ($ai['overall_score'] ?? 0) >= 70 ? 'high' : (($ai['overall_score'] ?? 0) >= 40 ? 'medium' : 'low') }}">
                {{ number_format($ai['overall_score'] ?? 0, 1) }}/100
            </div>
            <div class="text-sm text-muted">Overall Score</div>
        </div>

        @foreach(['skill_match_score'=>'Skill Match','experience_score'=>'Experience','relevance_score'=>'Relevance','authenticity_score'=>'Authenticity'] as $key=>$label)
        @if(isset($ai[$key]))
        <div class="skill-bar">
            <span class="label">{{ $label }}</span>
            <div class="bar"><div class="fill" style="width:{{ $ai[$key] }}%"></div></div>
            <span class="percent">{{ number_format($ai[$key], 0) }}%</span>
        </div>
        @endif
        @endforeach

        @if(isset($ai['recommendation']))
        <div class="mt-2"><strong>Recommendation:</strong> <span class="badge badge-blue">{{ ucwords(str_replace('_',' ',$ai['recommendation'])) }}</span></div>
        @endif
        @if(isset($ai['explanation']))
        <div class="mt-1 text-sm">{{ $ai['explanation'] }}</div>
        @endif
        @if(isset($ai['strengths']) && count($ai['strengths']))
        <div class="mt-2"><strong>Strengths:</strong><ul>@foreach($ai['strengths'] as $s)<li class="text-sm">{{ $s }}</li>@endforeach</ul></div>
        @endif
        @if(isset($ai['concerns']) && count($ai['concerns']))
        <div class="mt-1"><strong>Concerns:</strong><ul>@foreach($ai['concerns'] as $c)<li class="text-sm">{{ $c }}</li>@endforeach</ul></div>
        @endif
        <div class="text-sm text-muted mt-1">Analyzed: {{ $application->ai_analyzed_at?->format('M d, Y H:i') }}</div>
        @else
        <div class="empty-state"><p>No AI analysis yet.</p></div>
        @endif
    </div>
</div>

<div class="card">
    <div class="flex-between">
        <div class="card-header" style="border:0;margin:0;padding:0">Interview Feedback</div>
        <button onclick="openModal('feedbackModal')" class="btn btn-sm btn-primary">+ Add Feedback</button>
    </div>
    <table class="mt-2">
        <thead><tr><th>Stage</th><th>Interviewer</th><th>Rating</th><th>Recommendation</th><th>Notes</th><th></th></tr></thead>
        <tbody>
        @forelse($application->feedback as $fb)
        <tr>
            <td>{{ ucwords(str_replace('_',' ',$fb->stage)) }}</td>
            <td>{{ $fb->interviewer->name }}</td>
            <td>{{ $fb->rating ? str_repeat('*', $fb->rating) : '-' }}</td>
            <td>@if($fb->recommendation) @include('components.stage-badge', ['stage' => $fb->recommendation]) @endif</td>
            <td class="text-sm">{{ \Illuminate\Support\Str::limit($fb->notes, 80) }}</td>
            <td>
                <form method="POST" action="{{ route('feedback.destroy', $fb) }}" style="display:inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this feedback?')">Delete</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted">No feedback yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="feedbackModal">
    <div class="modal">
        <div class="modal-header">Add Interview Feedback</div>
        <form method="POST" action="{{ route('feedback.store', $application) }}">
            @csrf
            <div class="form-group">
                <label>Stage</label>
                <select name="stage" class="form-control">
                    @foreach(['hr_screening','technical_round_1','technical_round_2','offer'] as $s)
                    <option value="{{ $s }}">{{ ucwords(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Rating (1-5)</label>
                <select name="rating" class="form-control"><option value="">-</option>@for($i=1;$i<=5;$i++)<option value="{{ $i }}">{{ $i }}</option>@endfor</select>
            </div>
            <div class="form-group">
                <label>Recommendation</label>
                <select name="recommendation" class="form-control">
                    <option value="">-</option>
                    @foreach(['strong_yes','yes','neutral','no','strong_no'] as $r)
                    <option value="{{ $r }}">{{ ucwords(str_replace('_',' ',$r)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label>Strengths</label><textarea name="strengths" class="form-control" rows="2"></textarea></div>
            <div class="form-group"><label>Weaknesses</label><textarea name="weaknesses" class="form-control" rows="2"></textarea></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('feedbackModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>
@endsection
