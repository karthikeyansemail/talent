<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSignal;
use Illuminate\Support\Facades\Auth;

/**
 * Support Pulse — dashboard for customer-support agent activity.
 * Reads from EmployeeSignal directly (no live Zendesk/Freshdesk required).
 *
 * The same metric_keys are written by:
 *   - Real support sync jobs (SyncZendeskSupportJob, SyncFreshdeskSupportJob)
 *   - Demo refresher (RefreshDemoData::generateSupportSignals)
 */
class SupportPulseController extends Controller
{
    private const SUPPORT_METRIC_KEYS = [
        'tickets_resolved_count'   => ['label' => 'Tickets Resolved',  'unit' => 'count',   'higher_better' => true],
        'tickets_assigned_count'   => ['label' => 'Tickets Assigned',  'unit' => 'count',   'higher_better' => true],
        'tickets_open_count'       => ['label' => 'Open Tickets',      'unit' => 'count',   'higher_better' => false],
        'first_response_time_min'  => ['label' => 'Avg First Response','unit' => 'minutes', 'higher_better' => false],
        'avg_resolution_time_min'  => ['label' => 'Avg Resolution',    'unit' => 'minutes', 'higher_better' => false],
        'csat_score'               => ['label' => 'CSAT Score',        'unit' => 'percent', 'higher_better' => true],
        'reopen_rate_pct'          => ['label' => 'Reopen Rate',       'unit' => 'percent', 'higher_better' => false],
    ];

    public function index()
    {
        $orgId = Auth::user()->currentOrganizationId();
        if (!$orgId) {
            abort(404);
        }

        $latestPeriod = EmployeeSignal::where('organization_id', $orgId)
            ->whereIn('metric_key', array_keys(self::SUPPORT_METRIC_KEYS))
            ->max('period');

        $previousPeriod = EmployeeSignal::where('organization_id', $orgId)
            ->whereIn('metric_key', array_keys(self::SUPPORT_METRIC_KEYS))
            ->where('period', '<', $latestPeriod ?? '')
            ->max('period');

        $employees = Employee::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $signalRows = EmployeeSignal::where('organization_id', $orgId)
            ->whereIn('metric_key', array_keys(self::SUPPORT_METRIC_KEYS))
            ->whereIn('period', array_filter([$latestPeriod, $previousPeriod]))
            ->get();

        $byEmployee = [];
        foreach ($signalRows as $row) {
            $byEmployee[$row->employee_id][$row->period][$row->metric_key] = (float) $row->metric_value;
        }

        $agents = [];
        foreach ($employees as $emp) {
            $current = $byEmployee[$emp->id][$latestPeriod] ?? [];
            $prev    = $byEmployee[$emp->id][$previousPeriod] ?? [];
            if (empty($current)) continue;
            $agents[] = [
                'employee' => $emp,
                'current'  => $current,
                'previous' => $prev,
                'has_prev' => !empty($prev),
            ];
        }

        // Team aggregates: sum counts, average percents/minutes
        $teamCurrent = [];
        $teamPrev = [];
        foreach (array_keys(self::SUPPORT_METRIC_KEYS) as $key) {
            $sumCurr = 0; $sumPrev = 0; $countCurr = 0; $countPrev = 0;
            foreach ($agents as $a) {
                if (isset($a['current'][$key])) { $sumCurr += $a['current'][$key]; $countCurr++; }
                if (isset($a['previous'][$key])) { $sumPrev += $a['previous'][$key]; $countPrev++; }
            }
            $isAvgMetric = in_array(self::SUPPORT_METRIC_KEYS[$key]['unit'], ['percent', 'minutes'], true);
            $teamCurrent[$key] = $isAvgMetric && $countCurr > 0 ? $sumCurr / $countCurr : $sumCurr;
            $teamPrev[$key]    = $isAvgMetric && $countPrev > 0 ? $sumPrev / $countPrev : $sumPrev;
        }

        return view('support-pulse.index', [
            'metrics'        => self::SUPPORT_METRIC_KEYS,
            'agents'         => $agents,
            'teamCurrent'    => $teamCurrent,
            'teamPrev'       => $teamPrev,
            'latestPeriod'   => $latestPeriod,
            'previousPeriod' => $previousPeriod,
            'orgName'        => Auth::user()->currentOrganization()?->name ?? '',
        ]);
    }
}
