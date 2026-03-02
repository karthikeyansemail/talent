@php
    // ── Raw task data ──────────────────────────────────────────────
    $allTasks      = $employee->tasks;
    $tasksBySource = $allTasks->groupBy('source_type');
    $totalTasks    = $allTasks->count();
    $sourceLabels  = ['jira'=>'Jira','zoho_projects'=>'Zoho Projects','devops_boards'=>'DevOps Boards','github_projects'=>'GitHub Projects'];

    $statusGroups = $allTasks->groupBy('status');
    $doneTasks    = $statusGroups->get('Done', collect())->count();
    $inProgTasks  = collect();
    foreach(['In Progress','In Review','In Development','Review','Active'] as $_s) {
        $inProgTasks = $inProgTasks->merge($statusGroups->get($_s, collect()));
    }
    $inProgCount    = $inProgTasks->count();
    $typeGroups     = $allTasks->groupBy('task_type');
    $bugCount       = $typeGroups->get('Bug', collect())->count();
    $storyCount     = $typeGroups->get('Story', collect())->count() + $typeGroups->get('UserStory', collect())->count();
    $taskCount2     = $typeGroups->get('Task', collect())->count();

    // ── Signal data ────────────────────────────────────────────────
    $employeeSignals = $employee->signals ?? collect();
    $slackSignals    = $employeeSignals->where('source_type', 'slack');
    $teamsSignals    = $employeeSignals->where('source_type', 'teams');
    $githubSignals   = $employeeSignals->where('source_type', 'github');
    $commSignals     = $slackSignals->merge($teamsSignals);

    // ── Sprint sheet data ──────────────────────────────────────────
    $sprintSheets = $employee->sprintSheets->sortBy('start_date');

    // ── Insights from controller ───────────────────────────────────
    $ti  = $signalInsights['task'] ?? [];
    $ci  = $signalInsights['comm'] ?? [];
    $gi  = $signalInsights['code'] ?? [];
    $obs = $signalInsights['observations'] ?? [];
    $ei  = $signalInsights['engagement'] ?? [];

    // ── Jira skills ────────────────────────────────────────────────
    $jiraSkills = $employee->skills_from_jira['extracted_skills'] ?? [];
    $topSkills  = collect($jiraSkills)->sortByDesc('confidence')->take(8)->values();

    // ── Engagement data ────────────────────────────────────────────
    $engScore   = $ei['score'] ?? null;
    $engLevel   = $ei['level'] ?? null;
    $attrRisk   = $ei['risk'] ?? null;
    $sentScore  = $ei['sentiment_score'] ?? null;

    // Engagement level styling
    $engStyle = match($engLevel) {
        'Highly Engaged' => ['bg'=>'#dcfce7','fg'=>'#166534','ring'=>'#16a34a'],
        'Engaged'        => ['bg'=>'#dbeafe','fg'=>'#1e40af','ring'=>'#2563eb'],
        'Moderate'       => ['bg'=>'#fef9c3','fg'=>'#854d0e','ring'=>'#ca8a04'],
        'Disengaged'     => ['bg'=>'#fee2e2','fg'=>'#991b1b','ring'=>'#dc2626'],
        default          => ['bg'=>'#f3f4f6','fg'=>'#374151','ring'=>'#9ca3af'],
    };

    // Attrition risk styling
    $riskStyle = match($attrRisk) {
        'Elevated' => ['bg'=>'#fee2e2','fg'=>'#991b1b','dot'=>'#dc2626'],
        'Watch'    => ['bg'=>'#fef3c7','fg'=>'#92400e','dot'=>'#d97706'],
        default    => ['bg'=>'#dcfce7','fg'=>'#166534','dot'=>'#16a34a'],
    };

    // Sentiment label
    $sentLabel = null;
    if ($sentScore !== null) {
        $sentLabel = $sentScore >= 30 ? 'Positive Tone' : ($sentScore >= -20 ? 'Neutral Tone' : 'Strained Tone');
        $sentStyle = $sentScore >= 30
            ? ['bg'=>'#d1fae5','fg'=>'#065f46']
            : ($sentScore >= -20 ? ['bg'=>'#f3f4f6','fg'=>'#374151'] : ['bg'=>'#fee2e2','fg'=>'#991b1b']);
    }

    // Task source label
    $taskSourceStr = $tasksBySource->keys()->map(fn($s) => $sourceLabels[$s] ?? ucfirst(str_replace('_',' ',$s)))->join(', ');

    $hasAnything = $totalTasks > 0 || $commSignals->count() > 0 || $githubSignals->count() > 0
               || count($topSkills) > 0 || ($employee->skills_from_resume && count($employee->skills_from_resume) > 0)
               || $sprintSheets->count() > 0;

    // ── Period label helper ────────────────────────────────────────
    // Handles both YYYY-MM (tasks) and YYYY-WW (comm/code signals)
    $formatPeriodLabel = function(?string $period): string {
        if (!$period) return 'prior period';
        if (preg_match('/^(\d{4})-W(\d{1,2})$/', $period, $m)) {
            return 'Wk ' . (int)$m[2] . ' \'' . substr($m[1], 2);  // e.g. "Wk 8 '26"
        }
        if (preg_match('/^(\d{4})-(\d{2})$/', $period, $m)) {
            return date('M Y', mktime(0, 0, 0, (int)$m[2], 1, (int)$m[1]));
        }
        return $period;
    };

    // Current + prior period labels for comm signals (pick first signal with a period)
    $commCurrPeriod = null; $commPrevPeriod = null;
    foreach ($ci as $cKey => $cIns) {
        if (!empty($cIns['period'])) { $commCurrPeriod = $cIns['period']; }
        if (!empty($cIns['prev_period'])) { $commPrevPeriod = $cIns['prev_period']; }
        if ($commCurrPeriod) break;
    }
    $commCurrLabel = $formatPeriodLabel($commCurrPeriod);
    $commPrevLabel = $formatPeriodLabel($commPrevPeriod);

    // Current + prior period labels for code signals
    $codeCurrPeriod = null; $codePrevPeriod = null;
    foreach ($gi as $gKey => $gIns) {
        if (!empty($gIns['period'])) { $codeCurrPeriod = $gIns['period']; }
        if (!empty($gIns['prev_period'])) { $codePrevPeriod = $gIns['prev_period']; }
        if ($codeCurrPeriod) break;
    }
    $codeCurrLabel = $formatPeriodLabel($codeCurrPeriod);
    $codePrevLabel = $formatPeriodLabel($codePrevPeriod);
@endphp

{{-- ===== WORK SIGNAL OVERVIEW ===== --}}
@if($engLevel || $attrRisk || $engScore !== null || isset($aiInsight))
@php
    $heroPeriodParts = [];
    if (!empty($ti['curr_period_label'])) {
        $heroPeriodParts[] = 'Tasks: ' . $ti['curr_period_label'];
    }
    if ($commCurrPeriod) {
        $heroPeriodParts[] = 'Comms: ' . $commCurrLabel;
    }
    $heroPeriodStr = implode(' · ', $heroPeriodParts);
@endphp
<div style="background:#f8faff;border:1px solid #dbeafe;border-radius:14px;padding:20px 24px;margin-bottom:24px">

    {{-- Badge row --}}
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:14px">
        @if($engLevel)
        <span style="font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px;background:{{ $engStyle['bg'] }};color:{{ $engStyle['fg'] }}">{{ $engLevel }}</span>
        @endif
        @if($attrRisk && $attrRisk !== 'Low')
        <span style="display:flex;align-items:center;gap:5px;font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px;background:{{ $riskStyle['bg'] }};color:{{ $riskStyle['fg'] }}">
            <span style="width:6px;height:6px;border-radius:50%;background:{{ $riskStyle['dot'] }};display:inline-block;flex-shrink:0"></span>
            Engagement Risk: {{ $attrRisk }}
        </span>
        @endif
        @if($sentLabel !== null)
        <span style="font-size:12px;font-weight:700;padding:4px 14px;border-radius:20px;background:{{ $sentStyle['bg'] }};color:{{ $sentStyle['fg'] }}">{{ $sentLabel }}</span>
        @endif
        @if($heroPeriodStr)
        <span style="font-size:12px;color:var(--gray-400);margin-left:auto">{{ $heroPeriodStr }}</span>
        @endif
    </div>

    @php
        // Build a readable overview sentence from available data
        $ovParts = [];
        if ($ti['completion_rate'] !== null) {
            $rStr = $ti['completion_rate'] . '%';
            $prevStr = ($ti['completion_rate_prev'] !== null)
                ? ', up from ' . $ti['completion_rate_prev'] . '% last period'
                : '';
            if ($ti['completion_rate'] < $ti['completion_rate_prev'] ?? 101) {
                $diff = ($ti['completion_rate_prev'] !== null) ? abs((int)($ti['completion_rate'] - $ti['completion_rate_prev'])) : null;
                $prevStr = $diff !== null ? ', down ' . $diff . ' pts from last period' : '';
            }
            $ovParts[] = "completed tasks at {$rStr}{$prevStr}";
        }
        if (($ci['active_days_count']['value'] ?? null) !== null) {
            $ad = (int)$ci['active_days_count']['value'];
            $ovParts[] = "communicated on {$ad} " . ($ad == 1 ? 'day' : 'days') . " last week";
        }
        if (($ci['unique_collaborators_count']['value'] ?? null) !== null) {
            $col = (int)$ci['unique_collaborators_count']['value'];
            $ovParts[] = "reached {$col} " . ($col == 1 ? 'colleague' : 'colleagues');
        }
        $overviewStr = count($ovParts) > 0 ? ucfirst(implode(', ', $ovParts)) . '.' : null;
    @endphp

    @if($overviewStr)
    <p style="font-size:14px;color:var(--gray-700);line-height:1.7;margin:0 0 8px">{{ $overviewStr }}</p>
    @endif

    @if($attrRisk === 'Elevated' || $attrRisk === 'Watch')
    <p style="font-size:13px;color:{{ $attrRisk === 'Elevated' ? '#991b1b' : '#92400e' }};margin:0;line-height:1.5">
        @if($attrRisk === 'Elevated')
            {{ $ei['declining_count'] ?? 0 }} observable signals are trending lower than last period — warrants a direct conversation.
        @else
            Some signals are trending lower than last period — a check-in conversation may be helpful.
        @endif
        <em style="color:var(--gray-400);font-size:12px"> Signals only, not a prediction.</em>
    </p>
    @endif
</div>
@endif

{{-- ===== AI WORK PULSE ANALYSIS ===== --}}
@if(isset($aiInsight) && $aiInsight)
<div class="card" style="margin-bottom:24px">
    <div class="card-header" style="justify-content:space-between;flex-wrap:wrap;gap:8px">
        <span style="display:flex;align-items:center;gap:8px">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <span style="font-weight:700;font-size:15px;color:var(--gray-800)">AI Work Pulse Analysis</span>
        </span>
        <div style="display:flex;align-items:center;gap:12px">
            <span style="font-size:12px;color:var(--gray-400)">
                {{ $aiInsight->analyzed_at->diffForHumans() }} · {{ $aiInsight->data_context['total_tasks'] ?? 0 }} tasks reviewed
            </span>
            <button type="button"
                onclick="var b=document.querySelector('.wp-analyze-btn');if(b)b.click();"
                style="font-size:12px;padding:5px 12px;border:1px solid var(--gray-200);border-radius:6px;background:white;cursor:pointer;color:var(--gray-600)">
                Re-analyze
            </button>
        </div>
    </div>
    <div class="card-body">
        {{-- Management narrative — prominent --}}
        <blockquote style="margin:0 0 24px;padding:16px 20px;background:#f8faff;border-left:4px solid var(--primary);border-radius:0 8px 8px 0">
            <p style="font-size:16px;color:var(--gray-700);line-height:1.8;margin:0;font-style:italic">
                "{{ $aiInsight->management_narrative }}"
            </p>
        </blockquote>

        {{-- Dimensions grid --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px">
            @foreach($aiInsight->dimensions as $dim)
            @php
                $dimBg = match($dim['direction'] ?? '') {
                    'Strong'      => '#f0fdf4',
                    'Solid'       => '#eff6ff',
                    'Developing'  => '#fefce8',
                    default       => '#fef2f2',
                };
                $dimBorder = match($dim['direction'] ?? '') {
                    'Strong'      => '#bbf7d0',
                    'Solid'       => '#bfdbfe',
                    'Developing'  => '#fde68a',
                    default       => '#fecaca',
                };
                $dimFg = match($dim['direction'] ?? '') {
                    'Strong'      => '#166534',
                    'Solid'       => '#1e40af',
                    'Developing'  => '#854d0e',
                    default       => '#991b1b',
                };
            @endphp
            <div style="background:{{ $dimBg }};border:1px solid {{ $dimBorder }};border-radius:12px;padding:16px 18px">
                <div style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.06em;margin-bottom:8px">
                    {{ $dim['name'] ?? '' }}
                </div>
                <span style="background:{{ $dimBorder }};color:{{ $dimFg }};font-size:12px;font-weight:800;padding:3px 12px;border-radius:20px;display:inline-block;margin-bottom:10px">
                    {{ $dim['direction'] ?? '—' }}
                </span>
                <p style="font-size:13px;color:var(--gray-600);line-height:1.6;margin:0">
                    {{ $dim['description'] ?? '' }}
                </p>
            </div>
            @endforeach
        </div>
    </div>
</div>
@else
<div style="display:flex;align-items:center;justify-content:space-between;background:#f8fafc;border:1px dashed var(--gray-200);border-radius:10px;padding:18px 22px;margin-bottom:24px">
    <div>
        <div style="font-size:14px;font-weight:600;color:var(--gray-700);margin-bottom:4px">No AI Analysis Yet</div>
        <div style="font-size:13px;color:var(--gray-400)">Click <strong>AI Analyze</strong> in the action bar to derive qualitative work patterns — Complexity, Delivery, Execution Speed, and more.</div>
    </div>
    @if(isset($employee) && $employee->tasks->count() > 0)
    <button type="button"
        onclick="var b=document.querySelector('.wp-analyze-btn');if(b)b.click();"
        class="btn btn-primary" style="font-size:13px;white-space:nowrap;flex-shrink:0;margin-left:16px">
        Analyze Work Pulse
    </button>
    @endif
</div>
@endif

{{-- ===== EMPTY STATE ===== --}}
@if(!$hasAnything)
<div class="card"><div class="card-body">
    <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        <p>No work data available yet</p>
        <div class="empty-hint">Connect integrations (Jira, DevOps, GitHub, Slack, Teams) and sync tasks to see work patterns for this employee</div>
    </div>
</div></div>
@else

{{-- ===== OBSERVED CHANGES ===== --}}
@if(count($obs) > 0)
<div style="margin-bottom:24px">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        <span style="font-weight:700;font-size:14px;color:var(--gray-800)">Observed Changes</span>
        <span style="font-size:12px;color:var(--gray-400)">period-over-period factual observations</span>
    </div>
    <div style="display:flex;flex-direction:column;gap:6px">
        @foreach($obs as $observation)
        <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;background:#fff;border:1px solid #e5e7eb;border-radius:8px">
            <span style="color:var(--primary);flex-shrink:0;margin-top:2px;font-size:14px">•</span>
            <span style="font-size:13.5px;color:var(--gray-700);line-height:1.5">{{ $observation }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ===== TASK PERFORMANCE ===== --}}
@if($totalTasks > 0)
@php
    $completionTrend = ($ti['completion_rate'] !== null && $ti['completion_rate_prev'] !== null)
        ? (int)($ti['completion_rate'] - $ti['completion_rate_prev']) : null;
    $spTrend = ($ti['velocity_sp'] !== null && $ti['velocity_sp_prev'] !== null && $ti['velocity_sp_prev'] > 0)
        ? (int) round(($ti['velocity_sp'] - $ti['velocity_sp_prev']) / $ti['velocity_sp_prev'] * 100) : null;
    $ctTrendPct = ($ti['cycle_time_avg'] !== null && $ti['cycle_time_prev'] !== null && $ti['cycle_time_prev'] > 0)
        ? (int) round(($ti['cycle_time_avg'] - $ti['cycle_time_prev']) / $ti['cycle_time_prev'] * 100) : null;

    // Build task narrative sentences
    $taskSentences = [];

    // Completion sentence
    if ($ti['completion_rate'] !== null) {
        $done  = $ti['done_current'] ?? 0;
        $total = $ti['total_current'] ?? $totalTasks;
        $rate  = $ti['completion_rate'];
        $s = "{$done} of {$total} tasks completed ({$rate}%)";
        if ($completionTrend !== null) {
            $dir = $completionTrend >= 0 ? 'up' : 'down';
            $s  .= ", {$dir} " . abs($completionTrend) . " pts from " . ($ti['prev_period_label'] ?? 'last period');
        }
        $taskSentences[] = $s . '.';
    }

    // Cycle time sentence
    if ($ti['cycle_time_avg'] !== null) {
        $s = "Tasks averaged {$ti['cycle_time_avg']} days from creation to close";
        if ($ctTrendPct !== null) {
            $s .= $ctTrendPct < 0 ? ' — faster than last period' : ' — slower than last period';
        }
        $taskSentences[] = $s . '.';
    }

    // Story points + velocity
    if ($ti['velocity_sp'] !== null) {
        $sp = number_format($ti['velocity_sp'], 0);
        $s  = "{$sp} story points delivered";
        if ($spTrend !== null) {
            $dir = $spTrend >= 0 ? 'up' : 'down';
            $s  .= ", {$dir} " . abs($spTrend) . "% from " . ($ti['prev_period_label'] ?? 'last period');
        }
        $taskSentences[] = $s . '.';
    }

    // High priority
    if ($ti['high_priority_done_rate'] !== null) {
        $taskSentences[] = $ti['high_priority_done_rate'] . '% of high-priority tasks were resolved.';
    }

    // In progress + aging
    if ($inProgCount > 0) {
        $s = "{$inProgCount} task" . ($inProgCount != 1 ? 's' : '') . " currently in progress";
        if (($ti['aging_tasks'] ?? 0) > 0) {
            $aging = $ti['aging_tasks'];
            $s .= ", including {$aging} open for 30+ days — worth a review";
        }
        $taskSentences[] = $s . '.';
    }

    // Bugs
    if ($bugCount > 0) {
        $bugsDone = $allTasks->where('task_type','Bug')->where('status','Done')->count();
        if ($ti['bug_resolution_rate'] !== null) {
            $taskSentences[] = "{$bugsDone} of {$bugCount} bugs resolved ({$ti['bug_resolution_rate']}%).";
        } else {
            $taskSentences[] = "{$bugCount} bug" . ($bugCount != 1 ? 's' : '') . " in the task set.";
        }
    }
@endphp

<div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
    <span style="font-weight:700;font-size:14px;color:var(--gray-800)">Task Performance</span>
    <span style="font-size:12px;color:var(--gray-400)">from {{ $taskSourceStr }}@if(!empty($ti['curr_period_label'])) · {{ $ti['curr_period_label'] }}@endif</span>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px 22px;margin-bottom:24px;line-height:1.8">
    @if(count($taskSentences) > 0)
    <p style="font-size:14px;color:var(--gray-700);margin:0">{{ implode(' ', $taskSentences) }}</p>
    @else
    <p style="font-size:14px;color:var(--gray-400);margin:0;font-style:italic">{{ $totalTasks }} tasks loaded — no completion timestamps available for rate calculations.</p>
    @endif
</div>
@endif {{-- end totalTasks > 0 --}}

{{-- ===== COMMUNICATION & BEHAVIORAL SIGNALS ===== --}}
@if($commSignals->count() > 0)
@php
    $commSrc = ($slackSignals->count() > 0 ? 'Slack' : '') . ($slackSignals->count() > 0 && $teamsSignals->count() > 0 ? ' · ' : '') . ($teamsSignals->count() > 0 ? 'Teams' : '');

    // Build communication narrative
    $msgCount   = ($ci['messages_sent_count']['value'] ?? null) !== null ? (int)$ci['messages_sent_count']['value'] : null;
    $channels   = ($ci['channel_diversity_count']['value'] ?? null) !== null ? (int)$ci['channel_diversity_count']['value'] : null;
    $activeDays = ($ci['active_days_count']['value'] ?? null) !== null ? (int)$ci['active_days_count']['value'] : null;
    $collabs    = ($ci['unique_collaborators_count']['value'] ?? null) !== null ? (int)$ci['unique_collaborators_count']['value'] : null;
    $avgLen     = ($ci['avg_message_length']['value'] ?? null) !== null ? (int)$ci['avg_message_length']['value'] : null;
    $afterHrPct = ($ci['after_hours_message_pct']['value'] ?? null) !== null ? round((float)$ci['after_hours_message_pct']['value'], 1) : null;

    // Build the main comm sentence
    $commParts = [];
    if ($msgCount !== null) {
        $chanStr = $channels !== null ? " across {$channels} " . ($channels == 1 ? 'channel' : 'channels') : '';
        $dayStr  = $activeDays !== null ? " on {$activeDays} active " . ($activeDays == 1 ? 'day' : 'days') : '';
        $colStr  = $collabs !== null ? ", reaching {$collabs} " . ($collabs == 1 ? 'colleague' : 'colleagues') : '';
        $commParts[] = "Sent {$msgCount} messages{$chanStr}{$dayStr}{$colStr}.";
    }

    // Message length description
    if ($avgLen !== null) {
        $lenDesc = $avgLen < 60 ? 'brief' : ($avgLen < 150 ? 'medium-length' : 'detailed');
        $commParts[] = "Average message length was {$avgLen} characters — {$lenDesc} communication style.";
    }

    // After-hours
    if ($afterHrPct !== null && $afterHrPct > 20) {
        $commParts[] = "About {$afterHrPct}% of messages were sent outside business hours (before 9am / after 6pm UTC) — may reflect timezone or workload patterns.";
    }

    // Tone
    if ($sentScore !== null) {
        if ($sentScore >= 30) {
            $commParts[] = "Message tone shows positive patterns — collaboration, appreciation, and agreement signals are prominent.";
        } elseif ($sentScore >= -20) {
            $commParts[] = "Message tone appears balanced, with a healthy mix of operational and collaborative language.";
        } else {
            $commParts[] = "Message tone shows some friction signals (blockers, frustration language) — worth exploring in a 1-on-1.";
        }
    }
@endphp

<div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    <span style="font-weight:700;font-size:14px;color:var(--gray-800)">Communication</span>
    <span style="font-size:12px;color:var(--gray-400)">from {{ $commSrc }}@if($commCurrPeriod) · {{ $commCurrLabel }}@endif</span>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px 22px;margin-bottom:24px;line-height:1.8">
    @if(count($commParts) > 0)
    <p style="font-size:14px;color:var(--gray-700);margin:0">{{ implode(' ', $commParts) }}</p>
    @else
    <p style="font-size:14px;color:var(--gray-400);margin:0;font-style:italic">Communication signals available — run sync to compute detailed metrics.</p>
    @endif
</div>
@endif

{{-- ===== BEHAVIORAL PATTERNS ===== --}}
@php
    $beh          = $ei['behavioral'] ?? [];
    $behInitiated = $beh['initiated']        ?? null;
    $behInbound   = $beh['inbound_mentions'] ?? null;
    $behQuestions = $beh['questions_asked']  ?? null;
    $behProactive = $beh['proactive_status'] ?? null;
    $behChase     = $beh['chase_pattern']    ?? false;
    $behInitRatio = $beh['initiation_ratio'] ?? null;
    $hasBehData   = $behInitiated !== null || $behInbound !== null || $behQuestions !== null || $behProactive !== null;

    // Interpret initiation level
    $initiatorLabel = null;
    $initiatorStyle = null;
    if ($behInitiated !== null && $behInitRatio !== null) {
        if ($behInitRatio >= 40) {
            $initiatorLabel = 'Strong Initiator';
            $initiatorStyle = ['bg'=>'#f0fdf4','border'=>'#bbf7d0','fg'=>'#166534','icon'=>'↗'];
        } elseif ($behInitRatio >= 20) {
            $initiatorLabel = 'Active Contributor';
            $initiatorStyle = ['bg'=>'#eff6ff','border'=>'#bfdbfe','fg'=>'#1e40af','icon'=>'→'];
        } else {
            $initiatorLabel = 'Mostly Responding';
            $initiatorStyle = ['bg'=>'#fefce8','border'=>'#fde68a','fg'=>'#854d0e','icon'=>'↙'];
        }
    }
@endphp
@if($hasBehData)
<div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    <span style="font-weight:700;font-size:14px;color:var(--gray-800)">Behavioral Patterns</span>
    <span style="font-size:12px;color:var(--gray-400)">interpreted from {{ $commSrc ?? 'Slack' }} engagement · {{ $commCurrLabel }}</span>
</div>
<div class="card" style="margin-bottom:24px">
    <div class="card-body" style="padding:20px 24px">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px">

            {{-- Conversation Initiation --}}
            @if($behInitiated !== null && $initiatorStyle)
            <div style="background:{{ $initiatorStyle['bg'] }};border:1px solid {{ $initiatorStyle['border'] }};border-radius:12px;padding:16px 18px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                    <span style="font-size:18px;color:{{ $initiatorStyle['fg'] }}">{{ $initiatorStyle['icon'] }}</span>
                    <span style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.06em">Conversation Style</span>
                </div>
                <div style="font-size:15px;font-weight:700;color:{{ $initiatorStyle['fg'] }};margin-bottom:6px">{{ $initiatorLabel }}</div>
                <div style="font-size:13px;color:var(--gray-600);line-height:1.5">
                    Started <strong>{{ $behInitiated }}</strong> conversation{{ $behInitiated != 1 ? 's' : '' }} this period
                    @if($behInitRatio !== null)({{ $behInitRatio }}% of messages were thread starters)@endif.
                    @if($behInitRatio >= 40)
                        Proactively drives discussions — a good engagement signal.
                    @elseif($behInitRatio >= 20)
                        Engages in ongoing threads; occasionally opens new topics.
                    @else
                        Mostly responds to others — may benefit from being drawn into discussions proactively.
                    @endif
                </div>
            </div>
            @endif

            {{-- Inbound Mention Pressure --}}
            @if($behInbound !== null)
            @php
                $mentionStyle = $behChase
                    ? ['bg'=>'#fef2f2','border'=>'#fecaca','fg'=>'#991b1b','icon'=>'📥']
                    : ($behInbound >= 3
                        ? ['bg'=>'#fef9c3','border'=>'#fde68a','fg'=>'#854d0e','icon'=>'📬']
                        : ['bg'=>'#f9fafb','border'=>'#e5e7eb','fg'=>'#374151','icon'=>'💬']);
            @endphp
            <div style="background:{{ $mentionStyle['bg'] }};border:1px solid {{ $mentionStyle['border'] }};border-radius:12px;padding:16px 18px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                    <span style="font-size:16px">{{ $mentionStyle['icon'] }}</span>
                    <span style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.06em">Inbound Mentions</span>
                </div>
                <div style="font-size:15px;font-weight:700;color:{{ $mentionStyle['fg'] }};margin-bottom:6px">
                    Tagged {{ $behInbound }} time{{ $behInbound != 1 ? 's' : '' }} by others
                </div>
                <div style="font-size:13px;color:var(--gray-600);line-height:1.5">
                    @if($behChase)
                        Others are reaching out repeatedly while initiation from this person remains low — a pattern worth exploring in a 1-on-1.
                    @elseif($behInbound >= 3)
                        Colleagues tag this person for input — considered a good reference point by the team.
                    @else
                        Normal mention frequency — no pressure pattern detected.
                    @endif
                </div>
            </div>
            @endif

            {{-- Question-Asking (Learner Signal) --}}
            @if($behQuestions !== null)
            @php
                $questionStyle = $behQuestions >= 5
                    ? ['bg'=>'#eff6ff','border'=>'#bfdbfe','fg'=>'#1e40af','label'=>'Active Learner']
                    : ($behQuestions >= 2
                        ? ['bg'=>'#f0fdf4','border'=>'#bbf7d0','fg'=>'#166534','label'=>'Curious Contributor']
                        : ['bg'=>'#f9fafb','border'=>'#e5e7eb','fg'=>'#374151','label'=>'Low Question Rate']);
            @endphp
            <div style="background:{{ $questionStyle['bg'] }};border:1px solid {{ $questionStyle['border'] }};border-radius:12px;padding:16px 18px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                    <span style="font-size:18px;color:{{ $questionStyle['fg'] }}">?</span>
                    <span style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.06em">Problem-Solving Effort</span>
                </div>
                <div style="font-size:15px;font-weight:700;color:{{ $questionStyle['fg'] }};margin-bottom:6px">{{ $questionStyle['label'] }}</div>
                <div style="font-size:13px;color:var(--gray-600);line-height:1.5">
                    Asked <strong>{{ $behQuestions }}</strong> question{{ $behQuestions != 1 ? 's' : '' }} in messages this period.
                    @if($behQuestions >= 5)
                        High question rate indicates good problem-solving effort — actively seeking to unblock rather than waiting.
                    @elseif($behQuestions >= 2)
                        Actively seeks clarification and input — a positive engagement signal.
                    @else
                        Few questions this period — may be working independently or not using channels to unblock.
                    @endif
                </div>
            </div>
            @endif

            {{-- Proactive Status Updates --}}
            @if($behProactive !== null)
            @php
                $proactiveStyle = $behProactive >= 5
                    ? ['bg'=>'#f0fdf4','border'=>'#bbf7d0','fg'=>'#166534','label'=>'Proactive Communicator']
                    : ($behProactive >= 2
                        ? ['bg'=>'#eff6ff','border'=>'#bfdbfe','fg'=>'#1e40af','label'=>'Occasionally Updates']
                        : ['bg'=>'#fefce8','border'=>'#fde68a','fg'=>'#854d0e','label'=>'Infrequent Updates']);
            @endphp
            <div style="background:{{ $proactiveStyle['bg'] }};border:1px solid {{ $proactiveStyle['border'] }};border-radius:12px;padding:16px 18px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                    <span style="font-size:18px;color:{{ $proactiveStyle['fg'] }}">📢</span>
                    <span style="font-size:11px;font-weight:700;color:var(--gray-500);text-transform:uppercase;letter-spacing:.06em">Status Communication</span>
                </div>
                <div style="font-size:15px;font-weight:700;color:{{ $proactiveStyle['fg'] }};margin-bottom:6px">{{ $proactiveStyle['label'] }}</div>
                <div style="font-size:13px;color:var(--gray-600);line-height:1.5">
                    Sent <strong>{{ $behProactive }}</strong> proactive update{{ $behProactive != 1 ? 's' : '' }} (done / shipped / deployed / etc.) this period.
                    @if($behProactive >= 5)
                        Good initiative — regularly keeps the team informed without being asked.
                    @elseif($behProactive >= 2)
                        Shares progress updates occasionally; team generally knows where work stands.
                    @else
                        Few unprompted status updates this period — managers may want to actively check in for progress.
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- Chase pattern callout --}}
        @if($behChase)
        <div style="margin-top:16px;padding:12px 16px;background:#fff5f5;border:1px solid #fecaca;border-radius:8px;font-size:12.5px;color:#7f1d1d;line-height:1.6">
            <strong>Pattern noted:</strong> This person is being tagged by multiple others while initiating very few conversations themselves.
            This can indicate blocked work, unclear ownership, or communication friction — worth a direct conversation to understand the context.
            <em>This is an observable pattern, not a judgement.</em>
        </div>
        @endif

        <div style="margin-top:14px;font-size:11.5px;color:var(--gray-400)">
            Behavioral patterns are derived from message structure (thread starters vs. replies), mention tracking, question-asking frequency, and keyword signals in Slack messages. Interpret in context.
        </div>
    </div>
</div>
@endif

{{-- ===== CODE CONTRIBUTION ===== --}}
@if($githubSignals->count() > 0)
@php
    $fileTypes = $githubSignals->where('metric_key','file_types_touched')->sortByDesc('period')->first()?->metadata ?? [];
    $codeAreas = $githubSignals->where('metric_key','code_areas_touched')->sortByDesc('period')->first()?->metadata ?? [];

    $commits    = ($gi['commit_count']['value'] ?? null) !== null ? (int)$gi['commit_count']['value'] : null;
    $codedays   = ($gi['active_days_count']['value'] ?? null) !== null ? (int)$gi['active_days_count']['value'] : null;
    $prReviews  = ($gi['pr_reviews_count']['value'] ?? null) !== null ? (int)$gi['pr_reviews_count']['value'] : null;
    $linesAvg   = ($gi['lines_added_avg']['value'] ?? null) !== null ? (int)$gi['lines_added_avg']['value'] : null;

    $codeParts = [];
    if ($commits !== null) {
        $dayStr = $codedays !== null ? " across {$codedays} active " . ($codedays == 1 ? 'day' : 'days') : '';
        $s = "Made {$commits} " . ($commits == 1 ? 'commit' : 'commits') . "{$dayStr}";
        $commitTrend = ($gi['commit_count']['trend_pct'] ?? null);
        if ($commitTrend !== null) {
            $s .= $commitTrend >= 0 ? ', up ' . abs($commitTrend) . '% from ' . $codePrevLabel : ', down ' . abs($commitTrend) . '% from ' . $codePrevLabel;
        }
        $codeParts[] = $s . '.';
    }
    if ($prReviews !== null) {
        $codeParts[] = "Participated in {$prReviews} PR " . ($prReviews == 1 ? 'review' : 'reviews') . ".";
    }
    if ($linesAvg !== null) {
        $sizeDesc = $linesAvg < 30 ? 'small, focused' : ($linesAvg < 100 ? 'medium-sized' : 'large');
        $codeParts[] = "Average of {$linesAvg} lines added per commit — {$sizeDesc} changes.";
    }
    $topFileTypes = collect($fileTypes)->sortDesc()->take(5)->keys()->implode(', .');
    if ($topFileTypes) { $codeParts[] = "File types: .{$topFileTypes}."; }
@endphp

<div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
    <span style="font-weight:700;font-size:14px;color:var(--gray-800)">Code Contribution</span>
    <span style="font-size:12px;color:var(--gray-400)">from GitHub{{ $codeCurrPeriod ? ' · ' . $codeCurrLabel : '' }}</span>
</div>

<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px 22px;margin-bottom:{{ (count($fileTypes)+count($codeAreas))>0?'14':'24' }}px;line-height:1.8">
    @if(count($codeParts) > 0)
    <p style="font-size:14px;color:var(--gray-700);margin:0">{{ implode(' ', $codeParts) }}</p>
    @else
    <p style="font-size:14px;color:var(--gray-400);margin:0;font-style:italic">GitHub signals available — sync to compute commit metrics.</p>
    @endif
</div>

@if(count($fileTypes)>0||count($codeAreas)>0)
<div class="card" style="margin-bottom:24px"><div class="card-body">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        @if(count($fileTypes)>0)
        <div>
            <div style="font-size:13px;font-weight:600;color:var(--gray-600);margin-bottom:10px">File Types Touched</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                @foreach(collect($fileTypes)->sortDesc()->take(12) as $ext=>$cnt)
                <span style="background:var(--gray-100);color:var(--gray-700);border-radius:5px;padding:4px 10px;font-size:12px;font-weight:600">.{{ $ext }} <span style="font-weight:400;color:var(--gray-400)">({{ $cnt }})</span></span>
                @endforeach
            </div>
        </div>
        @endif
        @if(count($codeAreas)>0)
        <div>
            <div style="font-size:13px;font-weight:600;color:var(--gray-600);margin-bottom:10px">Code Areas</div>
            <div style="display:flex;flex-wrap:wrap;gap:6px">
                @foreach(collect($codeAreas)->sortDesc()->take(10) as $area=>$cnt)
                <span style="background:#eff6ff;color:#1e40af;border-radius:5px;padding:4px 10px;font-size:12px;font-weight:600">{{ $area }} <span style="font-weight:400;color:#3b82f6">({{ $cnt }})</span></span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div></div>
@else
<div style="margin-bottom:24px"></div>
@endif
@endif

{{-- ===== SKILL INTELLIGENCE ===== --}}
@if(count($topSkills) > 0 || ($employee->skills_from_resume && count($employee->skills_from_resume) > 0))
<div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    <span style="font-weight:700;font-size:14px;color:var(--gray-800)">Skill Intelligence</span>
    <span style="font-size:12px;color:var(--gray-400)">observed from {{ count($topSkills) > 0 ? 'actual task work' : 'resume' }}</span>
</div>
<div class="card" style="margin-bottom:24px"><div class="card-body">
    @if(count($topSkills) > 0)
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        @foreach($topSkills as $skill)
        @php $pct = round(($skill['confidence'] ?? 0) * 100); $depth = $skill['depth'] ?? ''; @endphp
        <div>
            <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:5px">
                <span style="color:var(--gray-700);font-weight:600">{{ $skill['skill'] ?? '' }}</span>
                <span style="color:var(--gray-500)">{{ $depth ? ucfirst($depth) : $pct . '%' }}</span>
            </div>
            <div style="height:6px;background:var(--gray-100);border-radius:3px;overflow:hidden">
                <div style="height:100%;width:{{ $pct }}%;background:{{ $pct>=70?'#2563eb':($pct>=40?'#0284c7':'#93c5fd') }};border-radius:3px"></div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div style="display:flex;flex-wrap:wrap;gap:7px">
        @foreach($employee->skills_from_resume as $skill)
        @if(is_string($skill))<span class="tag">{{ $skill }}</span>@endif
        @endforeach
    </div>
    <div style="font-size:13px;color:var(--gray-400);margin-top:10px">Sync tasks to get confidence-scored skill analysis from real work</div>
    @endif
</div></div>
@endif

{{-- ===== SPRINT / PROGRAM MANAGER SIGNALS ===== --}}
@if($sprintSheets->count() > 0)
@php
    $latestSprint    = $sprintSheets->last();
    $sprintAccuracies= $sprintSheets->map(fn($s) => $s->planned_points > 0
        ? round($s->completed_points / $s->planned_points * 100) : null)->filter()->values();
    $avgPlanAccuracy = $sprintAccuracies->count() > 0 ? round($sprintAccuracies->avg()) : null;
    $velocityTrend   = $sprintSheets->count() >= 2
        ? (int)($sprintSheets->last()->completed_points - $sprintSheets->first()->completed_points) : null;

    // Build sprint summary sentence
    $sprintSummaryParts = [];
    if ($avgPlanAccuracy !== null) {
        $sprintSummaryParts[] = "Across {$sprintSheets->count()} sprints, average planning accuracy is {$avgPlanAccuracy}%.";
    }
    if ($latestSprint) {
        $latestRate = $latestSprint->planned_points > 0
            ? round($latestSprint->completed_points / $latestSprint->planned_points * 100) : null;
        if ($latestRate !== null) {
            $sprintSummaryParts[] = "The most recent sprint ({$latestSprint->sprint_name}) delivered {$latestSprint->completed_points} of {$latestSprint->planned_points} story points ({$latestRate}%).";
        }
    }
    if ($velocityTrend !== null) {
        $dir = $velocityTrend > 0 ? 'up' : ($velocityTrend < 0 ? 'down' : 'unchanged');
        $sprintSummaryParts[] = "Velocity trend: {$dir} " . abs($velocityTrend) . " SP from first to latest sprint.";
    }
@endphp
<div style="display:flex;align-items:center;gap:8px;margin:0 0 12px">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    <span style="font-weight:700;font-size:14px;color:var(--gray-800)">Sprint Performance</span>
    <span style="font-size:12px;color:var(--gray-400)">{{ $sprintSheets->count() }} sprints</span>
</div>

@if(count($sprintSummaryParts) > 0)
<p style="font-size:14px;color:var(--gray-700);line-height:1.7;margin:0 0 14px;padding:14px 18px;background:#fff;border:1px solid #e5e7eb;border-radius:10px">{{ implode(' ', $sprintSummaryParts) }}</p>
@endif

<div class="card" style="margin-bottom:24px">
    <table>
        <thead><tr><th>Sprint</th><th>Period</th><th>Planned SP</th><th>Delivered SP</th><th>Accuracy</th><th>Tasks</th></tr></thead>
        <tbody>
        @foreach($sprintSheets as $s)
        @php $acc = $s->planned_points > 0 ? round($s->completed_points / $s->planned_points * 100) : null; @endphp
        <tr>
            <td style="font-weight:500;font-size:13px">{{ $s->sprint_name }}</td>
            <td style="font-size:12px;color:var(--gray-500)">{{ \Carbon\Carbon::parse($s->start_date)->format('M d') }} – {{ \Carbon\Carbon::parse($s->end_date)->format('M d') }}</td>
            <td style="font-size:13px">{{ $s->planned_points }}</td>
            <td style="font-size:13px">{{ $s->completed_points }}</td>
            <td style="font-size:13px;font-weight:700;color:{{ $acc!==null&&$acc>=80?'#059669':($acc!==null&&$acc>=50?'#d97706':'#dc2626') }}">{{ $acc !== null ? $acc.'%' : '—' }}</td>
            <td style="font-size:12px;color:var(--gray-500)">{{ $s->tasks_completed }}/{{ $s->tasks_planned }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Disclaimer --}}
<div style="display:flex;align-items:flex-start;gap:10px;background:#f8fafc;border:1px solid var(--gray-200);border-radius:8px;padding:12px 16px;margin-top:8px">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="var(--gray-400)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span style="font-size:12px;color:var(--gray-500);line-height:1.6">This view shows <strong>factual work patterns</strong> from connected tools — no character judgements are made. Engagement risk levels reflect observable signal trends, not predictions. <strong>Humans decide meaning.</strong></span>
</div>

@endif {{-- end hasAnything --}}
