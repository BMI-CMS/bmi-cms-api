<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromiseToPay extends Model
{
    use SoftDeletes;

    protected $table = 'promise_to_pays';

    protected $fillable = [
        'account_id',
        'contact_recording_id',
        'ptp_amount',
        'ptp_date',
        'remarks'
    ];
}
