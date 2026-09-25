<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceiptEncoding extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'contact_recording_id',
        'for_repossession_id',
        'collection_id',
        'ar_number',
        'ar_date',
        'amount',
        'receipt_image_path',
    ];
}
