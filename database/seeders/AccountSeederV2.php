<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class AccountSeederV2 extends Seeder
{
    private const TERMS_IN_MONTHS = [12, 18, 24, 36];

    private const DOWNPAYMENT_RATE = 0.20;

    private const FLAT_ANNUAL_RATE = 0.20;

    private function dpdBucket(int $days): string
    {
        return match (true) {
            $days <= 0 => 'DPD 0',
            $days <= 30 => 'DPD 1-30',
            $days <= 60 => 'DPD 31-60',
            $days <= 90 => 'DPD 61-90',
            $days <= 120 => 'DPD 91-120',
            $days <= 150 => 'DPD 121-150',
            $days <= 180 => 'DPD 151-180',
            $days <= 210 => 'DPD 181-210',
            $days <= 240 => 'DPD 211-240',
            $days <= 270 => 'DPD 241-270',
            $days <= 300 => 'DPD 271-300',
            $days <= 330 => 'DPD 301-330',
            $days <= 360 => 'DPD 331-360',
            default => 'DPD MORE THAN 360',
        };
    }

    private const MOTORCYCLES = [
        'Honda' => [
            'Beat' => 71900.00,
            'Click 125' => 79900.00,
            'Click 160' => 112900.00,
            'TMX 125' => 61900.00,
            'XRM 125' => 74900.00,
            'ADV 160' => 165900.00,
            'PCX 160' => 149900.00,
        ],
        'Yamaha' => [
            'Mio i 125' => 76900.00,
            'Mio Gear' => 79900.00,
            'Aerox 155' => 133900.00,
            'NMAX 155' => 144900.00,
            'Sniper 155' => 118900.00,
            'XSR 155' => 149900.00,
        ],
        'Suzuki' => [
            'Smash 115' => 58900.00,
            'Skydrive 125' => 75900.00,
            'Raider R150' => 107900.00,
            'Burgman Street 125' => 99900.00,
        ],
        'Kawasaki' => [
            'CT100' => 56900.00,
            'Barako 175' => 95900.00,
            'Rouser NS200' => 149900.00,
            'Ninja 400' => 389000.00,
        ],
    ];

    private const PSGC = [
        'MUNTINLUPA' => [
            'Alabang' => '1380800001',
            'Bayanan' => '1380800002',
            'Buli' => '1380800003',
            'Cupang' => '1380800004',
            'Poblacion' => '1380800005',
            'Putatan' => '1380800006',
            'Sucat' => '1380800007',
            'Tunasan' => '1380800008',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $ccUsers = $this->usersByRole(1);

        $today = Carbon::today();

        $dueToday = $today->copy();
        $dueEarlierThisMonth = $today->copy()->startOfMonth();
        $dueLaterThisMonth = $today->copy()->endOfMonth()->startOfDay();
        $dueLastMonth = $today->copy()->subMonthNoOverflow()->startOfMonth()->addDays(9);
        $dueNextMonth = $today->copy()->addMonthNoOverflow()->startOfMonth()->addDays(4);

        foreach ($ccUsers as $ccUser) {
            $this->buildCCVisitPlan(
                $ccUser->id,
                $today,
                $dueToday,
                $dueEarlierThisMonth,
                $dueLaterThisMonth,
                $dueLastMonth,
                $dueNextMonth, );
        }
    }

    private function buildCCVisitPlan(
        int $ccUserId,
        Carbon $today,
        Carbon $dueToday,
        Carbon $dueEarlierThisMonth,
        Carbon $dueLaterThisMonth,
        Carbon $dueLastMonth,
        Carbon $dueNextMonth,
    ) {
        $chIds = $this->usersByRole(2)->pluck('id');  // placeholder role ids
        $amIds = $this->usersByRole(3)->pluck('id');
        $rmIds = $this->usersByRole(4)->pluck('id');

        $noContactRecording = $this->getNoContactRecording($dueToday);
        $verlappingConditions = $this->getOverlappingConditions($dueToday);
        $notCoveredByAnyRule = $this->getNotCoveredByAnyRule($dueToday);
        $scheduledByDueDate = $this->getScheduledByDueDate($dueEarlierThisMonth,
            $dueLaterThisMonth,
            $dueNextMonth, $dueLastMonth);
        $noPaymentsBeyondNP3 = $this->getNoPaymentsBeyondNP3($dueToday);
        $withContactRecording = $this->getWithContactRecording($dueEarlierThisMonth);
        $currentCategory = $this->getCurrentCategory($dueEarlierThisMonth);
        $precendenceWithDueNextActionPlan = $this->getPrecendenceWithDueNextActionPlan($dueEarlierThisMonth);
        $multipleRecordings = $this->getMultipleRecordings($dueEarlierThisMonth, $dueLastMonth);
        $outOfScope = $this->getOutOfScope($dueEarlierThisMonth);

        $accounts = [
            ...$noContactRecording,
            ...$verlappingConditions,
            ...$notCoveredByAnyRule,
            ...$scheduledByDueDate,
            ...$noPaymentsBeyondNP3,
            ...$withContactRecording,
            ...$currentCategory,
            ...$precendenceWithDueNextActionPlan,
            ...$multipleRecordings,
            ...$outOfScope,
        ];

        $assignedCH = $this->pick($chIds);
        $assignedAMRM = $this->pick($amIds);
        $assignedRMDH = $this->pick($rmIds);

        foreach ($accounts as $index => [$dpdBucket, $nonPayments, $paymentStatus, $isForcePrioritized, $dueDate]) {
            $accountAttributes = $this->buildAccountAttributes(
                $index,
                $today,
                $dpdBucket,
                $nonPayments,
                $paymentStatus,
                $isForcePrioritized,
                str_starts_with($this->getAccountNumber($ccUserId, $index), '72'),
                $dueDate,
                $ccUserId,
                $assignedCH,
                $assignedAMRM,
                $assignedRMDH,
            );

            $this->upsert($accountAttributes);
        }
    }

    private function buildAccountAttributes(
        int $index,
        Carbon $today,
        string $dpdBucket,
        string $nonPayments,
        string $paymentStatus,
        bool $isForcePrioritized,
        bool $isContacted,
        Carbon $dueDate,
        int $assignedCC,
        int $assignedCH,
        int $assignedAMRM,
        int $assignedRMDH,
    ) {
        $accountNumber = $this->getAccountNumber($assignedCC, $index);
        $monthsUnpaid = $this->monthsUnpaid($nonPayments);

        // Unit
        $brand = fake()->randomElement(array_keys(self::MOTORCYCLES));
        $model = fake()->randomElement(array_keys(self::MOTORCYCLES[$brand]));
        $srp = self::MOTORCYCLES[$brand][$model];

        // Loan terms
        $term = fake()->randomElement(self::TERMS_IN_MONTHS);
        $financed = $srp * (1 - self::DOWNPAYMENT_RATE);
        $totalPayable = $financed * (1 + self::FLAT_ANNUAL_RATE * $term / 12);
        $monthlyAmortization = round($totalPayable / $term, 2);
        $outstandingBalance = $monthlyAmortization * 12;

        $barangay = fake()->randomElement(array_keys(self::PSGC['MUNTINLUPA']));

        return [
            'account_number' => $accountNumber,
            'customer_id' => 130000000 + $index,
            'cust_code' => 130000000 + $index + 2,
            'customer_name' => $this->fakeCustomerName($accountNumber),
            'monthly_amortization' => $monthlyAmortization,
            'last_payment_date' => $today->copy()->subMonthsNoOverflow($monthsUnpaid)->subDays(5)->toDateString(),
            'due_date' => $dueDate,
            'payment_status' => $paymentStatus,
            'payment_history' => 'LAST PAID 1 MONTH AGO',
            // 'days_past_due' => $dpd,
            'dpd_bucket' => $dpdBucket,
            // 'no_of_non_payments' => $missed,
            // 'payment_status' => $dpd === 0 ? 'CURRENT' : 'PAST DUE',
            'soa_balance' => $outstandingBalance,
            'total_due_balance' => $monthlyAmortization * $monthsUnpaid,
            'past_due_balance' => $monthlyAmortization * $monthsUnpaid,
            'outstanding_balance' => $outstandingBalance,
            'asset' => $brand.' - '.$model,
            'psgc_code' => self::PSGC['MUNTINLUPA'][$barangay],
            'assigned_cc_id' => $assignedCC,
            'assigned_ch_id' => $assignedCH,
            'assigned_am_ar_id' => $assignedAMRM,
            'assigned_rm_dh_id' => $assignedRMDH,
            // 'last_payment_amount' => $monthlyAmortization,
            'is_force_prioritized' => $isForcePrioritized,
            'is_contacted' => $isContacted,
            'send_date' => null,
        ];
    }

    private function usersByRole(int $roleId): Collection
    {
        return User::whereHas('CmsUser', fn ($q) => $q->where('role_id', $roleId))->get();
    }

    private function pick(Collection $ids): ?int
    {
        return $ids->isNotEmpty() ? $ids->random() : null;
    }

    private function getAccountNumber(int $assignedCC, int $index)
    {
        $assignedCCIndex = $assignedCC < 10 ? '0'.$assignedCC : $assignedCC;
        $accountIndex = $index < 10 ? '0'.$index : $index;

        return '720000'.$assignedCCIndex.$accountIndex;
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

        return $faker->firstName().' '.$faker->lastName();
    }

    private function monthsUnpaid(string $nonPayments): int
    {
        return preg_match('/LAST PAID (\d+) MONTHS? AGO/', $nonPayments, $matches) ? (int) $matches[1] : 0;
    }

    private function getNoContactRecording(Carbon $dueToday)
    {
        return [
            ['DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['DPD 61-90', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['DPD 1-30', 'UPDATED', 'FIRST DUE DATE', false, $dueToday],
            ['DPD 31-60', 'LAST PAID 1 MONTH AGO', 'NON-STARTER', false, $dueToday],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['DPD 0', 'UPDATED', 'WITH PAYMENT', false, $dueToday],
            ['DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
        ];
    }

    private function getOverlappingConditions(Carbon $dueToday)
    {
        return [
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', true, $dueToday],
            ['DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', true, $dueToday],
            ['DPD 1-30', 'UPDATED', 'FIRST DUE DATE', true, $dueToday],
            ['DPD 61-90', 'LAST PAID 2 MONTH AGO', 'FIRST DUE DATE', false, $dueToday],
            ['DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'NON-STARTER', false, $dueToday],
            ['DPD 1-30', 'UPDATED', 'WITH PAYMENT', false, $dueToday],
        ];
    }

    private function getNotCoveredByAnyRule(Carbon $dueToday)
    {
        return [
            ['DPD 121-150', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['DPD 181-210', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['DPD MORE THAN 360', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['DPD 61-90', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['DPD 91-120', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['DPD 31-60', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['DPD 0', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
        ];
    }

    private function getScheduledByDueDate(Carbon $dueEarlierThisMonth, Carbon $dueLaterThisMonth, Carbon $dueNextMonth, Carbon $dueLastMonth)
    {
        return [
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueLaterThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueLastMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueNextMonth],
        ];
    }

    private function getNoPaymentsBeyondNP3(Carbon $dueToday)
    {
        return [
            // ===== A. Non-payments beyond NP3 =====
            ['DPD 91-120', 'LAST PAID 4 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['DPD 61-90', 'LAST PAID 5 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['DPD 121-150', 'LAST PAID 6 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
        ];
    }

    private function getWithContactRecording(Carbon $dueEarlierThisMonth)
    {
        return [
            // ===== B. WITH CONTACT RECORDING — due next action plans =====
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
        ];
    }

    private function getCurrentCategory(Carbon $dueEarlierThisMonth)
    {
        return [
            // ===== B. Fallback to current_category =====
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 0', 'UPDATED', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'NON-STARTER', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
        ];
    }

    private function getPrecendenceWithDueNextActionPlan(Carbon $dueEarlierThisMonth)
    {
        return [
            // ===== B. Precedence with a due next action plan =====
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', true, $dueEarlierThisMonth],
            ['DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'UPDATED', 'FIRST DUE DATE', false, $dueEarlierThisMonth],
            ['DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', true, $dueEarlierThisMonth],
        ];
    }

    private function getMultipleRecordings(Carbon $dueEarlierThisMonth, Carbon $dueLastMonth)
    {
        return [
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueLastMonth]];
    }

    private function getOutOfScope(Carbon $dueEarlierThisMonth)
    {
        return [
            // ===== B. Out-of-scope / unlisted next action plans =====
            ['DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 121-150', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 151-180', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
        ];
    }
}
