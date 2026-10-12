<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyActionTakenType extends Model
{
    use SoftDeletes;

    protected $table = 'company_action_taken_types';

    protected $fillable = [
        'company_id',
        'action_taken_types_id',
        'is_available',
    ];
}
