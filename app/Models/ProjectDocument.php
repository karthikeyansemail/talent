<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectDocument extends Model
{
    protected $fillable = [
        'project_id',
        'organization_id',
        'document_type',
        'label',
        'original_filename',
        'file_path',
        'file_size',
        'file_type',
        'extracted_text',
        'uploaded_by',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isCharter(): bool
    {
        return $this->document_type === 'charter';
    }
}
