<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class AccountSeederV3 extends Seeder
{
    private const TERMS_IN_MONTHS  = [12, 18, 24, 36];
    private const DOWNPAYMENT_RATE = 0.20;  // placeholder, use your real rule
    private const FLAT_ANNUAL_RATE = 0.20;  // placeholder, use your real formula

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

    public function run(): void
    {
        $today = now()->startOfDay();

        $ccUsers = $this->usersByRole(1);
        $chIds   = $this->usersByRole(2)->pluck('id');  // placeholder role ids
        $amIds   = $this->usersByRole(3)->pluck('id');
        $rmIds   = $this->usersByRole(4)->pluck('id');

        foreach ($ccUsers as $ccUser) {
            $count = fake()->numberBetween(9, 20);

            for ($i = 0; $i < $count; $i++) {
                $account = $this->buildAccount($today);

                Account::create($account + [
                    'assigned_cc_id'     => $ccUser->id,
                    'assigned_ch_id'     => $this->pick($chIds),
                    'assigned_am_ar_id'  => $this->pick($amIds),
                    'assigned_rm__dh_id' => $this->pick($rmIds),
                ]);
            }
        }
    }

    private function buildAccount($today): array
    {
        $gender = fake()->randomElement(['male', 'female']);

        // Unit
        $brand = fake()->randomElement(array_keys(self::MOTORCYCLES));
        $model = fake()->randomElement(array_keys(self::MOTORCYCLES[$brand]));
        $srp   = self::MOTORCYCLES[$brand][$model];

        // Loan terms
        $term         = fake()->randomElement(self::TERMS_IN_MONTHS);
        $financed     = $srp * (1 - self::DOWNPAYMENT_RATE);
        $totalPayable = $financed * (1 + self::FLAT_ANNUAL_RATE * $term / 12);
        $monthly      = round($totalPayable / $term, 2);

        // Delinquency (about 40% current, the rest spread across buckets).
        // Capped so missed installments never exceed the term.
        $dpd = fake()->boolean(60) ? fake()->numberBetween(0, 30) : fake()->numberBetween(31, 425);
        // $dpd = min($dpd, ($term - 1) * 30);

        $missed = $dpd === 0 ? 0 : (int) ceil($dpd / 30);
        $paid   = fake()->numberBetween(1, $term - $missed);

        // Dates
        if ($dpd > 0) {
            $dueDate  = $today->copy()->subDays($dpd);   // oldest unpaid due date
            $sendDate = $today->copy();
        } else {
            $dueDate  = $today->copy()->startOfMonth()->addDays(fake()->numberBetween(0, 27));
            $sendDate = $today->copy()->startOfMonth();
        }
        $lastPaymentDate = $dueDate->copy()->subMonth();

        // Location
        $city     = fake()->randomElement(array_keys(self::PSGC));
        $barangay = fake()->randomElement(array_keys(self::PSGC[$city]));

        return [
            'account_number'       => fake()->unique()->numerify('#####'),
            'customer_id'          => fake()->unique()->numerify('#####'),
            'customer_name'        => fake()->firstName($gender) . ' ' . fake()->lastName(),
            'asset'                => "$brand $model",
            'psgc_code'            => self::PSGC[$city][$barangay],

            'monthly_amortization' => $monthly,
            'last_payment_amount'  => $monthly,
            'last_payment_date'    => $lastPaymentDate->toDateString(),
            'past_due_balance'     => round($missed * $monthly, 2),
            'outstanding_balance'  => round(($term - $paid) * $monthly, 2),

            'days_past_due'        => $dpd,
            'dpd_bucket'           => $this->dpdBucket($dpd),
            'no_of_non_payments'   => $missed,
            'payment_status'       => $dpd === 0 ? 'CURRENT' : 'PAST DUE', // match your real values

            'due_date'             => $dueDate->toDateString(),
            'send_date'            => $sendDate->toDateString(),
            'is_force_prioritized' => fake()->boolean(10),
            'is_contacted'         => fake()->boolean($dpd > 0 ? 60 : 20),
        ];
    }

    private function usersByRole(int $roleId): Collection
    {
        return User::whereHas('CmsUser', fn($q) => $q->where('role_id', $roleId))->get();
    }

    private function pick(Collection $ids): ?int
    {
        return $ids->isNotEmpty() ? $ids->random() : null;
    }
}
