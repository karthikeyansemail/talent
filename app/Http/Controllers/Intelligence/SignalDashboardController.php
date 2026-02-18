<?php

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Jobs\ComputeEmployeeSignalsJob;
use App\Models\Employee;
use App\Models\EmployeeSignal;
use App\Models\SignalSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SignalDashboardController extends Controller
{
    public function index(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();
        $period = $request->input('period', Carbon::now()->format('Y-\\WW'));

        // Get all employees with their latest signal snapshots
        $employees = Employee::where('organization_id', $orgId)
            ->where('is_active', true)
            ->with(['department'])
            ->get()
            ->map(function ($employee) use ($period) {
                $employee->snapshot = SignalSnapshot::where('employee_id', $employee->id)
                    ->where('period', $period)
                    ->first();
                $employee->signal_count = EmployeeSignal::where('employee_id', $employee->id)
                    ->where('period', $period)
                    ->count();
                return $employee;
            });

        // Org-level aggregates
        $snapshots = SignalSnapshot::where('organization_id', $orgId)
            ->where('period', $period)
            ->get();

        $orgStats = [
            'total_employees' => $employees->count(),
            'employees_with_signals' => $employees->where('signal_count', '>', 0)->count(),
            'avg_consistency' => $snapshots->avg('consistency_index'),
            'avg_workload_pressure' => $snapshots->avg('workload_pressure'),
            'avg_collaboration' => $snapshots->avg('collaboration_density'),
        ];

        return view('intelligence.dashboard', compact('employees', 'orgStats', 'period'));
    }

    public function employeeSignals(Employee $employee, Request $request)
    {
        if ($employee->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        $period = $request->input('period', Carbon::now()->format('Y-\\WW'));

        $signals = EmployeeSignal::where('employee_id', $employee->id)
            ->where('period', $period)
            ->orderBy('source_type')
            ->orderBy('metric_key')
            ->get();

        $snapshot = SignalSnapshot::where('employee_id', $employee->id)
            ->where('period', $period)
            ->first();

        // Get historical snapshots for trend
        $history = SignalSnapshot::where('employee_id', $employee->id)
            ->orderBy('period', 'desc')
            ->limit(12)
            ->get()
            ->reverse();

        $employee->load('department');

        return view('intelligence.employee', compact('employee', 'signals', 'snapshot', 'history', 'period'));
    }

    public function computeSignals(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();
        $period = $request->input('period', Carbon::now()->format('Y-\\WW'));

        $employees = Employee::where('organization_id', $orgId)
            ->where('is_active', true)
            ->get();

        foreach ($employees as $employee) {
            ComputeEmployeeSignalsJob::dispatch($employee, $period);
        }

        return back()->with('success', "Signal computation queued for {$employees->count()} employees (period: {$period}).");
    }
}
