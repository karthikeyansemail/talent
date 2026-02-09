<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $orgId = Auth::user()->organization_id;
        $query = Candidate::where('organization_id', $orgId)->with('resumes');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $candidates = $query->latest()->paginate(15);
        return view('candidates.index', compact('candidates'));
    }

    public function create()
    {
        return view('candidates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'current_company' => 'nullable|string|max:255',
            'current_title' => 'nullable|string|max:255',
            'experience_years' => 'nullable|numeric|min:0|max:50',
            'source' => 'required|in:upload,referral,direct',
            'notes' => 'nullable|string',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;
        $candidate = Candidate::create($validated);

        return redirect()->route('candidates.show', $candidate)->with('success', 'Candidate created.');
    }

    public function show(Candidate $candidate)
    {
        $this->authorizeOrg($candidate);
        $candidate->load(['resumes', 'applications.jobPosting']);
        return view('candidates.show', compact('candidate'));
    }

    public function edit(Candidate $candidate)
    {
        $this->authorizeOrg($candidate);
        return view('candidates.edit', compact('candidate'));
    }

    public function update(Request $request, Candidate $candidate)
    {
        $this->authorizeOrg($candidate);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'current_company' => 'nullable|string|max:255',
            'current_title' => 'nullable|string|max:255',
            'experience_years' => 'nullable|numeric|min:0|max:50',
            'source' => 'required|in:upload,referral,direct',
            'notes' => 'nullable|string',
        ]);

        $candidate->update($validated);
        return redirect()->route('candidates.show', $candidate)->with('success', 'Candidate updated.');
    }

    public function destroy(Candidate $candidate)
    {
        $this->authorizeOrg($candidate);
        $candidate->delete();
        return redirect()->route('candidates.index')->with('success', 'Candidate deleted.');
    }

    private function authorizeOrg(Candidate $candidate): void
    {
        if ($candidate->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }
    }
}
