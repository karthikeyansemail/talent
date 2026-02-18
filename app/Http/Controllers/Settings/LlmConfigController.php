<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LlmConfigController extends Controller
{
    public function edit()
    {
        $org = auth()->user()->currentOrganization();
        $config = $org->llm_config ?? [];

        // Mask the API key for display
        if (!empty($config['azure_api_key'])) {
            try {
                $decrypted = decrypt($config['azure_api_key']);
                $config['azure_api_key_masked'] = str_repeat('*', max(0, strlen($decrypted) - 4)) . substr($decrypted, -4);
            } catch (\Exception $e) {
                $config['azure_api_key_masked'] = '****';
            }
        }

        return view('settings.llm', compact('config'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:azure_openai',
            'azure_endpoint' => 'required|url',
            'azure_api_key' => 'required|string|min:5',
            'azure_deployment' => 'required|string|max:100',
            'azure_api_version' => 'required|string|max:30',
        ]);

        $org = auth()->user()->currentOrganization();

        // If the user submitted the masked placeholder, keep the old key
        $apiKey = $request->azure_api_key;
        $existingConfig = $org->llm_config ?? [];
        if (str_starts_with($apiKey, '****') || str_starts_with($apiKey, str_repeat('*', 10))) {
            $apiKey = null; // will keep existing
        }

        $org->llm_config = [
            'provider' => $request->provider,
            'azure_endpoint' => $request->azure_endpoint,
            'azure_api_key' => $apiKey ? encrypt($apiKey) : ($existingConfig['azure_api_key'] ?? ''),
            'azure_deployment' => $request->azure_deployment,
            'azure_api_version' => $request->azure_api_version,
        ];
        $org->save();

        // Also write to Python AI service .env for the local deployment
        $this->syncPythonEnv($org->llm_config);

        return back()->with('success', 'LLM configuration saved successfully.');
    }

    public function test(Request $request)
    {
        $org = auth()->user()->currentOrganization();
        $config = $org->llm_config ?? [];

        if (empty($config['azure_endpoint']) || empty($config['azure_api_key'])) {
            return back()->with('error', 'Please save your LLM configuration first.');
        }

        try {
            $apiKey = decrypt($config['azure_api_key']);
            $url = rtrim($config['azure_endpoint'], '/')
                . '/openai/deployments/' . $config['azure_deployment']
                . '/chat/completions?api-version=' . $config['azure_api_version'];

            $response = Http::withoutVerifying()
                ->timeout(15)
                ->withHeaders(['api-key' => $apiKey, 'Content-Type' => 'application/json'])
                ->post($url, [
                    'messages' => [
                        ['role' => 'user', 'content' => 'Respond with exactly: CONNECTION_OK']
                    ],
                    'max_tokens' => 20,
                    'temperature' => 0,
                ]);

            if ($response->successful()) {
                return back()->with('success', 'Azure OpenAI connection successful! Model responded correctly.');
            }

            return back()->with('error', 'Azure OpenAI returned error: ' . $response->status() . ' - ' . ($response->json('error.message') ?? $response->body()));
        } catch (\Exception $e) {
            return back()->with('error', 'Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Sync LLM config to the Python AI service .env file.
     */
    private function syncPythonEnv(array $config): void
    {
        $envPath = base_path('ai-service/.env');

        try {
            $apiKey = !empty($config['azure_api_key']) ? decrypt($config['azure_api_key']) : '';
        } catch (\Exception $e) {
            $apiKey = '';
        }

        $envVars = [
            'LLM_PROVIDER' => 'azure_openai',
            'AZURE_OPENAI_ENDPOINT' => $config['azure_endpoint'] ?? '',
            'AZURE_OPENAI_API_KEY' => $apiKey,
            'AZURE_OPENAI_DEPLOYMENT' => $config['azure_deployment'] ?? '',
            'AZURE_OPENAI_API_VERSION' => $config['azure_api_version'] ?? '2024-08-01-preview',
        ];

        // Read existing .env or start fresh
        $existingContent = file_exists($envPath) ? file_get_contents($envPath) : '';
        $lines = $existingContent ? explode("\n", $existingContent) : [];

        // Update or add each env var
        foreach ($envVars as $key => $value) {
            $found = false;
            foreach ($lines as $i => $line) {
                if (preg_match('/^' . preg_quote($key, '/') . '\s*=/', $line)) {
                    $lines[$i] = $key . '=' . $value;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $lines[] = $key . '=' . $value;
            }
        }

        file_put_contents($envPath, implode("\n", $lines));
    }
}
