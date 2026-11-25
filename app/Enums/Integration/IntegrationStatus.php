<?php

namespace App\Enums\Integration;

enum IntegrationStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case SENT = 'SENT';
    case FAILED = 'FAILED';
    case REJECTED = 'REJECTED';
    case EXHAUSTED = 'EXHAUSTED';
}
