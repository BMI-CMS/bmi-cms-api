<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AccountSeederV2 extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $today = Carbon::today();
        $dueToday = $today->copy();
        $dueEarlierThisMonth = $today->copy()->startOfMonth();
        $dueLaterThisMonth = $today->copy()->endOfMonth()->startOfDay();
        $dueLastMonth = $today->copy()->subMonthNoOverflow()->startOfMonth()->addDays(9);
        $dueNextMonth = $today->copy()->addMonthNoOverflow()->startOfMonth()->addDays(4);

        $accounts = [
            ['7100000001', 'A01 FOR REPO RULE', 'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000002', 'A02 PRIORITY 1 NP2', 'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000003', 'A03 PRIORITY 1 NP3', 'DPD 61-90', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000004', 'A04 FDD', 'DPD 1-30', 'UPDATED', 'FIRST DUE DATE', false, $dueToday],
            ['7100000005', 'A05 NON-STARTER', 'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'NON-STARTER', false, $dueToday],
            ['7100000006', 'A06 DPD 1-30', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],
            ['7100000007', 'A07 NP0', 'DPD 0', 'UPDATED', 'WITH PAYMENT', false, $dueToday],
            ['7100000008', 'A08 NP1', 'DPD 31-60', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', false, $dueToday],

            ['7100000009', 'A09 FORCED + DPD 1-30', 'DPD 1-30', 'LAST PAID 1 MONTH AGO', 'WITH PAYMENT', true, $dueToday],
            ['7100000010', 'A10 FORCED + FOR REPO RULE', 'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'WITH PAYMENT', true, $dueToday],
            ['7100000011', 'A11 FORCED + FDD', 'DPD 1-30', 'UPDATED', 'FIRST DUE DATE', true, $dueToday],
            ['7100000012', 'A12 FDD + PRIORITY 1', 'DPD 61-90', 'LAST PAID 2 MONTH AGO', 'FIRST DUE DATE', false, $dueToday],
            ['7100000013', 'A13 NON-STARTER + FOR REPO RULE', 'DPD 91-120', 'LAST PAID 3 MONTHS AGO', 'NON-STARTER', false, $dueToday],
            ['7100000014', 'A14 DPD 1-30 + UPDATED', 'DPD 1-30', 'UPDATED', 'WITH PAYMENT', false, $dueToday],

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


        $sampleAccounts = [];
        foreach ($accounts as $key => [$accountNumber, $customerName, $dpdBucket, $nonPayments, $paymentStatus, $isForcePrioritized, $dueDate]) {
            $sampleAccounts[] = [$accountNumber, $customerName, $dpdBucket, $nonPayments, $paymentStatus, $isForcePrioritized, $dueDate];
        };
    }
}
