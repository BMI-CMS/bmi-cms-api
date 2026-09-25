<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyFollowUpMode extends Model
{
    use SoftDeletes;

    protected $table = 'company_follow_up_modes';

    protected $fillable = [
        'company_id',
        'follow_up_mode_id',
        'is_available'
    ];
}
