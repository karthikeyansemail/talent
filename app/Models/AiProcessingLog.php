<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProcessingLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id', 'endpoint', 'request_payload', 'response_payload',
        'status', 'error_message', 'processing_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function organization() { return $this->belongsTo(Organization::class); }
}
