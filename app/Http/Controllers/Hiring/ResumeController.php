<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Resume;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    public function upload(Request $request, Candidate $candidate)
    {
        if ($candidate->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        $request->validate([
            'resume' => 'required|file|mimes:pdf,docx|max:10240',
        ]);

        $file = $request->file('resume');
        $path = $file->store('resumes/' . $candidate->id, 'public');
        $ext = strtolower($file->getClientOriginalExtension());

        $extractor = new DocumentTextExtractor();
        $extractedText = $extractor->extract(
            Storage::disk('public')->path($path),
            $ext
        );

        $resume = Resume::create([
            'candidate_id' => $candidate->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $ext === 'docx' ? 'docx' : 'pdf',
            'extracted_text' => $extractedText,
            'uploaded_by' => Auth::id(),
        ]);

        return back()->with('success', 'Resume uploaded successfully.');
    }

    public function download(Candidate $candidate, Resume $resume)
    {
        if ($candidate->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        return Storage::disk('public')->download($resume->file_path, $resume->file_name);
    }
}
