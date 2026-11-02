<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CollectingAddress;
use App\Models\CustomerDetail;

class CollectingAddressSeeder extends Seeder
{


    public function run(): void
    {
        foreach (CustomerDetail::all() as $account) {
            $faker = fake('en_PH');
            $faker->seed((int) $account->id);

            CollectingAddress::updateOrCreate(
                [
                    'customer_details_id' => $account->id,
                    'unit_lot_block'  => $faker->buildingNumber,
                    'street_name'  => $faker->streetName,
                    'subdivision_village'  =>  $this->randomSubdivision(),
                    'barangay'  => $this->randomBarangay(),
                    'city_municipality'  => 'Muntilupa',
                    'province'  => '',
                    'region'  => 'NCR',
                    'postal_code' => '1770'
                ],
            );
        }
    }

    public function randomBarangay()
    {
        return fake()->randomElement([
            'Alabang',
            'Bayanan',
            'Buli',
            'Cupang',
            'Poblacion',
            'Putatan',
            'Sucat',
            'Tunasan'
        ]);
    }
    public function randomSubdivision()
    {
        return fake()->randomElement([
            'Katarungan Village',
            'Almanville Subdivision',
            'South Greenheights Village',
            'Posadas Village',
            'Rhapsody Residences,',
            'Parkhomes Subdivision',
            'Abbey Place Subdivision',
            'Parkhomes Subdivision'
        ]);
    }
}
