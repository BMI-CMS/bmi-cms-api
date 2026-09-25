<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactRecording extends Model
{
    use SoftDeletes;

    protected $table = "contact_recordings";

    protected $fillable = [
        'account_id',
        'follow_up_mode',
        'action_taken',
        'reason_for_default',
        'next_action_plan',
        'assigned_support_cc',
        'remarks',
        'geotagging',
        'recorded_by',
        'contact_date',
        'next_action_date'
    ];
}
