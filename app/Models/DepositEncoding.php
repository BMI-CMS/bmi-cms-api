<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepositEncoding extends Model
{
    protected $fillable = [
        'collection_id',
        'payment_channel',
        'depository_account',
        'transaction_reference_number',
        'deposit_date',
        'total_deposit_amount',
        'deposit_charge',
        'deposit_amount',
        'deposit_slip',
        'status',
        'remarks',
    ];
}
