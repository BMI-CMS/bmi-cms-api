<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerDetail extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'contact_number',
        'email',
        'social_media_account',
        'collecting_address',
        'permanent_address'
    ];
}
