<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FollowUpMode extends Model
{
    use SoftDeletes;

    protected $table = 'follow_up_modes';

    protected $fillable = [
        'text'
    ];
}
