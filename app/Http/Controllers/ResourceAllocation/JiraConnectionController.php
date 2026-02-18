<?php

namespace App\Http\Controllers\ResourceAllocation;

use App\Http\Controllers\Controller;
use App\Models\JiraConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class JiraConnectionController extends Controller
{
    public function index()
    {
        $connections = JiraConnection::where('organization_id', Auth::user()->currentOrganizationId())->latest()->get();
        return view('jira.index', compact('connections'));
    }

    public function create()
    {
        return view('jira.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'jira_base_url' => 'required|url',
            'jira_email' => 'required|email',
            'jira_api_token' => 'required|string',
        ]);

        $validated['organization_id'] = Auth::user()->currentOrganizationId();
        $validated['is_active'] = true;

        JiraConnection::create($validated);
        return redirect()->route('jira-connections.index')->with('success', 'Jira connection created.');
    }

    public function test(JiraConnection $jiraConnection)
    {
        if ($jiraConnection->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        try {
            $response = Http::withBasicAuth($jiraConnection->jira_email, $jiraConnection->jira_api_token)
                ->get($jiraConnection->jira_base_url . '/rest/api/2/myself');

            if ($response->successful()) {
                return back()->with('success', 'Connection successful! Authenticated as: ' . $response->json('displayName'));
            }
            return back()->with('error', 'Connection failed: ' . $response->status());
        } catch (\Exception $e) {
            return back()->with('error', 'Connection failed: ' . $e->getMessage());
        }
    }

    public function sync(JiraConnection $jiraConnection)
    {
        if ($jiraConnection->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }

        $jiraConnection->update(['last_synced_at' => now()]);
        return back()->with('success', 'Sync initiated.');
    }

    public function destroy(JiraConnection $jiraConnection)
    {
        if ($jiraConnection->organization_id !== Auth::user()->currentOrganizationId()) {
            abort(403);
        }
        $jiraConnection->delete();
        return redirect()->route('jira-connections.index')->with('success', 'Jira connection removed.');
    }
}
