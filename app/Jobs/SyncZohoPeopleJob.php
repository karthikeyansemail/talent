<?php

namespace App\Jobs;

use App\Models\Department;
use App\Models\Employee;
use App\Models\ZohoPeopleConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncZohoPeopleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private ZohoPeopleConnection $connection
    ) {}

    public function handle(): void
    {
        $orgId = $this->connection->organization_id;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Zoho-oauthtoken ' . $this->connection->auth_token,
            ])->get("https://people.zoho.com/people/api/forms/employee/getRecords", [
                'sIndex' => 1,
                'limit' => 200,
            ]);

            if (!$response->successful()) {
                Log::warning('Zoho People sync failed', [
                    'connection_id' => $this->connection->id,
                    'status' => $response->status(),
                ]);
                return;
            }

            $data = $response->json();
            $records = $data['response']['result'] ?? [];
            $synced = 0;

            foreach ($records as $recordWrapper) {
                foreach ($recordWrapper as $record) {
                    $email = $record['EmailID'] ?? '';
                    $firstName = $record['FirstName'] ?? '';
                    $lastName = $record['LastName'] ?? '';
                    $externalId = $record['Zoho_ID'] ?? $record['EmployeeID'] ?? '';

                    if (!$email || !$firstName) {
                        continue;
                    }

                    // Resolve department
                    $departmentId = null;
                    $deptName = $record['Department'] ?? '';
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
                            'first_name' => $firstName,
                            'last_name' => $lastName ?: '',
                            'department_id' => $departmentId,
                            'designation' => $record['Designation'] ?? null,
                            'is_active' => true,
                            'import_source' => 'zoho_people',
                            'external_id' => $externalId ?: null,
                        ]
                    );

                    $synced++;
                }
            }

            $this->connection->update(['last_synced_at' => now()]);

            Log::info("Zoho People sync complete: {$synced} employees synced", [
                'connection_id' => $this->connection->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Zoho People sync error: ' . $e->getMessage(), [
                'connection_id' => $this->connection->id,
            ]);
            throw $e;
        }
    }
}
