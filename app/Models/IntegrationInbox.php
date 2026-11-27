<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationInbox extends Model
{
    protected $fillable = [
        'source_system',
        'event_type',
        'idempotency_key',
        'reference_type',
        'reference_id',
        'payload',
        'payload_hash',
        'response',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
