<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactInformation extends Model
{
    protected $fillable = [
        'account_id',
        'contact_number',
        'email',
        'social_media_account'
    ];
}
