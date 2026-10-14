<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeederV2 extends Seeder
{

    private function dpdBucket(int $days): string
    {
        return match (true) {
            $days <= 0   => 'DPD 0',
            $days <= 30  => 'DPD 1-30',
            $days <= 60  => 'DPD 31-60',
            $days <= 90  => 'DPD 61-90',
            $days <= 120 => 'DPD 91-120',
            $days <= 150 => 'DPD 121-150',
            $days <= 180 => 'DPD 151-180',
            $days <= 210 => 'DPD 181-210',
            $days <= 240 => 'DPD 211-240',
            $days <= 270 => 'DPD 241-270',
            $days <= 300 => 'DPD 271-300',
            $days <= 330 => 'DPD 301-330',
            $days <= 360 => 'DPD 331-360',
            default      => 'DPD MORE THAN 360',
        };
    }

    private const MOTORCYCLES = [
        'Honda' => [
            'Beat'       => 71900.00,
            'Click 125'  => 79900.00,
            'Click 160'  => 112900.00,
            'TMX 125'    => 61900.00,
            'XRM 125'    => 74900.00,
            'ADV 160'    => 165900.00,
            'PCX 160'    => 149900.00,
        ],
        'Yamaha' => [
            'Mio i 125'  => 76900.00,
            'Mio Gear'   => 79900.00,
            'Aerox 155'  => 133900.00,
            'NMAX 155'   => 144900.00,
            'Sniper 155' => 118900.00,
            'XSR 155'    => 149900.00,
        ],
        'Suzuki' => [
            'Smash 115'          => 58900.00,
            'Skydrive 125'       => 75900.00,
            'Raider R150'        => 107900.00,
            'Burgman Street 125' => 99900.00,
        ],
        'Kawasaki' => [
            'CT100'        => 56900.00,
            'Barako 175'   => 95900.00,
            'Rouser NS200' => 149900.00,
            'Ninja 400'    => 389000.00,
        ],
    ];

    private const PSGC = [
        'MUNTINLUPA' => [
            'Alabang'   => '1380800001',
            'Bayanan'   => '1380800002',
            'Buli'      => '1380800003',
            'Cupang'    => '1380800004',
            'Poblacion' => '1380800005',
            'Putatan'   => '1380800006',
            'Sucat'     => '1380800007',
            'Tunasan'   => '1380800008',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ccUsers = User::whereHas('cms_users', function ($query) {
            $query->where('role_id', "=", 1);
        })->get();

        foreach ($ccUsers as $ccUser) {
            $accounts = fake()->numberBetween(9, 21);

            for($account = 1; $account < $accounts; $account++){
                $account_number = fake()->unique()->numerify("#####");
                $customer_id = fake()->unique()->numerify("#####");
                $customer_name = fake()->firstName($gender) . " " . fake()->lastName();

                $paymentTermsInYears = fake()->range(1,3);
                $paymentTermsInMonths = $paymentTermsInYears * 12;

                $brand = fake()->randomElement(array_keys(self::MOTORCYCLES));
                $model = fake()->randomElement(array_keys(self::MOTORCYCLES[$brand]));
                $srp   = self::MOTORCYCLES[$brand][$model];

                $monthly_amortization = $srp / $paymentTermsInMonths;
                $gender = fake()->boolean() ? 'male' : "female";
                $dpd = fake()->numberBetween(0, 425);
                $dpdBucket = $this->dpdBucket($dpd);

                $psgc_code = fake()->

                Account::create([
                    'account_number' => $account_number,
                    'customer_id' => $customer_id,
                    'customer_name' => $customer_name,
                    'monthly_amortization' => $monthly_amortization,
                    'last_payment_amount' => $monthly_amortization,
                    'past_due_balance' =>
                    'days_past_due' => $dpd,
                    'dpd_bucket' => $dpdBucket
                    'no_of_non_payments' =>
                    'outstanding_balance' =>
                    'last_payment_date' =>
                    'payment_status' =>
                    'asset' => "$brand $model"
                    'psgc_code' =>
                    'is_force_prioritized' =>
                    'assigned_cc_id' =>
                    'assigned_ch_id' =>
                    'assigned_am_ar_id' =>
                    'assigned_rm__dh_id' =>
                    'is_contacted' =>
                    'due_date' =>
                    'send_date'
                ]);

            }
        };


    }
}
