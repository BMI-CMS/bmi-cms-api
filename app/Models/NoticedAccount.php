<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoticedAccount extends Model
{
    protected $fillable = [
        'account_id',
        'contact_recording_id',
        'letter_type',
        'deliver_status',
        'recipient_name',
        'document',
        'remarks'
    ];
}
