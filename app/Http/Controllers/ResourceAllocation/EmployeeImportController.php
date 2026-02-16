<?php

namespace App\Http\Controllers\ResourceAllocation;

use App\Http\Controllers\Controller;
use App\Jobs\SyncZohoPeopleJob;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ZohoPeopleConnection;
use App\Services\SpreadsheetParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmployeeImportController extends Controller
{
    public function showImport()
    {
        return view('employees.import');
    }

    public function uploadSpreadsheet(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,txt|max:5120',
        ]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === 'txt') {
            $ext = 'csv'; // treat .txt as CSV
        }

        $path = $file->store('imports/employees', 'public');
        $fullPath = Storage::disk('public')->path($path);

        $parser = new SpreadsheetParser();
        $rows = $parser->parse($fullPath, $ext);

        if (empty($rows)) {
            Storage::disk('public')->delete($path);
            return back()->with('error', 'No data rows found in the file.');
        }

        // Validate required columns
        $required = ['first_name', 'last_name', 'email'];
        $headers = array_keys($rows[0]);
        $missing = array_diff($required, $headers);

        if (!empty($missing)) {
            Storage::disk('public')->delete($path);
            return back()->with('error', 'Missing required columns: ' . implode(', ', $missing));
        }

        // Store parsed data in session for confirmation
        session([
            'employee_import_data' => $rows,
            'employee_import_file' => $path,
        ]);

        return view('employees.import', [
            'preview' => array_slice($rows, 0, 10),
            'totalRows' => count($rows),
            'headers' => $headers,
        ]);
    }

    public function confirmImport(Request $request)
    {
        $rows = session('employee_import_data');
        $filePath = session('employee_import_file');

        if (!$rows) {
            return redirect()->route('employees.import')->with('error', 'No import data found. Please upload again.');
        }

        $orgId = Auth::user()->organization_id;
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $email = trim($row['email'] ?? '');
            $firstName = trim($row['first_name'] ?? '');
            $lastName = trim($row['last_name'] ?? '');

            if (!$email || !$firstName || !$lastName) {
                $skipped++;
                continue;
            }

            // Check if employee with this email already exists in this org
            $exists = Employee::where('organization_id', $orgId)
                ->where('email', $email)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            // Resolve department
            $departmentId = null;
            if (!empty($row['department'])) {
                $dept = Department::firstOrCreate(
                    ['organization_id' => $orgId, 'name' => trim($row['department'])],
                    ['description' => '']
                );
                $departmentId = $dept->id;
            }

            // Parse skills
            $skills = [];
            if (!empty($row['skills'])) {
                $skills = array_map('trim', explode(',', $row['skills']));
            }

            Employee::create([
                'organization_id' => $orgId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'department_id' => $departmentId,
                'designation' => trim($row['designation'] ?? '') ?: null,
                'skills_from_resume' => !empty($skills) ? $skills : null,
                'is_active' => true,
                'import_source' => 'spreadsheet',
            ]);

            $imported++;
        }

        // Clean up
        if ($filePath) {
            Storage::disk('public')->delete($filePath);
        }
        session()->forget(['employee_import_data', 'employee_import_file']);

        $message = "Import complete: {$imported} employees imported";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped (duplicates or missing data)";
        }

        return redirect()->route('employees.index')->with('success', $message);
    }

    public function syncZohoPeople()
    {
        $orgId = Auth::user()->organization_id;
        $connection = ZohoPeopleConnection::where('organization_id', $orgId)
            ->where('is_active', true)
            ->first();

        if (!$connection) {
            return back()->with('error', 'No active Zoho People connection found. Configure one in Settings > Integrations.');
        }

        SyncZohoPeopleJob::dispatch($connection);
        return redirect()->route('employees.index')
            ->with('success', 'Zoho People sync started. Employees will be updated shortly.');
    }

    public function downloadTemplate()
    {
        $headers = ['first_name', 'last_name', 'email', 'department', 'designation', 'skills'];
        $example = ['John', 'Doe', 'john.doe@example.com', 'Engineering', 'Senior Developer', 'PHP,Laravel,JavaScript'];

        $content = implode(',', $headers) . "\n" . implode(',', $example) . "\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employee_import_template.csv"',
        ]);
    }
}
