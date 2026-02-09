<?php

namespace App\Http\Controllers;

use App\Models\JobPosting;
use App\Models\Candidate;
use App\Models\JobApplication;
use App\Models\Employee;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()->organization_id;

        $stats = [
            'total_jobs' => JobPosting::where('organization_id', $orgId)->count(),
            'open_jobs' => JobPosting::where('organization_id', $orgId)->where('status', 'open')->count(),
            'total_candidates' => Candidate::where('organization_id', $orgId)->count(),
            'total_applications' => JobApplication::whereHas('jobPosting', fn($q) => $q->where('organization_id', $orgId))->count(),
            'total_employees' => Employee::where('organization_id', $orgId)->count(),
            'total_projects' => Project::where('organization_id', $orgId)->count(),
        ];

        $recentApplications = JobApplication::whereHas('jobPosting', fn($q) => $q->where('organization_id', $orgId))
            ->with(['candidate', 'jobPosting'])
            ->latest()
            ->take(5)
            ->get();

        $pipelineStats = JobApplication::whereHas('jobPosting', fn($q) => $q->where('organization_id', $orgId))
            ->selectRaw('stage, count(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage')
            ->toArray();

        return view('dashboard.index', compact('stats', 'recentApplications', 'pipelineStats'));
    }
}
