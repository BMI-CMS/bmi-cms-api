<?php

namespace App\Queries;

use App\Models\CollectionAddress;

class CollectionAddressQuery
{
    public static function collectionAddress(string  $startDate)
    {
        return CollectionAddress::select([
            'id',
            'account_id',
            'unit_lot_block',
            'street_name',
            'subdivision_village',
            'province',
            'postal_code',
            'barangay',
            'city_municipality',
            'region'
        ])
            ->where('next_action_date', '<', $startDate);
    }
}
