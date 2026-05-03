<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestAttempt extends Model
{
    protected $fillable = [
        'aptitude_test_id', 'placement_drive_id', 'organization_id',
        'candidate_id', 'student_email', 'student_name', 'student_enrollment',
        'ip_address', 'started_at', 'submitted_at', 'time_taken_seconds',
        'total_marks_available', 'score_obtained', 'score_pct', 'passed',
        'grading_status',
    ];

    protected function casts(): array
    {
        return [
            'started_at'    => 'datetime',
            'submitted_at'  => 'datetime',
            'score_obtained'=> 'decimal:2',
            'score_pct'     => 'decimal:2',
            'passed'        => 'boolean',
        ];
    }

    public function test(): BelongsTo     { return $this->belongsTo(AptitudeTest::class, 'aptitude_test_id'); }
    public function drive(): BelongsTo    { return $this->belongsTo(PlacementDrive::class, 'placement_drive_id'); }
    public function candidate(): BelongsTo{ return $this->belongsTo(Candidate::class); }
    public function answers(): HasMany    { return $this->hasMany(TestAnswer::class); }
}
