<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    protected $fillable = [
        'candidate_id', 'file_path', 'file_name', 'file_type',
        'extracted_text', 'parsed_data', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['parsed_data' => 'array'];
    }

    public function candidate() { return $this->belongsTo(Candidate::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
