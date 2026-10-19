<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use Carbon\Carbon;
use App\Models\CmsUser;

class AccountSeeder extends Seeder
{
    private const MONTHLY_AMORTIZATION = 8500.00;

    // Quezon City, Pasig City, Makati City. Accounts take them in turn by index.
    private const PSGC_CODES = ['137404000', '137403000', '137602000'];

    public function run(): void
    {
        $today = Carbon::today();

        // On the 1st, "earlier this month" is today; on the last day, "later this month" is today.
        $dueToday = $today->copy();
        $dueEarlierThisMonth = $today->copy()->startOfMonth();
        $dueLaterThisMonth = $today->copy()->endOfMonth()->startOfDay();
        $dueLastMonth = $today->copy()->subMonthNoOverflow()->startOfMonth()->addDays(9);
        $dueNextMonth = $today->copy()->addMonthNoOverflow()->startOfMonth()->addDays(4);

        $accounts = [
            // ===== A. NO CONTACT RECORDING — base classification rules =====
            ['7100000001',  'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000002',  'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000003',  'DPD 61-90', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000004',  'DPD 1-30', 'UPDATED', 'FIRST DUE DATE', false, $dueToday],
            ['7100000005',  'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'NON-STARTER', false, $dueToday],
            ['7100000006',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000007',  'DPD 0', 'UPDATED', 'WITH PAYMENT', false, $dueToday],
            ['7100000008',  'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],

            // ===== A. Overlapping conditions (precedence) =====
            ['7100000009',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', true, $dueToday],
            ['7100000010',  'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', true, $dueToday],
            ['7100000011', 'DPD 1-30', 'UPDATED', 'FIRST DUE DATE', true, $dueToday],
            ['7100000012',  'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'FIRST DUE DATE', false, $dueToday],
            ['7100000013',  'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'NON-STARTER', false, $dueToday],
            ['7100000014',  'DPD 1-30', 'UPDATED', 'WITH PAYMENT', false, $dueToday],

            // ===== A. Combinations not covered by any rule yet =====
            ['7100000015',  'DPD 121-150', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000016',  'DPD 181-210', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000017',  'DPD MORE THAN 360', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000018',  'DPD 61-90', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000019',  'DPD 91-120', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000020',  'DPD 31-60', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000021', 'DPD 0', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],

            // ===== A. Scheduling by accounts.due_date =====
            ['7100000022',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7100000023',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueLaterThisMonth],
            ['7100000024',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueLastMonth],
            ['7100000025',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueNextMonth],

            // ===== A. Non-payments beyond NP3 =====
            ['7100000026',  'DPD 91-120', 'LAST PAID 4 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000027',  'DPD 61-90', 'LAST PAID 5 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000028',  'DPD 121-150', 'LAST PAID 6 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],

            // ===== B. WITH CONTACT RECORDING — due next action plans =====
            ['7200000001',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000002',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000003',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000004',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000005',  'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000006',  'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000007',  'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],

            // ===== B. Fallback to current_category =====
            ['7200000008',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000009',  'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000010',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000011',  'DPD 0', 'UPDATED', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000012', 'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000013',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'NON-STARTER', false, $dueEarlierThisMonth],
            ['7200000014',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000015',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],

            // ===== B. Precedence with a due next action plan =====
            ['7200000016',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', true, $dueEarlierThisMonth],
            ['7200000017', 'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000018', 'DPD 1-30', 'UPDATED', 'FIRST DUE DATE', false, $dueEarlierThisMonth],
            ['7200000019', 'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', true, $dueEarlierThisMonth],

            // ===== B. Multiple recordings / latest recording selection =====
            ['7200000020',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000021',  'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000022',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000023',  'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000024',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueLastMonth],

            // ===== B. Out-of-scope / unlisted next action plans =====
            ['7200000025',  'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000026',  'DPD 121-150', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000027',  'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000028',  'DPD 151-180', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000029',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000030',  'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000031',  'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
        ];

        foreach ($accounts as $index => [$accountNumber,  $dpdBucket, $nonPayments, $paymentStatus, $isForcePrioritized, $dueDate]) {
            $this->upsert($this->account(
                $index,
                $accountNumber,
                $dpdBucket,
                $nonPayments,
                $paymentStatus,
                $isForcePrioritized,
                str_starts_with($accountNumber, '72'),
                $dueDate,
                $today
            ));
        }
    }

    private function upsert(array $attributes): void
    {
        $account = Account::withTrashed()->updateOrCreate(
            ['account_number' => $attributes['account_number']],
            $attributes,
        );

        if ($account->trashed()) {
            $account->restore();
        }
    }

    private function fakeCustomerName(string $accountNumber): string
    {
        $faker = fake('en_PH');
        $faker->seed((int) $accountNumber);

        return $faker->firstName() . ' ' . $faker->lastName();
    }

    private function fakeAsset(): string
    {
        $motorcycleModels = [
            'Honda Click 125i',
            'Honda Beat',
            'Yamaha NMAX',
            'Yamaha Mio i 125',
            'Suzuki Raider R150',
        ];

        return fake()->randomElement($motorcycleModels);
    }

    private function fakeCC(): string
    {
        $ccUser = CmsUser::where('level', 1)
            ->pluck('id')
            ->toArray();

        return fake()->randomElement($ccUser);
    }

    private function monthsUnpaid(string $nonPayments): int
    {
        return preg_match('/LAST PAID (\d+) MONTHS? AGO/', $nonPayments, $matches) ? (int) $matches[1] : 0;
    }

    private function account(
        int $index,
        string $accountNumber,
        string $dpdBucket,
        string $nonPayments,
        string $paymentStatus,
        bool $isForcePrioritized,
        bool $isContacted,
        Carbon $dueDate,
        Carbon $today
    ): array {
        $monthsUnpaid = $this->monthsUnpaid($nonPayments);
        $outstandingBalance = self::MONTHLY_AMORTIZATION * 12;

        return [
            'account_number' => $accountNumber,
            'customer_id' => 130000000 + $index,
            'cust_code' => 130000000 + $index + 2,
            'customer_name' => $this->fakeCustomerName($accountNumber),
            'monthly_amortization' => self::MONTHLY_AMORTIZATION,
            'last_payment_date' => $today->copy()->subMonthsNoOverflow($monthsUnpaid)->subDays(5)->toDateString(),
            'due_date' => $dueDate->toDateString(),
            'payment_status' => $paymentStatus,
            'payment_history' => "LAST PAID 1 MONTH AGO",
            'dpd_bucket' => $dpdBucket,
            'soa_balance' => $outstandingBalance,
            'total_due_balance' => self::MONTHLY_AMORTIZATION * $monthsUnpaid,
            'past_due_balance' => self::MONTHLY_AMORTIZATION * $monthsUnpaid,
            'outstanding_balance' => $outstandingBalance,
            'asset' => $this->fakeAsset(),
            'psgc_code' => self::PSGC_CODES[$index % count(self::PSGC_CODES)],
            'is_force_prioritized' => $isForcePrioritized,
            'assigned_cc_id' => $this->fakeCC(),
            'assigned_ch_id' => 6,
            'assigned_am_ar_id' => 7,
            'assigned_rm_dh_id' => 8,
            'is_contacted' => $isContacted,
            'send_date' => null,
        ];
    }
}
