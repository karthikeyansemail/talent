<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'organization_id', 'action', 'subject_type', 'subject_id', 'details',
    ];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function organization() { return $this->belongsTo(Organization::class); }
}
