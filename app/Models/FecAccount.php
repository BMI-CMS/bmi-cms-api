<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FecAccount extends Model
{
    protected $fillable = [
        'account_number',
        'customer_id',
        'customer_name',
        'monthly_amortization',
        'past_due_balance',
        'days_past_due',
        'dpd_bucket',
        'no_of_non_payments',
        'outstanding_balance',
        'last_payment_date',
        'asset',
        'psgc_code',
        'is_adverse_finding',
        'form_status',
        'is_force_prioritized',
        'cms_user_id',
        'assigned_date',
        'follow_up_date',
        'created_at'
    ];
}
