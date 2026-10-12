<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyNextActionPlan extends Model
{
    use SoftDeletes;

    protected $table = 'company_next_action_plans';

    protected $fillable = [
        'company_id',
        'next_action_plan_id',
        'role_id',
        'is_available'
    ];
}
