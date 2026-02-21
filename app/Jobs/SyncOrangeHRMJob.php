<?php

namespace App\Jobs;

use App\Models\Department;
use App\Models\Employee;
use App\Models\IntegrationConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncOrangeHRMJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private IntegrationConnection $connection
    ) {}

    public function handle(): void
    {
        $credentials = $this->connection->credentials;
        $baseUrl = rtrim($credentials['base_url'] ?? '', '/');
        $clientId = $credentials['client_id'] ?? '';
        $clientSecret = $credentials['client_secret'] ?? '';
        $orgId = $this->connection->organization_id;

        try {
            // Step 1: Get access token via client_credentials grant
            $tokenResponse = Http::asForm()->post("{$baseUrl}/oauth2/token", [
                'grant_type'    => 'client_credentials',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
            ]);

            if (!$tokenResponse->successful()) {
                Log::warning('OrangeHRM token fetch failed', [
                    'connection_id' => $this->connection->id,
                    'status' => $tokenResponse->status(),
                ]);
                return;
            }

            $accessToken = $tokenResponse->json('access_token');

            // Step 2: Fetch employees
            $limit  = 50;
            $offset = 0;
            $synced = 0;

            do {
                $response = Http::withToken($accessToken)
                    ->get("{$baseUrl}/api/v2/employees", [
                        'limit'  => $limit,
                        'offset' => $offset,
                    ]);

                if (!$response->successful()) {
                    Log::warning('OrangeHRM employees fetch failed', [
                        'connection_id' => $this->connection->id,
                        'status' => $response->status(),
                    ]);
                    break;
                }

                $data      = $response->json();
                $employees = $data['data'] ?? [];

                foreach ($employees as $emp) {
                    $email = $emp['workEmail'] ?? ($emp['email'] ?? '');
                    $firstName = $emp['firstName'] ?? '';

                    if (!$email || !$firstName) {
                        continue;
                    }

                    // Resolve department
                    $departmentId = null;
                    $deptName = $emp['department']['name'] ?? '';
                    if ($deptName) {
                        $dept = Department::firstOrCreate(
                            ['organization_id' => $orgId, 'name' => $deptName],
                            ['description' => '']
                        );
                        $departmentId = $dept->id;
                    }

                    Employee::updateOrCreate(
                        ['organization_id' => $orgId, 'email' => $email],
                        [
                            'first_name'    => $firstName,
                            'last_name'     => $emp['lastName'] ?? '',
                            'department_id' => $departmentId,
                            'designation'   => $emp['jobTitle']['title'] ?? ($emp['designation'] ?? null),
                            'is_active'     => ($emp['terminationId'] ?? null) === null,
                            'import_source' => 'orangehrm',
                            'external_id'   => (string) ($emp['employeeId'] ?? ''),
                        ]
                    );

                    $synced++;
                }

                $total  = $data['meta']['total'] ?? count($employees);
                $offset += $limit;
            } while ($offset < $total);

            $this->connection->update(['last_synced_at' => now()]);

            Log::info("OrangeHRM sync complete: {$synced} employees synced", [
                'connection_id' => $this->connection->id,
            ]);
        } catch (\Exception $e) {
            Log::error('OrangeHRM sync error: ' . $e->getMessage(), [
                'connection_id' => $this->connection->id,
            ]);
            throw $e;
        }
    }
}
