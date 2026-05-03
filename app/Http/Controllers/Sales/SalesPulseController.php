<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSignal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Sales Pulse — dashboard for CRM activity signals.
 * Reads from EmployeeSignal directly (no live CRM sync required).
 *
 * The same metric_keys are written by both:
 *   - Real CRM sync jobs (SyncSalesforceCrmJob, SyncHubspotCrmJob, SyncZohoCrmJob)
 *   - Demo refresher (RefreshDemoData::generateCrmSignals)
 *
 * So this view works whether the org has a real CRM connected or not.
 */
class SalesPulseController extends Controller
{
    /**
     * Metric keys we render as KPI cards. Defined in one place so both
     * the dashboard view and any future CSV export use the same set.
     */
    private const CRM_METRIC_KEYS = [
        'deals_closed_count'  => ['label' => 'Deals Closed',     'unit' => 'count', 'icon' => 'trophy'],
        'deals_won_value'     => ['label' => 'Revenue Won',      'unit' => 'usd',   'icon' => 'dollar'],
        'pipeline_value_usd'  => ['label' => 'Open Pipeline',    'unit' => 'usd',   'icon' => 'pipeline'],
        'calls_made_count'    => ['label' => 'Calls Made',       'unit' => 'count', 'icon' => 'phone'],
        'emails_sent_count'   => ['label' => 'Emails Sent',      'unit' => 'count', 'icon' => 'mail'],
        'meetings_held_count' => ['label' => 'Meetings Held',    'unit' => 'count', 'icon' => 'users'],
    ];

    public function index()
    {
        $orgId = Auth::user()->currentOrganizationId();
        if (!$orgId) {
            abort(404);
        }

        // Latest period that has any CRM signal data for this org
        $latestPeriod = EmployeeSignal::where('organization_id', $orgId)
            ->whereIn('metric_key', array_keys(self::CRM_METRIC_KEYS))
            ->max('period');

        $previousPeriod = EmployeeSignal::where('organization_id', $orgId)
            ->whereIn('metric_key', array_keys(self::CRM_METRIC_KEYS))
            ->where('period', '<', $latestPeriod ?? '')
            ->max('period');

        $employees = Employee::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        // Fetch signals for current and previous period in one query
        $signalRows = EmployeeSignal::where('organization_id', $orgId)
            ->whereIn('metric_key', array_keys(self::CRM_METRIC_KEYS))
            ->whereIn('period', array_filter([$latestPeriod, $previousPeriod]))
            ->get();

        // Index: [employee_id][period][metric_key] = value
        $byEmployee = [];
        foreach ($signalRows as $row) {
            $byEmployee[$row->employee_id][$row->period][$row->metric_key] = (float) $row->metric_value;
        }

        // Build per-rep rows for the table
        $reps = [];
        foreach ($employees as $emp) {
            $current = $byEmployee[$emp->id][$latestPeriod] ?? [];
            $prev    = $byEmployee[$emp->id][$previousPeriod] ?? [];

            // Only show reps that actually have CRM data — skip employees with no sales activity
            if (empty($current)) {
                continue;
            }

            $reps[] = [
                'employee'      => $emp,
                'current'       => $current,
                'previous'      => $prev,
                'has_prev'      => !empty($prev),
            ];
        }

        // Team totals across all reps for the current period
        $teamTotals = [];
        foreach (array_keys(self::CRM_METRIC_KEYS) as $key) {
            $teamTotals[$key] = 0;
            foreach ($reps as $row) {
                $teamTotals[$key] += $row['current'][$key] ?? 0;
            }
        }

        // Previous-period totals for delta arrows
        $teamTotalsPrev = [];
        foreach (array_keys(self::CRM_METRIC_KEYS) as $key) {
            $teamTotalsPrev[$key] = 0;
            foreach ($reps as $row) {
                $teamTotalsPrev[$key] += $row['previous'][$key] ?? 0;
            }
        }

        return view('sales-pulse.index', [
            'metrics'         => self::CRM_METRIC_KEYS,
            'reps'            => $reps,
            'teamTotals'      => $teamTotals,
            'teamTotalsPrev'  => $teamTotalsPrev,
            'latestPeriod'    => $latestPeriod,
            'previousPeriod'  => $previousPeriod,
            'orgName'         => Auth::user()->currentOrganization()?->name ?? '',
        ]);
    }
}
