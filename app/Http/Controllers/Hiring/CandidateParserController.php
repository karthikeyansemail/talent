<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Services\AiServiceClient;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CandidateParserController extends Controller
{
    /**
     * Parse an uploaded resume via AI and return candidate profile fields.
     */
    public function parse(Request $request): JsonResponse
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,docx|max:10240',
        ]);

        try {
            $file = $request->file('resume');
            $ext = strtolower($file->getClientOriginalExtension());

            $extractor = new DocumentTextExtractor();
            $text = $extractor->extract($file->getPathname(), $ext);

            if (empty(trim($text))) {
                return response()->json([
                    'error' => 'Could not extract text from the resume. Please ensure it contains readable text.',
                ], 422);
            }

            // Store file temporarily so it can be saved as a Resume later
            $tempPath = $file->store('temp_resumes', 'public');

            $client = new AiServiceClient();
            $result = $client->parseResumeProfile(
                ['resume_text' => $text],
                Auth::user()->organization_id
            );

            if (isset($result['error'])) {
                return response()->json([
                    'error' => 'AI service is currently unavailable. Please fill in the fields manually.',
                ], 503);
            }

            // Fallback: if AI didn't extract email, try regex on raw text
            if (empty($result['email'])) {
                $result['email'] = DocumentTextExtractor::extractEmail($text) ?? '';
            }

            // Attach temp file info for Resume creation on candidate save
            $result['_temp_file_path'] = $tempPath;
            $result['_temp_file_name'] = $file->getClientOriginalName();
            $result['_temp_file_type'] = $ext;
            $result['_extracted_text'] = $text;

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process resume: ' . $e->getMessage(),
            ], 500);
        }
    }
}
