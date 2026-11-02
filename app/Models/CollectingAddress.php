<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CollectingAddress extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_details_id',
        'unit_lot_block',
        'street_name',
        'subdivision_village',
        'barangay',
        'city_municipality',
        'province',
        'region',
        'postal_code'
    ];
}
