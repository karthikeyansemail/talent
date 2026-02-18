<?php

namespace App\Http\Controllers\Intelligence;

use App\Http\Controllers\Controller;
use App\Models\IntegrationConnection;
use App\Models\SignalSource;
use App\Models\SprintSheet;
use App\Services\SpreadsheetParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SignalConfigController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()->currentOrganizationId();

        $signalSources = SignalSource::where('organization_id', $orgId)->get();
        $integrationConnections = IntegrationConnection::where('organization_id', $orgId)->get();

        return view('intelligence.config', compact('signalSources', 'integrationConnections'));
    }

    public function storeSource(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:jira,zoho_projects,slack,teams,sprint_sheet',
            'name' => 'required|string|max:255',
        ]);

        $validated['organization_id'] = Auth::user()->currentOrganizationId();
        SignalSource::create($validated);

        return back()->with('success', 'Signal source added.');
    }

    public function destroySource(SignalSource $source)
    {
        if ($source->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        $source->delete();
        return back()->with('success', 'Signal source removed.');
    }

    public function storeIntegration(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:slack,teams',
            'name' => 'required|string|max:255',
            'webhook_url' => 'nullable|url',
            'api_token' => 'nullable|string',
        ]);

        IntegrationConnection::create([
            'organization_id' => Auth::user()->currentOrganizationId(),
            'type' => $validated['type'],
            'name' => $validated['name'],
            'credentials' => array_filter([
                'webhook_url' => $validated['webhook_url'] ?? null,
                'api_token' => $validated['api_token'] ?? null,
            ]),
        ]);

        return back()->with('success', "{$validated['name']} integration added.");
    }

    public function destroyIntegration(IntegrationConnection $connection)
    {
        if ($connection->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        $connection->delete();
        return back()->with('success', 'Integration removed.');
    }

    public function uploadSprintSheet(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,txt|max:5120',
        ]);

        $orgId = Auth::user()->currentOrganizationId();
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === 'txt') {
            $ext = 'csv';
        }

        $path = $file->store('imports/sprints', 'public');
        $fullPath = Storage::disk('public')->path($path);

        try {
            $parser = new SpreadsheetParser();
            $rows = $parser->parse($fullPath, $ext);
        } catch (\Exception $e) {
            Storage::disk('public')->delete($path);
            return back()->with('error', 'Failed to parse file: ' . $e->getMessage());
        }

        Storage::disk('public')->delete($path);

        if (empty($rows)) {
            return back()->with('error', 'No data rows found.');
        }

        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $email = $row['employee_email'] ?? '';
            $sprintName = $row['sprint_name'] ?? '';

            if (!$email || !$sprintName) {
                $skipped++;
                continue;
            }

            $employee = \App\Models\Employee::where('organization_id', $orgId)
                ->where('email', $email)
                ->first();

            if (!$employee) {
                $skipped++;
                continue;
            }

            SprintSheet::updateOrCreate(
                [
                    'organization_id' => $orgId,
                    'sprint_name' => $sprintName,
                    'employee_id' => $employee->id,
                ],
                [
                    'start_date' => !empty($row['start_date']) ? $row['start_date'] : null,
                    'end_date' => !empty($row['end_date']) ? $row['end_date'] : null,
                    'planned_points' => isset($row['planned_points']) && is_numeric($row['planned_points']) ? (int) $row['planned_points'] : null,
                    'completed_points' => isset($row['completed_points']) && is_numeric($row['completed_points']) ? (int) $row['completed_points'] : null,
                    'tasks_planned' => isset($row['tasks_planned']) && is_numeric($row['tasks_planned']) ? (int) $row['tasks_planned'] : null,
                    'tasks_completed' => isset($row['tasks_completed']) && is_numeric($row['tasks_completed']) ? (int) $row['tasks_completed'] : null,
                ]
            );

            $imported++;
        }

        $message = "Sprint sheet processed: {$imported} entries imported";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped";
        }

        return back()->with('success', $message);
    }
}
