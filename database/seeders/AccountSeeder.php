<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use Carbon\Carbon;


/**
 * Visit Plan sample accounts.
 *
 * Every account covers one scenario from docs/visit_plan_business_rules_claude.md.
 * Only these columns matter for classification/scheduling:
 * dpd_bucket, no_of_non_payments, payment_status, is_force_prioritized,
 * is_contacted and due_date. The other columns are filler.
 *
 * Group A (7100000xxx): no contact recording.
 * Group B (7200000xxx): has contact recordings, see ContactRecordingSeeder.
 * Group C (7300000xxx): other credit counselor.
 *
 * All dates are relative to the day the seeder runs, in the application
 * timezone. Safe to rerun: accounts are matched by account_number.
 */
class AccountSeeder extends Seeder
{
    private const DAYS_PAST_DUE = [
        'DPD 0' => 0,
        'DPD 1-30' => 15,
        'DPD 31-60' => 45,
        'DPD 61-90' => 75,
        'DPD 91-120' => 105,
        'DPD 121-150' => 135,
        'DPD 151-180' => 165,
        'DPD 181-210' => 195,
        'DPD 211-240' => 225,
        'DPD 241-270' => 255,
        'DPD 271-300' => 285,
        'DPD 301-330' => 315,
        'DPD 331-360' => 345,
        'DPD MORE THAN 360' => 400,
    ];

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
            ['7100000001', 'A01 FOR REPO RULE', 'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000002', 'A02 PRIORITY 1 NP2', 'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000003', 'A03 PRIORITY 1 NP3', 'DPD 61-90', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000004', 'A04 FDD', 'DPD 1-30', 'UPDATED', 'FIRST DUE DATE', false, $dueToday],
            ['7100000005', 'A05 NON-STARTER', 'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'NON-STARTER', false, $dueToday],
            ['7100000006', 'A06 DPD 1-30', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000007', 'A07 NP0', 'DPD 0', 'UPDATED', 'WITH PAYMENT', false, $dueToday],
            ['7100000008', 'A08 NP1', 'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],

            // ===== A. Overlapping conditions (precedence) =====
            ['7100000009', 'A09 FORCED + DPD 1-30', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', true, $dueToday],
            ['7100000010', 'A10 FORCED + FOR REPO RULE', 'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', true, $dueToday],
            ['7100000011', 'A11 FORCED + FDD', 'DPD 1-30', 'UPDATED', 'FIRST DUE DATE', true, $dueToday],
            ['7100000012', 'A12 FDD + PRIORITY 1', 'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'FIRST DUE DATE', false, $dueToday],
            ['7100000013', 'A13 NON-STARTER + FOR REPO RULE', 'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'NON-STARTER', false, $dueToday],
            ['7100000014', 'A14 DPD 1-30 + UPDATED', 'DPD 1-30', 'UPDATED', 'WITH PAYMENT', false, $dueToday],

            // ===== A. Combinations not covered by any rule yet =====
            ['7100000015', 'A15 UNCOVERED DPD 121-150', 'DPD 121-150', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000016', 'A16 UNCOVERED DPD 181-210', 'DPD 181-210', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000017', 'A17 UNCOVERED DPD MORE THAN 360', 'DPD MORE THAN 360', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000018', 'A18 UNCOVERED DPD 61-90 NP1', 'DPD 61-90', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000019', 'A19 UNCOVERED DPD 91-120 NP2', 'DPD 91-120', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000020', 'A20 UNCOVERED DPD 31-60 NP2', 'DPD 31-60', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000021', 'A21 UNCOVERED DPD 0 NP1', 'DPD 0', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],

            // ===== A. Scheduling by accounts.due_date =====
            ['7100000022', 'A22 DUE EARLIER THIS MONTH', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7100000023', 'A23 DUE LATER THIS MONTH', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueLaterThisMonth],
            ['7100000024', 'A24 DUE LAST MONTH', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueLastMonth],
            ['7100000025', 'A25 DUE NEXT MONTH', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueNextMonth],

            // ===== A. Non-payments beyond NP3 =====
            ['7100000026', 'A26 FOR REPO RULE NP4', 'DPD 91-120', 'LAST PAID 4 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000027', 'A27 PRIORITY 1 NP5', 'DPD 61-90', 'LAST PAID 5 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000028', 'A28 UNCOVERED DPD 121-150 NP6', 'DPD 121-150', 'LAST PAID 6 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],

            // ===== B. WITH CONTACT RECORDING — due next action plans =====
            ['7200000001', 'B01 PTP DUE EARLIER TODAY', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000002', 'B02 PTP LATER TODAY', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000003', 'B03 PTP TOMORROW', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000004', 'B04 PTP OVERDUE YESTERDAY', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000005', 'B05 SKIPTRACE DUE', 'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000006', 'B06 NOTICE AND DEMAND DUE', 'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000007', 'B07 FOR REPO NAP NO APPROVAL', 'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],

            // ===== B. Fallback to current_category =====
            ['7200000008', 'B08 FALLBACK FIELD VISIT', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000009', 'B09 FALLBACK BRGY BLOTTER', 'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000010', 'B10 FALLBACK BRGY HEARING', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000011', 'B11 FALLBACK DISCREPANCY', 'DPD 0', 'UPDATED', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000012', 'B12 FALLBACK ASSUMPTION', 'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000013', 'B13 FALLBACK WRITE OFF', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'NON-STARTER', false, $dueEarlierThisMonth],
            ['7200000014', 'B14 FALLBACK NULL CATEGORY', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000015', 'B15 FALLBACK CATEGORY CHANGED', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],

            // ===== B. Precedence with a due next action plan =====
            ['7200000016', 'B16 FORCED + PTP DUE', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', true, $dueEarlierThisMonth],
            ['7200000017', 'B17 FOR REPO RULE + PTP DUE', 'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000018', 'B18 FDD + PTP DUE', 'DPD 1-30', 'UPDATED', 'FIRST DUE DATE', false, $dueEarlierThisMonth],
            ['7200000019', 'B19 FORCED + FOR REPO RULE + SKIPTRACE', 'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', true, $dueEarlierThisMonth],

            // ===== B. Multiple recordings / latest recording selection =====
            ['7200000020', 'B20 SAME DAY RESCHEDULE', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000021', 'B21 OLD PTP DUE LATEST FUTURE', 'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000022', 'B22 LATEST SOFT DELETED', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000023', 'B23 OFFLINE SYNC ORDER', 'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000024', 'B24 PTP OVERDUE LAST MONTH', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueLastMonth],

            // ===== B. Out-of-scope / unlisted next action plans =====
            ['7200000025', 'B25 INSURANCE CLAIM', 'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000026', 'B26 ENDORSEMENT TO RCD', 'DPD 121-150', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000027', 'B27 RESTRUCTURING', 'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000028', 'B28 LEGAL SMALL CLAIMS', 'DPD 151-180', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000029', 'B29 COLLECTED NO NEXT ACTION', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000030', 'B30 UNLISTED REVISIT CALL', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
            ['7200000031', 'B31 UNLISTED FOR COLLECTION', 'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueEarlierThisMonth],
        ];

        foreach ($accounts as $index => [$accountNumber, $customerName, $dpdBucket, $nonPayments, $paymentStatus, $isForcePrioritized, $dueDate]) {
            $this->upsert($this->account(
                $index,
                $accountNumber,
                $customerName,
                $dpdBucket,
                $nonPayments,
                $paymentStatus,
                $isForcePrioritized,
                str_starts_with($accountNumber, '72'),
                $dueDate,
                $today,
                assignedCcId: 1,
            ));
        }

        // ===== Other credit counselor — must never appear in CC 1's Visit Plan =====
        $this->upsert($this->account(
            count($accounts),
            '7300000001',
            'C01 OTHER CC DUE TODAY',
            'DPD 1-30',
            'LAST PAID 1 MONTH AGO',
            'WITH PAYMENT',
            false,
            false,
            $dueToday,
            $today,
            assignedCcId: 2,
        ));
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

    /**
     * Seeded from the account number so reruns produce the same name for the same account.
     */
    private function fakeCustomerName(string $accountNumber): string
    {
        $faker = fake('en_PH');
        $faker->seed((int) $accountNumber);

        return $faker->firstName() . ' ' . $faker->lastName();
    }

    /**
     * "UPDATED" = 0, "LAST PAID 1 MONTH AGO" = 1, "LAST PAID 3 MONTHS AGO" = 3, ...
     * Only used to fill the balance/date filler columns.
     */
    private function monthsUnpaid(string $nonPayments): int
    {
        return preg_match('/LAST PAID (\d+) MONTHS? AGO/', $nonPayments, $matches) ? (int) $matches[1] : 0;
    }

    private function account(
        int $index,
        string $accountNumber,
        string $customerName,
        string $dpdBucket,
        string $nonPayments,
        string $paymentStatus,
        bool $isForcePrioritized,
        bool $isContacted,
        Carbon $dueDate,
        Carbon $today,
        int $assignedCcId,
    ): array {
        $monthsUnpaid = $this->monthsUnpaid($nonPayments);
        $outstandingBalance = self::MONTHLY_AMORTIZATION * 12;

        return [
            'account_number' => $accountNumber,
            'customer_id' => 130000000 + $index,
            'customer_name' => $this->fakeCustomerName($accountNumber),
            'monthly_amortization' => self::MONTHLY_AMORTIZATION,
            'last_payment_amount' => self::MONTHLY_AMORTIZATION,
            'past_due_balance' => self::MONTHLY_AMORTIZATION * $monthsUnpaid,
            'days_past_due' => self::DAYS_PAST_DUE[$dpdBucket],
            'dpd_bucket' => $dpdBucket,
            'no_of_non_payments' => $nonPayments,
            'outstanding_balance' => $outstandingBalance,
            'last_payment_date' => $today->copy()->subMonthsNoOverflow($monthsUnpaid)->subDays(5)->toDateString(),
            'payment_status' => $paymentStatus,
            'asset' => 'MOTORCYCLE',
            'psgc_code' => self::PSGC_CODES[$index % count(self::PSGC_CODES)],
            'is_force_prioritized' => $isForcePrioritized,
            'assigned_cc_id' => $assignedCcId,
            'assigned_ch_id' => 6,
            'assigned_am_ar_id' => 7,
            'assigned_rm__dh_id' => 8,
            'is_contacted' => $isContacted,
            'due_date' => $dueDate->toDateString(),
            'send_date' => null,
        ];
    }
}
