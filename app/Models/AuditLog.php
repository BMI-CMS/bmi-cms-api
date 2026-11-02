<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'correlation_id',
        'device_id',
        'ip_address',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }
}
