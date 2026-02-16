<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSprintSheet extends Model
{
    protected $fillable = [
        'project_id', 'organization_id', 'original_filename', 'file_path',
        'file_size', 'row_count', 'parsed_summary', 'status', 'error_message',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'parsed_summary' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
