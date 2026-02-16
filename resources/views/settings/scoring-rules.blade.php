@extends('layouts.app')
@section('title', 'Scoring Rules')
@section('page-title', 'Scoring Rules')
@section('content')
<div class="page-header">
    <h1>Resume Scoring Rules</h1>
    <p style="color:var(--gray-500);margin:4px 0 0">Configure how AI signals are weighted to compute the final resume score. Version {{ $currentVersion }}</p>
</div>

{{-- Prediction Accuracy Card --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Prediction Accuracy
        </span>
        @if($accuracy['enough_data'])
        <form method="POST" action="{{ route('settings.scoring.optimize') }}" style="margin:0">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                Run Optimization
            </button>
        </form>
        @endif
    </div>
    <div class="card-body">
        <div class="scoring-accuracy-grid">
            <div class="accuracy-stat">
                <div class="accuracy-value">{{ $accuracy['sample_size'] }}</div>
                <div class="accuracy-label">Scored Applications with Feedback</div>
            </div>
            <div class="accuracy-stat">
                <div class="accuracy-value">{{ $accuracy['correlation'] !== null ? number_format($accuracy['correlation'], 3) : '---' }}</div>
                <div class="accuracy-label">Correlation (AI Score vs HR Rating)</div>
                @if($accuracy['correlation'] !== null)
                <div class="accuracy-hint">
                    @if($accuracy['correlation'] > 0.7) Strong positive
                    @elseif($accuracy['correlation'] > 0.4) Moderate positive
                    @elseif($accuracy['correlation'] > 0.1) Weak positive
                    @elseif($accuracy['correlation'] > -0.1) No correlation
                    @else Negative correlation
                    @endif
                </div>
                @endif
            </div>
            <div class="accuracy-stat">
                <div class="accuracy-value">{{ $accuracy['mae'] !== null ? number_format($accuracy['mae'], 1) : '---' }}</div>
                <div class="accuracy-label">Mean Absolute Error (0-100 scale)</div>
                @if($accuracy['mae'] !== null)
                <div class="accuracy-hint">
                    @if($accuracy['mae'] < 10) Excellent
                    @elseif($accuracy['mae'] < 20) Good
                    @elseif($accuracy['mae'] < 30) Fair
                    @else Needs improvement
                    @endif
                </div>
                @endif
            </div>
        </div>
        @if(!$accuracy['enough_data'])
        <div class="scoring-info-box" style="margin-top:16px">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            Need at least 20 applications with AI signals and interview feedback ratings to enable automatic optimization.
            Currently: {{ $accuracy['sample_size'] }} samples.
        </div>
        @endif
    </div>
</div>

{{-- Weight Configuration Card --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
            Signal Weights
        </span>
    </div>
    <div class="card-body">
        {{-- Visual weight proportion bar --}}
        <div class="weight-proportion-bar" id="weightProportionBar"></div>

        <form method="POST" action="{{ route('settings.scoring.update') }}" id="scoringWeightsForm">
            @csrf
            @method('PUT')

            {{-- Core Signals --}}
            <div class="scoring-category">
                <h3 class="scoring-category-title">Core Signals</h3>
                <p class="scoring-category-desc">Primary factors for evaluating candidate-job fit</p>
                @foreach($coreRules as $rule)
                <div class="scoring-rule-row" data-signal="{{ $rule->signal_key }}">
                    <div class="scoring-rule-info">
                        <div class="scoring-rule-label">
                            {{ $rule->signal_label }}
                            <form method="POST" action="{{ route('settings.scoring.toggle', $rule) }}" style="display:inline;margin:0">
                                @csrf
                                <button type="submit" class="scoring-toggle {{ $rule->is_active ? 'active' : '' }}" title="{{ $rule->is_active ? 'Disable' : 'Enable' }}">
                                    <span class="toggle-track"><span class="toggle-thumb"></span></span>
                                </button>
                            </form>
                        </div>
                        <div class="scoring-rule-desc">{{ $rule->description }}</div>
                    </div>
                    <div class="scoring-rule-slider">
                        <input type="range" name="weights[{{ $rule->signal_key }}]" min="0" max="100" value="{{ round($rule->weight * 100) }}" class="weight-slider" data-key="{{ $rule->signal_key }}" {{ !$rule->is_active ? 'disabled' : '' }}>
                        <span class="weight-value" data-key="{{ $rule->signal_key }}">{{ round($rule->weight * 100) }}%</span>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Authenticity Signals --}}
            <div class="scoring-category" style="margin-top:24px">
                <h3 class="scoring-category-title">Authenticity Signals</h3>
                <p class="scoring-category-desc">Quality and credibility indicators — enable these to penalize keyword-stuffed or generic resumes</p>
                @foreach($authenticityRules as $rule)
                <div class="scoring-rule-row" data-signal="{{ $rule->signal_key }}">
                    <div class="scoring-rule-info">
                        <div class="scoring-rule-label">
                            {{ $rule->signal_label }}
                            <form method="POST" action="{{ route('settings.scoring.toggle', $rule) }}" style="display:inline;margin:0">
                                @csrf
                                <button type="submit" class="scoring-toggle {{ $rule->is_active ? 'active' : '' }}" title="{{ $rule->is_active ? 'Disable' : 'Enable' }}">
                                    <span class="toggle-track"><span class="toggle-thumb"></span></span>
                                </button>
                            </form>
                        </div>
                        <div class="scoring-rule-desc">{{ $rule->description }}</div>
                    </div>
                    <div class="scoring-rule-slider">
                        <input type="range" name="weights[{{ $rule->signal_key }}]" min="0" max="100" value="{{ round($rule->weight * 100) }}" class="weight-slider" data-key="{{ $rule->signal_key }}" {{ !$rule->is_active ? 'disabled' : '' }}>
                        <span class="weight-value" data-key="{{ $rule->signal_key }}">{{ round($rule->weight * 100) }}%</span>
                    </div>
                </div>
                @endforeach
            </div>

            <div style="margin-top:20px;display:flex;align-items:center;gap:12px">
                <button type="submit" class="btn btn-primary">Save Weights</button>
                <span class="scoring-normalize-hint">Weights will be normalized to sum to 100% on save</span>
            </div>
        </form>
    </div>
</div>

{{-- Version History Card --}}
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Version History
        </span>
    </div>
    <div class="card-body">
        @if($versions->isEmpty())
            <p style="color:var(--gray-500);text-align:center;padding:20px 0">No version history yet.</p>
        @else
        <table class="data-table">
            <thead>
                <tr>
                    <th>Version</th>
                    <th>Trigger</th>
                    <th>By</th>
                    <th>Notes</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($versions as $version)
                <tr>
                    <td><strong>v{{ $version->version }}</strong></td>
                    <td>
                        <span class="badge {{ $version->trigger === 'auto_optimization' ? 'badge-info' : 'badge-secondary' }}">
                            {{ $version->trigger === 'auto_optimization' ? 'Auto' : 'Manual' }}
                        </span>
                    </td>
                    <td>{{ $version->user?->name ?? 'System' }}</td>
                    <td style="color:var(--gray-600);font-size:13px">{{ $version->notes ?? '-' }}</td>
                    <td style="white-space:nowrap">{{ $version->created_at?->format('M j, Y H:i') }}</td>
                    <td>
                        @if($version->version !== $currentVersion)
                        <form method="POST" action="{{ route('settings.scoring.rollback', $version) }}" style="margin:0" onsubmit="return confirm('Rollback to version {{ $version->version }}?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-secondary">Rollback</button>
                        </form>
                        @else
                        <span class="badge badge-success">Current</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

{{-- Optimization Runs Card --}}
@if($optimizationRuns->isNotEmpty())
<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
            Optimization Runs
        </span>
    </div>
    <div class="card-body">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Samples</th>
                    <th>Correlation Before</th>
                    <th>Correlation After</th>
                    <th>MAE Before</th>
                    <th>MAE After</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($optimizationRuns as $run)
                <tr>
                    <td style="white-space:nowrap">{{ $run->created_at?->format('M j, Y H:i') }}</td>
                    <td>{{ $run->applications_analyzed }}</td>
                    <td>{{ $run->correlation_before !== null ? number_format($run->correlation_before, 3) : '-' }}</td>
                    <td>
                        @if($run->correlation_after !== null)
                            <span style="color:{{ $run->correlation_after > ($run->correlation_before ?? 0) ? 'var(--success)' : 'var(--danger)' }}">
                                {{ number_format($run->correlation_after, 3) }}
                            </span>
                        @else - @endif
                    </td>
                    <td>{{ $run->mae_before !== null ? number_format($run->mae_before, 1) : '-' }}</td>
                    <td>
                        @if($run->mae_after !== null)
                            <span style="color:{{ $run->mae_after < ($run->mae_before ?? 999) ? 'var(--success)' : 'var(--danger)' }}">
                                {{ number_format($run->mae_after, 1) }}
                            </span>
                        @else - @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $run->status === 'completed' ? 'success' : ($run->status === 'failed' ? 'danger' : 'warning') }}">
                            {{ ucfirst($run->status) }}
                        </span>
                        @if($run->error_message)
                        <span title="{{ $run->error_message }}" style="cursor:help;color:var(--danger)">(?)</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
