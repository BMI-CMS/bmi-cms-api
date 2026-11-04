<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EarlyEndorsed extends Model
{
    protected $fillable = [
        'account_id',
        'contact_recording_id',
        'reason_of_endorsement',
        'remarks',
    ];
}
