<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Support\Facades\Auth;

class HiringReportsController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()->organization_id;

        $pipelineStats = JobApplication::whereHas('jobPosting', fn($q) => $q->where('organization_id', $orgId))
            ->selectRaw('stage, count(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage')
            ->toArray();

        $jobStats = JobPosting::where('organization_id', $orgId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $recentHires = JobApplication::whereHas('jobPosting', fn($q) => $q->where('organization_id', $orgId))
            ->where('stage', 'hired')
            ->with(['candidate', 'jobPosting'])
            ->latest()
            ->take(10)
            ->get();

        $avgScores = JobApplication::whereHas('jobPosting', fn($q) => $q->where('organization_id', $orgId))
            ->whereNotNull('ai_score')
            ->selectRaw('stage, AVG(ai_score) as avg_score, COUNT(*) as count')
            ->groupBy('stage')
            ->get();

        return view('hiring.reports', compact('pipelineStats', 'jobStats', 'recentHires', 'avgScores'));
    }
}
