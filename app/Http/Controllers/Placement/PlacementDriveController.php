<?php

namespace App\Http\Controllers\Placement;

use App\Http\Controllers\Controller;
use App\Models\PlacementDrive;
use Illuminate\Support\Facades\Auth;

/**
 * Placement Drive controller — placeholder index for Commit A. Full CRUD
 * (create from document parse, edit, manage students, schedule interview)
 * comes in Commit B.
 */
class PlacementDriveController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()->currentOrganizationId();
        $drives = PlacementDrive::where('organization_id', $orgId)
            ->orderByDesc('drive_date')
            ->orderByDesc('created_at')
            ->get();

        return view('placement.drives.index', compact('drives'));
    }
}
