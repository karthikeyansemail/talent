@extends('layouts.app')
@section('title', 'LLM Configuration')
@section('page-title', 'LLM Configuration')
@section('content')
<div class="page-header">
    <h1>LLM Configuration</h1>
</div>

<div class="card" style="margin-bottom:24px">
    <div class="card-header">
        <span class="card-header-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a4 4 0 0 0-4 4c0 2 2 3 2 6H6a2 2 0 0 0-2 2v2h16v-2a2 2 0 0 0-2-2h-4c0-3 2-4 2-6a4 4 0 0 0-4-4z"/><path d="M9 18v1a3 3 0 0 0 6 0v-1"/></svg>
            Azure OpenAI Configuration
        </span>
        <form method="POST" action="{{ route('settings.llm.test') }}" style="margin:0">
            @csrf
            <button type="submit" class="btn btn-sm btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                Test Connection
            </button>
        </form>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('settings.llm.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="provider" value="azure_openai">

            <div class="ai-section" style="margin-bottom:20px;padding:16px;background:var(--gray-50);border-radius:10px;border:1px solid var(--gray-200)">
                <p style="font-size:13px;color:var(--gray-600);margin:0">
                    <strong>Azure OpenAI</strong> is used for all AI features: resume analysis, job description parsing, project requirement parsing, resource matching, and signal intelligence. Configure your Azure OpenAI deployment below.
                </p>
            </div>

            <div class="form-group">
                <label>Azure Endpoint URL *</label>
                <input type="url" name="azure_endpoint" class="form-control" value="{{ $config['azure_endpoint'] ?? '' }}" placeholder="https://your-resource.openai.azure.com" required>
                <small class="text-muted">Your Azure OpenAI resource endpoint (e.g., https://mycompany.openai.azure.com)</small>
            </div>

            <div class="form-group">
                <label>API Key *</label>
                <input type="password" name="azure_api_key" class="form-control" value="{{ $config['azure_api_key_masked'] ?? '' }}" placeholder="Enter your Azure OpenAI API key" required>
                <small class="text-muted">Found in Azure Portal > Your OpenAI Resource > Keys and Endpoint</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Deployment Name / Model *</label>
                    <input type="text" name="azure_deployment" class="form-control" value="{{ $config['azure_deployment'] ?? '' }}" placeholder="gpt-4o" required>
                    <small class="text-muted">The deployment name you created in Azure OpenAI Studio</small>
                </div>
                <div class="form-group">
                    <label>API Version *</label>
                    <input type="text" name="azure_api_version" class="form-control" value="{{ $config['azure_api_version'] ?? '2024-08-01-preview' }}" placeholder="2024-08-01-preview" required>
                    <small class="text-muted">Azure OpenAI API version string</small>
                </div>
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
            How It Works
        </span>
    </div>
    <div class="card-body">
        <div style="font-size:13.5px;color:var(--gray-600);line-height:1.7">
            <p style="margin-bottom:12px"><strong>The AI features in this platform use Azure OpenAI for:</strong></p>
            <ul style="padding-left:20px;margin:0">
                <li>Parsing uploaded job descriptions to auto-fill job posting forms</li>
                <li>Parsing uploaded resumes to auto-fill candidate profiles</li>
                <li>Parsing project requirement documents to auto-fill project forms</li>
                <li>Analyzing resumes against job descriptions for candidate scoring</li>
                <li>Matching employees to project requirements based on skills</li>
                <li>Computing signal intelligence meta-signals from behavioral data</li>
            </ul>
            <p style="margin-top:12px;margin-bottom:0">All AI requests are routed through your Azure OpenAI deployment. Your API keys are encrypted at rest and never exposed to end users.</p>
        </div>
    </div>
</div>
@endsection
