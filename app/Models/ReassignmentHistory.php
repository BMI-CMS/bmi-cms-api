<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReassignmentHistory extends Model
{
    protected $fillable = [
        'account_id',
        'previous_assigned_id',
        'new_assigned_id',
        'assigned_by'
    ];
}
