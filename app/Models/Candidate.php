<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = [
        'organization_id', 'first_name', 'last_name', 'email', 'phone',
        'current_company', 'current_title', 'experience_years', 'source', 'notes',
    ];

    protected function casts(): array
    {
        return ['experience_years' => 'decimal:1'];
    }

    public function organization() { return $this->belongsTo(Organization::class); }
    public function resumes() { return $this->hasMany(Resume::class); }
    public function applications() { return $this->hasMany(JobApplication::class); }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
}
