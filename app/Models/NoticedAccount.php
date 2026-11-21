<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoticedAccount extends Model
{
    protected $fillable = [
        'account_id',
        'contact_recording_id',
        'letter_type',
        'reason_for_notice',
        'status',
        //'document',
        'remarks'
    ];
}
