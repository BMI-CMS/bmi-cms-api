<?php

namespace Tests\Feature\VisitPlan;

use App\Constants\Constants;
use App\Models\Account;
use App\Models\ContactRecording;
use App\Repositories\DashboardRepository;
use Carbon\Carbon;
use Database\Seeders\CollectionAddressSeeder;
use Database\Seeders\CustomerDetailSeeder;

/**
 * Which accounts the Visit Plan returns (eligibility only, not classification),
 * per docs/general-business-rules.md section 7.1.
 *
 * The sample data is seeded at 2026-10-14 14:00 Asia/Manila (Wednesday); next_action_carry_over_days is 1.
 * Relative to that time:
 *  - A01–A21, A26–A28 are due today, A22 was due 1 Oct, A23 is due 31 Oct, A24 was due last month, A25 is due 5 Nov.
 *  - B recordings: next action 1 hour ago (most), 1 hour from now (B02, B20), tomorrow 10:00 (B03, B21),
 *    yesterday 10:00 (B04), last month (B24), none (B29).
 */
class VisitPlanEligibilityTest extends VisitPlanTestCase
{
    private const SEEDED_AT = '2026-10-14 14:00:00';

    /** Unvisited accounts whose due date has arrived (1 Oct–14 Oct). */
    private const UNVISITED_DUE = [
        'A01', 'A02', 'A03', 'A04', 'A05', 'A06', 'A07', 'A08', 'A09', 'A10', 'A11', 'A12', 'A13', 'A14',
        'A15', 'A16', 'A17', 'A18', 'A19', 'A20', 'A21', 'A22', 'A26', 'A27', 'A28',
    ];

    /** Unvisited, due later in October. */
    private const UNVISITED_DUE_LATER_THIS_MONTH = ['A23'];

    /** Latest next action arrived at 13:00 on 14 Oct. */
    private const NEXT_ACTION_ARRIVED_AT_SEED = [
        'B01', 'B05', 'B06', 'B07', 'B08', 'B09', 'B10', 'B11', 'B12', 'B13', 'B14', 'B15', 'B16', 'B17',
        'B18', 'B19', 'B22', 'B23', 'B25', 'B26', 'B27', 'B28', 'B30', 'B31',
    ];

    /** Next action yesterday (13 Oct) 10:00. */
    private const NEXT_ACTION_YESTERDAY = ['B04'];

    /** Next action 15:00 on 14 Oct. */
    private const NEXT_ACTION_LATER_TODAY = ['B02', 'B20'];

    /** Next action 15 Oct 10:00. */
    private const NEXT_ACTION_TOMORROW = ['B03', 'B21'];

    private function seedAt(string $dateTime): void
    {
        $this->travelToManila($dateTime);
        $this->seedVisitPlanData();
        $this->seed([CustomerDetailSeeder::class, CollectionAddressSeeder::class]);
    }

    /** @return array<int, string> sorted account codes the Visit Plan returns at the given Manila time */
    private function eligibleAt(string $dateTime, string $period = Constants::PERIOD_DAILY): array
    {
        $this->travelToManila($dateTime);

        $codes = app(DashboardRepository::class)
            ->getVisitPlanAccounts(self::CREDIT_COUNSELOR_ID, Carbon::now(), $period)
            ->map(fn ($account) => strtok($account->customer_name, ' '))
            ->all();

        sort($codes);

        return $codes;
    }

    private function sorted(array ...$groups): array
    {
        $codes = array_merge(...$groups);
        sort($codes);

        return $codes;
    }

    // ---------------------------------------------------------------- daily

    public function test_daily_returns_due_unvisited_accounts_and_arrived_next_actions(): void
    {
        $this->seedAt(self::SEEDED_AT);

        $this->assertSame(
            $this->sorted(self::UNVISITED_DUE, self::NEXT_ACTION_ARRIVED_AT_SEED, self::NEXT_ACTION_YESTERDAY),
            $this->eligibleAt(self::SEEDED_AT),
        );
    }

    public function test_daily_hides_future_due_dates_and_future_next_actions(): void
    {
        $this->seedAt(self::SEEDED_AT);

        $codes = $this->eligibleAt(self::SEEDED_AT);

        foreach (['A23', 'A25', ...self::NEXT_ACTION_LATER_TODAY, ...self::NEXT_ACTION_TOMORROW] as $code) {
            $this->assertNotContains($code, $codes, "{$code} is scheduled after 14:00 on 14 Oct.");
        }
    }

    public function test_daily_next_action_scheduled_later_today_appears_at_that_exact_time(): void
    {
        $this->seedAt(self::SEEDED_AT);

        $this->assertNotContains('B02', $this->eligibleAt('2026-10-14 14:59:59'));
        $this->assertNotContains('B20', $this->eligibleAt('2026-10-14 14:59:59'));

        $this->assertContains('B02', $this->eligibleAt('2026-10-14 15:00:00'));
        $this->assertContains('B20', $this->eligibleAt('2026-10-14 15:00:00'));
    }

    public function test_an_uncompleted_next_action_shows_for_one_more_day(): void
    {
        $this->seedAt(self::SEEDED_AT);

        // B04 was due yesterday 10:00.
        $this->assertContains('B04', $this->eligibleAt(self::SEEDED_AT));

        // Next action due 14 Oct, not visited: shown again on 15 Oct, no longer on 16 Oct.
        $nextDay = $this->eligibleAt('2026-10-15 14:00:00');
        $this->assertSame(
            $this->sorted(
                self::UNVISITED_DUE,
                self::NEXT_ACTION_ARRIVED_AT_SEED,
                self::NEXT_ACTION_LATER_TODAY,
                self::NEXT_ACTION_TOMORROW,
            ),
            $nextDay,
        );
        $this->assertNotContains('B04', $nextDay, 'B04 was due 13 Oct: two days ago.');

        $this->assertSame(
            $this->sorted(self::UNVISITED_DUE, self::NEXT_ACTION_TOMORROW),
            $this->eligibleAt('2026-10-16 14:00:00'),
            'Only the 15 Oct next actions remain; 14 Oct ones are two days old.',
        );
    }

    public function test_carry_over_days_setting_controls_how_long_a_next_action_stays(): void
    {
        $this->seedAt(self::SEEDED_AT);
        config(['visit_plan.next_action_carry_over_days' => 0]);

        $this->assertNotContains('B04', $this->eligibleAt(self::SEEDED_AT), 'No carry-over: yesterday is gone.');
        $this->assertSame(
            $this->sorted(self::UNVISITED_DUE, self::NEXT_ACTION_TOMORROW),
            $this->eligibleAt('2026-10-15 14:00:00'),
        );

        config(['visit_plan.next_action_carry_over_days' => 2]);

        $this->assertContains('B04', $this->eligibleAt('2026-10-15 14:00:00'), '13 Oct is within 2 days of 15 Oct.');
    }

    public function test_carry_over_never_reaches_into_the_previous_month(): void
    {
        $this->seedAt(self::SEEDED_AT);
        $this->travelToManila(self::SEEDED_AT);

        ContactRecording::create([
            'account_id' => Account::where('account_number', '7100000001')->firstOrFail()->id,
            'current_category' => 'For Repossession',
            'follow_up_mode' => 'FIELD VISIT',
            'action_taken' => 'FOLLOW UP PAYMENT',
            'next_action_plan' => 'PROMISE TO PAY',
            'recorded_by' => 1,
            'contact_date' => now(),
            'next_action_date' => Carbon::parse('2026-10-31 20:00:00'),
        ]);

        $this->assertContains('A01', $this->eligibleAt('2026-10-31 21:00:00'));
        $this->assertContains('A01', $this->eligibleAt('2026-10-31 21:00:00', Constants::PERIOD_MONTHLY));

        // 1 Nov: yesterday (31 Oct) is still inside the one-day carry-over, but it belongs to October.
        $this->assertNotContains('A01', $this->eligibleAt('2026-11-01 08:00:00'));
        $this->assertNotContains('A01', $this->eligibleAt('2026-11-01 08:00:00', Constants::PERIOD_MONTHLY));
    }

    // -------------------------------------------------------------- monthly

    public function test_monthly_adds_future_schedules_within_the_month(): void
    {
        $this->seedAt(self::SEEDED_AT);

        $this->assertSame(
            $this->sorted(
                self::UNVISITED_DUE,
                self::UNVISITED_DUE_LATER_THIS_MONTH,
                self::NEXT_ACTION_ARRIVED_AT_SEED,
                self::NEXT_ACTION_YESTERDAY,
                self::NEXT_ACTION_LATER_TODAY,
                self::NEXT_ACTION_TOMORROW,
            ),
            $this->eligibleAt(self::SEEDED_AT, Constants::PERIOD_MONTHLY),
        );
    }

    public function test_monthly_never_includes_schedules_of_another_month(): void
    {
        $this->seedAt(self::SEEDED_AT);

        $codes = $this->eligibleAt(self::SEEDED_AT, Constants::PERIOD_MONTHLY);

        $this->assertNotContains('A24', $codes, 'Unvisited, due last month.');
        $this->assertNotContains('A25', $codes, 'Due 5 Nov: a future month.');
        $this->assertNotContains('B24', $codes, 'Next action last month.');
    }

    public function test_monthly_contains_everything_daily_returns(): void
    {
        $this->seedAt(self::SEEDED_AT);

        foreach (['2026-10-14 14:00:00', '2026-10-14 15:30:00', '2026-10-15 14:00:00', '2026-10-16 14:00:00', '2026-10-21 14:00:00', '2026-10-31 14:00:00'] as $at) {
            $this->assertSame(
                [],
                array_diff($this->eligibleAt($at), $this->eligibleAt($at, Constants::PERIOD_MONTHLY)),
                "At {$at} the daily plan must be inside the monthly plan.",
            );
        }
    }

    public function test_a_week_later_only_unvisited_accounts_remain(): void
    {
        $this->seedAt(self::SEEDED_AT);

        $this->assertSame(self::UNVISITED_DUE, $this->eligibleAt('2026-10-21 14:00:00'));
        $this->assertSame(
            $this->sorted(self::UNVISITED_DUE, self::UNVISITED_DUE_LATER_THIS_MONTH),
            $this->eligibleAt('2026-10-21 14:00:00', Constants::PERIOD_MONTHLY),
            'Monthly still lists A23, due later this month.',
        );
    }

    public function test_unvisited_accounts_keep_appearing_until_the_end_of_the_month(): void
    {
        $this->seedAt(self::SEEDED_AT);

        $expected = $this->sorted(self::UNVISITED_DUE, self::UNVISITED_DUE_LATER_THIS_MONTH);

        $this->assertSame($expected, $this->eligibleAt('2026-10-31 14:00:00'), 'A23 is due on 31 Oct.');
        $this->assertSame($expected, $this->eligibleAt('2026-10-31 23:59:59', Constants::PERIOD_MONTHLY));
    }

    public function test_nothing_carries_over_into_the_next_month(): void
    {
        $this->seedAt(self::SEEDED_AT);

        // The month changes at 1 Nov 00:00 Manila time.
        $this->assertSame([], $this->eligibleAt('2026-11-01 00:00:00'));
        $this->assertSame([], $this->eligibleAt('2026-11-01 00:30:00'));

        // The only account scheduled in November is A25 (due 5 Nov); monthly lists it, daily does not yet.
        $this->assertSame(['A25'], $this->eligibleAt('2026-11-01 00:30:00', Constants::PERIOD_MONTHLY));
    }

    // ------------------------------------------------------- both periods

    public function test_a_visit_removes_the_account_until_its_next_action_arrives(): void
    {
        $this->seedAt(self::SEEDED_AT);
        $this->travelToManila(self::SEEDED_AT);

        ContactRecording::create([
            'account_id' => Account::where('account_number', '7100000001')->firstOrFail()->id,
            'current_category' => 'For Repossession',
            'follow_up_mode' => 'FIELD VISIT',
            'action_taken' => 'FOLLOW UP PAYMENT',
            'next_action_plan' => 'PROMISE TO PAY',
            'recorded_by' => 1,
            'contact_date' => now(),
            'next_action_date' => Carbon::parse('2026-10-15 10:00:00'),
        ]);

        $afterVisit = $this->eligibleAt(self::SEEDED_AT);
        $this->assertNotContains('A01', $afterVisit, 'A01 was just visited.');
        $this->assertContains('A02', $afterVisit, 'A02 is still unvisited.');

        $this->assertNotContains('A01', $this->eligibleAt('2026-10-15 09:59:59'));
        $this->assertContains('A01', $this->eligibleAt('2026-10-15 10:00:00'));

        $this->assertContains('A01', $this->eligibleAt(self::SEEDED_AT, Constants::PERIOD_MONTHLY), 'Monthly lists the 15 Oct schedule.');
    }

    public function test_a_soft_deleted_recording_does_not_count_as_a_visit(): void
    {
        $this->seedAt(self::SEEDED_AT);

        $b04 = Account::where('account_number', '7200000004')->firstOrFail();
        $this->assertSame(1, $b04->contactRecordings()->count());
        $b04->contactRecordings()->delete();

        // Unvisited again, due 1 Oct: daily and monthly list it by its due date.
        $this->assertContains('B04', $this->eligibleAt(self::SEEDED_AT));
        $this->assertContains('B04', $this->eligibleAt('2026-10-21 14:00:00'), 'It keeps appearing on later days.');
    }

    public function test_accounts_without_a_next_action_date_are_not_scheduled(): void
    {
        $this->seedAt(self::SEEDED_AT);

        $this->assertNotContains('B29', $this->eligibleAt(self::SEEDED_AT));
        $this->assertNotContains('B29', $this->eligibleAt(self::SEEDED_AT, Constants::PERIOD_MONTHLY));
    }

    public function test_the_latest_recording_by_contact_date_decides_not_the_highest_id(): void
    {
        $this->seedAt(self::SEEDED_AT);

        // B23: the higher id is an older visit whose next action was yesterday; the latest visit's next action arrived at 13:00.
        $this->assertContains('B23', $this->eligibleAt(self::SEEDED_AT));
        // B21: the latest visit is scheduled for tomorrow; the older one (due) is superseded.
        $this->assertNotContains('B21', $this->eligibleAt(self::SEEDED_AT));
        $this->assertContains('B21', $this->eligibleAt(self::SEEDED_AT, Constants::PERIOD_MONTHLY));
        // B22: the latest recording is soft-deleted, so the older due one counts.
        $this->assertContains('B22', $this->eligibleAt(self::SEEDED_AT));
    }

    public function test_just_after_midnight_in_manila_uses_the_manila_date(): void
    {
        // 00:30 on 14 Oct: "today" must already be 14 Oct (it was still 13 Oct when the app ran on UTC).
        $this->seedAt('2026-10-14 00:30:00');

        $codes = $this->eligibleAt('2026-10-14 00:30:00');

        $this->assertContains('A01', $codes, 'Due 14 Oct (Manila).');
        $this->assertContains('B01', $codes, 'Next action at 00:00 Manila on 14 Oct.');
        $this->assertNotContains('A23', $codes);
    }

    public function test_only_the_credit_counselors_own_accounts_are_returned(): void
    {
        $this->seedAt(self::SEEDED_AT);

        $this->assertNotContains('C01', $this->eligibleAt(self::SEEDED_AT));
        $this->assertNotContains('C01', $this->eligibleAt(self::SEEDED_AT, Constants::PERIOD_MONTHLY));
    }

    // ----------------------------------------------------------------- API

    public function test_the_daily_response_never_contains_future_or_other_month_accounts(): void
    {
        $this->seedAt(self::SEEDED_AT);
        $this->travelToManila(self::SEEDED_AT);

        $returned = $this->returnedAccountCodes($this->getVisitPlan('daily')->assertOk()->json('data'));

        foreach (['A23', 'A24', 'A25', 'B02', 'B03', 'B20', 'B21', 'B24', 'B29', 'C01'] as $code) {
            $this->assertNotContains($code, $returned, "{$code} must not be in today's Visit Plan.");
        }

        foreach ([...self::UNVISITED_DUE, 'B04'] as $code) {
            $this->assertContains($code, $returned, "{$code} is eligible today.");
        }

        $this->assertEmpty(array_diff($returned, $this->eligibleAt(self::SEEDED_AT)), 'Returned accounts must be eligible ones.');
    }

    public function test_the_monthly_response_includes_the_daily_accounts_and_future_schedules(): void
    {
        $this->seedAt(self::SEEDED_AT);
        $this->travelToManila(self::SEEDED_AT);

        $daily = $this->returnedAccountCodes($this->getVisitPlan('daily')->assertOk()->json('data'));
        $monthly = $this->returnedAccountCodes($this->getVisitPlan('monthly')->assertOk()->json('data'));

        $this->assertEmpty(array_diff($daily, $monthly), 'Daily accounts must also be in the monthly plan.');

        foreach (['A23', 'B02', 'B03', 'B20', 'B21'] as $code) {
            $this->assertContains($code, $monthly, "{$code} is scheduled later this month.");
        }

        foreach (['A24', 'A25', 'B24', 'B29', 'C01'] as $code) {
            $this->assertNotContains($code, $monthly, "{$code} must not be in this month's plan.");
        }
    }

    public function test_the_period_queries_return_the_eligible_accounts_and_the_visits_of_their_period(): void
    {
        $this->seedAt(self::SEEDED_AT);
        $this->travelToManila(self::SEEDED_AT);
        $repository = app(DashboardRepository::class);
        $codes = fn ($accounts) => $accounts->map(fn ($account) => strtok($account->customer_name, ' '))->unique()->sort()->values()->all();

        $daily = $repository->getDailyAccounts(self::CREDIT_COUNSELOR_ID);
        $monthly = $repository->getMonthlyAccounts(self::CREDIT_COUNSELOR_ID);

        $this->assertSame($this->eligibleAt(self::SEEDED_AT), $codes($daily['for_visit']));
        $this->assertSame($this->eligibleAt(self::SEEDED_AT, Constants::PERIOD_MONTHLY), $codes($monthly['for_visit']));

        // Visits of the period: recordings with a contact_date today / this month, not deleted.
        $this->assertSame(['B20', 'B23', 'B29'], $codes($daily['visited']));
        $this->assertCount(3, $daily['visited']);
        $this->assertCount(33, $monthly['visited'], '35 sample recordings - 1 deleted (B22) - 1 from last month (B24).');
        $this->assertCount(33, $monthly['visited']->pluck('contact_recording_id')->unique());
        $this->assertNotContains('B24', $codes($monthly['visited']));
    }

    public function test_the_visited_lists_ignore_soft_deleted_recordings(): void
    {
        $this->seedAt(self::SEEDED_AT);
        $this->travelToManila(self::SEEDED_AT);

        // B22's latest recording (13 Oct 10:00) is soft-deleted; only its valid one (11 Oct) is a visit.
        $b22 = app(DashboardRepository::class)->getMonthlyAccounts(self::CREDIT_COUNSELOR_ID)['visited']
            ->where('account_number', '7200000022');

        $this->assertCount(1, $b22);
        $this->assertSame('2026-10-11 10:00:00', $b22->first()->contact_date);

        // A visit recorded today and then deleted is not a visit either.
        $recording = ContactRecording::create([
            'account_id' => Account::where('account_number', '7100000001')->firstOrFail()->id,
            'current_category' => 'NP0',
            'follow_up_mode' => 'FIELD VISIT',
            'action_taken' => 'FOLLOW UP PAYMENT',
            'recorded_by' => 1,
            'contact_date' => Carbon::now(),
        ]);
        $recording->delete();

        $daily = app(DashboardRepository::class)->getDailyAccounts(self::CREDIT_COUNSELOR_ID)['visited'];
        $this->assertNotContains('A01', $daily->map(fn ($account) => strtok($account->customer_name, ' '))->all());
    }

    public function test_the_monthly_visited_window_ends_at_the_start_of_next_month_even_on_the_31st(): void
    {
        $this->seedAt(self::SEEDED_AT);
        $this->travelToManila('2026-10-31 12:00:00');

        $accountId = Account::where('account_number', '7100000001')->firstOrFail()->id;
        foreach (['2026-10-31 09:00:00', '2026-11-15 10:00:00'] as $contactDate) {
            ContactRecording::create([
                'account_id' => $accountId,
                'current_category' => 'NP0',
                'follow_up_mode' => 'FIELD VISIT',
                'action_taken' => 'FOLLOW UP PAYMENT',
                'recorded_by' => 1,
                'contact_date' => Carbon::parse($contactDate),
            ]);
        }

        $visited = app(DashboardRepository::class)->getMonthlyAccounts(self::CREDIT_COUNSELOR_ID)['visited'];

        $contactDates = $visited->pluck('contact_date')->all();

        $this->assertContains('2026-10-31 09:00:00', $contactDates);
        $this->assertNotContains('2026-11-15 10:00:00', $contactDates, 'A November visit is not part of the October window.');
    }

    public function test_the_visited_list_still_holds_todays_visits(): void
    {
        $this->seedAt(self::SEEDED_AT);
        $this->travelToManila(self::SEEDED_AT);

        $visited = collect($this->getVisitPlan('daily')->assertOk()->json('data.visited'))
            ->map(fn ($account) => strtok($account['customer_name'], ' '))
            ->unique()->sort()->values()->all();

        $this->assertSame(['B20', 'B23', 'B29'], $visited);
    }
}
