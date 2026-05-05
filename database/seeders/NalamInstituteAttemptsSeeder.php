<?php

namespace Database\Seeders;

use App\Models\AptitudeTest;
use App\Models\Candidate;
use App\Models\PlacementDrive;
use App\Models\TestAnswer;
use App\Models\TestAttempt;
use App\Models\TestQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Demo data for Student Progress dashboard:
 *  - Adds a 2nd placement drive (FinanceFirst Analytics) to Nalam Institute
 *  - Creates a small published aptitude test for each drive (5 hand-crafted
 *    questions — no AI call needed for seeding)
 *  - Generates ~3 attempts per student across the 2 drives spread over the
 *    last 8 weeks, with realistic per-student score profiles so the
 *    improvement chart + skill heatmap render meaningfully
 *
 * Run with: php artisan db:seed --class=NalamInstituteAttemptsSeeder
 */
class NalamInstituteAttemptsSeeder extends Seeder
{
    public function run(): void
    {
        $org = \App\Models\Organization::where('slug', 'nalam-institute')->first();
        if (!$org) {
            $this->command->warn('Nalam Institute org not found. Run NalamInstituteSeeder first.');
            return;
        }

        // ── Drive 2: FinanceFirst Analytics ─────────────────────────
        $financeDrive = PlacementDrive::firstOrCreate(
            ['organization_id' => $org->id, 'company_name' => 'FinanceFirst Analytics'],
            [
                'role_title'           => 'Junior Data Analyst',
                'description'          => 'FinanceFirst is hiring 2026 graduates for their Mumbai analytics team. Looking for strong SQL, Python (pandas), and statistical reasoning. Training period: 2 months.',
                'eligible_courses'     => ['B.Tech CSE', 'B.Tech IT', 'MCA'],
                'eligible_batch_years' => [2026],
                'min_cgpa'             => 7.00,
                'required_skills'      => ['SQL', 'Python', 'Statistics', 'Excel'],
                'package_lpa'          => 9,
                'drive_date'           => now()->addDays(28)->toDateString(),
                'test_format'          => 'aptitude_plus_interview',
                'status'               => 'open',
                'created_by'           => \App\Models\User::where('organization_id', $org->id)->where('role', 'org_admin')->first()?->id,
            ]
        );

        // The TechCorp drive seeded earlier
        $techDrive = PlacementDrive::where('organization_id', $org->id)
            ->where('company_name', 'TechCorp India')->first();

        // ── Tests for both drives ──────────────────────────────────
        $techTest = $this->ensureTechCorpTest($techDrive, $org->id);
        $finTest  = $this->ensureFinanceTest($financeDrive, $org->id);

        $this->command->info("Tests ready: {$techTest->questions->count()} Q for TechCorp, {$finTest->questions->count()} Q for FinanceFirst");

        // ── Generate attempts ──────────────────────────────────────
        $students = Candidate::where('organization_id', $org->id)->get();

        // Per-student profile: base ability 0.0-1.0 and improvement direction
        // (determines how scores trend across attempts).
        $profiles = [
            'aditya.sharma@nalaminstitute.edu'  => ['ability' => 0.85, 'trend' => 'stable'],
            'sneha.reddy@nalaminstitute.edu'    => ['ability' => 0.82, 'trend' => 'improving'],
            'karan.patel@nalaminstitute.edu'    => ['ability' => 0.62, 'trend' => 'improving'],
            'ananya.iyer@nalaminstitute.edu'    => ['ability' => 0.58, 'trend' => 'stable'],
            'vivek.nair@nalaminstitute.edu'     => ['ability' => 0.65, 'trend' => 'improving'],
            'riya.gupta@nalaminstitute.edu'     => ['ability' => 0.45, 'trend' => 'improving'],
            'suresh.pillai@nalaminstitute.edu'  => ['ability' => 0.35, 'trend' => 'stable'],
            'meena.joshi@nalaminstitute.edu'    => ['ability' => 0.40, 'trend' => 'improving'],
        ];

        $created = 0;
        // Each student does the TechCorp test first (8 weeks ago) then FinanceFirst (3 weeks ago)
        foreach ($students as $student) {
            $profile = $profiles[$student->email] ?? ['ability' => 0.5, 'trend' => 'stable'];

            // Attempt 1: TechCorp, ~8 weeks ago
            $this->createAttempt($techTest, $techDrive, $student, $profile, weeksAgo: 8, attemptNum: 1);
            // Attempt 2: FinanceFirst, ~3 weeks ago
            $this->createAttempt($finTest, $financeDrive, $student, $profile, weeksAgo: 3, attemptNum: 2);
            $created += 2;
        }

        $this->command->info("Created {$created} test attempts across " . count($students) . " students");
        $this->command->info("Visit: /placement/progress (login as arvind.krishnan@nalaminstitute.edu / NalamDemo@Edu1)");
    }

    /**
     * Create a TestAttempt + TestAnswers with realistic scoring.
     */
    private function createAttempt(AptitudeTest $test, PlacementDrive $drive, Candidate $student, array $profile, int $weeksAgo, int $attemptNum): void
    {
        // Skip if already attempted (idempotent re-runs)
        if (TestAttempt::where('aptitude_test_id', $test->id)->where('student_email', $student->email)->exists()) {
            return;
        }

        $startedAt = now()->subWeeks($weeksAgo)->subMinutes(rand(0, 60));
        $duration  = rand(15, $test->time_limit_minutes - 2);  // not at the wire
        $submittedAt = $startedAt->copy()->addMinutes($duration);

        // Adjust ability for "improving" trend on later attempts
        $effectiveAbility = $profile['ability'];
        if ($profile['trend'] === 'improving' && $attemptNum > 1) {
            $effectiveAbility = min(0.95, $profile['ability'] + 0.10);
        }

        $totalAvailable = $test->questions->sum('marks');
        $totalAwarded = 0.0;

        $attempt = TestAttempt::create([
            'aptitude_test_id'      => $test->id,
            'placement_drive_id'    => $drive->id,
            'organization_id'       => $test->organization_id,
            'candidate_id'          => $student->id,
            'student_email'         => $student->email,
            'student_name'          => $student->first_name . ' ' . $student->last_name,
            'student_enrollment'    => $student->enrollment_number,
            'ip_address'            => '127.0.0.1',
            'started_at'            => $startedAt,
            'submitted_at'          => $submittedAt,
            'time_taken_seconds'    => $duration * 60,
            'total_marks_available' => $totalAvailable,
            'grading_status'        => 'complete',
        ]);

        foreach ($test->questions as $q) {
            // Roll dice based on ability — chance of getting it right
            $isCorrect = (mt_rand(0, 100) / 100) <= $effectiveAbility;

            if ($q->type === 'mcq') {
                $selected = $isCorrect
                    ? $q->correct_option
                    : $this->wrongOption($q->correct_option, count($q->options ?? []));
                $marks = $isCorrect ? (float) $q->marks : 0;

                TestAnswer::create([
                    'test_attempt_id'  => $attempt->id,
                    'test_question_id' => $q->id,
                    'selected_option'  => $selected,
                    'is_correct'       => $isCorrect,
                    'marks_awarded'    => $marks,
                ]);
                $totalAwarded += $marks;
            } else {
                // Descriptive: understanding score scales with ability + small variance
                $understanding = max(0, min(100, (int) round($effectiveAbility * 100 + rand(-15, 10))));
                $marks = round((float) $q->marks * ($understanding / 100), 2);

                $rubric = $q->rubric_points ?? [];
                $coverage = array_map(fn() => (mt_rand(0, 100) / 100) <= $effectiveAbility, $rubric);

                TestAnswer::create([
                    'test_attempt_id'     => $attempt->id,
                    'test_question_id'    => $q->id,
                    'answer_text'         => $this->fakeStudentAnswer($q, $effectiveAbility),
                    'marks_awarded'       => $marks,
                    'understanding_score' => $understanding,
                    'rubric_coverage'     => $coverage,
                    'ai_feedback'         => $this->fakeFeedback($understanding),
                ]);
                $totalAwarded += $marks;
            }
        }

        $scorePct = $totalAvailable > 0 ? round(($totalAwarded / $totalAvailable) * 100, 2) : 0;
        $attempt->update([
            'score_obtained' => $totalAwarded,
            'score_pct'      => $scorePct,
            'passed'         => $scorePct >= $test->passing_score_pct,
        ]);
    }

    private function wrongOption(?int $correct, int $optionCount): int
    {
        if ($optionCount <= 1 || $correct === null) return 0;
        do {
            $idx = rand(0, $optionCount - 1);
        } while ($idx === $correct);
        return $idx;
    }

    private function fakeStudentAnswer(TestQuestion $q, float $ability): string
    {
        // Plausible-looking placeholder answer (length scales with ability)
        $shortPool = ['I think this code traverses the array.', 'It checks duplicates by adding to a set.', 'Uses a hash set for O(n) time.'];
        $longPool  = ['This function detects duplicate values in an array using a hash set. It iterates each element and checks membership in O(1) average time. Time complexity is O(n) and space is O(n) due to the set storage. Returns true on first duplicate, false otherwise.'];
        return $ability >= 0.6 ? $longPool[0] : $shortPool[array_rand($shortPool)];
    }

    private function fakeFeedback(int $understanding): string
    {
        if ($understanding >= 80) return 'Strong applied understanding. Clear reasoning with correct complexity analysis.';
        if ($understanding >= 60) return 'Good grasp of the core concept. Could go deeper into edge cases and tradeoffs.';
        if ($understanding >= 40) return 'Partial understanding — explains the surface mechanism but misses key reasoning.';
        return 'Answer is too brief or off-topic. Practice explaining the "why" behind your code, not just the "what".';
    }

    private function ensureTechCorpTest(PlacementDrive $drive, int $orgId): AptitudeTest
    {
        $test = AptitudeTest::where('placement_drive_id', $drive->id)->first();
        if ($test && $test->questions->count() > 0) {
            return $test->load('questions');
        }
        if (!$test) {
            $test = AptitudeTest::create([
                'placement_drive_id' => $drive->id,
                'organization_id'    => $orgId,
                'title'              => 'TechCorp SWE Aptitude Test',
                'instructions'       => 'Mix of MCQ and descriptive. AI grades descriptive answers for understanding, not keyword match.',
                'time_limit_minutes' => 45,
                'passing_score_pct'  => 60,
                'public_token'       => Str::random(40),
                'status'             => 'published',
            ]);
        }

        $questions = [
            ['mcq', 'DSA', 'medium', 1, 'What is the time complexity of looking up a key in a HashMap (average case)?',
                'Hash maps use a hash function to compute an index for O(1) average lookup, with O(n) worst-case if many collisions.',
                ['O(log n)', 'O(1)', 'O(n)', 'O(n log n)'], 1, '', []],
            ['mcq', 'OOP', 'easy', 1, 'Which OOP principle allows a subclass to be used in place of its parent class?',
                'Polymorphism — specifically the Liskov Substitution Principle.',
                ['Encapsulation', 'Inheritance', 'Polymorphism', 'Abstraction'], 2, '', []],
            ['mcq', 'DBMS', 'medium', 1, 'A query is slow on a 10M-row table. What index helps a WHERE on a frequently-filtered low-cardinality column?',
                'For low-cardinality columns (gender, status), a covering composite index or a bitmap index helps; a plain B-tree on a 2-value column is rarely useful alone.',
                ['B-tree on that column alone', 'Composite index combining the column with a high-cardinality one', 'Drop all indexes', 'Use SELECT *'], 1, '', []],
            ['descriptive', 'DSA', 'medium', 3,
                'Explain what this function does and identify its time and space complexity.',
                "def mystery(arr):\n    seen = set()\n    for x in arr:\n        if x in seen:\n            return True\n        seen.add(x)\n    return False",
                [], null,
                'This function returns True if the input array contains any duplicate value, otherwise False. It uses a hash set to remember values seen so far; checking membership is O(1) average. Time complexity is O(n) where n is array length, space is O(n) in the worst case (no duplicates) since every element is added to the set. Early return on first duplicate gives best-case O(1) extra time.',
                ['Identifies it detects duplicates', 'States O(n) time complexity', 'States O(n) space complexity', 'Mentions hash set / O(1) lookup', 'Notes early-return behaviour']],
            ['descriptive', 'System Design', 'medium', 3,
                'A web app handles 100 req/s today. The CTO wants to plan for 10x traffic. Where would you add caching, and what tradeoffs should you discuss?',
                '',
                [], null,
                'Add caching at three layers: (1) CDN/edge cache for static assets and cacheable HTML — biggest win for read-heavy traffic; (2) Application-level cache (Redis/Memcached) for hot DB query results, computed views, and session data; (3) Database query cache or materialized views for expensive aggregations. Tradeoffs: cache invalidation is hard (stale data risks), memory cost, increased deployment complexity. Start with CDN for immediate ROI, add Redis for hot keys with explicit TTLs, and only consider DB-level caching last.',
                ['Mentions multiple cache layers', 'CDN/edge caching', 'Application/Redis caching', 'Discusses cache invalidation tradeoff', 'Mentions monitoring or TTL']],
        ];

        foreach ($questions as $i => [$type, $topic, $difficulty, $marks, $qText, $context, $options, $correctOpt, $idealAnswer, $rubric]) {
            TestQuestion::create([
                'aptitude_test_id'    => $test->id,
                'order'               => $i + 1,
                'type'                => $type,
                'question_text'       => $qText,
                'context'             => $context ?: null,
                'topic'               => $topic,
                'difficulty'          => $difficulty,
                'marks'               => $marks,
                'options'             => $options ?: null,
                'correct_option'      => $correctOpt,
                'ideal_answer'        => $idealAnswer ?: null,
                'rubric_points'       => $rubric ?: null,
                'expected_word_count' => $type === 'descriptive' ? 120 : null,
            ]);
        }
        return $test->load('questions');
    }

    private function ensureFinanceTest(PlacementDrive $drive, int $orgId): AptitudeTest
    {
        $test = AptitudeTest::where('placement_drive_id', $drive->id)->first();
        if ($test && $test->questions->count() > 0) {
            return $test->load('questions');
        }
        if (!$test) {
            $test = AptitudeTest::create([
                'placement_drive_id' => $drive->id,
                'organization_id'    => $orgId,
                'title'              => 'FinanceFirst Analyst Aptitude Test',
                'instructions'       => 'SQL, Python (pandas) and statistical reasoning. Show your work in descriptive answers.',
                'time_limit_minutes' => 40,
                'passing_score_pct'  => 60,
                'public_token'       => Str::random(40),
                'status'             => 'published',
            ]);
        }

        $questions = [
            ['mcq', 'SQL', 'medium', 1,
                'Given the table below, which query returns departments where AVG salary > 75000?',
                "id | name  | dept | salary\n1  | Asha  | ENG  | 80000\n2  | Bran  | ENG  | 95000\n3  | Cara  | HR   | 70000",
                [
                    'SELECT dept FROM emp WHERE salary > 75000',
                    'SELECT dept FROM emp GROUP BY dept HAVING AVG(salary) > 75000',
                    'SELECT dept, AVG(salary) FROM emp WHERE AVG(salary) > 75000',
                    'SELECT * FROM emp WHERE salary > 75000 GROUP BY dept',
                ], 1, '', []],
            ['mcq', 'Quantitative', 'easy', 1,
                'A dataset has values [4, 8, 6, 5, 3]. What is the median?',
                'Sort: [3,4,5,6,8]. Middle value (5th of 5) is 5.',
                ['4', '5', '6', '5.2'], 1, '', []],
            ['mcq', 'Python', 'medium', 1,
                'In pandas, df.groupby("dept")["salary"].mean() returns a Series. How do you reset its index to a DataFrame?',
                'Add .reset_index() to convert the grouped Series back into a DataFrame with dept as a column.',
                ['.to_frame()', '.reset_index()', '.reindex()', '.unstack()'], 1, '', []],
            ['descriptive', 'SQL', 'medium', 3,
                'A query SELECT * FROM orders WHERE created_at >= "2026-01-01" runs in 8 seconds on a 5M-row table. What three things would you investigate first to speed it up?',
                '',
                [], null,
                'Three first checks: (1) Is there an index on created_at? Without one, this is a full table scan. EXPLAIN will confirm. (2) SELECT * pulls every column — large rows multiply IO. List only needed columns. (3) Is the date column the right type? If created_at is varchar, the index (if any) may not be used; cast or fix the schema. Bonus: check the query plan for the chosen access path, look for missing partition pruning if the table is partitioned.',
                ['Suggests checking/adding an index on created_at', 'Mentions SELECT * is wasteful', 'Mentions EXPLAIN / query plan', 'Discusses column types or partitioning']],
            ['descriptive', 'Statistics', 'easy', 2,
                'Explain the difference between correlation and causation, with one concrete example each.',
                '',
                [], null,
                'Correlation means two variables move together statistically (e.g. ice cream sales and drowning rates rise in summer — both correlate but neither causes the other; both are driven by a confounder, hot weather). Causation means one variable directly produces a change in the other (e.g. taking aspirin reduces headache pain — controlled trials confirm the causal link). Correlation is necessary but not sufficient for causation. Establishing causation requires experiments (RCTs), eliminating confounders, and ruling out reverse causation.',
                ['Defines correlation', 'Defines causation', 'Provides distinct examples', 'Mentions confounders or experiments']],
        ];

        foreach ($questions as $i => [$type, $topic, $difficulty, $marks, $qText, $context, $options, $correctOpt, $idealAnswer, $rubric]) {
            TestQuestion::create([
                'aptitude_test_id'    => $test->id,
                'order'               => $i + 1,
                'type'                => $type,
                'question_text'       => $qText,
                'context'             => $context ?: null,
                'topic'               => $topic,
                'difficulty'          => $difficulty,
                'marks'               => $marks,
                'options'             => $options ?: null,
                'correct_option'      => $correctOpt,
                'ideal_answer'        => $idealAnswer ?: null,
                'rubric_points'       => $rubric ?: null,
                'expected_word_count' => $type === 'descriptive' ? 120 : null,
            ]);
        }
        return $test->load('questions');
    }
}
