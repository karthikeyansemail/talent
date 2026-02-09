<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Resume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ResumeController extends Controller
{
    public function upload(Request $request, Candidate $candidate)
    {
        if ($candidate->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }

        $request->validate([
            'resume' => 'required|file|mimes:pdf,docx|max:10240',
        ]);

        $file = $request->file('resume');
        $path = $file->store('resumes/' . $candidate->id, 'public');
        $ext = strtolower($file->getClientOriginalExtension());

        $extractedText = '';
        $filePath = Storage::disk('public')->path($path);

        if ($ext === 'pdf') {
            $extractedText = $this->extractPdfText($filePath);
        } elseif ($ext === 'docx') {
            $extractedText = $this->extractDocxText($filePath);
        }

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
        if ($candidate->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }

        return Storage::disk('public')->download($resume->file_path, $resume->file_name);
    }

    private function extractPdfText(string $filePath): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Exception $e) {
            return '';
        }
    }

    private function extractDocxText(string $filePath): string
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) === true) {
                $xml = $zip->getFromName('word/document.xml');
                $zip->close();
                if ($xml) {
                    $dom = new \DOMDocument();
                    $dom->loadXML($xml);
                    return strip_tags($dom->saveXML());
                }
            }
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
