<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActionTakenType extends Model
{
    use SoftDeletes;

    protected $table = 'action_taken_types';

    protected $fillable = [
        'text'
    ];
}
