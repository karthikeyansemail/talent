<?php

namespace App\Http\Controllers\Placement;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Support\Facades\Auth;

/**
 * Students are stored in the candidates table with optional student fields
 * (enrollment_number, course, batch_year). This controller scopes the same
 * model with a placement-flavored UI.
 */
class StudentController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()->currentOrganizationId();
        $students = Candidate::where('organization_id', $orgId)
            ->orderBy('first_name')
            ->paginate(50);

        return view('placement.students.index', compact('students'));
    }
}
