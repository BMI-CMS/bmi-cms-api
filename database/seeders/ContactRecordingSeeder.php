<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\ContactRecording;
use Carbon\Carbon;

/**
 * Visit Plan sample contact recordings for the group B accounts in AccountSeeder.
 *
 * Only these columns matter for classification/scheduling:
 * current_category, next_action_plan, next_action_date, contact_date
 * (to pick the latest recording) and deleted_at. The other columns are filler.
 * is_contacted (optional 8th value, default false) = the account was visited when this
 * recording's next action came due; true on the recordings followed by a later visit.
 *
 * Recordings are inserted in array order, so ids follow that order.
 * All dates are relative to the moment the seeder runs, in the application timezone.
 *
 * Safe to rerun: existing recordings of the sample accounts are permanently
 * deleted first. No other account's recordings are touched.
 */
class ContactRecordingSeeder extends Seeder
{
    private const PROMISE_TO_PAY = 'PROMISE TO PAY';
    private const SKIPTRACE = 'FOR SKIPTRACE';
    private const NOTICE_AND_DEMAND = 'FOR SERVICE OF COLLECTION NOTICE';
    private const FOR_REPO = 'FOR REPO';
    private const FIELD_VISIT = 'FOR FIELD VISIT';

    public function run(): void
    {
        $now = Carbon::now();
        $today = Carbon::today();

        // Already due when seeded, but still today.
        $earlierToday = $now->copy()->subHour()->max($today);
        // Same day, not yet due when seeded (becomes due later today).
        $laterToday = $now->copy()->addHour()->min($today->copy()->endOfDay());
        $yesterday = $today->copy()->subDay()->setTime(10, 0);
        $tomorrow = $today->copy()->addDay()->setTime(10, 0);
        $daysAgo = fn (int $days) => $today->copy()->subDays($days)->setTime(10, 0);
        $lastMonth = $today->copy()->subMonthNoOverflow()->startOfMonth()->addDays(11)->setTime(10, 0);

        // [account_number, current_category, action_taken, next_action_plan, contact_date, next_action_date, soft_deleted, is_contacted?]
        $recordings = [
            // ===== Due next action plans =====
            ['7200000001', 'DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $earlierToday, false],
            ['7200000002', 'DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $laterToday, false],
            ['7200000003', 'DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $tomorrow, false],
            ['7200000004', 'DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $yesterday, false],
            ['7200000005', 'Priority 1 Accounts', 'SKIPTRACE', self::SKIPTRACE, $daysAgo(3), $earlierToday, false],
            ['7200000006', 'NP1', 'SERVED COLLECTION NOTICE', self::NOTICE_AND_DEMAND, $daysAgo(3), $earlierToday, false],
            // No repossession_requests approval row exists for this recording.
            ['7200000007', 'Priority 1 Accounts', 'FOLLOW UP PAYMENT', self::FOR_REPO, $daysAgo(3), $earlierToday, false],

            // ===== Fallback to current_category =====
            ['7200000008', 'DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::FIELD_VISIT, $daysAgo(3), $earlierToday, false],
            ['7200000009', 'NP1', 'FILED BRGY BLOTTER/COMPLAIN', 'FOR FILING BRGY BLOTTER/COMPLAIN', $daysAgo(3), $earlierToday, false],
            ['7200000010', 'DPD 1-30 Days', 'ATTEND BRGY HEARING', 'TO ATTEND BARANGAY HEARING', $daysAgo(3), $earlierToday, false],
            ['7200000011', 'NP0', 'PROCESS DISCREPANCY ADJUSTMENT', 'FOR DISCREPANCY ADJUSTMENT', $daysAgo(3), $earlierToday, false],
            ['7200000012', 'Priority 1 Accounts', 'FOLLOW UP PAYMENT', 'FOR ASSUMPTION OF UNIT', $daysAgo(3), $earlierToday, false],
            // Spelling from the rules document; the master list seeder has no space before "(".
            ['7200000013', 'FDD/NS', 'FOR WRITE OFF(MINIMAL BALANCE)', 'FOR WRITE OFF (MINIMAL BALANCE)', $daysAgo(3), $earlierToday, false],
            ['7200000014', null, 'FOLLOW UP PAYMENT', self::FIELD_VISIT, $daysAgo(3), $earlierToday, false],
            // Category recorded at visit time differs from the account's current attributes (now DPD 1-30).
            ['7200000015', 'Priority 1 Accounts', 'FOLLOW UP PAYMENT', self::FIELD_VISIT, $daysAgo(3), $earlierToday, false],

            // ===== Precedence with a due next action plan =====
            ['7200000016', 'DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $earlierToday, false],
            ['7200000017', 'For Repossession', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $earlierToday, false],
            ['7200000018', 'FDD/NS', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $earlierToday, false],
            ['7200000019', 'For Repossession', 'SKIPTRACE', self::SKIPTRACE, $daysAgo(3), $earlierToday, false],

            // ===== B20 same-day rescheduling: morning visit sets a new action later today =====
            ['7200000020', 'DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::FIELD_VISIT, $yesterday, $today->copy(), false, true],
            ['7200000020', 'DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $earlierToday, $laterToday, false],

            // ===== B21 older recording is due, but the latest one is future-dated =====
            ['7200000021', 'Priority 1 Accounts', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(5), $earlierToday, false, true],
            ['7200000021', 'Priority 1 Accounts', 'SKIPTRACE', self::SKIPTRACE, $yesterday, $tomorrow, false],

            // ===== B22 latest recording is soft-deleted; the older PTP is the valid one =====
            ['7200000022', 'DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $earlierToday, false],
            ['7200000022', 'DPD 1-30 Days', 'SKIPTRACE', self::SKIPTRACE, $yesterday, $earlierToday, true],

            // ===== B23 offline sync: older visit synced later (higher id, earlier contact_date) =====
            ['7200000023', 'NP1', 'SERVED COLLECTION NOTICE', self::NOTICE_AND_DEMAND, $earlierToday, $earlierToday, false],
            ['7200000023', 'NP1', 'FOLLOW UP PAYMENT', self::FIELD_VISIT, $daysAgo(2), $yesterday, false, true],

            // ===== B24 PTP from last month never followed up =====
            ['7200000024', 'DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $lastMonth, $lastMonth->copy()->addDays(2), false],

            // ===== Out-of-scope next action plans (future workflows) =====
            ['7200000025', 'Priority 1 Accounts', 'PROCESS INSURANCE CLAIM', 'FOR INSURANCE CLAIM', $daysAgo(3), $earlierToday, false],
            // DPD 121-150 / 151-180 have no base classification yet, so no category is assumed.
            ['7200000026', null, 'ENDORSED TO RCD', 'FOR ENDORSEMENT TO RCD', $daysAgo(3), $earlierToday, false],
            ['7200000027', 'NP1', 'OFFER RESTRUCTURING', 'FOR RESTRUCTURING', $daysAgo(3), $earlierToday, false],
            ['7200000028', null, 'ENDORSED TO LEGAL', 'FOR LEGAL/SMALL CLAIMS', $daysAgo(3), $earlierToday, false],
            ['7200000029', 'DPD 1-30 Days', 'COLLECTED', null, $earlierToday, null, false],

            // ===== Next action plans not listed in the rules document =====
            ['7200000030', 'DPD 1-30 Days', 'FOLLOW UP PAYMENT', 'FOR REVISIT/CALL', $daysAgo(3), $earlierToday, false],
            ['7200000031', 'NP1', 'FOLLOW UP PAYMENT', 'FOR COLLECTION', $daysAgo(3), $earlierToday, false],
        ];

        $accountIds = Account::whereIn('account_number', array_unique(array_column($recordings, 0)))
            ->pluck('id', 'account_number');

        ContactRecording::withTrashed()->whereIn('account_id', $accountIds->values())->forceDelete();

        foreach ($recordings as $row) {
            [$accountNumber, $currentCategory, $actionTaken, $nextActionPlan, $contactDate, $nextActionDate, $softDeleted] = $row;

            $recording = ContactRecording::create([
                'account_id' => $accountIds[$accountNumber],
                'current_category' => $currentCategory,
                'follow_up_mode' => 'FIELD VISIT',
                'action_taken' => $actionTaken,
                'reason_for_default' => 'JOB/SALARY RELATED',
                'next_action_plan' => $nextActionPlan,
                'remarks' => 'Visit Plan sample data.',
                'geotagging' => '14.5547,121.0244',
                'recorded_by' => 1,
                'contact_date' => $contactDate,
                'next_action_date' => $nextActionDate,
                'is_contacted' => $row[7] ?? false,
            ]);

            if ($softDeleted) {
                $recording->delete();
            }
        }
    }
}
