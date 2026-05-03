<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AptitudeTest extends Model
{
    protected $fillable = [
        'placement_drive_id', 'organization_id', 'title', 'instructions',
        'time_limit_minutes', 'passing_score_pct', 'public_token', 'status',
        'opens_at', 'closes_at',
    ];

    protected function casts(): array
    {
        return [
            'opens_at'  => 'datetime',
            'closes_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $test) {
            if (empty($test->public_token)) {
                $test->public_token = Str::random(40);
            }
        });
    }

    public function drive(): BelongsTo    { return $this->belongsTo(PlacementDrive::class, 'placement_drive_id'); }
    public function questions(): HasMany  { return $this->hasMany(TestQuestion::class)->orderBy('order'); }
    public function attempts(): HasMany   { return $this->hasMany(TestAttempt::class); }
}
