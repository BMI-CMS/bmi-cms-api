<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Restructuring extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'contact_recording_id',
        'outstanding_balance',
        'monthly_amortization',
        'terms',
        'remarks'
    ];
}
