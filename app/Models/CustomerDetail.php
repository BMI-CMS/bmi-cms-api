<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerDetail extends Model
{
    protected $fillable = [
        'account_id',
        'contact_number',
        'email',
        'social_media_account'
    ];
}
