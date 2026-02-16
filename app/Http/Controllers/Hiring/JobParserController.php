<?php

namespace App\Http\Controllers\Hiring;

use App\Http\Controllers\Controller;
use App\Services\AiServiceClient;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobParserController extends Controller
{
    /**
     * Parse an uploaded job description document via AI and return structured fields.
     */
    public function parse(Request $request): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,docx|max:10240',
        ]);

        $file = $request->file('document');
        $ext = strtolower($file->getClientOriginalExtension());

        $extractor = new DocumentTextExtractor();
        $text = $extractor->extract($file->getPathname(), $ext);

        if (empty(trim($text))) {
            return response()->json([
                'error' => 'Could not extract text from the document. Please ensure it contains readable text.',
            ], 422);
        }

        $client = new AiServiceClient();
        $result = $client->parseJobDescription(
            ['document_text' => $text],
            Auth::user()->organization_id
        );

        if (isset($result['error'])) {
            return response()->json([
                'error' => 'AI service is currently unavailable. Please fill in the fields manually.',
            ], 503);
        }

        return response()->json($result);
    }
}
