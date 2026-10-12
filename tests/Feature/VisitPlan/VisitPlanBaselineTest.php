<?php

namespace Tests\Feature\VisitPlan;

use App\Models\AccountClassificationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Expected Visit Plan classification (response group) of every sample account for
 * GET /api/v1/dashboard/assigned-accounts, per docs/general-business-rules.md.
 *
 * Each row: [account code, expected daily group, expected monthly group (defaults to the daily one)].
 * "ABSENT" = not in the plan. Rows marked UNDECIDED are deferred business rules and are marked
 * incomplete, not passed.
 *
 * Time is frozen at 2026-10-14 14:00 (application timezone, Asia/Manila).
 */
class VisitPlanBaselineTest extends VisitPlanTestCase
{
    private const NOW = '2026-10-14 14:00:00';

    /** Not in the plan. */
    private const ABSENT = null;

    /** Deferred feature: not asserted. */
    private const UNDECIDED = 'undecided';

    /**
     * Keys of every account entry in the response, as DashboardService builds them
     * (current_category is inside activity_records, not at the top level).
     */
    private const ACCOUNT_KEYS = [
        'accounts_id', 'account_number', 'customer_name', 'monthly_amortization', 'past_due_balance', 'days_past_due',
        'dpd_bucket', 'no_of_non_payments', 'outstanding_balance', 'last_payment_date', 'asset', 'payment_status',
        'is_force_prioritized', 'psgc_code', 'is_contacted', 'visit_date', 'assigned_cc_id', 'assigned_ch_id',
        'assigned_am_id', 'assigned_dh_id', 'unit_lot_block', 'street_name', 'subdivision_village', 'province',
        'postal_code', 'barangay', 'city_municipality', 'region', 'contact_number', 'email', 'social_media_account',
        'next_action_date', 'next_action_plan', 'activity_records',
    ];

    private const GROUP_KEYS = [
        'for_repossession', 'priority_1_accounts', 'fdd_ns', 'promise_to_pay',
        'force_prioritized', 'for_skiptrace', 'notice_and_demand_letter',
        'dpd_1_30_days', 'np_1', 'np_0',
    ];

    public static function expectedClassifications(): array
    {
        $none = self::ABSENT;
        $deferred = self::UNDECIDED;

        return [
            // A. No contact recording — base rules
            'A01 DPD 91-120' => ['7100000001', 'for_repossession'],
            'A02 priority 1 NP2' => ['7100000002', 'priority_1_accounts'],
            'A03 priority 1 NP3' => ['7100000003', 'priority_1_accounts'],
            'A04 FDD' => ['7100000004', 'fdd_ns'],
            'A05 NON-STARTER' => ['7100000005', 'fdd_ns'],
            'A06 DPD 1-30' => ['7100000006', 'dpd_1_30_days'],
            'A07 DPD 0 NP0' => ['7100000007', 'np_0'],
            'A08 NP1' => ['7100000008', 'np_1'],

            // A. Overlaps
            'A09 forced + DPD 1-30' => ['7100000009', 'force_prioritized'],
            'A10 forced + DPD 91-120' => ['7100000010', 'for_repossession'],
            'A11 forced + FDD' => ['7100000011', 'force_prioritized'],
            'A12 FDD + priority 1' => ['7100000012', 'fdd_ns'],
            'A13 NON-STARTER + DPD 91-120 (never For Repo)' => ['7100000013', 'fdd_ns'],
            'A14 DPD 1-30 NP0' => ['7100000014', 'dpd_1_30_days'],

            // A. The DPD bucket decides, whatever the NP
            'A15 DPD 121-150' => ['7100000015', 'for_repossession'],
            'A16 DPD 181-210' => ['7100000016', 'for_repossession'],
            'A17 DPD MORE THAN 360' => ['7100000017', 'for_repossession'],
            'A18 DPD 61-90 NP1' => ['7100000018', 'priority_1_accounts'],
            'A19 DPD 91-120 NP2' => ['7100000019', 'for_repossession'],
            'A20 DPD 31-60 NP2' => ['7100000020', 'np_1'],
            'A21 DPD 0 NP1' => ['7100000021', 'np_0'],

            // A. Scheduling by due_date
            'A22 due earlier this month' => ['7100000022', 'dpd_1_30_days'],
            'A23 due later this month' => ['7100000023', $none, 'dpd_1_30_days'],
            'A24 due last month' => ['7100000024', $none],
            'A25 due next month' => ['7100000025', $none],

            // A. Beyond NP3
            'A26 DPD 91-120 NP4' => ['7100000026', 'for_repossession'],
            'A27 priority 1 NP5' => ['7100000027', 'priority_1_accounts'],
            'A28 DPD 121-150 NP6' => ['7100000028', 'for_repossession'],

            // B. Next action plans
            'B01 PTP due earlier today' => ['7200000001', 'promise_to_pay'],
            'B02 PTP later today' => ['7200000002', $none, 'promise_to_pay'],
            'B03 PTP tomorrow' => ['7200000003', $none, 'promise_to_pay'],
            'B04 PTP yesterday (carried over)' => ['7200000004', 'promise_to_pay'],
            'B05 skiptrace' => ['7200000005', 'for_skiptrace'],
            'B06 notice and demand' => ['7200000006', 'notice_and_demand_letter'],
            'B07 FOR REPO not approved -> base' => ['7200000007', 'priority_1_accounts'],

            // B. Fallback to current_category
            'B08 field visit' => ['7200000008', 'dpd_1_30_days'],
            'B09 brgy blotter' => ['7200000009', 'np_1'],
            'B10 brgy hearing' => ['7200000010', 'dpd_1_30_days'],
            'B11 discrepancy' => ['7200000011', 'np_0'],
            'B12 assumption of unit' => ['7200000012', 'priority_1_accounts'],
            'B13 write off' => ['7200000013', 'fdd_ns'],
            'B14 fallback without category -> base' => ['7200000014', 'dpd_1_30_days'],
            'B15 recorded category wins over current data' => ['7200000015', 'priority_1_accounts'],

            // B. Precedence with a due next action plan
            'B16 forced + PTP' => ['7200000016', 'force_prioritized'],
            'B17 DPD 91-120 + PTP' => ['7200000017', 'for_repossession'],
            'B18 FDD + PTP' => ['7200000018', 'promise_to_pay'],
            'B19 forced + DPD 91-120 + skiptrace' => ['7200000019', 'for_repossession'],

            // B. Latest recording
            'B20 same-day reschedule' => ['7200000020', $none, 'promise_to_pay'],
            'B21 latest recording is tomorrow' => ['7200000021', $none, 'for_skiptrace'],
            'B22 latest recording deleted' => ['7200000022', 'promise_to_pay'],
            'B23 offline sync order' => ['7200000023', 'notice_and_demand_letter'],
            'B24 last month' => ['7200000024', $none],

            // B. Out-of-scope plans (handled in a later feature) and no next action
            'B25 insurance claim' => ['7200000025', $deferred],
            'B26 endorsement to RCD' => ['7200000026', $deferred],
            'B27 restructuring' => ['7200000027', $deferred],
            'B28 legal / small claims' => ['7200000028', $deferred],
            'B29 collected, no next action' => ['7200000029', $none],

            // B. Plans without a group -> base
            'B30 FOR REVISIT/CALL' => ['7200000030', 'dpd_1_30_days'],
            'B31 FOR COLLECTION' => ['7200000031', 'np_1'],

            // C. Other credit counselor
            'C01 other credit counselor' => ['7300000001', $none],
        ];
    }

    #[DataProvider('expectedClassifications')]
    public function test_daily_visit_plan_classification(string $accountNumber, ?string $daily, ?string $monthly = null): void
    {
        $this->assertClassification($accountNumber, $daily, 'daily');
    }

    #[DataProvider('expectedClassifications')]
    public function test_monthly_visit_plan_classification(string $accountNumber, ?string $daily, ?string $monthly = null): void
    {
        $this->assertClassification($accountNumber, func_num_args() > 2 ? $monthly : $daily, 'monthly');
    }

    private function assertClassification(string $accountNumber, ?string $expected, string $period): void
    {
        if ($expected === self::UNDECIDED) {
            $this->markTestIncomplete('Deferred feature: out-of-scope next action plans.');
        }

        $this->assertSame(
            $expected,
            $this->classifications($period)[$accountNumber] ?? self::ABSENT,
            "Account {$accountNumber} is in the wrong group ({$period}).",
        );
    }

    public function test_same_day_reschedule_appears_once_the_new_time_arrives(): void
    {
        // Seeded at 14:00, so B20 and B02 are due at 15:00.
        $classifications = $this->classifications('daily', viewAt: '2026-10-14 16:00:00');

        $this->assertSame('promise_to_pay', $classifications['7200000020'] ?? self::ABSENT);
        $this->assertSame('promise_to_pay', $classifications['7200000002'] ?? self::ABSENT);
    }

    public function test_each_account_appears_in_exactly_one_group(): void
    {
        foreach (['daily', 'monthly'] as $period) {
            $this->travelToManila(self::NOW);
            $this->seedVisitPlanData();

            $occurrences = [];
            $this->collect($this->getVisitPlan($period)->assertOk()->json('data'), $occurrences);

            $this->assertSame([], array_filter($occurrences, fn (array $groups) => count($groups) > 1), "{$period}: accounts listed twice.");
        }
    }

    public function test_daily_and_monthly_responses_have_the_same_structure_and_property_names(): void
    {
        $this->travelToManila(self::NOW);
        $this->seedVisitPlanData();

        $responses = ['daily' => $this->getVisitPlan('daily')->assertOk()->json(), 'monthly' => $this->getVisitPlan('monthly')->assertOk()->json()];

        foreach ($responses as $period => $json) {
            $this->assertSame(['message', 'data'], array_keys($json), "{$period}: top-level properties.");
            $this->assertContains('visited', array_keys($json['data']), "{$period}: visited list.");

            $groups = [];
            $this->collectGroupKeys(array_diff_key($json['data'], ['visited' => 1]), $groups);
            $this->assertEmpty(array_diff($groups, self::GROUP_KEYS, ['prioritization', 'regular', 'next_action_plan']), "{$period}: unexpected group names " . json_encode($groups));

            foreach ($this->accountEntries(array_diff_key($json['data'], ['visited' => 1])) as $entry) {
                $keys = array_keys($entry);
                sort($keys);
                $expected = self::ACCOUNT_KEYS;
                sort($expected);
                $this->assertSame($expected, $keys, "{$period}: account entry properties changed.");
            }
        }

        // Same top-level groups; monthly may add accounts, never other properties.
        $this->assertEmpty(array_diff(
            array_keys(array_diff_key($responses['daily']['data'], ['visited' => 1])),
            array_keys($responses['monthly']['data']),
        ));
    }

    public function test_invalid_rules_return_a_clear_error_and_are_logged(): void
    {
        $this->travelToManila(self::NOW);
        $this->seedVisitPlanData();
        Log::spy();
        AccountClassificationRule::query()->forceDelete();

        foreach (['daily', 'monthly'] as $period) {
            $response = $this->getVisitPlan($period);

            $response->assertStatus(500);
            $response->assertJsonPath('message', 'Account classification rules are not configured correctly.');
            $this->assertStringNotContainsString('SQL', (string) $response->getContent());
        }

        Log::shouldHaveReceived('error')->twice();
    }

    public function test_an_account_with_an_unknown_dpd_bucket_does_not_break_the_plan(): void
    {
        $this->travelToManila(self::NOW);
        $this->seedVisitPlanData();
        // Data error: a bucket outside the agreed list cannot be classified.
        DB::table('accounts')->whereIn('account_number', ['7100000006', '7100000007'])->update(['dpd_bucket' => 'DPD 15']);
        Log::spy();

        $classified = $this->getVisitPlan('daily')->assertOk()->json('data');
        $codes = [];
        $this->collect($classified, $codes);

        $this->assertArrayNotHasKey('7100000006', $codes);
        $this->assertArrayNotHasKey('7100000007', $codes);
        $this->assertArrayHasKey('7100000001', $codes, 'The rest of the plan is still returned.');

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn ($message, $context) => str_contains($message, 'could not be classified')
                && $context['count'] === 2
                && $context['account_numbers'] === ['7100000006', '7100000007']
        );
    }

    public function test_no_sample_account_is_left_unclassified(): void
    {
        $this->travelToManila(self::NOW);
        $this->seedVisitPlanData();
        Log::spy();

        $this->getVisitPlan('monthly')->assertOk();

        Log::shouldNotHaveReceived('warning');
    }

    /**
     * @return array<string, string> account_number => innermost response group key
     */
    private function classifications(string $period, ?string $viewAt = null): array
    {
        $this->travelToManila(self::NOW);
        $this->seedVisitPlanData();

        if ($viewAt !== null) {
            $this->travelToManila($viewAt);
        }

        $occurrences = [];
        $this->collect($this->getVisitPlan($period)->assertOk()->json('data'), $occurrences);

        return array_map(fn (array $groups) => $groups[0], $occurrences);
    }

    /**
     * Walks the grouped response and records the group key each account is listed under.
     * The "visited" list is not a classification and is skipped.
     */
    private function collect(array $node, array &$occurrences, ?string $group = null): void
    {
        foreach ($node as $key => $value) {
            if ($key === 'visited' || !is_array($value)) {
                continue;
            }

            if (isset($value['account_number'])) {
                $occurrences[$value['account_number']][] = $group;
                continue;
            }

            $this->collect($value, $occurrences, is_string($key) ? $key : $group);
        }
    }

    private function collectGroupKeys(array $node, array &$keys): void
    {
        foreach ($node as $key => $value) {
            if (is_string($key)) {
                $keys[] = $key;
            }
            if (is_array($value) && !isset($value['account_number']) && !array_is_list($value)) {
                $this->collectGroupKeys($value, $keys);
            }
        }
    }

    private function accountEntries(array $node): array
    {
        $entries = [];
        foreach ($node as $value) {
            if (!is_array($value)) {
                continue;
            }
            if (isset($value['account_number'])) {
                $entries[] = $value;
                continue;
            }
            $entries = [...$entries, ...$this->accountEntries($value)];
        }

        return $entries;
    }
}
