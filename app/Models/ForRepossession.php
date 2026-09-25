<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForRepossession extends Model
{
    use SoftDeletes;

    protected $table = 'for_repossessions';

    protected $fillable = [
        'account_id',
        'repossession_request_id',
        'stockyard',
        'payment_status',
        'reason_for_unrepossessed',
        'status_name',
        'status'
    ];
}
