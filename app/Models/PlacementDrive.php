<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlacementDrive extends Model
{
    protected $fillable = [
        'organization_id', 'company_name', 'role_title', 'description',
        'eligible_courses', 'eligible_batch_years', 'min_cgpa', 'required_skills',
        'package_lpa', 'drive_date', 'test_format', 'status',
        'source_doc_path', 'source_doc_text', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'eligible_courses'     => 'array',
            'eligible_batch_years' => 'array',
            'required_skills'      => 'array',
            'min_cgpa'             => 'decimal:2',
            'drive_date'           => 'date',
        ];
    }

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function creator(): BelongsTo      { return $this->belongsTo(User::class, 'created_by'); }
    public function tests(): HasMany          { return $this->hasMany(AptitudeTest::class); }
    public function attempts(): HasMany       { return $this->hasMany(TestAttempt::class); }
}
