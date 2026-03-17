@extends('layouts.app')
@section('title', 'Speech Recognition (ASR)')
@section('page-title', 'Speech Recognition')
@section('content')
<div class="page-header">
    <h1>Speech Recognition Configuration</h1>
</div>

<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
            Interview Transcription Settings
        </span>
        <form method="POST" action="{{ route('settings.asr.test') }}" style="margin:0">
            @csrf
            <button type="submit" class="btn btn-sm btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Test Connection
            </button>
        </form>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('settings.asr.update') }}">
            @csrf
            @method('PUT')

            <div class="ai-section" style="margin-bottom:20px;padding:16px;background:var(--gray-50);border-radius:10px;border:1px solid var(--gray-200)">
                <p style="font-size:13px;color:var(--gray-600);margin:0">
                    Configure the speech-to-text engine used during live interviews. <strong>Azure Speech Services</strong> provides speaker diarization (identifying who is speaking) and custom vocabulary boosting. <strong>Whisper</strong> runs locally with no cloud dependency.
                </p>
            </div>

            {{-- Provider Selection --}}
            <div class="form-group">
                <label>ASR Provider *</label>
                <select name="asr_provider" id="asr-provider" class="form-control" required onchange="toggleProviderSections()">
                    <option value="whisper" {{ ($config['provider'] ?? 'whisper') === 'whisper' ? 'selected' : '' }}>Whisper (Local — No Cloud Cost)</option>
                    <option value="azure_speech" {{ ($config['provider'] ?? '') === 'azure_speech' ? 'selected' : '' }}>Azure Speech Services (Recommended)</option>
                    <option value="google_speech" {{ ($config['provider'] ?? '') === 'google_speech' ? 'selected' : '' }}>Google Cloud Speech-to-Text</option>
                </select>
                <small class="text-muted">Azure Speech: 5 hrs/month free, speaker diarization, phrase boosting. Whisper: runs locally, no limits.</small>
            </div>

            {{-- Azure Speech Section --}}
            <div id="azure-section" style="display:none; margin-top:16px; padding:16px; background:var(--gray-50); border-radius:10px; border:1px solid var(--gray-200);">
                <h4 style="margin:0 0 12px; font-size:14px; font-weight:600;">Azure Speech Services</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label>Speech Key *</label>
                        <input type="password" name="azure_speech_key" class="form-control" value="{{ $config['azure_speech_key_masked'] ?? '' }}" placeholder="Enter Azure Speech subscription key">
                        <small class="text-muted">Azure Portal > Speech resource > Keys and Endpoint</small>
                    </div>
                    <div class="form-group">
                        <label>Region *</label>
                        <input type="text" name="azure_speech_region" class="form-control" value="{{ $config['azure_speech_region'] ?? '' }}" placeholder="e.g. eastus, westeurope, centralindia">
                        <small class="text-muted">The Azure region of your Speech resource</small>
                    </div>
                </div>
                <div style="padding:10px; background:#fff; border-radius:8px; border:1px solid var(--gray-200); font-size:12px; color:var(--gray-500);">
                    <strong>Free tier:</strong> 5 hours/month of speech-to-text. Speaker diarization (ConversationTranscriber) and phrase lists are included at no extra cost. The browser-side SDK connects directly to Azure — audio never touches your server.
                </div>
            </div>

            {{-- Google Speech Section --}}
            <div id="google-section" style="display:none; margin-top:16px; padding:16px; background:var(--gray-50); border-radius:10px; border:1px solid var(--gray-200);">
                <h4 style="margin:0 0 12px; font-size:14px; font-weight:600;">Google Cloud Speech-to-Text</h4>
                <div class="form-group">
                    <label>Service Account JSON</label>
                    <textarea name="google_credentials_json" class="form-control" rows="4" placeholder="Paste your Google Cloud service account JSON key here">{{ $config['google_credentials_json_masked'] ?? '' }}</textarea>
                    <small class="text-muted">Google Cloud Console > APIs & Services > Credentials > Service Account Key (JSON)</small>
                </div>
                <div class="form-group">
                    <label>Model</label>
                    <select name="google_speech_model" class="form-control">
                        <option value="chirp_2" {{ ($config['google_speech_model'] ?? 'chirp_2') === 'chirp_2' ? 'selected' : '' }}>Chirp 2 (Latest)</option>
                        <option value="latest_long" {{ ($config['google_speech_model'] ?? '') === 'latest_long' ? 'selected' : '' }}>Latest Long</option>
                        <option value="latest_short" {{ ($config['google_speech_model'] ?? '') === 'latest_short' ? 'selected' : '' }}>Latest Short</option>
                    </select>
                </div>
                <div style="padding:10px; background:#fff; border-radius:8px; border:1px solid var(--gray-200); font-size:12px; color:var(--gray-500);">
                    <strong>How it works:</strong> Uses Chrome's built-in Web Speech API (powered by Google's speech recognition) for interviewer mic transcription — free, real-time, and high quality. System/meeting audio uses the local Whisper model. <strong>Requires Google Chrome browser.</strong> No server credentials needed for basic use. The credential fields above are reserved for future server-side Google Cloud Speech integration.
                </div>
            </div>

            <hr style="margin:24px 0; border-color:var(--gray-200);">

            {{-- Feature Toggles --}}
            <h4 style="margin:0 0 16px; font-size:14px; font-weight:600;">Enhanced Features</h4>

            <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                <input type="hidden" name="enable_diarization" value="0">
                <input type="checkbox" name="enable_diarization" value="1" id="enable-diarization" {{ ($config['enable_diarization'] ?? false) ? 'checked' : '' }}>
                <label for="enable-diarization" style="margin:0; cursor:pointer;">
                    <strong>Speaker Diarization</strong>
                    <small class="text-muted" style="display:block;">Identify individual speakers (Interviewer 1, Interviewer 2, Candidate) instead of just Mic/System Audio. Requires Azure Speech.</small>
                </label>
            </div>

            <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                <input type="hidden" name="enable_screen_capture" value="0">
                <input type="checkbox" name="enable_screen_capture" value="1" id="enable-screen-capture" {{ ($config['enable_screen_capture'] ?? false) ? 'checked' : '' }}>
                <label for="enable-screen-capture" style="margin:0; cursor:pointer;">
                    <strong>Screen Capture & Code Extraction</strong>
                    <small class="text-muted" style="display:block;">When candidate shares screen, periodically capture screenshots and extract code using Vision AI. Requires LLM configuration.</small>
                </label>
            </div>

            <div class="form-group" style="display:flex; align-items:center; gap:10px;">
                <input type="hidden" name="enable_llm_correction" value="0">
                <input type="checkbox" name="enable_llm_correction" value="1" id="enable-llm-correction" {{ ($config['enable_llm_correction'] ?? false) ? 'checked' : '' }}>
                <label for="enable-llm-correction" style="margin:0; cursor:pointer;">
                    <strong>LLM Transcript Correction</strong>
                    <small class="text-muted" style="display:block;">Use AI to fix domain-specific transcription errors ("coffee" → "copy", "reduct" → "Redux") using job context and accumulated vocabulary.</small>
                </label>
            </div>

            <hr style="margin:24px 0; border-color:var(--gray-200);">

            {{-- Custom Phrase Hints --}}
            <div class="form-group">
                <label>Custom Phrase Hints</label>
                <textarea name="phrase_hints" class="form-control" rows="3" placeholder="JIRA, Redux, Kubernetes, microservices, sprint velocity, CI/CD, PostgreSQL">{{ $config['phrase_hints'] ?? '' }}</textarea>
                <small class="text-muted">Comma-separated list of domain-specific terms to boost transcription accuracy. These are added alongside job-specific skills automatically extracted from the job posting.</small>
            </div>

            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Save Configuration
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Provider Comparison
        </span>
    </div>
    <div class="card-body">
        <table class="data-table" style="font-size:13px;">
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>Whisper (Local)</th>
                    <th>Azure Speech</th>
                    <th>Google Cloud Speech</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><strong>Free Tier</strong></td><td>Unlimited (local CPU)</td><td>5 hrs/month</td><td>60 min/month</td></tr>
                <tr><td><strong>Speaker Diarization</strong></td><td>No</td><td>Yes (ConversationTranscriber)</td><td>Yes (server-side)</td></tr>
                <tr><td><strong>Phrase Boosting</strong></td><td>No</td><td>Yes (PhraseListGrammar)</td><td>Yes (SpeechAdaptation)</td></tr>
                <tr><td><strong>Real-time Streaming</strong></td><td>Chunked (5-7s)</td><td>Real-time (browser SDK)</td><td>Server relay required</td></tr>
                <tr><td><strong>Latency</strong></td><td>Medium (CPU-bound)</td><td>Low (~200ms)</td><td>Low (requires proxy)</td></tr>
                <tr><td><strong>Accuracy</strong></td><td>Good (base model)</td><td>Excellent</td><td>Excellent</td></tr>
                <tr><td><strong>Cloud Dependency</strong></td><td>None</td><td>Azure</td><td>Google Cloud</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleProviderSections() {
    var provider = document.getElementById('asr-provider').value;
    document.getElementById('azure-section').style.display = provider === 'azure_speech' ? 'block' : 'none';
    document.getElementById('google-section').style.display = provider === 'google_speech' ? 'block' : 'none';
}
toggleProviderSections();
</script>
@endsection
