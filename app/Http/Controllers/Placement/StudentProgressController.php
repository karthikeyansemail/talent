<?php

namespace App\Http\Controllers\Placement;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\TestAttempt;
use Illuminate\Support\Facades\Auth;

class StudentProgressController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()->currentOrganizationId();
        $students = Candidate::where('organization_id', $orgId)
            ->orderBy('first_name')
            ->get();

        // Aggregate test attempts per student for cohort overview
        $attemptStats = TestAttempt::where('organization_id', $orgId)
            ->whereNotNull('score_pct')
            ->selectRaw('candidate_id, COUNT(*) as attempts, AVG(score_pct) as avg_score, MAX(submitted_at) as last_attempt')
            ->groupBy('candidate_id')
            ->get()
            ->keyBy('candidate_id');

        return view('placement.progress.index', compact('students', 'attemptStats'));
    }
}
