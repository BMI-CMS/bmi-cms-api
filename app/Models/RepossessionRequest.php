<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepossessionRequest extends Model
{
    use SoftDeletes;

    protected $table = 'repossession_requests';

    protected $fillable = [
        'contact_recording_id',
        'is_first_level_approved',
        'first_level_approved_by',
        'first_level_review_at',
        'first_level_approved_remarks',
        'is_second_level_approved',
        'second_level_approved_by',
        'second_level_review_at',
        'second_level_approved_remarks',
        'is_third_level_approved',
        'third_level_approved_by',
        'third_level_review_at',
        'third_level_approved_remarks',
    ];
}
