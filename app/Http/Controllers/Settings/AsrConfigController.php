<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AsrConfigController extends Controller
{
    public function edit()
    {
        $org = auth()->user()->currentOrganization();
        $config = $org->asr_config ?? [];

        // Mask keys for display
        $masked = $config;
        foreach (['azure_speech_key', 'google_credentials_json'] as $field) {
            if (!empty($config[$field])) {
                try {
                    $raw = decrypt($config[$field]);
                } catch (\Exception $e) {
                    $raw = $config[$field];
                }
                if ($raw) {
                    $masked[$field . '_masked'] = str_repeat('*', max(0, strlen($raw) - 4)) . substr($raw, -4);
                }
            }
        }

        return view('settings.asr', ['config' => $masked]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'asr_provider' => 'required|in:whisper,azure_speech,google_speech',
            'azure_speech_key' => 'nullable|string',
            'azure_speech_region' => 'nullable|string|max:50',
            'google_credentials_json' => 'nullable|string',
            'google_speech_model' => 'nullable|string|max:50',
            'phrase_hints' => 'nullable|string|max:2000',
            'enable_diarization' => 'nullable|boolean',
            'enable_screen_capture' => 'nullable|boolean',
            'enable_llm_correction' => 'nullable|boolean',
        ]);

        $org = auth()->user()->currentOrganization();
        $existing = $org->asr_config ?? [];

        // Handle masked key preservation
        $azureKey = $request->azure_speech_key;
        if ($azureKey && (str_starts_with($azureKey, '****') || str_starts_with($azureKey, str_repeat('*', 10)))) {
            $azureKey = null;
        }

        $googleCreds = $request->google_credentials_json;
        if ($googleCreds && (str_starts_with($googleCreds, '****') || str_starts_with($googleCreds, str_repeat('*', 10)))) {
            $googleCreds = null;
        }

        $org->asr_config = [
            'provider' => $request->asr_provider,
            'azure_speech_key' => $azureKey ? encrypt($azureKey) : ($existing['azure_speech_key'] ?? ''),
            'azure_speech_region' => $request->azure_speech_region ?: ($existing['azure_speech_region'] ?? ''),
            'google_credentials_json' => $googleCreds ? encrypt($googleCreds) : ($existing['google_credentials_json'] ?? ''),
            'google_speech_model' => $request->google_speech_model ?: ($existing['google_speech_model'] ?? 'chirp_2'),
            'phrase_hints' => $request->phrase_hints ?: '',
            'enable_diarization' => (bool) $request->enable_diarization,
            'enable_screen_capture' => (bool) $request->enable_screen_capture,
            'enable_llm_correction' => (bool) $request->enable_llm_correction,
        ];
        $org->save();

        return back()->with('success', 'Speech recognition configuration saved.');
    }

    public function test(Request $request)
    {
        $org = auth()->user()->currentOrganization();
        $config = $org->asr_config ?? [];
        $provider = $config['provider'] ?? 'whisper';

        if ($provider === 'whisper') {
            // Test local Whisper endpoint
            try {
                $resp = Http::timeout(5)->get(config('ai.service_url') . '/health');
                if ($resp->successful()) {
                    return back()->with('success', 'Local Whisper service is healthy.');
                }
                return back()->with('error', 'Whisper service unreachable: ' . $resp->status());
            } catch (\Exception $e) {
                return back()->with('error', 'Cannot reach Whisper service: ' . $e->getMessage());
            }
        }

        if ($provider === 'azure_speech') {
            if (empty($config['azure_speech_key']) || empty($config['azure_speech_region'])) {
                return back()->with('error', 'Azure Speech key and region are required.');
            }
            try {
                $key = decrypt($config['azure_speech_key']);
                $region = $config['azure_speech_region'];
                $url = "https://{$region}.api.cognitive.microsoft.com/sts/v1.0/issueToken";
                $resp = Http::withoutVerifying()
                    ->timeout(10)
                    ->withHeaders([
                        'Ocp-Apim-Subscription-Key' => $key,
                        'Content-Length' => '0',
                    ])
                    ->post($url);

                if ($resp->successful()) {
                    return back()->with('success', 'Azure Speech Services connection successful! Token issued.');
                }
                return back()->with('error', 'Azure Speech returned error: ' . $resp->status() . ' - ' . $resp->body());
            } catch (\Exception $e) {
                return back()->with('error', 'Azure Speech test failed: ' . $e->getMessage());
            }
        }

        if ($provider === 'google_speech') {
            return back()->with('success', 'Google Cloud Speech configuration saved. Browser-side testing not available — Google Speech requires server-side streaming.');
        }

        return back()->with('error', 'Unknown provider.');
    }

    /**
     * Issue a short-lived Azure Speech token for browser-side SDK usage.
     * Called via AJAX from the interview page.
     */
    public function token(Request $request)
    {
        $org = auth()->user()->currentOrganization();
        $config = $org->asr_config ?? [];

        if (($config['provider'] ?? 'whisper') !== 'azure_speech') {
            return response()->json(['error' => 'Azure Speech not configured'], 400);
        }

        if (empty($config['azure_speech_key']) || empty($config['azure_speech_region'])) {
            return response()->json(['error' => 'Azure Speech key/region missing'], 400);
        }

        try {
            $key = decrypt($config['azure_speech_key']);
            $region = $config['azure_speech_region'];
            $url = "https://{$region}.api.cognitive.microsoft.com/sts/v1.0/issueToken";

            $resp = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'Ocp-Apim-Subscription-Key' => $key,
                    'Content-Length' => '0',
                ])
                ->post($url);

            if ($resp->successful()) {
                return response()->json([
                    'token' => $resp->body(),
                    'region' => $region,
                ]);
            }

            return response()->json(['error' => 'Token issue failed: ' . $resp->status()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token error: ' . $e->getMessage()], 500);
        }
    }
}
