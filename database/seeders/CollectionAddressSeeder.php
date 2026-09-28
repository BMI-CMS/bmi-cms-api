<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CollectionAddress;

class CollectionAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $addresses = [
            // PSGC: 137404000 — 3 accounts
            [
                'account_id' => 1,
                'psgc_code' => '137404000',
                'unit_lot_block' => 'Unit 101',
                'street_name' => 'Ayala Avenue',
                'subdivision_village' => 'Bel-Air Village',
                'province' => 'Metro Manila',
                'postal_code' => '1200',
                'barangay' => 'Bel-Air',
                'city_municipality' => 'Makati City',
                'region' => 'NCR',
            ],
            [
                'account_id' => 2,
                'psgc_code' => '137404000',
                'unit_lot_block' => 'Lot 12 Block 5',
                'street_name' => 'Makati Avenue',
                'subdivision_village' => 'Urdaneta Village',
                'province' => 'Metro Manila',
                'postal_code' => '1226',
                'barangay' => 'Urdaneta',
                'city_municipality' => 'Makati City',
                'region' => 'NCR',
            ],
            [
                'account_id' => 3,
                'psgc_code' => '137404000',
                'unit_lot_block' => 'Unit 305',
                'street_name' => 'Jupiter Street',
                'subdivision_village' => 'Bel-Air',
                'province' => 'Metro Manila',
                'postal_code' => '1209',
                'barangay' => 'Bel-Air',
                'city_municipality' => 'Makati City',
                'region' => 'NCR',
            ],

            // PSGC: 137501000 — 3 accounts
            [
                'account_id' => 4,
                'psgc_code' => '137501000',
                'unit_lot_block' => 'Lot 8 Block 2',
                'street_name' => 'Rizal Avenue',
                'subdivision_village' => 'San Antonio Village',
                'province' => 'Metro Manila',
                'postal_code' => '1103',
                'barangay' => 'San Antonio',
                'city_municipality' => 'Quezon City',
                'region' => 'NCR',
            ],
            [
                'account_id' => 5,
                'psgc_code' => '137501000',
                'unit_lot_block' => 'Unit 204',
                'street_name' => 'Commonwealth Avenue',
                'subdivision_village' => 'Teachers Village',
                'province' => 'Metro Manila',
                'postal_code' => '1101',
                'barangay' => 'Teachers Village East',
                'city_municipality' => 'Quezon City',
                'region' => 'NCR',
            ],
            [
                'account_id' => 6,
                'psgc_code' => '137501000',
                'unit_lot_block' => 'Lot 15 Block 7',
                'street_name' => 'Katipunan Avenue',
                'subdivision_village' => 'Blue Ridge',
                'province' => 'Metro Manila',
                'postal_code' => '1109',
                'barangay' => 'Blue Ridge A',
                'city_municipality' => 'Quezon City',
                'region' => 'NCR',
            ],

            // PSGC: 042108000 — 3 accounts
            [
                'account_id' => 7,
                'psgc_code' => '042108000',
                'unit_lot_block' => 'Lot 10 Block 4',
                'street_name' => 'Governor Drive',
                'subdivision_village' => 'Dasmarinas Village',
                'province' => 'Cavite',
                'postal_code' => '4114',
                'barangay' => 'Salitran',
                'city_municipality' => 'Dasmarinas City',
                'region' => 'CALABARZON',
            ],
            [
                'account_id' => 8,
                'psgc_code' => '042108000',
                'unit_lot_block' => 'Unit 3A',
                'street_name' => 'Paliparan Road',
                'subdivision_village' => 'Springville',
                'province' => 'Cavite',
                'postal_code' => '4114',
                'barangay' => 'Paliparan I',
                'city_municipality' => 'Dasmarinas City',
                'region' => 'CALABARZON',
            ],
            [
                'account_id' => 9,
                'psgc_code' => '042108000',
                'unit_lot_block' => 'Lot 22 Block 8',
                'street_name' => 'Molino Boulevard',
                'subdivision_village' => 'Lancaster',
                'province' => 'Cavite',
                'postal_code' => '4102',
                'barangay' => 'Buhay na Tubig',
                'city_municipality' => 'Imus City',
                'region' => 'CALABARZON',
            ],

            // PSGC: 138060000 — 3 accounts
            [
                'account_id' => 10,
                'psgc_code' => '138060000',
                'unit_lot_block' => 'Lot 5 Block 3',
                'street_name' => 'MacArthur Highway',
                'subdivision_village' => 'San Fernando Heights',
                'province' => 'Pampanga',
                'postal_code' => '2000',
                'barangay' => 'Dolores',
                'city_municipality' => 'San Fernando City',
                'region' => 'CENTRAL LUZON',
            ],
            [
                'account_id' => 11,
                'psgc_code' => '138060000',
                'unit_lot_block' => 'Unit 201',
                'street_name' => 'Jose Abad Santos Avenue',
                'subdivision_village' => 'Greenfields',
                'province' => 'Pampanga',
                'postal_code' => '2000',
                'barangay' => 'San Agustin',
                'city_municipality' => 'San Fernando City',
                'region' => 'CENTRAL LUZON',
            ],
            [
                'account_id' => 12,
                'psgc_code' => '138060000',
                'unit_lot_block' => 'Lot 18 Block 6',
                'street_name' => 'Sindalan Road',
                'subdivision_village' => 'Sunrise Village',
                'province' => 'Pampanga',
                'postal_code' => '2000',
                'barangay' => 'Sindalan',
                'city_municipality' => 'San Fernando City',
                'region' => 'CENTRAL LUZON',
            ],

            // PSGC: 072217000 — 2 accounts
            [
                'account_id' => 13,
                'psgc_code' => '072217000',
                'unit_lot_block' => 'Lot 7 Block 2',
                'street_name' => 'Osmena Boulevard',
                'subdivision_village' => 'Capitol Site',
                'province' => 'Cebu',
                'postal_code' => '6000',
                'barangay' => 'Capitol Site',
                'city_municipality' => 'Cebu City',
                'region' => 'CENTRAL VISAYAS',
            ],
            [
                'account_id' => 14,
                'psgc_code' => '072217000',
                'unit_lot_block' => 'Unit 4B',
                'street_name' => 'Fuente Osmena',
                'subdivision_village' => 'Mango Avenue',
                'province' => 'Cebu',
                'postal_code' => '6000',
                'barangay' => 'Kamputhaw',
                'city_municipality' => 'Cebu City',
                'region' => 'CENTRAL VISAYAS',
            ],
        ];

        $count = 1;
        foreach ($addresses as $accountNumber => $address) {
            CollectionAddress::create([
                'account_id' => $count,
                ...$address,
            ]);
            $count++;
        }
    }
}
