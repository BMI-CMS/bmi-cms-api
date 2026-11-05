<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportReassignment extends Model
{
    protected $fillable = [
        'account_id',
        'contact_recording_id',
        'new_assigned_id',
        'next_action_date',
    ];
}
