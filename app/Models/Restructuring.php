<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Restructuring extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id'
    ];
}
