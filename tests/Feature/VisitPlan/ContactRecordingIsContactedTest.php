<?php

namespace Tests\Feature\VisitPlan;

use App\Models\Account;
use App\Models\ContactRecording;
use App\Repositories\DashboardRepository;
use Carbon\Carbon;
use Database\Seeders\CmsUserSeeder;
use Database\Seeders\CompanySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * contact_recordings.is_contacted mirrors accounts.is_contacted (boolean, default false, indexed):
 * whether the account was visited when the recording's next action came due.
 */
class ContactRecordingIsContactedTest extends VisitPlanTestCase
{
    public function test_the_column_mirrors_the_accounts_column_definition(): void
    {
        $definition = fn (string $table) => collect(Schema::getColumns($table))->firstWhere('name', 'is_contacted');

        $accounts = $definition('accounts');
        $recordings = $definition('contact_recordings');

        $this->assertNotNull($recordings, 'contact_recordings.is_contacted is missing.');
        $this->assertSame($accounts['type'], $recordings['type']);
        $this->assertSame($accounts['nullable'], $recordings['nullable']);
        $this->assertSame($accounts['default'], $recordings['default']);

        $indexed = fn (string $table) => collect(Schema::getIndexes($table))
            ->contains(fn ($index) => $index['columns'] === ['is_contacted']);

        $this->assertTrue($indexed('accounts'));
        $this->assertTrue($indexed('contact_recordings'));
    }

    public function test_a_new_recording_defaults_to_not_contacted(): void
    {
        $account = $this->createAccount();

        $recording = ContactRecording::create($this->recordingAttributes($account));

        $this->assertFalse($recording->fresh()->is_contacted);
        $this->assertSame(0, (int) DB::table('contact_recordings')->where('id', $recording->id)->value('is_contacted'));
    }

    public function test_the_flag_can_be_saved_and_is_cast_to_a_boolean(): void
    {
        $account = $this->createAccount();

        $recording = ContactRecording::create($this->recordingAttributes($account) + ['is_contacted' => true]);

        $this->assertTrue($recording->fresh()->is_contacted);

        $recording->update(['is_contacted' => false]);

        $this->assertFalse($recording->fresh()->is_contacted);
    }

    public function test_the_sample_data_marks_only_recordings_followed_by_a_later_visit(): void
    {
        $this->travelToManila('2026-10-14 14:00:00');
        $this->seedVisitPlanData();

        $contacted = DB::table('contact_recordings')
            ->join('accounts', 'accounts.id', '=', 'contact_recordings.account_id')
            ->where('contact_recordings.is_contacted', true)
            ->orderBy('contact_recordings.id')
            ->pluck('accounts.account_number')
            ->all();

        // B20, B21 and B23: their earlier recording was followed by a later visit.
        $this->assertSame(['7200000020', '7200000021', '7200000023'], $contacted);
    }

    public function test_the_visit_plan_rows_carry_the_flag_of_the_latest_recording_and_of_the_history(): void
    {
        $this->travelToManila('2026-10-14 14:00:00');
        $this->seedVisitPlanData();

        $b20 = app(DashboardRepository::class)
            ->getVisitPlanAccounts(self::CREDIT_COUNSELOR_ID, Carbon::now(), 'monthly')
            ->firstWhere('account_number', '7200000020');

        $this->assertEquals(0, $b20->contact_recording_is_contacted, 'The latest recording is not visited yet.');
        $this->assertEquals(1, $b20->is_contacted, 'accounts.is_contacted is a separate flag: B20 was contacted.');
        $this->assertCount(1, $b20->contact_history);
        $this->assertTrue($b20->contact_history->first()->is_contacted, 'The earlier recording was followed by a visit.');
    }

    public function test_accounts_without_recordings_have_no_flag(): void
    {
        $this->travelToManila('2026-10-14 14:00:00');
        $this->seedVisitPlanData();

        $a01 = app(DashboardRepository::class)
            ->getVisitPlanAccounts(self::CREDIT_COUNSELOR_ID, Carbon::now())
            ->firstWhere('account_number', '7100000001');

        $this->assertNull($a01->contact_recording_is_contacted);
    }

    private function createAccount(): Account
    {
        $this->seed([CompanySeeder::class, RoleSeeder::class, CmsUserSeeder::class]);

        return Account::create([
            'account_number' => '7900000001',
            'customer_id' => 1,
            'customer_name' => 'Z01 FLAG TEST',
            'monthly_amortization' => 1,
            'last_payment_amount' => 1,
            'past_due_balance' => 1,
            'days_past_due' => 1,
            'dpd_bucket' => 'DPD 1-30',
            'no_of_non_payments' => 'UPDATED',
            'outstanding_balance' => 1,
            'last_payment_date' => '2026-10-01',
            'payment_status' => 'WITH PAYMENT',
            'asset' => 'X',
            'psgc_code' => '137404000',
            'assigned_cc_id' => 1,
            'assigned_ch_id' => 6,
            'assigned_am_ar_id' => 7,
            'assigned_rm__dh_id' => 8,
            'due_date' => '2026-10-14',
        ]);
    }

    private function recordingAttributes(Account $account): array
    {
        return [
            'account_id' => $account->id,
            'current_category' => 'NP0',
            'follow_up_mode' => 'FIELD VISIT',
            'action_taken' => 'FOLLOW UP PAYMENT',
            'next_action_plan' => 'PROMISE TO PAY',
            'recorded_by' => 1,
            'contact_date' => now(),
            'next_action_date' => now()->addDay(),
        ];
    }
}
