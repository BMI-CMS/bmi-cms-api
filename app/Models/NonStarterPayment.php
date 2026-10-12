<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NonStarterPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'contact_recording_id',
        'monthly_amortization',
        'total_payment',
        'shortfall_amount',
        'next_action_date'
    ];
}
