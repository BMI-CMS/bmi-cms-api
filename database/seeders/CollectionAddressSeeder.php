<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\CollectionAddress;

/**
 * Collecting address of every account, in the city of the account's own psgc_code,
 * so the address always agrees with accounts.psgc_code (see AccountSeeder::PSGC_CODES).
 * Street, barangay and lot are filler that vary per account.
 * Safe to rerun: addresses are matched by account_id.
 */
class CollectionAddressSeeder extends Seeder
{
    private const AREAS = [
        // Quezon City
        '137404000' => [
            'city_municipality' => 'Quezon City',
            'postal_code' => '1100',
            'street_name' => 'Commonwealth Avenue',
            'subdivision_village' => 'Villa Beatriz',
            'barangays' => ['Batasan Hills', 'Bagumbayan', 'Holy Spirit'],
        ],
        // Pasig City
        '137403000' => [
            'city_municipality' => 'Pasig City',
            'postal_code' => '1600',
            'street_name' => 'Shaw Boulevard',
            'subdivision_village' => 'Greenmeadows',
            'barangays' => ['San Antonio', 'Kapitolyo', 'Ugong'],
        ],
        // Makati City
        '137602000' => [
            'city_municipality' => 'Makati City',
            'postal_code' => '1200',
            'street_name' => 'Makati Avenue',
            'subdivision_village' => 'Bel-Air Village',
            'barangays' => ['Poblacion', 'Bel-Air', 'San Lorenzo'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Account::query()->orderBy('id')->get(['id', 'psgc_code']) as $account) {
            $area = self::AREAS[$account->psgc_code] ?? null;

            if (! $area) {
                $this->command?->warn("No address area for psgc_code {$account->psgc_code} (account {$account->id}); skipped.");

                continue;
            }

            $barangays = $area['barangays'];

            CollectionAddress::updateOrCreate(
                ['account_id' => $account->id],
                [
                    'unit_lot_block' => 'Lot '.(($account->id % 30) + 1).' Block '.(($account->id % 9) + 1),
                    'street_name' => $area['street_name'],
                    'subdivision_village' => $area['subdivision_village'],
                    'province' => 'Metro Manila',
                    'postal_code' => $area['postal_code'],
                    'barangay' => $barangays[$account->id % count($barangays)],
                    'city_municipality' => $area['city_municipality'],
                    'region' => 'NCR',
                ],
            );
        }
    }
}
