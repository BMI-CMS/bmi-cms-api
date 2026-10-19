<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ContactRecording;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Visit Plan sample contact recordings, attached to randomly chosen existing accounts.
 *
 * Each scenario takes one distinct account; its recordings are inserted in order,
 * so later rows get higher ids (B23 depends on this).
 * Requires AccountSeederV3 to have run first, with at least as many accounts
 * as there are scenarios (31).
 *
 * Safe to rerun: recordings created by this seeder (remarks start with MARKER)
 * are permanently deleted first. Other recordings are not touched.
 */
class ContactRecordingSeederV2 extends Seeder
{
    private const MARKER = 'Visit Plan sample data.';

    private const PROMISE_TO_PAY = 'PROMISE TO PAY';

    private const SKIPTRACE = 'FOR SKIPTRACE';

    private const NOTICE_AND_DEMAND = 'FOR SERVICE OF COLLECTION NOTICE';

    private const FOR_REPO = 'FOR REPO';

    private const FIELD_VISIT = 'FOR FIELD VISIT';

    public function run(): void
    {
        $scenarios = $this->scenarios();

        // run(): select dpd_bucket instead of days_past_due
        $pool = Account::select('id', 'account_number', 'dpd_bucket', 'assigned_cc_id')
            ->inRandomOrder()
            ->get();

        if ($pool->count() < count($scenarios)) {
            throw new \RuntimeException(sprintf(
                'ContactRecordingSeeder needs at least %d accounts, found %d. Run AccountSeederV3 first.',
                count($scenarios),
                $pool->count()
            ));
        }

        // Remove this seeder's previous output, including soft-deleted rows.
        ContactRecording::withTrashed()
            ->where('remarks', 'like', self::MARKER.'%')
            ->forceDelete();

        // Scenarios that need a specific account pick first so they can't be starved.
        // (usort is stable in PHP 8+, so the original order is kept otherwise.)
        usort($scenarios, fn ($a, $b) => isset($b['bucket']) <=> isset($a['bucket']));
        $summary = [];

        foreach ($scenarios as $scenario) {
            $account = $this->takeAccount($pool, $scenario['bucket'] ?? null);

            foreach ($scenario['rows'] as $row) {
                [$category, $actionTaken, $plan, $contactDate, $nextActionDate, $softDeleted] = $row;

                $recording = ContactRecording::create([
                    'account_id' => $account->id,
                    'current_category' => $category,
                    'follow_up_mode' => 'FIELD VISIT',
                    'action_taken' => $actionTaken,
                    'reason_for_default' => 'JOB/SALARY RELATED',
                    'next_action_plan' => $plan,
                    'remarks' => self::MARKER." [{$scenario['label']}]",
                    'geotagging' => '14.5547,121.0244',
                    'recorded_by' => $account->assigned_cc_id ?? 1,
                    'contact_date' => $contactDate,
                    'next_action_date' => $nextActionDate,
                    'is_contacted' => $row[6] ?? false,
                ]);

                if ($softDeleted) {
                    $recording->delete();
                }
            }

            $summary[] = [$scenario['label'], $account->account_number, $account->dpd_bucket];
        }

        usort($summary, fn ($a, $b) => strcmp($a[0], $b[0]));
        $this->command?->table(['Scenario', 'Account number', 'Bucket'], $summary);
    }

    private function takeAccount(Collection $pool, ?string $bucket): Account
    {
        $key = $pool->keys()->first();

        if ($bucket !== null) {
            $match = $pool->search(fn ($a) => $a->dpd_bucket === $bucket);

            if ($match !== false) {
                $key = $match;
            }
        }

        return $pool->pull($key);
    }

    /**
     * Row shape:
     * [current_category, action_taken, next_action_plan, contact_date, next_action_date, soft_deleted, is_contacted?]
     *
     * is_contacted (optional 7th value, default false) = the account was visited when
     * this recording's next action came due; true on recordings followed by a later visit.
     */
    private function scenarios(): array
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

        return [
            // ===== Due next action plans =====
            ['label' => 'B01', 'rows' => [['DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B02', 'rows' => [['DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $laterToday, false]]],
            ['label' => 'B03', 'rows' => [['DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $tomorrow, false]]],
            ['label' => 'B04', 'rows' => [['DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $yesterday, false]]],
            ['label' => 'B05', 'rows' => [['Priority 1 Accounts', 'SKIPTRACE', self::SKIPTRACE, $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B06', 'rows' => [['NP1', 'SERVED COLLECTION NOTICE', self::NOTICE_AND_DEMAND, $daysAgo(3), $earlierToday, false]]],
            // No repossession_requests approval row exists for this recording.
            ['label' => 'B07', 'rows' => [['Priority 1 Accounts', 'FOLLOW UP PAYMENT', self::FOR_REPO, $daysAgo(3), $earlierToday, false]]],

            // ===== Fallback to current_category =====
            ['label' => 'B08', 'rows' => [['DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::FIELD_VISIT, $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B09', 'rows' => [['NP1', 'FILED BRGY BLOTTER/COMPLAIN', 'FOR FILING BRGY BLOTTER/COMPLAIN', $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B10', 'rows' => [['DPD 1-30 Days', 'ATTEND BRGY HEARING', 'TO ATTEND BARANGAY HEARING', $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B11', 'rows' => [['NP0', 'PROCESS DISCREPANCY ADJUSTMENT', 'FOR DISCREPANCY ADJUSTMENT', $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B12', 'rows' => [['Priority 1 Accounts', 'FOLLOW UP PAYMENT', 'FOR ASSUMPTION OF UNIT', $daysAgo(3), $earlierToday, false]]],
            // Spelling from the rules document; the master list seeder has no space before "(".
            ['label' => 'B13', 'rows' => [['FDD/NS', 'FOR WRITE OFF(MINIMAL BALANCE)', 'FOR WRITE OFF (MINIMAL BALANCE)', $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B14', 'rows' => [[null, 'FOLLOW UP PAYMENT', self::FIELD_VISIT, $daysAgo(3), $earlierToday, false]]],
            // Category recorded at visit time differs from the account's current attributes (now DPD 1-30).
            ['label' => 'B15', 'bucket' => 'DPD 1-30', 'rows' => [['Priority 1 Accounts', 'FOLLOW UP PAYMENT', self::FIELD_VISIT, $daysAgo(3), $earlierToday, false]]],

            // ===== Precedence with a due next action plan =====
            ['label' => 'B16', 'rows' => [['DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B17', 'rows' => [['For Repossession', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B18', 'rows' => [['FDD/NS', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B19', 'rows' => [['For Repossession', 'SKIPTRACE', self::SKIPTRACE, $daysAgo(3), $earlierToday, false]]],

            // ===== B20 same-day rescheduling: morning visit sets a new action later today =====
            ['label' => 'B20', 'rows' => [
                ['DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::FIELD_VISIT, $yesterday, $today->copy(), false, true],
                ['DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $earlierToday, $laterToday, false],
            ]],

            // ===== B21 older recording is due, but the latest one is future-dated =====
            ['label' => 'B21', 'rows' => [
                ['Priority 1 Accounts', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(5), $earlierToday, false, true],
                ['Priority 1 Accounts', 'SKIPTRACE', self::SKIPTRACE, $yesterday, $tomorrow, false],
            ]],

            // ===== B22 latest recording is soft-deleted; the older PTP is the valid one =====
            ['label' => 'B22', 'rows' => [
                ['DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $daysAgo(3), $earlierToday, false],
                ['DPD 1-30 Days', 'SKIPTRACE', self::SKIPTRACE, $yesterday, $earlierToday, true],
            ]],

            // ===== B23 offline sync: older visit synced later (higher id, earlier contact_date) =====
            ['label' => 'B23', 'rows' => [
                ['NP1', 'SERVED COLLECTION NOTICE', self::NOTICE_AND_DEMAND, $earlierToday, $earlierToday, false],
                ['NP1', 'FOLLOW UP PAYMENT', self::FIELD_VISIT, $daysAgo(2), $yesterday, false, true],
            ]],

            // ===== B24 PTP from last month never followed up =====
            ['label' => 'B24', 'rows' => [['DPD 1-30 Days', 'FOLLOW UP PAYMENT', self::PROMISE_TO_PAY, $lastMonth, $lastMonth->copy()->addDays(2), false]]],

            // ===== Out-of-scope next action plans (future workflows) =====
            ['label' => 'B25', 'rows' => [['Priority 1 Accounts', 'PROCESS INSURANCE CLAIM', 'FOR INSURANCE CLAIM', $daysAgo(3), $earlierToday, false]]],
            // DPD 121-150 / 151-180 have no base classification yet, so no category is assumed.
            ['label' => 'B26', 'rows' => [[null, 'ENDORSED TO RCD', 'FOR ENDORSEMENT TO RCD', $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B27', 'rows' => [['NP1', 'OFFER RESTRUCTURING', 'FOR RESTRUCTURING', $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B28', 'rows' => [[null, 'ENDORSED TO LEGAL', 'FOR LEGAL/SMALL CLAIMS', $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B29', 'rows' => [['DPD 1-30 Days', 'COLLECTED', null, $earlierToday, null, false]]],

            // ===== Next action plans not listed in the rules document =====
            ['label' => 'B30', 'rows' => [['DPD 1-30 Days', 'FOLLOW UP PAYMENT', 'FOR REVISIT/CALL', $daysAgo(3), $earlierToday, false]]],
            ['label' => 'B31', 'rows' => [['NP1', 'FOLLOW UP PAYMENT', 'FOR COLLECTION', $daysAgo(3), $earlierToday, false]]],
        ];
    }
}
