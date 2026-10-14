<?php

namespace Tests\Feature\VisitPlan;

use App\Exceptions\ClassificationRulesException;
use App\Models\AccountClassificationRule;
use App\Services\SettingService;
use Database\Seeders\AccountClassificationRuleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Rules-driven account classification (account_classification_rules), docs/general-business-rules.md.
 *
 * Precedence (ascending): For Repossession, Forced Prioritized, due next action plan
 * (Promise To Pay, Skiptrace, Notice & Demand Letter, or the recording's current_category),
 * then the base rules: FDD/NS > Priority 1 Accounts > DPD 1-30 Days > NP1 > NP0.
 */
class SettingServiceTest extends VisitPlanTestCase
{
    private const FOR_REPO_BUCKETS = [
        'DPD 91-120',
        'DPD 121-150',
        'DPD 151-180',
        'DPD 181-210',
        'DPD 211-240',
        'DPD 241-270',
        'DPD 271-300',
        'DPD 301-330',
        'DPD 331-360',
        'DPD MORE THAN 360',
    ];

    private const BELOW_REPO_BUCKETS = ['DPD 0', 'DPD 1-30', 'DPD 31-60', 'DPD 61-90'];

    private const NP = [
        0 => 'UPDATED',
        1 => 'LAST PAID 1 MONTH AGO',
        2 => 'LAST PAID 2 MONTH AGO',
        3 => 'LAST PAID 3 MONTHS AGO',
        4 => 'LAST PAID 4 MONTHS AGO',
        6 => 'LAST PAID 6 MONTHS AGO',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccountClassificationRuleSeeder::class);
    }

    // ------------------------------------------------------------ seeded rules

    public function test_the_seeder_creates_the_rules_once_and_they_are_valid(): void
    {
        $this->assertSame(11, AccountClassificationRule::count());
        $this->assertSame(11, AccountClassificationRule::distinct()->count('precedence'), 'Precedences are unique.');
        $this->assertSame(1, AccountClassificationRule::whereNull('classification')->count(), 'One current_category fallback row.');

        $this->seed(AccountClassificationRuleSeeder::class);

        $this->assertSame(11, AccountClassificationRule::count(), 'Re-seeding does not duplicate.');
        $this->assertSame(
            [],
            AccountClassificationRule::all()->filter(fn($rule) => str_contains(json_encode($rule->conditions), '"np"'))->pluck('classification')->all(),
            'The seeded rules classify by DPD bucket and do not use NP.',
        );
        $this->assertSame('For Repossession', $this->classify($this->account(['dpd_bucket' => 'DPD 91-120'])));
    }

    // ------------------------------------------------ classification by DPD bucket

    public static function bucketClassification(): array
    {
        // The DPD bucket alone decides the classification; the number of non-payments never matters.
        $expected = ['DPD 0' => 'NP0', 'DPD 1-30' => 'DPD 1-30 Days', 'DPD 31-60' => 'NP1', 'DPD 61-90' => 'Priority 1 Accounts'];
        foreach (self::FOR_REPO_BUCKETS as $bucket) {
            $expected[$bucket] = 'For Repossession';
        }

        $nonPayments = ['UPDATED', 'LAST PAID 1 MONTH AGO', 'LAST PAID 2 MONTH AGO', 'LAST PAID 3 MONTHS AGO', 'LAST PAID 6 MONTHS AGO', 'GARBAGE', null];

        $cases = [];
        foreach ($expected as $bucket => $label) {
            foreach ($nonPayments as $np) {
                $cases["{$bucket} / " . ($np ?? 'null')] = [$bucket, $np, $label];
            }
        }

        return $cases;
    }

    #[DataProvider('bucketClassification')]
    public function test_the_dpd_bucket_alone_decides_the_classification(string $bucket, ?string $nonPayments, string $expected): void
    {
        $this->assertSame($expected, $this->classify($this->account(['dpd_bucket' => $bucket, 'no_of_non_payments' => $nonPayments])));
    }

    public function test_the_first_three_buckets_and_a_higher_one_follow_the_agreed_mapping(): void
    {
        // DPD 0 ~ NP0, DPD 31-60 ~ NP1, DPD 61-90 ~ NP2 (Priority 1), DPD 91-120+ ~ NP3+ (For Repossession), whatever the stored NP.
        $this->assertSame('NP0', $this->classify($this->account(['dpd_bucket' => 'DPD 0', 'no_of_non_payments' => self::NP[3]])));
        $this->assertSame('DPD 1-30 Days', $this->classify($this->account(['dpd_bucket' => 'DPD 1-30', 'no_of_non_payments' => self::NP[0]])));
        $this->assertSame('NP1', $this->classify($this->account(['dpd_bucket' => 'DPD 31-60', 'no_of_non_payments' => self::NP[0]])));
        $this->assertSame('NP1', $this->classify($this->account(['dpd_bucket' => 'DPD 31-60', 'no_of_non_payments' => self::NP[2]])));
        $this->assertSame('Priority 1 Accounts', $this->classify($this->account(['dpd_bucket' => 'DPD 61-90', 'no_of_non_payments' => self::NP[1]])));
        $this->assertSame('For Repossession', $this->classify($this->account(['dpd_bucket' => 'DPD 91-120', 'no_of_non_payments' => self::NP[0]])));
        $this->assertSame('For Repossession', $this->classify($this->account(['dpd_bucket' => 'DPD MORE THAN 360', 'no_of_non_payments' => self::NP[0]])));
    }

    public function test_fdd_and_non_starter_come_before_the_bucket_rules(): void
    {
        foreach (['FIRST DUE DATE', 'NON-STARTER'] as $status) {
            foreach (['DPD 0', 'DPD 1-30', 'DPD 31-60', 'DPD 61-90'] as $bucket) {
                $this->assertSame('FDD/NS', $this->classify($this->account(['payment_status' => $status, 'dpd_bucket' => $bucket])), "{$status} {$bucket}");
            }
        }
    }

    public function test_an_unknown_dpd_bucket_cannot_be_classified(): void
    {
        foreach (['DPD 15', 'dpd 0', 'DPD 1-31', '', null] as $bucket) {
            $this->assertNull($this->classify($this->account(['dpd_bucket' => $bucket, 'no_of_non_payments' => self::NP[0]])), json_encode($bucket));
        }
    }

    public function test_a_configured_rule_can_still_use_non_payments(): void
    {
        // The seeded rules do not use NP, but a rule row may; the stored text is converted to a number.
        $this->setConditions('Priority 1 Accounts', [
            ['field' => 'np', 'operator' => '>=', 'value' => 2],
            ['field' => 'dpd_bucket', 'operator' => '=', 'value' => 'DPD 61-90'],
        ]);

        $this->assertSame('Priority 1 Accounts', $this->classify($this->account(['dpd_bucket' => 'DPD 61-90', 'no_of_non_payments' => 'LAST PAID 12 MONTHS AGO'])));
        $this->assertSame('Priority 1 Accounts', $this->classify($this->account(['dpd_bucket' => 'DPD 61-90', 'no_of_non_payments' => self::NP[2]])));
        $this->assertNull($this->classify($this->account(['dpd_bucket' => 'DPD 61-90', 'no_of_non_payments' => self::NP[1]])));
        $this->assertNull($this->classify($this->account(['dpd_bucket' => 'DPD 61-90', 'no_of_non_payments' => 'UPDATED'])));
        $this->assertNull($this->classify($this->account(['dpd_bucket' => 'DPD 61-90', 'no_of_non_payments' => 'GARBAGE'])), 'Unrecognised text never satisfies an np condition.');
    }

    // ------------------------------------------------------------- For Repossession

    public static function forRepoBuckets(): array
    {
        $cases = [];
        foreach (self::FOR_REPO_BUCKETS as $bucket) {
            foreach ([0, 1, 3, 6] as $np) {
                $cases["{$bucket} NP{$np}"] = [$bucket, $np];
            }
        }

        return $cases;
    }

    #[DataProvider('forRepoBuckets')]
    public function test_every_bucket_from_dpd_91_120_up_is_for_repossession_whatever_the_np(string $bucket, int $np): void
    {
        $this->assertSame('For Repossession', $this->classify($this->account(['dpd_bucket' => $bucket, 'no_of_non_payments' => self::NP[$np]])));
    }

    public static function fddNsStatuses(): array
    {
        $cases = [];
        foreach (['FIRST DUE DATE', 'NON-STARTER'] as $status) {
            foreach ([...self::BELOW_REPO_BUCKETS, ...self::FOR_REPO_BUCKETS] as $bucket) {
                $cases["{$status} {$bucket}"] = [$status, $bucket];
            }
        }

        return $cases;
    }

    #[DataProvider('fddNsStatuses')]
    public function test_fdd_and_non_starter_accounts_are_never_for_repossession(string $status, string $bucket): void
    {
        $this->assertSame('FDD/NS', $this->classify($this->account([
            'payment_status' => $status,
            'dpd_bucket' => $bucket,
            'no_of_non_payments' => self::NP[6],
        ])));
    }

    public function test_for_repossession_beats_forced_prioritized(): void
    {
        foreach (self::FOR_REPO_BUCKETS as $bucket) {
            $this->assertSame('For Repossession', $this->classify($this->account(['dpd_bucket' => $bucket, 'is_force_prioritized' => true])), $bucket);
        }
    }

    public function test_fdd_and_non_starter_accounts_can_still_be_forced_prioritized(): void
    {
        foreach (['FIRST DUE DATE', 'NON-STARTER'] as $status) {
            foreach (['DPD 1-30', 'DPD 91-120', 'DPD MORE THAN 360'] as $bucket) {
                $this->assertSame('Forced Prioritized', $this->classify($this->account([
                    'payment_status' => $status,
                    'dpd_bucket' => $bucket,
                    'is_force_prioritized' => true,
                ])), "{$status} {$bucket}");
            }
        }
    }

    public function test_forced_prioritized_applies_below_dpd_91_120(): void
    {
        foreach (self::BELOW_REPO_BUCKETS as $bucket) {
            $this->assertSame('Forced Prioritized', $this->classify($this->account(['dpd_bucket' => $bucket, 'is_force_prioritized' => true])), $bucket);
        }
    }

    // ---------------------------------------------------------- next action plans

    public function test_due_next_action_plans_with_their_own_group(): void
    {
        $this->assertSame('Promise To Pay', $this->classify($this->account(['next_action_plan' => 'PROMISE TO PAY'])));
        $this->assertSame('Skiptrace', $this->classify($this->account(['next_action_plan' => 'FOR SKIPTRACE'])));
        $this->assertSame('Notice & Demand Letter', $this->classify($this->account(['next_action_plan' => 'FOR SERVICE OF COLLECTION NOTICE'])));
    }

    public function test_next_action_plans_beat_the_base_rules_but_not_forced_or_for_repossession(): void
    {
        $this->assertSame('Promise To Pay', $this->classify($this->account(['payment_status' => 'FIRST DUE DATE', 'next_action_plan' => 'PROMISE TO PAY'])));
        $this->assertSame('Promise To Pay', $this->classify($this->account(['payment_status' => 'NON-STARTER', 'dpd_bucket' => 'DPD 91-120', 'next_action_plan' => 'PROMISE TO PAY'])), 'Not For Repo, so the plan wins over FDD/NS.');
        $this->assertSame('Forced Prioritized', $this->classify($this->account(['is_force_prioritized' => true, 'next_action_plan' => 'PROMISE TO PAY'])));
        $this->assertSame('For Repossession', $this->classify($this->account(['dpd_bucket' => 'DPD 91-120', 'next_action_plan' => 'FOR SKIPTRACE'])));
    }

    public static function fallbackPlans(): array
    {
        return [
            'FOR FIELD VISIT' => ['FOR FIELD VISIT'],
            'FOR FILING BRGY BLOTTER/COMPLAIN' => ['FOR FILING BRGY BLOTTER/COMPLAIN'],
            'TO ATTEND BARANGAY HEARING' => ['TO ATTEND BARANGAY HEARING'],
            'FOR DISCREPANCY ADJUSTMENT' => ['FOR DISCREPANCY ADJUSTMENT'],
            'FOR ASSUMPTION OF UNIT' => ['FOR ASSUMPTION OF UNIT'],
            'FOR WRITE OFF (MINIMAL BALANCE)' => ['FOR WRITE OFF (MINIMAL BALANCE)'],
        ];
    }

    #[DataProvider('fallbackPlans')]
    public function test_fallback_plans_use_the_recordings_current_category(string $plan): void
    {
        foreach (['DPD 1-30 Days', 'NP0', 'NP1', 'Priority 1 Accounts', 'FDD/NS', 'For Repossession'] as $category) {
            // A paying account whose data would give another classification: the recorded category wins.
            $this->assertSame($category, $this->classify($this->account([
                'dpd_bucket' => 'DPD 31-60',
                'no_of_non_payments' => self::NP[1],
                'next_action_plan' => $plan,
                'current_category' => $category,
            ])), "{$plan} / {$category}");
        }
    }

    public function test_a_fallback_plan_without_a_usable_category_uses_the_base_classification(): void
    {
        $base = ['dpd_bucket' => 'DPD 1-30', 'no_of_non_payments' => self::NP[1], 'next_action_plan' => 'FOR FIELD VISIT'];

        $this->assertSame('DPD 1-30 Days', $this->classify($this->account($base + ['current_category' => null])));
        $this->assertSame('DPD 1-30 Days', $this->classify($this->account($base + ['current_category' => ''])));
        $this->assertSame('DPD 1-30 Days', $this->classify($this->account($base + ['current_category' => 'SOMETHING ELSE'])));
    }

    public function test_the_category_is_read_from_the_latest_recording_in_activity_records(): void
    {
        $entry = $this->account([
            'dpd_bucket' => 'DPD 1-30',
            'no_of_non_payments' => self::NP[1],
            'next_action_plan' => 'FOR FIELD VISIT',
            'activity_records' => [['current_category' => 'Priority 1 Accounts', 'next_action_plan' => 'FOR FIELD VISIT']],
        ]);

        $this->assertSame('Priority 1 Accounts', $this->classify($entry));

        $entry['activity_records'] = [['current_category' => null]];
        $this->assertSame('DPD 1-30 Days', $this->classify($entry));

        $entry['activity_records'] = [];
        $this->assertSame('DPD 1-30 Days', $this->classify($entry));
    }

    public function test_a_recorded_for_repossession_category_cannot_make_an_fdd_ns_account_for_repossession(): void
    {
        $this->assertSame('FDD/NS', $this->classify($this->account([
            'payment_status' => 'NON-STARTER',
            'dpd_bucket' => 'DPD 1-30',
            'next_action_plan' => 'FOR FIELD VISIT',
            'current_category' => 'For Repossession',
        ])));
    }

    public static function plansWithoutAGroup(): array
    {
        return [
            'FOR REVISIT/CALL' => ['FOR REVISIT/CALL'],
            'FOR COLLECTION' => ['FOR COLLECTION'],
            'FOR REPO (not approved)' => ['FOR REPO'],
            'FOR INSURANCE CLAIM (out of scope, handled later)' => ['FOR INSURANCE CLAIM'],
            'lowercase text is not a match' => ['promise to pay'],
        ];
    }

    #[DataProvider('plansWithoutAGroup')]
    public function test_plans_without_a_dedicated_group_use_the_base_classification(string $plan): void
    {
        $this->assertSame('Priority 1 Accounts', $this->classify($this->account([
            'dpd_bucket' => 'DPD 61-90',
            'no_of_non_payments' => self::NP[2],
            'next_action_plan' => $plan,
            'current_category' => 'NP0',
        ])));
    }

    // ------------------------------------------------------------------ grouping

    public function test_classify_groups_accounts_under_the_existing_response_properties(): void
    {
        $accounts = [
            $this->account(['account_number' => '1', 'dpd_bucket' => 'DPD 91-120']),
            $this->account(['account_number' => '2', 'is_force_prioritized' => true]),
            $this->account(['account_number' => '3', 'next_action_plan' => 'PROMISE TO PAY']),
            $this->account(['account_number' => '4', 'next_action_plan' => 'FOR SKIPTRACE']),
            $this->account(['account_number' => '5', 'next_action_plan' => 'FOR SERVICE OF COLLECTION NOTICE']),
            $this->account(['account_number' => '6', 'payment_status' => 'FIRST DUE DATE']),
            $this->account(['account_number' => '7', 'dpd_bucket' => 'DPD 61-90', 'no_of_non_payments' => self::NP[2]]),
            $this->account(['account_number' => '8', 'dpd_bucket' => 'DPD 1-30', 'no_of_non_payments' => self::NP[1]]),
            $this->account(['account_number' => '9', 'dpd_bucket' => 'DPD 31-60', 'no_of_non_payments' => self::NP[1]]),
            $this->account(['account_number' => '10', 'dpd_bucket' => 'DPD 0']),
            $this->account(['account_number' => '11', 'dpd_bucket' => 'DPD 1-30', 'no_of_non_payments' => self::NP[1]]),
        ];

        $groups = app(SettingService::class)->classify($accounts);

        $numbers = fn(array $list) => array_column($list, 'account_number');

        $this->assertSame(['prioritization', 'regular'], array_keys($groups));
        $this->assertSame(['for_repossession', 'next_action_plan', 'promise_to_pay', 'fdd_ns', 'priority_1_accounts'], array_keys($groups['prioritization']));
        $this->assertSame(['force_prioritized', 'for_skiptrace', 'notice_and_demand_letter'], array_keys($groups['prioritization']['next_action_plan']));
        $this->assertSame(['dpd_1_30_days', 'np_1', 'np_0'], array_keys($groups['regular']));

        $this->assertSame(['1'], $numbers($groups['prioritization']['for_repossession']));
        $this->assertSame(['2'], $numbers($groups['prioritization']['next_action_plan']['force_prioritized']));
        $this->assertSame(['3'], $numbers($groups['prioritization']['promise_to_pay']));
        $this->assertSame(['4'], $numbers($groups['prioritization']['next_action_plan']['for_skiptrace']));
        $this->assertSame(['5'], $numbers($groups['prioritization']['next_action_plan']['notice_and_demand_letter']));
        $this->assertSame(['6'], $numbers($groups['prioritization']['fdd_ns']));
        $this->assertSame(['7'], $numbers($groups['prioritization']['priority_1_accounts']));
        $this->assertSame(['8', '11'], $numbers($groups['regular']['dpd_1_30_days']));
        $this->assertSame(['9'], $numbers($groups['regular']['np_1']));
        $this->assertSame(['10'], $numbers($groups['regular']['np_0']));
    }

    public function test_classify_keeps_the_account_entries_as_they_are_and_omits_empty_groups(): void
    {
        $account = $this->account(['account_number' => '1', 'dpd_bucket' => 'DPD 1-30', 'activity_records' => [['remarks' => 'x']]]);

        $groups = app(SettingService::class)->classify([$account]);

        $this->assertSame(['regular' => ['dpd_1_30_days' => [$account]]], $groups);
        $this->assertSame([], app(SettingService::class)->classify([]));
    }

    public function test_accounts_with_an_unknown_bucket_are_left_out_and_reported_in_one_warning(): void
    {
        Log::spy();

        $groups = app(SettingService::class)->classify([
            $this->account(['account_number' => 'OK', 'dpd_bucket' => 'DPD 1-30']),
            $this->account(['account_number' => 'BAD1', 'dpd_bucket' => 'DPD 1-31']),
            $this->account(['account_number' => 'BAD2', 'dpd_bucket' => null]),
            $this->account(['account_number' => 'BAD3', 'dpd_bucket' => 'DPD 15']),
        ]);

        $this->assertSame(['regular' => ['dpd_1_30_days' => [$this->account(['account_number' => 'OK', 'dpd_bucket' => 'DPD 1-30'])]]], $groups);

        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn($message, $context) => str_contains($message, 'could not be classified')
                && $context['count'] === 3
                && $context['account_numbers'] === ['BAD1', 'BAD2', 'BAD3']
        );
    }

    // ------------------------------------------- changing the rules changes the result

    public function test_editing_a_rule_condition_changes_the_classification(): void
    {
        $account = $this->account(['dpd_bucket' => 'DPD 61-90', 'no_of_non_payments' => self::NP[2]]);
        $this->assertSame('Priority 1 Accounts', $this->classify($account));

        $this->setConditions('Priority 1 Accounts', [
            ['field' => 'np', 'operator' => '>=', 'value' => 3],
            ['field' => 'dpd_bucket', 'operator' => '=', 'value' => 'DPD 61-90'],
        ]);

        $this->assertNull($this->classify($account), 'NP2 no longer qualifies and no other rule matches.');
        $this->assertSame('Priority 1 Accounts', $this->classify($this->account(['dpd_bucket' => 'DPD 61-90', 'no_of_non_payments' => self::NP[3]])));
    }

    public function test_editing_the_for_repossession_buckets_changes_the_classification(): void
    {
        $account = $this->account(['dpd_bucket' => 'DPD 91-120', 'no_of_non_payments' => self::NP[3]]);
        $this->assertSame('For Repossession', $this->classify($account));

        $this->setConditions('For Repossession', [
            ['field' => 'dpd_bucket', 'operator' => 'in', 'value' => array_slice(self::FOR_REPO_BUCKETS, 1)],
            ['field' => 'payment_status', 'operator' => 'not_in', 'value' => ['FIRST DUE DATE', 'NON-STARTER']],
        ]);

        $this->assertNull($this->classify($account), 'DPD 91-120 is no longer For Repo and no other rule matches NP3 there.');
        $this->assertSame('For Repossession', $this->classify($this->account(['dpd_bucket' => 'DPD 121-150', 'no_of_non_payments' => self::NP[3]])));
    }

    public function test_changing_the_precedence_changes_which_rule_wins(): void
    {
        $account = $this->account(['payment_status' => 'FIRST DUE DATE', 'dpd_bucket' => 'DPD 61-90', 'no_of_non_payments' => self::NP[2]]);
        $this->assertSame('FDD/NS', $this->classify($account));

        $fdd = AccountClassificationRule::where('classification', 'FDD/NS')->firstOrFail();
        $priority1 = AccountClassificationRule::where('classification', 'Priority 1 Accounts')->firstOrFail();
        [$fddOrder, $priority1Order] = [$fdd->precedence, $priority1->precedence];
        $fdd->update(['precedence' => $priority1Order]);
        $priority1->update(['precedence' => $fddOrder]);

        $this->assertSame('Priority 1 Accounts', $this->classify($account));
    }

    public function test_a_deactivated_rule_is_ignored(): void
    {
        $account = $this->account(['payment_status' => 'FIRST DUE DATE', 'dpd_bucket' => 'DPD 1-30']);
        $this->assertSame('FDD/NS', $this->classify($account));

        AccountClassificationRule::where('classification', 'FDD/NS')->update(['is_active' => false]);

        $this->assertSame('DPD 1-30 Days', $this->classify($account));
        $this->assertNull(
            $this->classify($this->account(['payment_status' => 'FIRST DUE DATE', 'dpd_bucket' => 'DPD 121-150', 'no_of_non_payments' => self::NP[3]])),
            'Still not For Repo (the restriction is in that rule), and FDD/NS is off.'
        );
    }

    public function test_a_soft_deleted_rule_is_ignored(): void
    {
        AccountClassificationRule::where('classification', 'Priority 1 Accounts')->firstOrFail()->delete();

        $this->assertNull($this->classify($this->account(['dpd_bucket' => 'DPD 61-90', 'no_of_non_payments' => self::NP[2]])));
    }

    // --------------------------------------------------- invalid configuration

    public function test_an_empty_rules_table_is_an_error_that_is_logged(): void
    {
        Log::spy();
        AccountClassificationRule::query()->forceDelete();

        $this->assertConfigurationError('no active', fn() => $this->classify($this->account()));

        Log::shouldHaveReceived('error')->once();
    }

    public function test_when_every_rule_is_inactive_or_deleted_it_is_an_error(): void
    {
        AccountClassificationRule::query()->update(['is_active' => false]);

        $this->assertConfigurationError('no active', fn() => $this->classify($this->account()));
    }

    public static function invalidRules(): array
    {
        $valid = [['field' => 'dpd_bucket', 'operator' => '=', 'value' => 'DPD 0']];

        return [
            'unknown field' => [['conditions' => ['all' => [['field' => 'customer_name', 'operator' => '=', 'value' => 'x']]]], 'unknown field'],
            'unknown operator' => [['conditions' => ['all' => [['field' => 'dpd_bucket', 'operator' => 'LIKE', 'value' => 'x']]]], 'unknown operator'],
            'in without a list' => [['conditions' => ['all' => [['field' => 'dpd_bucket', 'operator' => 'in', 'value' => 'DPD 0']]]], 'list'],
            'scalar operator with a list' => [['conditions' => ['all' => [['field' => 'dpd_bucket', 'operator' => '=', 'value' => ['DPD 0']]]]], 'single value'],
            'np with text' => [['conditions' => ['all' => [['field' => 'np', 'operator' => '>=', 'value' => 'two']]]], 'number'],
            'flag with text' => [['conditions' => ['all' => [['field' => 'is_force_prioritized', 'operator' => '=', 'value' => 'yes']]]], 'true or false'],
            'condition without a field' => [['conditions' => ['all' => [['operator' => '=', 'value' => 'x']]]], 'field'],
            'no all list' => [['conditions' => ['any' => $valid]], 'all'],
            'empty all list' => [['conditions' => ['all' => []]], 'all'],
            'conditions missing' => [['conditions' => null], 'conditions'],
            'unknown classification' => [['classification' => 'Gold Customers', 'conditions' => ['all' => $valid]], 'unknown classification'],
            'parent does not match' => [['parent' => 'Regular', 'conditions' => ['all' => $valid]], 'parent'],
            'missing precedence' => [['precedence' => null, 'conditions' => ['all' => $valid]], 'precedence'],
        ];
    }

    #[DataProvider('invalidRules')]
    public function test_an_invalid_rule_is_an_error_that_is_logged(array $changes, string $expectedMessagePart): void
    {
        Log::spy();
        $rule = AccountClassificationRule::where('classification', 'Priority 1 Accounts')->firstOrFail();
        foreach ($changes as $column => $value) {
            $rule->{$column} = $value;
        }
        $rule->saveQuietly();

        $this->assertConfigurationError($expectedMessagePart, fn() => $this->classify($this->account()));

        Log::shouldHaveReceived('error')->once();
    }

    public function test_two_rules_with_the_same_precedence_are_a_conflict(): void
    {
        $order = AccountClassificationRule::where('classification', 'FDD/NS')->value('precedence');
        AccountClassificationRule::where('classification', 'Priority 1 Accounts')->update(['precedence' => $order]);

        $this->assertConfigurationError('precedence', fn() => $this->classify($this->account()));
    }

    public function test_the_error_message_does_not_expose_internals(): void
    {
        AccountClassificationRule::query()->forceDelete();

        try {
            $this->classify($this->account());
            $this->fail('Expected a ClassificationRulesException.');
        } catch (ClassificationRulesException $e) {
            $this->assertStringNotContainsString('account_classification_rules', $e->getMessage());
            $this->assertStringNotContainsString('SELECT', strtoupper($e->getMessage()));
        }
    }

    // ------------------------------------------------------------------ helpers

    private function classify(array $account): ?string
    {
        return app(SettingService::class)->classifyAccount($account);
    }

    private function account(array $attributes = []): array
    {
        return $attributes + [
            'account_number' => 'TEST',
            'is_force_prioritized' => false,
            'payment_status' => 'WITH PAYMENT',
            'no_of_non_payments' => 'UPDATED',
            'dpd_bucket' => 'DPD 0',
            'next_action_plan' => null,
            'next_action_date' => null,
            'current_category' => null,
        ];
    }

    private function setConditions(string $classification, array $conditions): void
    {
        AccountClassificationRule::where('classification', $classification)->firstOrFail()
            ->update(['conditions' => ['all' => $conditions]]);
    }

    private function assertConfigurationError(string $messagePart, callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a ClassificationRulesException.');
        } catch (ClassificationRulesException $e) {
            $this->assertStringContainsStringIgnoringCase('classification rules', $e->getMessage());
            $this->assertNotSame('', $e->details(), 'The underlying issue is kept for the log.');
            $this->assertStringContainsStringIgnoringCase($messagePart, $e->details());
        }
    }
}
