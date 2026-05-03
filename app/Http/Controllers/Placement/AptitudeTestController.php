<?php

namespace App\Http\Controllers\Placement;

use App\Http\Controllers\Controller;
use App\Models\AptitudeTest;
use Illuminate\Support\Facades\Auth;

class AptitudeTestController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()->currentOrganizationId();
        $tests = AptitudeTest::where('organization_id', $orgId)
            ->with('drive')
            ->orderByDesc('created_at')
            ->get();

        return view('placement.tests.index', compact('tests'));
    }
}
