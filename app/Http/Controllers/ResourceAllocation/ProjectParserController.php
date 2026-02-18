<?php

namespace App\Http\Controllers\ResourceAllocation;

use App\Http\Controllers\Controller;
use App\Services\AiServiceClient;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\Request;

class ProjectParserController extends Controller
{
    public function parse(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,docx|max:10240',
        ]);

        $file = $request->file('document');
        $extension = strtolower($file->getClientOriginalExtension());

        $extractor = new DocumentTextExtractor();
        $text = $extractor->extract($file->getRealPath(), $extension);

        if (empty(trim($text))) {
            return response()->json(['error' => 'Could not extract text from the document. Please try a different file.'], 422);
        }

        $aiClient = new AiServiceClient();
        $orgId = auth()->user()->currentOrganizationId();

        $result = $aiClient->parseProjectRequirements(['document_text' => $text], $orgId);

        if (isset($result['error'])) {
            return response()->json(['error' => 'AI service unavailable. Please try again later.'], 503);
        }

        return response()->json($result);
    }
}
