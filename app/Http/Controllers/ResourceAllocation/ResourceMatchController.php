<?php

namespace App\Http\Controllers\ResourceAllocation;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectResourceMatch;
use Illuminate\Support\Facades\Auth;

class ResourceMatchController extends Controller
{
    public function assign(Project $project, ProjectResourceMatch $match)
    {
        if ($project->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
        $match->update(['is_assigned' => true, 'assigned_at' => now()]);
        return back()->with('success', 'Resource assigned to project.');
    }

    public function unassign(Project $project, ProjectResourceMatch $match)
    {
        if ($project->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
        $match->update(['is_assigned' => false, 'assigned_at' => null]);
        return back()->with('success', 'Resource unassigned from project.');
    }
}
