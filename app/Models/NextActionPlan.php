<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NextActionPlan extends Model
{
    use SoftDeletes;

    protected $table = 'next_action_plans';

    protected $fillable = [
        'text'
    ];
}
