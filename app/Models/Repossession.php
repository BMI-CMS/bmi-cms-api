<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Repossession extends Model
{
        use SoftDeletes;

        //  protected $table = 'repossessions';

        protected $fillable = [
                'account_id',
                'contact_recording_id',
                'repossession_request_id',
                'stockyard',
                'payment_status',
                'reason_for_unrepossessed',
                'status'
        ];
}
