<?php

namespace Database\Seeders;

use App\Models\JiraConnection;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class NalamJiraSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'nalam-systems')->firstOrFail();

        // Remove any existing connection first
        JiraConnection::where('organization_id', $org->id)->delete();

        $conn = JiraConnection::create([
            'organization_id' => $org->id,
            'jira_base_url'   => 'https://nalamsystems.atlassian.net',
            'jira_email'      => 'rahul.kumar@nalamsystems.work',
            'jira_api_token'  => env('NALAM_JIRA_API_TOKEN', 'your-jira-api-token-here'),
            'is_active'       => true,
        ]);

        $this->command->info("Jira connection created (ID: {$conn->id})");
        $this->command->info("URL: {$conn->jira_base_url}");
        $this->command->info("Email: {$conn->jira_email}");
    }
}
