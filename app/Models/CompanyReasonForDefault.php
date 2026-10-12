<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyReasonForDefault extends Model
{
    use SoftDeletes;

    protected $table = 'company_reason_for_defaults';

    protected $fillable = [
        'company_id',
        'reason_for_default_id',
        'is_available'
    ];
}
