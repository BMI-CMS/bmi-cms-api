<?php

namespace Tests\Feature\VisitPlan;

use App\Models\Account;
use App\Models\ContactRecording;
use Carbon\Carbon;
use Database\Seeders\AccountSeeder;
use Database\Seeders\ContactRecordingSeeder;
use Illuminate\Support\Facades\DB;

/**
 * Verifies the isolated test database and the Visit Plan sample seeders.
 */
class TestEnvironmentTest extends VisitPlanTestCase
{
    private const SAMPLE_ACCOUNTS = 60;

    private const SAMPLE_RECORDINGS = 35;

    public function test_tests_run_on_the_isolated_testing_database(): void
    {
        $this->assertSame('bmi-cms-api_testing', DB::connection()->getDatabaseName());
    }

    public function test_seeders_create_the_expected_sample_data(): void
    {
        $this->travelToManila('2026-10-14 14:00:00');
        $this->seedVisitPlanData();

        $this->assertSame(self::SAMPLE_ACCOUNTS, Account::where('account_number', 'like', '7_00000%')->count());
        $this->assertSame(self::SAMPLE_RECORDINGS, $this->sampleRecordings()->count());
        $this->assertSame(1, $this->sampleRecordings()->onlyTrashed()->count());
        $this->assertSame(0, Account::where('account_number', 'like', '72%')->where('is_contacted', false)->count(),
            'Every group B account (with recordings) is contacted.');
        $this->assertSame(0, Account::where('account_number', 'like', '71%')->where('is_contacted', true)->count(),
            'No group A account (without recordings) is contacted.');
    }

    public function test_seeders_can_be_rerun_without_duplicates(): void
    {
        $this->travelToManila('2026-10-14 14:00:00');
        $this->seedVisitPlanData();
        $accountIds = Account::where('account_number', 'like', '7_00000%')->orderBy('id')->pluck('id');

        $this->seed([AccountSeeder::class, ContactRecordingSeeder::class]);

        $this->assertSame(self::SAMPLE_ACCOUNTS, Account::where('account_number', 'like', '7_00000%')->count());
        $this->assertEquals($accountIds, Account::where('account_number', 'like', '7_00000%')->orderBy('id')->pluck('id'));
        $this->assertSame(self::SAMPLE_RECORDINGS, $this->sampleRecordings()->count());
        $this->assertSame(1, $this->sampleRecordings()->onlyTrashed()->count());
    }

    public function test_the_application_timezone_is_asia_manila(): void
    {
        $this->assertSame('Asia/Manila', config('app.timezone'));
        $this->assertSame('Asia/Manila', date_default_timezone_get());
        $this->assertSame('Asia/Manila', Carbon::now()->tzName);
        $this->assertNull(config('visit_plan.timezone'), 'No second timezone setting may exist.');
    }

    public function test_carbon_uses_the_application_timezone_for_today_and_the_month(): void
    {
        // 00:30 on 10 Oct is still 9 Oct in UTC; with Asia/Manila "today" is already 10 Oct.
        $this->travelToManila('2026-10-10 00:30:00');

        $this->assertSame('2026-10-10 00:00:00', Carbon::today()->toDateTimeString());
        $this->assertSame('2026-10-01 00:00:00', Carbon::now()->startOfMonth()->toDateTimeString());
    }

    public function test_timestamps_are_stored_as_manila_local_time_without_any_shift(): void
    {
        $this->travelToManila('2026-10-10 14:00:00');
        $this->seedVisitPlanData();

        $this->assertSame('2026-10-10 14:00:00', (string) DB::table('accounts')->where('account_number', '7100000001')->value('created_at'));
    }

    public function test_just_after_midnight_uses_the_current_calendar_date(): void
    {
        $this->travelToManila('2026-10-10 00:30:00');
        $this->seedVisitPlanData();

        $this->assertSame('2026-10-10', $this->dueDate('7100000001'));

        // B01 "earlier today" is clamped to midnight.
        $this->assertSame('2026-10-10 00:00:00', $this->rawNextActionDate('7200000001'));
        // B03 "tomorrow 10:00".
        $this->assertSame('2026-10-11 10:00:00', $this->rawNextActionDate('7200000003'));
    }

    public function test_late_evening_keeps_later_today_on_the_same_day(): void
    {
        $this->travelToManila('2026-10-10 23:30:00');
        $this->seedVisitPlanData();

        $this->assertSame('2026-10-10', $this->dueDate('7100000001'));
        // B02 "later today" is clamped to 23:59:59, not pushed to the next day.
        $this->assertSame('2026-10-10 23:59:59', $this->rawNextActionDate('7200000002'));
    }

    public function test_morning_visit_and_afternoon_follow_up_fall_on_the_same_day(): void
    {
        $this->travelToManila('2026-10-10 09:00:00');
        $this->seedVisitPlanData();

        $latest = $this->sampleRecordings()
            ->whereHas('account', fn ($query) => $query->where('account_number', '7200000020'))
            ->orderByDesc('id')
            ->firstOrFail();

        // Visit at 08:00, follow-up at 10:00.
        $this->assertSame('2026-10-10 08:00:00', $latest->getRawOriginal('contact_date'));
        $this->assertSame('2026-10-10 10:00:00', $latest->getRawOriginal('next_action_date'));

        $this->assertTrue(
            Carbon::parse($latest->getRawOriginal('contact_date'))->isSameDay(Carbon::parse($latest->getRawOriginal('next_action_date')))
        );
    }

    private function sampleRecordings()
    {
        return ContactRecording::withTrashed()
            ->whereHas('account', fn ($query) => $query->where('account_number', 'like', '72%'));
    }

    private function dueDate(string $accountNumber): string
    {
        return (string) DB::table('accounts')->where('account_number', $accountNumber)->value('due_date');
    }

    private function rawNextActionDate(string $accountNumber): string
    {
        return (string) DB::table('contact_recordings')
            ->join('accounts', 'accounts.id', '=', 'contact_recordings.account_id')
            ->where('accounts.account_number', $accountNumber)
            ->orderByDesc('contact_recordings.id')
            ->value('contact_recordings.next_action_date');
    }
}
