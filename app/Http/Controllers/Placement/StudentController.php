<?php

namespace App\Http\Controllers\Placement;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    private const CSV_HEADERS = [
        'first_name', 'last_name', 'email', 'enrollment_number',
        'course', 'batch_year', 'phone', 'skills',
    ];

    public function index(Request $request)
    {
        $orgId = Auth::user()->currentOrganizationId();
        $query = Candidate::where('organization_id', $orgId);

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

        $students = $query->orderBy('first_name')->paginate(50)->withQueryString();

        // Filter dropdowns
        $batches = Candidate::where('organization_id', $orgId)
            ->whereNotNull('batch_year')->distinct()->orderByDesc('batch_year')->pluck('batch_year');
        $courses = Candidate::where('organization_id', $orgId)
            ->whereNotNull('course')->distinct()->orderBy('course')->pluck('course');

        return view('placement.students.index', compact('students', 'batches', 'courses'));
    }

    public function create()
    {
        return view('placement.students.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'        => 'required|string|max:64',
            'last_name'         => 'required|string|max:64',
            'email'             => 'required|email|unique:candidates,email',
            'enrollment_number' => 'nullable|string|max:64',
            'course'            => 'nullable|string|max:128',
            'batch_year'        => 'nullable|integer|min:2000|max:2100',
            'phone'             => 'nullable|string|max:32',
            'skills'            => 'nullable|string',
        ]);

        Candidate::create([
            'organization_id'   => Auth::user()->currentOrganizationId(),
            'first_name'        => $validated['first_name'],
            'last_name'         => $validated['last_name'],
            'email'             => $validated['email'],
            'enrollment_number' => $validated['enrollment_number'] ?? null,
            'course'            => $validated['course'] ?? null,
            'batch_year'        => $validated['batch_year'] ?? null,
            'phone'             => $validated['phone'] ?? null,
            'skills'            => $this->csvToArray($validated['skills'] ?? ''),
            'source'            => 'upload',
        ]);

        return redirect()->route('placement.students.index')->with('success', 'Student added.');
    }

    /**
     * Stream a CSV template with the expected headers + one example row.
     */
    public function template(): StreamedResponse
    {
        $headers = self::CSV_HEADERS;
        $example = [
            'Aditya', 'Sharma', 'aditya.sharma@example.edu',
            'CSE_2026_001', 'B.Tech CSE', '2026',
            '+91-9876543210', 'Python; DSA; SQL',
        ];

        return response()->streamDownload(function () use ($headers, $example) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, $example);
            fclose($out);
        }, 'students-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function bulkUpload()
    {
        return view('placement.students.bulk-upload');
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
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

        $created = 0;
        $skipped = 0;
        $errors  = [];

        while (($row = fgetcsv($handle)) !== false) {
            $email = trim($row[$colIdx['email']] ?? '');
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }

            // Check for duplicate by email within this org
            $exists = Candidate::where('organization_id', $orgId)
                ->where('email', $email)->exists();
            if ($exists) {
                $skipped++;
                continue;
            }

            $skills = isset($colIdx['skills'])
                ? collect(preg_split('/[;,]/', $row[$colIdx['skills']] ?? ''))
                    ->map(fn($s) => trim($s))->filter()->values()->all()
                : [];

            try {
                Candidate::create([
                    'organization_id'   => $orgId,
                    'first_name'        => trim($row[$colIdx['first_name']] ?? ''),
                    'last_name'         => isset($colIdx['last_name']) ? trim($row[$colIdx['last_name']] ?? '') : '',
                    'email'             => $email,
                    'enrollment_number' => isset($colIdx['enrollment_number']) ? trim($row[$colIdx['enrollment_number']] ?? '') : null,
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
