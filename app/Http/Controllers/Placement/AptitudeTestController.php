<?php

namespace App\Http\Controllers\Placement;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAptitudeTestJob;
use App\Models\AptitudeTest;
use App\Models\PlacementDrive;
use App\Models\TestQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AptitudeTestController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()->currentOrganizationId();
        $tests = AptitudeTest::where('organization_id', $orgId)
            ->with(['drive', 'questions', 'attempts'])
            ->orderByDesc('created_at')
            ->get();

        return view('placement.tests.index', compact('tests'));
    }

    /**
     * Show the configure-and-generate form. Officer picks counts/difficulty,
     * clicks Generate → POST /tests/generate which creates the test + questions.
     */
    public function create(Request $request)
    {
        $orgId  = Auth::user()->currentOrganizationId();
        $drives = PlacementDrive::where('organization_id', $orgId)
            ->whereIn('status', ['draft', 'open'])
            ->orderByDesc('drive_date')
            ->get();

        $selectedDriveId = $request->input('drive');
        return view('placement.tests.create', compact('drives', 'selectedDriveId'));
    }

    /**
     * Kick off async AI test generation. Creates the test row immediately,
     * dispatches a queued job to populate questions, returns JSON with test ID
     * + status URL so the frontend can poll progress.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'placement_drive_id' => 'required|exists:placement_drives,id',
            'title'              => 'nullable|string|max:255',
            'time_limit_minutes' => 'required|integer|min:5|max:240',
            'passing_score_pct'  => 'required|integer|min:0|max:100',
            'num_mcq'            => 'required|integer|min:0|max:50',
            'num_descriptive'    => 'required|integer|min:0|max:15',
            'difficulty'         => 'required|in:easy,medium,hard',
        ]);

        $drive = PlacementDrive::where('organization_id', Auth::user()->currentOrganizationId())
            ->findOrFail($validated['placement_drive_id']);

        if ($validated['num_mcq'] + $validated['num_descriptive'] === 0) {
            return response()->json(['error' => 'At least one question type must have count > 0.'], 422);
        }

        // Create test record up front (empty, status=draft)
        $test = AptitudeTest::create([
            'placement_drive_id' => $drive->id,
            'organization_id'    => $drive->organization_id,
            'title'              => $validated['title'] ?: "{$drive->company_name} Aptitude Test",
            'instructions'       => '',
            'time_limit_minutes' => $validated['time_limit_minutes'],
            'passing_score_pct'  => $validated['passing_score_pct'],
            'status'             => 'draft',
        ]);

        // Seed cache so the polling endpoint sees "running" immediately
        Cache::put(GenerateAptitudeTestJob::cacheKey($test->id), [
            'status' => 'running',
            'phase'  => 'Queued for AI generation…',
        ], now()->addMinutes(10));

        // Dispatch async job
        GenerateAptitudeTestJob::dispatch($test->id, [
            'company_name'     => $drive->company_name,
            'role_title'       => $drive->role_title,
            'role_description' => $drive->description ?? '',
            'required_skills'  => $drive->required_skills ?? [],
            'eligible_courses' => $drive->eligible_courses ?? [],
            'num_mcq'          => $validated['num_mcq'],
            'num_descriptive'  => $validated['num_descriptive'],
            'difficulty'       => $validated['difficulty'],
        ]);

        return response()->json([
            'status'     => 'queued',
            'test_id'    => $test->id,
            'status_url' => route('placement.tests.generationStatus', $test),
        ]);
    }

    /**
     * Status polling endpoint for in-progress AI generation. Returns JSON
     * with current phase, or { status:'complete', redirect: '...' } when done.
     */
    public function generationStatus(AptitudeTest $test)
    {
        $this->authorizeTest($test);
        $state = Cache::get(GenerateAptitudeTestJob::cacheKey($test->id));

        // No cache + has questions = generation finished long ago and cache expired
        if (!$state) {
            $count = $test->questions()->count();
            if ($count > 0) {
                return response()->json([
                    'status'   => 'complete',
                    'redirect' => route('placement.tests.edit', $test),
                ]);
            }
            return response()->json(['status' => 'unknown']);
        }

        return response()->json($state);
    }

    public function show(AptitudeTest $test)
    {
        $this->authorizeTest($test);
        $test->load(['drive', 'questions', 'attempts.candidate']);
        return view('placement.tests.show', compact('test'));
    }

    public function edit(AptitudeTest $test)
    {
        $this->authorizeTest($test);
        $test->load(['drive', 'questions']);
        return view('placement.tests.edit', compact('test'));
    }

    /**
     * Update test-level settings (title, time limit, pass score, instructions, status).
     */
    public function update(Request $request, AptitudeTest $test)
    {
        $this->authorizeTest($test);

        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'instructions'       => 'nullable|string',
            'time_limit_minutes' => 'required|integer|min:5|max:240',
            'passing_score_pct'  => 'required|integer|min:0|max:100',
            'status'             => 'required|in:draft,published,closed',
        ]);

        $test->update($validated);
        return back()->with('success', 'Test settings updated.');
    }

    /**
     * Update a single question. Used by the inline editor.
     */
    public function updateQuestion(Request $request, AptitudeTest $test, TestQuestion $question)
    {
        $this->authorizeTest($test);
        if ($question->aptitude_test_id !== $test->id) abort(404);

        $validated = $request->validate([
            'question_text' => 'required|string',
            'context'       => 'nullable|string',
            'topic'         => 'nullable|string|max:64',
            'difficulty'    => 'required|in:easy,medium,hard',
            'marks'         => 'required|integer|min:0|max:20',
            // MCQ
            'options'         => 'nullable|array',
            'options.*'       => 'nullable|string',
            'correct_option'  => 'nullable|integer|min:0',
            // Descriptive
            'ideal_answer'        => 'nullable|string',
            'rubric_points'       => 'nullable|array',
            'rubric_points.*'     => 'nullable|string',
            'expected_word_count' => 'nullable|integer|min:10|max:1000',
        ]);

        // Strip empty option/rubric strings
        if (isset($validated['options'])) {
            $validated['options'] = array_values(array_filter($validated['options'], fn($o) => trim($o) !== ''));
        }
        if (isset($validated['rubric_points'])) {
            $validated['rubric_points'] = array_values(array_filter($validated['rubric_points'], fn($p) => trim($p) !== ''));
        }

        $question->update($validated);
        return back()->with('success', 'Question updated.');
    }

    public function destroyQuestion(AptitudeTest $test, TestQuestion $question)
    {
        $this->authorizeTest($test);
        if ($question->aptitude_test_id !== $test->id) abort(404);
        $question->delete();
        return back()->with('success', 'Question removed.');
    }

    /**
     * Add a new blank question to the test (officer fills it in via the edit page).
     */
    public function addQuestion(Request $request, AptitudeTest $test)
    {
        $this->authorizeTest($test);
        $type = $request->input('type', 'mcq');
        if (!in_array($type, ['mcq', 'descriptive'], true)) abort(422);

        $maxOrder = $test->questions()->max('order') ?? 0;
        $base = [
            'aptitude_test_id' => $test->id,
            'order'            => $maxOrder + 1,
            'type'             => $type,
            'question_text'    => 'New question — click edit to fill in',
            'difficulty'       => 'medium',
            'marks'            => 1,
        ];
        if ($type === 'mcq') {
            $base['options']        = ['Option A', 'Option B', 'Option C', 'Option D'];
            $base['correct_option'] = 0;
        } else {
            $base['ideal_answer']        = '';
            $base['rubric_points']       = [];
            $base['expected_word_count'] = 100;
        }
        TestQuestion::create($base);
        return back()->with('success', 'Question added — fill in the details.');
    }

    public function publish(AptitudeTest $test)
    {
        $this->authorizeTest($test);
        if ($test->questions()->count() === 0) {
            return back()->with('error', 'Cannot publish a test with no questions.');
        }
        $test->update(['status' => 'published']);
        return back()->with('success', 'Test published. Share the public link with students.');
    }

    public function unpublish(AptitudeTest $test)
    {
        $this->authorizeTest($test);
        $test->update(['status' => 'draft']);
        return back()->with('success', 'Test moved back to draft.');
    }

    public function destroy(AptitudeTest $test)
    {
        $this->authorizeTest($test);
        $title = $test->title;
        $test->delete();
        return redirect()->route('placement.tests.index')->with('success', "Test '{$title}' deleted.");
    }

    private function authorizeTest(AptitudeTest $test): void
    {
        if ($test->organization_id !== Auth::user()->currentOrganizationId()) abort(403);
    }
}
