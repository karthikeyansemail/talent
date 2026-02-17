{{-- Reusable AI analysis display. Expects $application (JobApplication model) and optional $compact (bool) --}}
@php $compact = $compact ?? false; @endphp

@if($application->ai_analysis)
@php $ai = $application->ai_analysis; @endphp

<div class="ai-score-hero {{ $compact ? 'ai-score-hero-compact' : '' }}">
    <div class="score {{ ($application->ai_score ?? 0) >= 70 ? 'high' : (($application->ai_score ?? 0) >= 40 ? 'medium' : 'low') }}">
        {{ number_format($application->ai_score ?? $ai['overall_score'] ?? 0, 1) }}
    </div>
    <div class="score-label">Overall Score out of 100</div>
    @if(isset($ai['recommendation']))
    <div class="mt-1">
        <span class="badge badge-blue">{{ ucwords(str_replace('_',' ',$ai['recommendation'])) }}</span>
        @if($application->ai_score_version)
        <span class="badge badge-secondary" style="margin-left:4px">v{{ $application->ai_score_version }}</span>
        @endif
    </div>
    @endif
</div>

{{-- Show expanded signals if ai_signals present, otherwise fall back to legacy 4-bar --}}
@if($application->ai_signals)
    @php
        $coreSignals = [
            'skill_match_score' => 'Skill Match',
            'experience_score' => 'Experience',
            'relevance_score' => 'Relevance',
            'authenticity_score' => 'Authenticity',
        ];
        $qualitySignals = [
            'keyword_density' => 'Keyword Density',
            'generic_language' => 'Generic Language',
            'verifiable_evidence' => 'Verifiable Evidence',
            'career_progression' => 'Career Progression',
            'quantified_claims' => 'Quantified Claims',
        ];
        $signals = $application->ai_signals;
    @endphp
    <div class="signal-group-label">Core Signals</div>
    @foreach($coreSignals as $key => $label)
    @if(isset($signals[$key]))
    <div class="skill-bar">
        <span class="label">{{ $label }}</span>
        <div class="bar"><div class="fill" style="width:{{ $signals[$key] }}%"></div></div>
        <span class="percent">{{ number_format($signals[$key], 0) }}%</span>
    </div>
    @endif
    @endforeach

    @php $hasQuality = collect($qualitySignals)->keys()->filter(fn($k) => isset($signals[$k]) && $signals[$k] != 50)->isNotEmpty(); @endphp
    @if($hasQuality)
    <div class="signal-group-label" style="margin-top:12px">Quality Signals</div>
    @foreach($qualitySignals as $key => $label)
    @if(isset($signals[$key]))
    <div class="skill-bar">
        <span class="label">{{ $label }}</span>
        <div class="bar"><div class="fill quality-fill" style="width:{{ $signals[$key] }}%"></div></div>
        <span class="percent">{{ number_format($signals[$key], 0) }}%</span>
    </div>
    @endif
    @endforeach
    @endif
@else
    @foreach(['skill_match_score'=>'Skill Match','experience_score'=>'Experience','relevance_score'=>'Relevance','authenticity_score'=>'Authenticity'] as $key=>$label)
    @if(isset($ai[$key]))
    <div class="skill-bar">
        <span class="label">{{ $label }}</span>
        <div class="bar"><div class="fill" style="width:{{ $ai[$key] }}%"></div></div>
        <span class="percent">{{ number_format($ai[$key], 0) }}%</span>
    </div>
    @endif
    @endforeach
@endif

@if(isset($ai['explanation']))
<div class="notes-block mt-2">
    <p>{{ $ai['explanation'] }}</p>
</div>
@endif

@if((isset($ai['strengths']) && count($ai['strengths'])) || (isset($ai['concerns']) && count($ai['concerns'])))
<div class="ai-strengths-concerns">
    @if(isset($ai['strengths']) && count($ai['strengths']))
    <div class="sc-section strengths">
        <h4>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            Strengths
        </h4>
        <ul class="sc-list">@foreach($ai['strengths'] as $s)<li>{{ $s }}</li>@endforeach</ul>
    </div>
    @endif
    @if(isset($ai['concerns']) && count($ai['concerns']))
    <div class="sc-section concerns">
        <h4>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--warning)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Concerns
        </h4>
        <ul class="sc-list">@foreach($ai['concerns'] as $c)<li>{{ $c }}</li>@endforeach</ul>
    </div>
    @endif
</div>
@endif

<div class="text-sm text-muted mt-2" style="display:flex;align-items:center;gap:6px">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    Analyzed: {{ $application->ai_analyzed_at?->format('M d, Y H:i') }}
</div>
@else
<div class="empty-state">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
    <p>No AI analysis yet</p>
    <div class="empty-hint">Click "Run AI Analysis" to generate insights</div>
</div>
@endif
