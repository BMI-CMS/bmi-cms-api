<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CHValidation extends Model
{
    protected $table = 'ch_validations';

    protected $fillable = [
        'account_id',
        'contact_recording_id',
        'next_action_plan',
        'status',
        'remark',
        'next_action_date'
    ];
}
