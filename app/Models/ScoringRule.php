<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoringRule extends Model
{
    protected $fillable = [
        'organization_id', 'signal_key', 'signal_label', 'weight',
        'is_active', 'category', 'description',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
