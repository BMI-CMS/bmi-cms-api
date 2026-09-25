<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReasonForDefault extends Model
{
    use SoftDeletes;

    protected $table = 'reason_for_defaults';

    protected $fillable = [
        'text'
    ];
}
