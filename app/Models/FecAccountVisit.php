<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FecAccountVisit extends Model
{
    protected $table = 'fec_account_visits';

    protected $fillable  = [
        'fec_accounts_id',
        'is_customer_available',
        'next_visit_date',
        'remarks'
    ];
}
