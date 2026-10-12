<?php

namespace Tests\Feature\VisitPlan;

use App\Models\Account;
use App\Models\ContactRecording;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * accounts.is_contacted = the account's initial contact status: it becomes true when the
 * account is visited for the first time (its first contact recording) and stays true.
 *
 * contact_recordings.is_contacted is a separate, future feature and must not be touched here.
 */
class AccountFirstContactTest extends VisitPlanTestCase
{
    private const SEEDED_AT = '2026-10-14 14:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelToManila(self::SEEDED_AT);
        $this->seedVisitPlanData();
    }

    public function test_the_first_recording_marks_the_account_as_contacted(): void
    {
        $a01 = $this->account('7100000001');
        $this->assertFalse((bool) $a01->is_contacted, 'A01 starts unvisited.');

        $this->recordVisit($a01);

        $this->assertTrue((bool) $a01->fresh()->is_contacted);
    }

    public function test_other_accounts_are_not_affected(): void
    {
        $this->recordVisit($this->account('7100000001'));

        $this->assertFalse((bool) $this->account('7100000002')->is_contacted);
        $this->assertSame(
            0,
            Account::where('account_number', 'like', '71%')->where('account_number', '!=', '7100000001')->where('is_contacted', true)->count(),
        );
    }

    public function test_a_later_recording_leaves_the_flag_and_the_account_untouched(): void
    {
        $a01 = $this->account('7100000001');
        $this->recordVisit($a01);
        $updatedAt = $a01->fresh()->updated_at->toDateTimeString();

        $this->travelToManila('2026-10-15 09:00:00');
        $this->recordVisit($a01);

        $after = $a01->fresh();
        $this->assertTrue((bool) $after->is_contacted);
        $this->assertSame($updatedAt, $after->updated_at->toDateTimeString(), 'The account is not rewritten by later visits.');
    }

    public function test_the_flag_stays_true_when_the_recording_is_deleted(): void
    {
        $a01 = $this->account('7100000001');
        $recording = $this->recordVisit($a01);

        $recording->delete();

        $this->assertTrue((bool) $a01->fresh()->is_contacted, 'The first contact happened and is not undone.');
    }

    public function test_an_already_contacted_account_stays_contacted(): void
    {
        $b01 = $this->account('7200000001');
        $this->assertTrue((bool) $b01->is_contacted);

        $this->recordVisit($b01);

        $this->assertTrue((bool) $b01->fresh()->is_contacted);
    }

    public function test_the_recordings_own_flag_is_not_touched(): void
    {
        $recording = $this->recordVisit($this->account('7100000001'));

        $this->assertFalse($recording->fresh()->is_contacted, 'contact_recordings.is_contacted is a future feature.');
    }

    public function test_every_account_with_a_recording_is_marked_contacted_in_the_sample_data(): void
    {
        $unmarked = DB::table('accounts')
            ->where('is_contacted', false)
            ->whereExists(fn ($query) => $query->select(DB::raw(1))->from('contact_recordings')->whereColumn('contact_recordings.account_id', 'accounts.id'))
            ->count();

        $this->assertSame(0, $unmarked);
    }

    private function account(string $accountNumber): Account
    {
        return Account::where('account_number', $accountNumber)->firstOrFail();
    }

    private function recordVisit(Account $account): ContactRecording
    {
        return ContactRecording::create([
            'account_id' => $account->id,
            'current_category' => 'NP0',
            'follow_up_mode' => 'FIELD VISIT',
            'action_taken' => 'FOLLOW UP PAYMENT',
            'next_action_plan' => 'PROMISE TO PAY',
            'recorded_by' => 1,
            'contact_date' => Carbon::now(),
            'next_action_date' => Carbon::now()->addDay(),
        ]);
    }
}
