<?php

namespace App\Http\Controllers\Placement;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    private const CSV_HEADERS = [
        'first_name', 'last_name', 'email', 'enrollment_number',
        'department', 'course', 'batch_year', 'phone', 'skills',
    ];

    public function index(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();
        $query = Candidate::where('organization_id', $orgId)->with('department');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('enrollment_number', 'like', "%{$search}%");
            });
        }

        if ($batch = $request->input('batch')) {
            $query->where('batch_year', $batch);
        }
        if ($course = $request->input('course')) {
            $query->where('course', $course);
        }
        if ($deptId = $request->input('department')) {
            $query->where('department_id', $deptId);
        }

        $students = $query->orderBy('first_name')->paginate(50)->withQueryString();

        // Filter dropdowns
        $batches = Candidate::where('organization_id', $orgId)
            ->whereNotNull('batch_year')->distinct()->orderByDesc('batch_year')->pluck('batch_year');
        $courses = Candidate::where('organization_id', $orgId)
            ->whereNotNull('course')->distinct()->orderBy('course')->pluck('course');
        $departments = Department::where('organization_id', $orgId)->orderBy('name')->get();

        // Per-department counts for the dept-grouped summary panel
        $deptCounts = Candidate::where('organization_id', $orgId)
            ->whereNotNull('department_id')
            ->selectRaw('department_id, COUNT(*) as cnt')
            ->groupBy('department_id')
            ->pluck('cnt', 'department_id');

        return view('placement.students.index', compact('students', 'batches', 'courses', 'departments', 'deptCounts'));
    }

    public function create(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();
        $departments = Department::where('organization_id', $orgId)->orderBy('name')->get();
        $preselectDept = $request->input('department');
        return view('placement.students.create', compact('departments', 'preselectDept'));
    }

    public function store(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();

        $validated = $request->validate([
            'first_name'        => 'required|string|max:64',
            'last_name'         => 'required|string|max:64',
            'email'             => 'required|email|unique:candidates,email',
            'enrollment_number' => 'nullable|string|max:64',
            'department_id'     => 'nullable|exists:departments,id',
            'course'            => 'nullable|string|max:128',
            'batch_year'        => 'nullable|integer|min:2000|max:2100',
            'phone'             => 'nullable|string|max:32',
            'skills'            => 'nullable|string',
        ]);

        // Authorize department belongs to this org
        if (!empty($validated['department_id'])) {
            $dept = Department::where('id', $validated['department_id'])->where('organization_id', $orgId)->first();
            if (!$dept) {
                return back()->with('error', 'Invalid department.')->withInput();
            }
        }

        Candidate::create([
            'organization_id'   => $orgId,
            'first_name'        => $validated['first_name'],
            'last_name'         => $validated['last_name'],
            'email'             => $validated['email'],
            'enrollment_number' => $validated['enrollment_number'] ?? null,
            'department_id'     => $validated['department_id'] ?? null,
            'course'            => $validated['course'] ?? null,
            'batch_year'        => $validated['batch_year'] ?? null,
            'phone'             => $validated['phone'] ?? null,
            'skills'            => $this->csvToArray($validated['skills'] ?? ''),
            'source'            => 'upload',
        ]);

        return redirect()->route('placement.students.index')->with('success', 'Student added.');
    }

    /**
     * Stream a CSV template with the expected headers + a few example rows
     * spanning multiple departments so users see the format clearly.
     */
    public function template(): StreamedResponse
    {
        $headers = self::CSV_HEADERS;
        $examples = [
            ['Aditya',  'Sharma',     'aditya.sharma@example.edu',  'CSE2026001', 'Computer Science & Engineering', 'B.Tech CSE',         '2026', '+91-9876543210', 'Python; DSA; SQL'],
            ['Riya',    'Gupta',      'riya.gupta@example.edu',     'ECE2026010', 'Electronics & Communication',    'B.Tech ECE',         '2026', '+91-9876543211', 'VLSI; Embedded C; MATLAB'],
            ['Naveen',  'Subhash',    'naveen.subhash@example.edu', 'MTRX2026018','Mechatronics',                   'B.Tech Mechatronics','2026', '+91-9876543212', 'Robotics; ROS; PLC'],
            ['Vishal',  'D Souza',    'vishal.dsouza@example.edu',  'MAR2026022', 'Marine Engineering',             'B.Tech Marine Eng.', '2026', '+91-9876543213', 'Marine Engines; Ship Design'],
        ];

        return response()->streamDownload(function () use ($headers, $examples) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($examples as $row) fputcsv($out, $row);
            fclose($out);
        }, 'students-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function bulkUpload(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();
        $departments = Department::where('organization_id', $orgId)->orderBy('name')->get();
        // If invoked from the "Manage" link of a specific department, pre-select it
        $preselectDept = $request->input('department');
        return view('placement.students.bulk-upload', compact('departments', 'preselectDept'));
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'csv_file'             => 'required|file|mimes:csv,txt|max:5120',
            'default_department_id' => 'nullable|exists:departments,id',
        ]);

        $orgId = Auth::user()->currentOrganizationId();
        $file = $request->file('csv_file');
        $handle = fopen($file->getPathname(), 'r');

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'CSV is empty.');
        }
        $header = array_map(fn($h) => trim(strtolower($h)), $header);

        // Map column index by header name (tolerant — accepts any subset)
        $colIdx = [];
        foreach (self::CSV_HEADERS as $key) {
            $idx = array_search($key, $header, true);
            if ($idx !== false) {
                $colIdx[$key] = $idx;
            }
        }

        if (!isset($colIdx['email']) || !isset($colIdx['first_name'])) {
            fclose($handle);
            return back()->with('error', 'CSV must contain at least first_name and email columns.');
        }

        // Pre-load all departments for this org as a name → id map (case-insensitive)
        $deptMap = Department::where('organization_id', $orgId)
            ->get(['id', 'name'])
            ->mapWithKeys(fn($d) => [strtolower(trim($d->name)) => $d->id])
            ->all();

        $defaultDeptId = $request->input('default_department_id') ?: null;
        if ($defaultDeptId) {
            // Verify default belongs to org
            if (!Department::where('id', $defaultDeptId)->where('organization_id', $orgId)->exists()) {
                $defaultDeptId = null;
            }
        }

        $created = 0;
        $skipped = 0;
        $unknownDepts = [];
        $errors  = [];

        while (($row = fgetcsv($handle)) !== false) {
            $email = trim($row[$colIdx['email']] ?? '');
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            $exists = Candidate::where('organization_id', $orgId)->where('email', $email)->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            $skills = isset($colIdx['skills'])
                ? collect(preg_split('/[;,]/', $row[$colIdx['skills']] ?? ''))
                    ->map(fn($s) => trim($s))->filter()->values()->all()
                : [];

            // Resolve department: per-row "department" column wins, else use upload-form default
            $deptId = $defaultDeptId;
            if (isset($colIdx['department'])) {
                $deptName = trim($row[$colIdx['department']] ?? '');
                if ($deptName) {
                    $matchedId = $deptMap[strtolower($deptName)] ?? null;
                    if ($matchedId) {
                        $deptId = $matchedId;
                    } else {
                        $unknownDepts[$deptName] = true;
                    }
                }
            }

            try {
                Candidate::create([
                    'organization_id'   => $orgId,
                    'first_name'        => trim($row[$colIdx['first_name']] ?? ''),
                    'last_name'         => isset($colIdx['last_name']) ? trim($row[$colIdx['last_name']] ?? '') : '',
                    'email'             => $email,
                    'enrollment_number' => isset($colIdx['enrollment_number']) ? trim($row[$colIdx['enrollment_number']] ?? '') : null,
                    'department_id'     => $deptId,
                    'course'            => isset($colIdx['course']) ? trim($row[$colIdx['course']] ?? '') : null,
                    'batch_year'        => isset($colIdx['batch_year']) ? (int) trim($row[$colIdx['batch_year']] ?? 0) ?: null : null,
                    'phone'             => isset($colIdx['phone']) ? trim($row[$colIdx['phone']] ?? '') : null,
                    'skills'            => $skills,
                    'source'            => 'upload',
                ]);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$email}: " . $e->getMessage();
                $skipped++;
            }
        }
        fclose($handle);

        $msg = "Imported {$created} students. Skipped {$skipped} (duplicates or invalid).";
        if (!empty($unknownDepts)) {
            $msg .= ' Unknown departments (left unassigned): ' . implode(', ', array_slice(array_keys($unknownDepts), 0, 5));
            if (count($unknownDepts) > 5) $msg .= ' (+' . (count($unknownDepts) - 5) . ' more)';
        }
        if (!empty($errors)) {
            $msg .= ' First errors: ' . implode('; ', array_slice($errors, 0, 3));
        }
        return redirect()->route('placement.students.index')->with('success', $msg);
    }

    public function destroy(Candidate $student)
    {
        if ($student->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }
        $student->delete();
        return back()->with('success', 'Student removed.');
    }

    private function csvToArray(string $csv): array
    {
        return collect(preg_split('/[;,]/', $csv))
            ->map(fn($s) => trim($s))->filter()->values()->all();
    }
}
