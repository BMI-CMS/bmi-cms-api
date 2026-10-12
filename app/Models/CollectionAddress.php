<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionAddress extends Model
{
    protected $table = 'collecting_addresses';

    protected $fillable = [
        'account_id',
        'unit_lot_block',
        'street_name',
        'subdivision_village',
        'province',
        'postal_code',
        'barangay',
        'city_municipality',
        'region',
    ];
}
