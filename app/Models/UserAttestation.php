<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAttestation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'is_attested',
    ];
}
