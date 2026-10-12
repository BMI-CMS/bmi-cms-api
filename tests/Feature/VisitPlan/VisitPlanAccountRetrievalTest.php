<?php

namespace Tests\Feature\VisitPlan;

use App\Models\Account;
use App\Repositories\DashboardRepository;
use Carbon\Carbon;
use Database\Seeders\CollectionAddressSeeder;
use Database\Seeders\CustomerDetailSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Step 2 — retrieval only: DashboardRepository::getAssignedAccounts().
 * No classification or scheduling is checked here.
 *
 * Time is frozen at 2026-10-14 14:00 (application timezone, Asia/Manila).
 */
class VisitPlanAccountRetrievalTest extends VisitPlanTestCase
{
    private const CC1_ACCOUNTS = 59;

    private const ACCOUNT_COLUMNS = [
        'account_number', 'customer_name', 'monthly_amortization', 'past_due_balance', 'days_past_due',
        'dpd_bucket', 'no_of_non_payments', 'outstanding_balance', 'last_payment_date', 'asset',
        'payment_status', 'is_force_prioritized', 'assigned_cc_id', 'assigned_ch_id', 'assigned_am_ar_id',
        'assigned_rm__dh_id', 'due_date', 'psgc_code', 'is_contacted', 'created_at',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelToManila('2026-10-14 14:00:00');
        $this->seedVisitPlanData();
        $this->seed([CustomerDetailSeeder::class, CollectionAddressSeeder::class]);
    }

    public function test_returns_every_account_of_the_credit_counselor_once(): void
    {
        $accounts = $this->retrieve();

        $this->assertCount(self::CC1_ACCOUNTS, $accounts);
        $this->assertCount(self::CC1_ACCOUNTS, $accounts->pluck('id')->unique());
        $this->assertTrue($accounts->every(fn ($account) => $account->assigned_cc_id == self::CREDIT_COUNSELOR_ID));
        $this->assertNull($this->find($accounts, '7300000001'), 'Another credit counselor\'s account was returned.');
    }

    public function test_account_columns_match_the_database(): void
    {
        $stored = DB::table('accounts')->where('assigned_cc_id', self::CREDIT_COUNSELOR_ID)->get()->keyBy('id');

        foreach ($this->retrieve() as $account) {
            foreach (self::ACCOUNT_COLUMNS as $column) {
                $this->assertEquals(
                    $stored[$account->id]->{$column},
                    $account->getRawOriginal($column),
                    "Account {$account->account_number}: {$column} differs from the database.",
                );
            }
        }
    }

    public function test_classification_inputs_are_returned_as_stored(): void
    {
        $accounts = $this->retrieve();

        $a12 = $this->find($accounts, '7100000012');
        $this->assertSame('DPD 61-90', $a12->dpd_bucket);
        $this->assertSame('LAST PAID 2 MONTH AGO', $a12->no_of_non_payments);
        $this->assertSame('FIRST DUE DATE', $a12->payment_status);
        $this->assertSame('2026-10-14', $a12->due_date);

        $this->assertSame('LAST PAID 4 MONTHS AGO', $this->find($accounts, '7100000026')->no_of_non_payments);
        $this->assertEquals(1, $this->find($accounts, '7200000016')->is_force_prioritized);
        $this->assertEquals(0, $this->find($accounts, '7100000001')->is_force_prioritized);
    }

    public function test_accounts_without_recordings_have_no_recording_data(): void
    {
        $withoutRecordings = $this->retrieve()->filter(fn ($account) => str_starts_with($account->account_number, '71'));

        $this->assertCount(28, $withoutRecordings);

        foreach ($withoutRecordings as $account) {
            $this->assertNull($account->contact_recording_id, "{$account->account_number} has a recording.");
            $this->assertNull($account->next_action_plan);
            $this->assertNull($account->next_action_date);
            $this->assertNull($account->current_category);
            $this->assertCount(0, $account->contact_history);
        }
    }

    public function test_every_account_with_recordings_gets_its_latest_recording(): void
    {
        $withRecordings = $this->retrieve()->filter(fn ($account) => str_starts_with($account->account_number, '72'));

        $this->assertCount(31, $withRecordings);
        $this->assertTrue($withRecordings->every(fn ($account) => $account->contact_recording_id !== null));
    }

    public function test_latest_recording_uses_contact_date_then_id(): void
    {
        $accounts = $this->retrieve();

        // B20: the visit today (13:00) replaces yesterday's recording.
        $b20 = $this->find($accounts, '7200000020');
        $this->assertSame('PROMISE TO PAY', $b20->next_action_plan);
        $this->assertSame('2026-10-14 13:00:00', $b20->contact_date);
        $this->assertSame('2026-10-14 15:00:00', $b20->next_action_date);
        $this->assertSame(['FOR FIELD VISIT'], $b20->contact_history->pluck('next_action_plan')->all());

        // B21: yesterday's future-dated SKIPTRACE is newer than the 5-day-old PTP.
        $b21 = $this->find($accounts, '7200000021');
        $this->assertSame('FOR SKIPTRACE', $b21->next_action_plan);
        $this->assertSame(['PROMISE TO PAY'], $b21->contact_history->pluck('next_action_plan')->all());

        // B23: the higher id was synced later but has the older contact_date.
        $b23 = $this->find($accounts, '7200000023');
        $this->assertSame('FOR SERVICE OF COLLECTION NOTICE', $b23->next_action_plan);
        $this->assertSame(['FOR FIELD VISIT'], $b23->contact_history->pluck('next_action_plan')->all());
    }

    public function test_soft_deleted_recordings_are_ignored(): void
    {
        $b22 = $this->find($this->retrieve(), '7200000022');

        $this->assertSame('PROMISE TO PAY', $b22->next_action_plan);
        $this->assertCount(0, $b22->contact_history);
    }

    public function test_recording_fields_are_returned_as_stored(): void
    {
        $accounts = $this->retrieve();

        $b13 = $this->find($accounts, '7200000013');
        $this->assertSame('FDD/NS', $b13->current_category);
        $this->assertSame('FOR WRITE OFF (MINIMAL BALANCE)', $b13->next_action_plan);

        $this->assertNull($this->find($accounts, '7200000014')->current_category);

        $b29 = $this->find($accounts, '7200000029');
        $this->assertSame('COLLECTED', $b29->action_taken);
        $this->assertNull($b29->next_action_plan);
        $this->assertNull($b29->next_action_date);
    }

    public function test_contact_history_holds_only_the_earlier_recordings(): void
    {
        $accounts = $this->retrieve();

        $this->assertSame(3, $accounts->sum(fn ($account) => $account->contact_history->count()));
        $this->assertTrue($accounts->every(
            fn ($account) => !$account->contact_history->contains('id', $account->contact_recording_id)
        ));
    }

    public function test_address_and_contact_details_are_joined(): void
    {
        $accounts = $this->retrieve();

        $a01 = $this->find($accounts, '7100000001');
        $this->assertSame('09170000001', $a01->contact_number);
        $this->assertSame('Quezon City', $a01->city_municipality);

        // Every sample account has an address in the city of its own psgc_code.
        $a05 = $this->find($accounts, '7100000005');
        $this->assertSame('09170000005', $a05->contact_number);
        $this->assertSame('Pasig City', $a05->city_municipality);
    }

    public function test_soft_deleted_accounts_are_excluded(): void
    {
        Account::where('account_number', '7100000007')->firstOrFail()->delete();

        $accounts = $this->retrieve();

        $this->assertCount(self::CC1_ACCOUNTS - 1, $accounts);
        $this->assertNull($this->find($accounts, '7100000007'));
    }

    public function test_uses_two_queries_regardless_of_account_count(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->retrieve();

        $this->assertCount(2, DB::getQueryLog());
    }

    public function test_date_range_keeps_only_accounts_created_inside_it(): void
    {
        $this->setCreatedAt('7100000001', '2026-09-30 23:59:59'); // last month
        $this->setCreatedAt('7100000002', '2026-10-01 00:00:00'); // first instant of the month
        $this->setCreatedAt('7100000003', '2026-10-31 23:59:59'); // last second of the month
        $this->setCreatedAt('7100000004', '2026-11-01 00:00:00'); // next month (upper bound is exclusive)

        $accounts = $this->retrieve();

        $this->assertNull($this->find($accounts, '7100000001'));
        $this->assertNotNull($this->find($accounts, '7100000002'));
        $this->assertNotNull($this->find($accounts, '7100000003'));
        $this->assertNull($this->find($accounts, '7100000004'));
        $this->assertCount(self::CC1_ACCOUNTS - 2, $accounts);
    }

    public function test_recordings_of_accounts_outside_the_range_are_not_returned(): void
    {
        $this->setCreatedAt('7200000020', '2026-09-15 10:00:00');

        $accounts = $this->retrieve();

        $this->assertNull($this->find($accounts, '7200000020'));
        // B20 had 1 earlier recording; the other 2 earlier recordings (B21, B23) are still returned.
        $this->assertSame(2, $accounts->sum(fn ($account) => $account->contact_history->count()));
    }

    private function retrieve(): Collection
    {
        $from = Carbon::parse('2026-10-01')->startOfMonth();
        $to = $from->copy()->addMonth();

        return app(DashboardRepository::class)->getAssignedAccounts(self::CREDIT_COUNSELOR_ID, $from, $to);
    }

    private function setCreatedAt(string $accountNumber, string $dateTime): void
    {
        DB::table('accounts')->where('account_number', $accountNumber)->update(['created_at' => $dateTime]);
    }

    private function find(Collection $accounts, string $accountNumber): ?Account
    {
        return $accounts->firstWhere('account_number', $accountNumber);
    }
}
