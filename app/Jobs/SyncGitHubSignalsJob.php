<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\EmployeeSignal;
use App\Models\IntegrationConnection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncGitHubSignalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private IntegrationConnection $connection
    ) {}

    public function handle(): void
    {
        $credentials = $this->connection->credentials;
        $orgName     = $credentials['org_name'] ?? '';
        $token       = $credentials['access_token'] ?? '';
        $orgId       = $this->connection->organization_id;

        try {
            $http = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/vnd.github+json', 'X-GitHub-Api-Version' => '2022-11-28']);

            // Fetch repos for the org
            $reposResponse = $http->get("https://api.github.com/orgs/{$orgName}/repos", [
                'per_page' => 100,
                'sort'     => 'pushed',
            ]);

            if (!$reposResponse->successful()) {
                Log::warning('GitHub repos fetch failed', [
                    'connection_id' => $this->connection->id,
                    'status' => $reposResponse->status(),
                ]);
                return;
            }

            $repos     = $reposResponse->json();
            $employees = Employee::where('organization_id', $orgId)->whereNotNull('email')->get()->keyBy('email');

            // Period: current month
            $period = now()->format('Y-m');
            $since  = now()->startOfMonth()->toIso8601String();

            // Aggregate per-author commit stats across all repos
            $authorStats = []; // email => [commits, lines_added, lines_removed, days, file_types, code_areas]

            foreach ($repos as $repo) {
                $repoName  = $repo['name'];
                $commitsPage = 1;

                do {
                    $commitsResponse = $http->get("https://api.github.com/repos/{$orgName}/{$repoName}/commits", [
                        'since'    => $since,
                        'per_page' => 100,
                        'page'     => $commitsPage,
                    ]);

                    if (!$commitsResponse->successful()) {
                        break;
                    }

                    $commits = $commitsResponse->json();

                    foreach ($commits as $commit) {
                        $authorEmail = $commit['commit']['author']['email'] ?? '';
                        if (!$authorEmail || !isset($employees[$authorEmail])) {
                            continue;
                        }

                        if (!isset($authorStats[$authorEmail])) {
                            $authorStats[$authorEmail] = [
                                'commits'       => 0,
                                'lines_added'   => 0,
                                'lines_removed' => 0,
                                'active_days'   => [],
                                'file_types'    => [],
                                'code_areas'    => [],
                                'pr_reviews'    => 0,
                            ];
                        }

                        $authorStats[$authorEmail]['commits']++;
                        $day = substr($commit['commit']['author']['date'] ?? '', 0, 10);
                        if ($day) {
                            $authorStats[$authorEmail]['active_days'][$day] = true;
                        }

                        // Fetch commit detail for stats + files
                        $sha            = $commit['sha'];
                        $detailResponse = $http->get("https://api.github.com/repos/{$orgName}/{$repoName}/commits/{$sha}");
                        if ($detailResponse->successful()) {
                            $detail = $detailResponse->json();
                            $stats  = $detail['stats'] ?? [];
                            $authorStats[$authorEmail]['lines_added']   += $stats['additions'] ?? 0;
                            $authorStats[$authorEmail]['lines_removed']  += $stats['deletions'] ?? 0;

                            foreach ($detail['files'] ?? [] as $file) {
                                $filename = $file['filename'] ?? '';
                                // File type
                                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                                if ($ext) {
                                    $authorStats[$authorEmail]['file_types'][$ext] =
                                        ($authorStats[$authorEmail]['file_types'][$ext] ?? 0) + 1;
                                }
                                // Code area (top-level dir)
                                $parts = explode('/', $filename);
                                if (count($parts) > 1) {
                                    $area = $parts[0] . '/' . ($parts[1] ?? '');
                                    $authorStats[$authorEmail]['code_areas'][$area] =
                                        ($authorStats[$authorEmail]['code_areas'][$area] ?? 0) + 1;
                                }
                            }
                        }
                    }

                    $commitsPage++;
                } while (count($commits ?? []) === 100);

                // PR reviews
                $prsResponse = $http->get("https://api.github.com/repos/{$orgName}/{$repoName}/pulls", [
                    'state'    => 'all',
                    'per_page' => 100,
                ]);

                if ($prsResponse->successful()) {
                    foreach ($prsResponse->json() as $pr) {
                        $reviewsResponse = $http->get(
                            "https://api.github.com/repos/{$orgName}/{$repoName}/pulls/{$pr['number']}/reviews"
                        );
                        if ($reviewsResponse->successful()) {
                            foreach ($reviewsResponse->json() as $review) {
                                $reviewerEmail = $review['user']['email'] ?? '';
                                if ($reviewerEmail && isset($employees[$reviewerEmail])) {
                                    $authorStats[$reviewerEmail]['pr_reviews'] =
                                        ($authorStats[$reviewerEmail]['pr_reviews'] ?? 0) + 1;
                                }
                            }
                        }
                    }
                }
            }

            // Write signals
            $synced = 0;
            foreach ($authorStats as $email => $stats) {
                $employee = $employees[$email] ?? null;
                if (!$employee) {
                    continue;
                }

                $commitCount  = $stats['commits'];
                $activeDays   = count($stats['active_days']);
                $linesAdded   = $stats['lines_added'];
                $linesRemoved = $stats['lines_removed'];
                $prReviews    = $stats['pr_reviews'];
                $fileTypes    = $stats['file_types'];
                $codeAreas    = $stats['code_areas'];

                $metrics = [
                    ['metric_key' => 'commit_count',      'metric_value' => $commitCount,  'metric_unit' => 'count',   'metadata' => null],
                    ['metric_key' => 'active_days_count', 'metric_value' => $activeDays,   'metric_unit' => 'days',    'metadata' => null],
                    ['metric_key' => 'pr_reviews_count',  'metric_value' => $prReviews,    'metric_unit' => 'count',   'metadata' => null],
                    ['metric_key' => 'lines_added_avg',   'metric_value' => $commitCount > 0 ? round($linesAdded / $commitCount, 1) : 0, 'metric_unit' => 'lines', 'metadata' => null],
                    ['metric_key' => 'lines_removed_avg', 'metric_value' => $commitCount > 0 ? round($linesRemoved / $commitCount, 1) : 0, 'metric_unit' => 'lines', 'metadata' => null],
                    ['metric_key' => 'file_types_touched', 'metric_value' => count($fileTypes), 'metric_unit' => 'count', 'metadata' => $fileTypes],
                    ['metric_key' => 'code_areas_touched', 'metric_value' => count($codeAreas), 'metric_unit' => 'count', 'metadata' => $codeAreas],
                ];

                foreach ($metrics as $metric) {
                    EmployeeSignal::updateOrCreate(
                        [
                            'employee_id'  => $employee->id,
                            'source_type'  => 'github',
                            'metric_key'   => $metric['metric_key'],
                            'period'       => $period,
                        ],
                        [
                            'organization_id' => $orgId,
                            'metric_value'    => $metric['metric_value'],
                            'metric_unit'     => $metric['metric_unit'],
                            'metadata'        => $metric['metadata'],
                        ]
                    );
                }

                $synced++;
            }

            $this->connection->update(['last_synced_at' => now()]);

            Log::info("GitHub signals sync complete: {$synced} employees", [
                'connection_id' => $this->connection->id,
            ]);
        } catch (\Exception $e) {
            Log::error('GitHub signals sync error: ' . $e->getMessage(), [
                'connection_id' => $this->connection->id,
            ]);
            throw $e;
        }
    }
}
