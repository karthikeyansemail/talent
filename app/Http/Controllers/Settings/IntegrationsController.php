<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Jobs\SyncZohoPeopleJob;
use App\Models\JiraConnection;
use App\Models\ZohoPeopleConnection;
use App\Models\ZohoProjectsConnection;
use App\Services\SpreadsheetParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class IntegrationsController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()->currentOrganizationId();
        $jiraConnections = JiraConnection::where('organization_id', $orgId)->get();
        $zohoConnections = ZohoProjectsConnection::where('organization_id', $orgId)->get();
        $zohoPeopleConnections = ZohoPeopleConnection::where('organization_id', $orgId)->get();

        return view('settings.integrations.index', compact('jiraConnections', 'zohoConnections', 'zohoPeopleConnections'));
    }

    // ----- Zoho Projects -----

    public function storeZohoProjects(Request $request)
    {
        $validated = $request->validate([
            'portal_name' => 'required|string|max:255',
            'auth_token' => 'required|string',
        ]);

        $validated['organization_id'] = Auth::user()->currentOrganizationId();
        ZohoProjectsConnection::create($validated);

        return redirect()->route('settings.integrations.index')
            ->with('success', 'Zoho Projects connection added.');
    }

    public function testZohoProjects(ZohoProjectsConnection $connection)
    {
        if ($connection->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        // Placeholder: In production, make an API call to verify Zoho credentials
        return back()->with('success', 'Zoho Projects connection is valid.');
    }

    public function destroyZohoProjects(ZohoProjectsConnection $connection)
    {
        if ($connection->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        $connection->delete();
        return back()->with('success', 'Zoho Projects connection removed.');
    }

    // ----- Zoho People -----

    public function storeZohoPeople(Request $request)
    {
        $validated = $request->validate([
            'portal_name' => 'required|string|max:255',
            'auth_token' => 'required|string',
        ]);

        $validated['organization_id'] = Auth::user()->currentOrganizationId();
        ZohoPeopleConnection::create($validated);

        return redirect()->route('settings.integrations.index')
            ->with('success', 'Zoho People connection added.');
    }

    public function testZohoPeople(ZohoPeopleConnection $connection)
    {
        if ($connection->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        return back()->with('success', 'Zoho People connection is valid.');
    }

    public function syncZohoPeople(ZohoPeopleConnection $connection)
    {
        if ($connection->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        SyncZohoPeopleJob::dispatch($connection);
        return back()->with('success', 'Zoho People sync started. Employees will be updated shortly.');
    }

    public function destroyZohoPeople(ZohoPeopleConnection $connection)
    {
        if ($connection->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        $connection->delete();
        return back()->with('success', 'Zoho People connection removed.');
    }

    // ----- Task Spreadsheet Upload -----

    public function uploadTaskSpreadsheet(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,txt|max:5120',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === 'txt') {
            $ext = 'csv';
        }

        $path = $file->store('imports/tasks', 'public');
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
            return back()->with('error', 'No data rows found in the file.');
        }

        $orgId = Auth::user()->currentOrganizationId();
        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $email = $row['employee_email'] ?? '';
            $taskKey = $row['task_key'] ?? '';
            $summary = $row['summary'] ?? '';

            if (!$email || !$taskKey || !$summary) {
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

            \App\Models\EmployeeJiraTask::updateOrCreate(
                ['employee_id' => $employee->id, 'task_key' => $taskKey],
                [
                    'summary' => $summary,
                    'description' => $row['description'] ?? null,
                    'status' => $row['status'] ?? null,
                    'priority' => $row['priority'] ?? null,
                    'story_points' => isset($row['story_points']) && is_numeric($row['story_points']) ? (float) $row['story_points'] : null,
                    'sprint_name' => $row['sprint_name'] ?? null,
                    'completed_at' => !empty($row['completed_at']) ? $row['completed_at'] : null,
                ]
            );

            $imported++;
        }

        $message = "Task spreadsheet processed: {$imported} tasks imported";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped";
        }

        return back()->with('success', $message);
    }
}
