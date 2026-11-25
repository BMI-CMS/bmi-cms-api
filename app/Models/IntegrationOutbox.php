<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\Integration\IntegrationEvent;
use App\Enums\Integration\IntegrationStatus;
use App\Enums\Integration\IntegrationSystem;

class IntegrationOutbox extends Model
{
    protected $fillable = [
        'idempotency_key',
        'destination_system',
        'event_type',
        'reference_type',
        'reference_id',
        'payload',
        'status',
        'attempts',
        'max_attempts',
        'last_error',
        'next_retry_at',
        'processing_token',
        'locked_until',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'destination_system' => IntegrationSystem::class,
            'event_type' => IntegrationEvent::class,
            'status' => IntegrationStatus::class,
            'payload' => 'array',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'next_retry_at' => 'datetime',
            'locked_until' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }
}
