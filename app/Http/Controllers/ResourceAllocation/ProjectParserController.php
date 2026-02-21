<?php

namespace App\Http\Controllers\ResourceAllocation;

use App\Http\Controllers\Controller;
use App\Services\AiServiceClient;
use App\Services\DocumentTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectParserController extends Controller
{
    public function parse(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,docx|max:10240',
        ]);

        $file      = $request->file('document');
        $extension = strtolower($file->getClientOriginalExtension());

        $extractor = new DocumentTextExtractor();
        $text      = $extractor->extract($file->getRealPath(), $extension);

        if (empty(trim($text))) {
            return response()->json(['error' => 'Could not extract text from the document. Please try a different file.'], 422);
        }

        $aiClient = new AiServiceClient();
        $orgId    = auth()->user()->currentOrganizationId();

        $result = $aiClient->parseProjectRequirements(['document_text' => $text], $orgId);

        if (isset($result['error'])) {
            return response()->json(['error' => 'AI service unavailable. Please try again later.'], 503);
        }

        // Store the uploaded file temporarily so the create form can persist it
        $tempKey  = Str::uuid()->toString();
        $tempPath = 'project-documents/temp/' . $tempKey . '.' . $extension;
        Storage::disk('public')->put($tempPath, file_get_contents($file->getRealPath()));

        // Store extracted text alongside so we don't re-extract on store
        Storage::disk('public')->put(
            'project-documents/temp/' . $tempKey . '.txt',
            $text
        );

        $result['charter_temp_key']      = $tempKey;
        $result['charter_original_name'] = $file->getClientOriginalName();
        $result['charter_file_type']     = $extension;
        $result['charter_file_size']     = $file->getSize();

        return response()->json($result);
    }
}
