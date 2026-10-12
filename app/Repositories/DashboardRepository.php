<?php

namespace App\Repositories;

use App\Models\Account;
use App\Models\ContactRecording;
use App\Models\FecAccount;
use App\Models\RepossessionRequest;
use App\Queries\ReceiptEncodingQuery;
use App\Queries\RestructuringQuery;
use App\Queries\ForRepossessionQuery;
use App\Queries\ContactRecordingQuery;
use App\Queries\RepossessionRequestQuery;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use App\Constants\Constants;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;

class DashboardRepository
{
    public function getAccountPerformance(int $userId)
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfDay();

        $receiptEncodings = ReceiptEncodingQuery::receiptEncoding(
            $startDate,
            $endDate
        );

        $restructurings = RestructuringQuery::restructured(
            $startDate,
            $endDate
        );

        $forRepossessions = ForRepossessionQuery::forRepossessed(
            $startDate,
            $endDate
        );

        return Account::leftJoinSub(
            $receiptEncodings,
            'receipt_encodings',
            'accounts.id',
            '=',
            'receipt_encodings.account_id'
        )
            ->leftJoinSub(
                $restructurings,
                'restructurings',
                'accounts.id',
                '=',
                'restructurings.account_id'
            )
            ->leftJoinSub(
                $forRepossessions,
                'for_repossessions',
                'accounts.id',
                '=',
                'for_repossessions.account_id'
            )
            ->selectRaw('
            COUNT(accounts.id) AS total_accounts,
            COUNT(receipt_encodings.account_id) AS total_paid_accounts,
            COUNT(for_repossessions.account_id) AS total_repossessed_accounts,
            COUNT(restructurings.account_id) AS total_restructured_accounts,

            SUM(accounts.past_due_balance) AS total_past_due_amount,
            SUM(receipt_encodings.amount) AS total_receipt_encode_amount,
            SUM(for_repossessions.amount) AS total_repossessed_amount,
            SUM(restructurings.new_monthly_amortization) AS total_restructured_amount
            ')
            ->where('accounts.assigned_cc_id', $userId)
            ->where('accounts.created_at', '>=', $startDate)
            ->where('accounts.created_at', '<', $endDate)
            ->first();
    }
    public function getDailyAccounts(int $userId)
    {
        $startOfDay = Carbon::today()->startOfDay();
        $startOfNextDay = $startOfDay->copy()->addDay();

        return [
            "for_visit" => $this->getVisitPlanAccounts($userId, Carbon::now(), Constants::PERIOD_DAILY),
            "visited" => $this->visitedAccount($userId, $startOfDay, $startOfNextDay),
        ];
    }
    public function getMonthlyAccounts(int $userId)
    {
        $startOfTheMonth = Carbon::now()->startOfMonth();
        // From the 1st: addMonth() on the 31st overflows (31 Oct + 1 month = 1 Dec).
        $endOfTheMonth = $startOfTheMonth->copy()->addMonth();

        return [
            "for_visit" => $this->getVisitPlanAccounts($userId, Carbon::now(), Constants::PERIOD_MONTHLY),
            "visited" => $this->visitedAccount($userId, $startOfTheMonth, $endOfTheMonth)
        ];
    }

    /**
     * Visit Plan eligibility: the accounts of the credit counselor to visit at $now
     * (docs/general-business-rules.md, section 7.1).
     *
     * Unvisited (no contact recording that is not soft-deleted), by due_date within the current month:
     *  - daily:   due_date has arrived (<= today); it keeps matching on later days until visited.
     *  - monthly: any due_date in the month, including later days.
     * Visited (only the latest recording counts), by next_action_date:
     *  - daily:   from `next_action_carry_over_days` ago (never before the month start) until now.
     *  - monthly: the same start, but up to the end of the month (future schedules included).
     * Schedules of other months are never included; archiving is not implemented.
     *
     * $now is a Carbon in the application timezone (Asia/Manila), the same one the datetime
     * columns are stored in, so it is compared as-is.
     *
     * Returns one row per account with its latest recording and the earlier recordings as
     * contact_history. Classification is not done here.
     */
    public function getVisitPlanAccounts(int $userId, CarbonInterface $now, string $period = Constants::PERIOD_DAILY)
    {
        $monthly = $period === Constants::PERIOD_MONTHLY;

        $today = $now->copy()->startOfDay();
        $monthStart = $now->copy()->startOfMonth();

        $dueFrom = $monthStart->toDateString();
        $dueUntil = ($monthly ? $now->copy()->endOfMonth() : $today)->toDateString();

        $nextActionFrom = $today->copy()->subDays((int) config('visit_plan.next_action_carry_over_days'))->max($monthStart);
        $nextActionUntil = $monthly ? $monthStart->copy()->addMonth() : $now;
        $nextActionUntilOperator = $monthly ? '<' : '<=';

        $accounts = $this->assignedAccountsQuery($userId)
            ->where(function ($query) use ($dueFrom, $dueUntil, $nextActionFrom, $nextActionUntil, $nextActionUntilOperator) {
                $query->where(function ($unvisited) use ($dueFrom, $dueUntil) {
                    $unvisited->whereNull('latest_recordings.id')
                        ->where('accounts.due_date', '>=', $dueFrom)
                        ->where('accounts.due_date', '<=', $dueUntil);
                })->orWhere(function ($scheduled) use ($nextActionFrom, $nextActionUntil, $nextActionUntilOperator) {
                    $scheduled->where('latest_recordings.next_action_date', '>=', $nextActionFrom)
                        ->where('latest_recordings.next_action_date', $nextActionUntilOperator, $nextActionUntil);
                });
            })
            ->get();

        return $this->withContactHistory($accounts);
    }

    /**
     * Visit Plan retrieval: the accounts assigned to the credit counselor that were created
     * within [$createdFrom, $createdTo), one row per account, with its latest contact recording
     * (null when it has none) and the earlier recordings as contact_history.
     * No classification or scheduling filters here.
     *
     * The range is half-open on accounts.created_at (indexed) and is compared as-is, with no
     * function on the column, so the index stays usable. The caller decides what the range
     * means (e.g. the current month).
     *
     * Latest recording = latest contact_date, then highest id; soft-deleted recordings are
     * ignored (SoftDeletes scope on ContactRecording).
     */
    public function getAssignedAccounts(int $userId, CarbonInterface $createdFrom, CarbonInterface $createdTo)
    {
        $accounts = $this->assignedAccountsQuery($userId, function ($query) use ($createdFrom, $createdTo) {
            $query->where('accounts.created_at', '>=', $createdFrom)
                ->where('accounts.created_at', '<', $createdTo);
        })->get();

        return $this->withContactHistory($accounts);
    }

    /**
     * Accounts of the credit counselor, one row each, left-joined to their latest non-deleted
     * contact recording (latest_recordings.*, null when there is none), address and contact
     * details. $accountScope may add conditions on the accounts table; they are applied to the
     * ranked recordings as well so MySQL ranks only the rows it needs.
     */
    private function assignedAccountsQuery(int $userId, ?Closure $accountScope = null): EloquentBuilder
    {
        $scopedAccountIds = Account::query()
            ->select('accounts.id')
            ->where('accounts.assigned_cc_id', $userId);

        if ($accountScope) {
            $accountScope($scopedAccountIds);
        }

        $latestContactRecordings = ContactRecording::query()
            ->select([
                'contact_recordings.id',
                'contact_recordings.account_id',
                'contact_recordings.current_category',
                'contact_recordings.follow_up_mode',
                'contact_recordings.reason_for_default',
                'contact_recordings.action_taken',
                'contact_recordings.next_action_plan',
                'contact_recordings.next_action_date',
                'contact_recordings.remarks',
                'contact_recordings.geotagging',
                'contact_recordings.recorded_by',
                'contact_recordings.contact_date',
                'contact_recordings.is_contacted',
            ])
            ->selectRaw('ROW_NUMBER() OVER (
                PARTITION BY contact_recordings.account_id
                ORDER BY contact_recordings.contact_date DESC, contact_recordings.id DESC
            ) AS recording_rank')
            ->whereIn('contact_recordings.account_id', $scopedAccountIds);

        $accounts = Account::select([
            'accounts.id',
            'accounts.account_number',
            'accounts.customer_name',
            'accounts.monthly_amortization',
            'accounts.past_due_balance',
            'accounts.days_past_due',
            'accounts.dpd_bucket',
            'accounts.no_of_non_payments',
            'accounts.outstanding_balance',
            'accounts.last_payment_date',
            'accounts.asset',
            'accounts.payment_status',
            'accounts.is_force_prioritized',
            'accounts.assigned_cc_id',
            'accounts.assigned_ch_id',
            'accounts.assigned_am_ar_id',
            'accounts.assigned_rm__dh_id',
            'accounts.due_date',
            'accounts.psgc_code',
            'accounts.is_contacted',
            'accounts.created_at',
            'collecting_addresses.unit_lot_block',
            'collecting_addresses.street_name',
            'collecting_addresses.subdivision_village',
            'collecting_addresses.province',
            'collecting_addresses.postal_code',
            'collecting_addresses.barangay',
            'collecting_addresses.city_municipality',
            'collecting_addresses.region',
            'customer_details.contact_number',
            'customer_details.email',
            'customer_details.social_media_account',
            'latest_recordings.id as contact_recording_id',
            'latest_recordings.current_category',
            'latest_recordings.follow_up_mode',
            'latest_recordings.reason_for_default',
            'latest_recordings.action_taken',
            'latest_recordings.next_action_plan',
            'latest_recordings.next_action_date',
            'latest_recordings.remarks',
            'latest_recordings.geotagging',
            'latest_recordings.recorded_by',
            'latest_recordings.contact_date',
            'latest_recordings.is_contacted as contact_recording_is_contacted',
        ])
            ->leftJoinSub($latestContactRecordings, 'latest_recordings', function ($join) {
                $join->on('latest_recordings.account_id', '=', 'accounts.id')
                    ->where('latest_recordings.recording_rank', '=', 1);
            })
            ->leftJoin('collecting_addresses', function ($join) {
                $join->on('collecting_addresses.account_id', '=', 'accounts.id')
                    ->whereNull('collecting_addresses.deleted_at');
            })
            ->leftJoin('customer_details', function ($join) {
                $join->on('customer_details.account_id', '=', 'accounts.id')
                    ->whereNull('customer_details.deleted_at');
            })
            ->where('accounts.assigned_cc_id', $userId)
            ->orderBy('accounts.id');

        if ($accountScope) {
            $accountScope($accounts);
        }

        return $accounts;
    }

    /**
     * Sets $account->contact_history to the earlier (non-latest) recordings of each account,
     * newest first, using one query.
     */
    private function withContactHistory(Collection $accounts)
    {
        $latestRecordingIds = $accounts->pluck('contact_recording_id')->filter();

        $contactHistory = ContactRecording::select([
            'contact_recordings.id',
            'contact_recordings.current_category',
            'contact_recordings.account_id',
            'contact_recordings.follow_up_mode',
            'contact_recordings.reason_for_default',
            'contact_recordings.action_taken',
            'contact_recordings.next_action_plan',
            'contact_recordings.next_action_date',
            'contact_recordings.remarks',
            'contact_recordings.geotagging',
            'contact_recordings.recorded_by',
            'contact_recordings.contact_date',
            'contact_recordings.is_contacted',
        ])
            ->whereIn('account_id', $accounts->pluck('id'))
            ->orderByDesc('contact_date')
            ->orderByDesc('id')
            ->get()
            // Excluded here, not with whereNotIn('id'): that pushes MySQL off the account_id index
            // onto a primary-key range scan.
            ->reject(fn($recording) => $latestRecordingIds->contains($recording->id))
            ->groupBy('account_id');

        return $accounts->each(function ($account) use ($contactHistory) {
            $account->contact_history = $contactHistory
                ->get($account->id, collect())
                ->values();
        });
    }
    public function visitedAccount(int $userId, string $startDate, string $startOfNextDay)
    {
        return Account::select([
            'accounts.id',
            'accounts.account_number',
            'accounts.customer_name',
            'accounts.monthly_amortization',
            'accounts.past_due_balance',
            'accounts.days_past_due',
            'accounts.dpd_bucket',
            'accounts.no_of_non_payments',
            'accounts.outstanding_balance',
            'accounts.last_payment_date',
            'accounts.asset',
            'accounts.payment_status',
            'accounts.is_force_prioritized',
            'accounts.assigned_cc_id',
            'accounts.assigned_ch_id',
            'accounts.assigned_am_ar_id',
            'accounts.assigned_rm__dh_id',
            'accounts.due_date',
            'accounts.psgc_code',
            'accounts.is_contacted',
            'contact_recordings.id as contact_recording_id',
            'contact_recordings.account_id',
            'contact_recordings.follow_up_mode',
            'contact_recordings.reason_for_default',
            'contact_recordings.action_taken',
            'contact_recordings.current_category',
            'contact_recordings.remarks',
            'contact_recordings.geotagging',
            'contact_recordings.recorded_by',
            'contact_recordings.contact_date',
        ])
            ->join('contact_recordings', function ($join) {
                $join->on('contact_recordings.account_id', '=', 'accounts.id')
                    ->whereNull('contact_recordings.deleted_at');
            })
            ->where('accounts.assigned_cc_id', $userId)
            ->where('contact_recordings.contact_date', '>=', $startDate)
            ->where('contact_recordings.contact_date', '<', $startOfNextDay)
            ->get();
    }
    //higher up
    public function getDailyTeamAccounts(int $userId, string $psgc_code, string $period)
    {
        $startDate = Carbon::today()->addDay();
        $contactRecording = ContactRecordingQuery::contactRecorded();

        return Account::leftJoinSub(
            $contactRecording,
            'contact_recordings',
            'accounts.id',
            '=',
            'contact_recordings.account_id'
        )
            ->join(
                'cms_users',
                'accounts.assigned_cc_id',
                '=',
                'cms_users.id'
            )
            ->leftJoin(
                'collecting_addresses',
                'accounts.id',
                '=',
                'collecting_addresses.account_id'
            )
            ->leftJoin(
                'customer_details',
                'accounts.id',
                '=',
                'customer_details.account_id'
            )
            ->select([
                'accounts.id as account_id',
                'cms_users.id as user_id',
                'cms_users.name AS credit_counselor_name',
                'contact_recordings.contact_recording_id',
                'accounts.account_number',
                'accounts.customer_name',
                'accounts.monthly_amortization',
                'accounts.past_due_balance',
                'accounts.days_past_due',
                'accounts.dpd_bucket',
                'accounts.no_of_non_payments',
                'accounts.outstanding_balance',
                'accounts.last_payment_date',
                'accounts.asset',
                'accounts.is_contacted',
                'accounts.payment_status',
                'accounts.is_force_prioritized',
                'contact_recordings.follow_up_mode',
                'contact_recordings.reason_for_default',
                'contact_recordings.action_taken',
                'contact_recordings.next_action_plan',
                'contact_recordings.remarks',
                'contact_recordings.geotagging',
                'contact_recordings.recorded_by',
                'contact_recordings.contact_date',
                'accounts.assigned_cc_id',
                'accounts.assigned_ch_id',
                'accounts.assigned_am_ar_id',
                'accounts.assigned_rm__dh_id',
                'accounts.due_date',
                'accounts.psgc_code',
                'collecting_addresses.unit_lot_block',
                'collecting_addresses.street_name',
                'collecting_addresses.subdivision_village',
                'collecting_addresses.province',
                'collecting_addresses.postal_code',
                'collecting_addresses.barangay',
                'collecting_addresses.city_municipality',
                'collecting_addresses.region',
                'customer_details.contact_number',
                'customer_details.email',
                'customer_details.social_media_account',
                'accounts.created_at',
                'contact_recordings.next_action_date'
            ])
            ->where('accounts.assigned_ch_id', $userId)
            ->where('accounts.psgc_code', $psgc_code)
            ->where('accounts.created_at', '<', $startDate)
            ->orderBy('accounts.id', 'asc')
            ->orderBy('accounts.created_at', 'desc')
            ->get();
        if ($period === Constants::PERIOD_DAILY) {
        } else {
        }
    }
    public function getMonthlyTeamAccounts(int $userId, string $psgc_code, string $period)
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->addMonth()->startOfMonth();

        $contactRecording = ContactRecordingQuery::contactRecorded();

        return Account::leftJoinSub(
            $contactRecording,
            'contact_recordings',
            'accounts.id',
            '=',
            'contact_recordings.account_id'
        )
            ->join(
                'cms_users',
                'accounts.assigned_cc_id',
                '=',
                'cms_users.id'
            )
            ->leftJoin(
                'collecting_addresses',
                'accounts.id',
                '=',
                'collecting_addresses.account_id'
            )
            ->leftJoin(
                'customer_details',
                'accounts.id',
                '=',
                'customer_details.account_id'
            )
            ->select([
                'accounts.id as account_id',
                'cms_users.id as user_id',
                'cms_users.name AS credit_counselor_name',
                'contact_recordings.contact_recording_id',
                'accounts.account_number',
                'accounts.customer_name',
                'accounts.monthly_amortization',
                'accounts.past_due_balance',
                'accounts.days_past_due',
                'accounts.dpd_bucket',
                'accounts.no_of_non_payments',
                'accounts.outstanding_balance',
                'accounts.last_payment_date',
                'accounts.asset',
                'accounts.payment_status',
                'accounts.is_force_prioritized',
                'contact_recordings.follow_up_mode',
                'contact_recordings.reason_for_default',
                'contact_recordings.action_taken',
                'contact_recordings.next_action_plan',
                'contact_recordings.remarks',
                'contact_recordings.geotagging',
                'contact_recordings.recorded_by',
                'contact_recordings.contact_date',
                'accounts.assigned_cc_id',
                'accounts.assigned_ch_id',
                'accounts.assigned_am_ar_id',
                'accounts.assigned_rm__dh_id',
                'accounts.due_date',
                'accounts.psgc_code',
                'accounts.is_contacted',
                'collecting_addresses.unit_lot_block',
                'collecting_addresses.street_name',
                'collecting_addresses.subdivision_village',
                'collecting_addresses.province',
                'collecting_addresses.postal_code',
                'collecting_addresses.barangay',
                'collecting_addresses.city_municipality',
                'collecting_addresses.region',
                'customer_details.contact_number',
                'customer_details.email',
                'customer_details.social_media_account',
                'accounts.created_at',
                'contact_recordings.next_action_date'
            ])
            ->where('accounts.assigned_ch_id', $userId)
            ->where('accounts.psgc_code', $psgc_code)
            ->where('accounts.created_at', '>=', $startDate)
            ->where('accounts.created_at', '<', $endDate)
            ->orderBy('accounts.id', 'asc')
            ->orderBy('accounts.created_at', 'desc')
            ->get();
    }
    /**
     * Visit Plan eligibility for a higher-up's team in one PSGC code: the same rules as
     * getVisitPlanAccounts (docs/general-business-rules.md, section 7.1), but for every credit
     * counselor of the requester's team. Each row carries credit_counselor_name and the account's
     * latest non-deleted recording. Classification is not done here.
     */
    public function getTeamVisitPlanAccounts(int $level, int $requesterId, string $psgcCode, CarbonInterface $now, string $period, ?array $counselorIds = null)
    {
        return $this->teamEligibleQuery($level, $requesterId, $psgcCode, $now, $period)
            ->when($counselorIds !== null, fn($query) => $query->whereIn('accounts.assigned_cc_id', $counselorIds))
            ->get();
    }

    /**
     * The ids of the team's credit counselors that have something to show in [$from, $until):
     * eligible Visit Plan accounts or visited accounts. Sorted ascending; the unit of pagination.
     *
     * @return array<int, int>
     */
    public function getTeamCounselorIds(int $level, int $requesterId, string $psgcCode, CarbonInterface $now, string $period, CarbonInterface $from, CarbonInterface $until): array
    {
        $planned = $this->teamEligibleQuery($level, $requesterId, $psgcCode, $now, $period)
            ->reorder()
            ->select('accounts.assigned_cc_id')
            ->distinct()
            ->pluck('assigned_cc_id');

        $visited = $this->teamVisitedQuery($level, $requesterId, $psgcCode, $from, $until)
            ->select('accounts.assigned_cc_id')
            ->distinct()
            ->pluck('assigned_cc_id');

        return $planned->merge($visited)->map(fn($id) => (int) $id)->unique()->sort()->values()->all();
    }

    private function teamEligibleQuery(int $level, int $requesterId, string $psgcCode, CarbonInterface $now, string $period): EloquentBuilder
    {
        $monthly = $period === Constants::PERIOD_MONTHLY;

        $today = $now->copy()->startOfDay();
        $monthStart = $now->copy()->startOfMonth();

        $dueFrom = $monthStart->toDateString();
        $dueUntil = ($monthly ? $now->copy()->endOfMonth() : $today)->toDateString();

        $nextActionFrom = $today->copy()->subDays((int) config('visit_plan.next_action_carry_over_days'))->max($monthStart);
        $nextActionUntil = $monthly ? $monthStart->copy()->addMonth() : $now;
        $nextActionUntilOperator = $monthly ? '<' : '<=';

        return $this->teamAccountsQuery($level, $requesterId, $psgcCode)
            ->where(function ($query) use ($dueFrom, $dueUntil, $nextActionFrom, $nextActionUntil, $nextActionUntilOperator) {
                $query->where(function ($unvisited) use ($dueFrom, $dueUntil) {
                    $unvisited->whereNull('latest_recordings.id')
                        ->where('accounts.due_date', '>=', $dueFrom)
                        ->where('accounts.due_date', '<=', $dueUntil);
                })->orWhere(function ($scheduled) use ($nextActionFrom, $nextActionUntil, $nextActionUntilOperator) {
                    $scheduled->where('latest_recordings.next_action_date', '>=', $nextActionFrom)
                        ->where('latest_recordings.next_action_date', $nextActionUntilOperator, $nextActionUntil);
                });
            });
    }

    /**
     * The team's visited accounts in [$from, $until): accounts with a non-deleted contact recording
     * in that range, one row per recording, like visitedAccount but for the requester's team.
     */
    public function getTeamVisitedAccounts(int $level, int $requesterId, string $psgcCode, CarbonInterface $from, CarbonInterface $until, ?array $counselorIds = null)
    {
        return $this->teamVisitedQuery($level, $requesterId, $psgcCode, $from, $until)
            ->select([
                'accounts.id',
                'accounts.account_number',
                'accounts.customer_name',
                'accounts.monthly_amortization',
                'accounts.past_due_balance',
                'accounts.days_past_due',
                'accounts.dpd_bucket',
                'accounts.no_of_non_payments',
                'accounts.outstanding_balance',
                'accounts.last_payment_date',
                'accounts.asset',
                'accounts.payment_status',
                'accounts.is_force_prioritized',
                'accounts.assigned_cc_id',
                'accounts.assigned_ch_id',
                'accounts.assigned_am_ar_id',
                'accounts.assigned_rm__dh_id',
                'accounts.due_date',
                'accounts.psgc_code',
                'accounts.is_contacted',
                'cms_users.name as credit_counselor_name',
                'contact_recordings.id as contact_recording_id',
                'contact_recordings.account_id',
                'contact_recordings.follow_up_mode',
                'contact_recordings.reason_for_default',
                'contact_recordings.action_taken',
                'contact_recordings.current_category',
                'contact_recordings.remarks',
                'contact_recordings.geotagging',
                'contact_recordings.recorded_by',
                'contact_recordings.contact_date',
            ])
            ->when($counselorIds !== null, fn($query) => $query->whereIn('accounts.assigned_cc_id', $counselorIds))
            ->orderBy('accounts.id')
            ->get();
    }

    private function teamVisitedQuery(int $level, int $requesterId, string $psgcCode, CarbonInterface $from, CarbonInterface $until): EloquentBuilder
    {
        return Account::query()
            ->join('contact_recordings', function ($join) {
                $join->on('contact_recordings.account_id', '=', 'accounts.id')
                    ->whereNull('contact_recordings.deleted_at');
            })
            ->leftJoin('cms_users', 'cms_users.id', '=', 'accounts.assigned_cc_id')
            ->where('accounts.' . $this->teamColumn($level), $requesterId)
            ->where('accounts.psgc_code', $psgcCode)
            ->where('contact_recordings.contact_date', '>=', $from)
            ->where('contact_recordings.contact_date', '<', $until);
    }

    /**
     * Accounts of the requester's team in one PSGC code, one row each, left-joined to their latest
     * non-deleted contact recording (latest_recordings.*), address, contact details and credit
     * counselor name. The team is defined by the requester's level (a whitelisted column).
     */
    private function teamAccountsQuery(int $level, int $requesterId, string $psgcCode): EloquentBuilder
    {
        $teamColumn = 'accounts.' . $this->teamColumn($level);

        $scopedAccountIds = Account::query()
            ->select('accounts.id')
            ->where($teamColumn, $requesterId)
            ->where('accounts.psgc_code', $psgcCode);

        $latestContactRecordings = ContactRecording::query()
            ->select([
                'contact_recordings.id',
                'contact_recordings.account_id',
                'contact_recordings.current_category',
                'contact_recordings.follow_up_mode',
                'contact_recordings.reason_for_default',
                'contact_recordings.action_taken',
                'contact_recordings.next_action_plan',
                'contact_recordings.next_action_date',
                'contact_recordings.remarks',
                'contact_recordings.geotagging',
                'contact_recordings.recorded_by',
                'contact_recordings.contact_date',
                'contact_recordings.is_contacted',
            ])
            ->selectRaw('ROW_NUMBER() OVER (
                PARTITION BY contact_recordings.account_id
                ORDER BY contact_recordings.contact_date DESC, contact_recordings.id DESC
            ) AS recording_rank')
            ->whereIn('contact_recordings.account_id', $scopedAccountIds);

        return Account::select([
            'accounts.id',
            'accounts.account_number',
            'accounts.customer_name',
            'accounts.monthly_amortization',
            'accounts.past_due_balance',
            'accounts.days_past_due',
            'accounts.dpd_bucket',
            'accounts.no_of_non_payments',
            'accounts.outstanding_balance',
            'accounts.last_payment_date',
            'accounts.asset',
            'accounts.payment_status',
            'accounts.is_force_prioritized',
            'accounts.assigned_cc_id',
            'accounts.assigned_ch_id',
            'accounts.assigned_am_ar_id',
            'accounts.assigned_rm__dh_id',
            'accounts.due_date',
            'accounts.psgc_code',
            'accounts.is_contacted',
            'accounts.created_at',
            'cms_users.name as credit_counselor_name',
            'collecting_addresses.unit_lot_block',
            'collecting_addresses.street_name',
            'collecting_addresses.subdivision_village',
            'collecting_addresses.province',
            'collecting_addresses.postal_code',
            'collecting_addresses.barangay',
            'collecting_addresses.city_municipality',
            'collecting_addresses.region',
            'customer_details.contact_number',
            'customer_details.email',
            'customer_details.social_media_account',
            'latest_recordings.id as contact_recording_id',
            'latest_recordings.current_category',
            'latest_recordings.follow_up_mode',
            'latest_recordings.reason_for_default',
            'latest_recordings.action_taken',
            'latest_recordings.next_action_plan',
            'latest_recordings.next_action_date',
            'latest_recordings.remarks',
            'latest_recordings.geotagging',
            'latest_recordings.recorded_by',
            'latest_recordings.contact_date',
            'latest_recordings.is_contacted as contact_recording_is_contacted',
        ])
            ->leftJoinSub($latestContactRecordings, 'latest_recordings', function ($join) {
                $join->on('latest_recordings.account_id', '=', 'accounts.id')
                    ->where('latest_recordings.recording_rank', '=', 1);
            })
            ->leftJoin('cms_users', 'cms_users.id', '=', 'accounts.assigned_cc_id')
            ->leftJoin('collecting_addresses', function ($join) {
                $join->on('collecting_addresses.account_id', '=', 'accounts.id')
                    ->whereNull('collecting_addresses.deleted_at');
            })
            ->leftJoin('customer_details', function ($join) {
                $join->on('customer_details.account_id', '=', 'accounts.id')
                    ->whereNull('customer_details.deleted_at');
            })
            ->where($teamColumn, $requesterId)
            ->where('accounts.psgc_code', $psgcCode)
            ->orderBy('accounts.id');
    }

    /** The accounts column that ties an account to the requester's team: 2 = CH, 3 = AM/AR, 4 = RM/DH. */
    private function teamColumn(int $level): string
    {
        return match ($level) {
            2 => 'assigned_ch_id',
            3 => 'assigned_am_ar_id',
            4 => 'assigned_rm__dh_id',
            default => throw new \InvalidArgumentException("Level {$level} has no team."),
        };
    }

    // public function getRepossessionRequests(int $userId, string $psgc_code, string $period)
    // {
    //     if ($period === Constants::PERIOD_DAILY) {
    //         $startDate = Carbon::today()->addDay();
    //         $contactRecording = ContactRecordingQuery::contactRecordedDaily(
    //             $startDate
    //         );


    //         return Account::leftJoinSub(
    //             $contactRecording,
    //             'contact_recordings',
    //             'accounts.id',
    //             '=',
    //             'contact_recordings.account_id'
    //         )
    //             ->join(
    //                 'cms_users',
    //                 'accounts.assigned_cc_id',
    //                 '=',
    //                 'cms_users.id'
    //             )
    //             ->join(
    //                 'repossession_requests',
    //                 'contact_recordings.contact_recording_id',
    //                 '=',
    //                 'repossession_requests.contact_recording_id'
    //             )
    //             ->select([
    //                 'cms_users.name AS credit_counselor_name',
    //                 'accounts.customer_name',
    //                 'accounts.account_number',
    //                 'accounts.days_past_due',
    //                 'accounts.monthly_amortization',
    //                 'contact_recordings.action_taken',
    //                 'contact_recordings.next_action_plan',
    //                 'contact_recordings.contact_date',
    //                 'repossession_requests.status',
    //                 'contact_recordings.remarks',
    //                 'repossession_requests.remarks AS remark_by_ch'
    //             ])
    //             ->where('accounts.assigned_ch_id', $userId)
    //             ->where('accounts.psgc_code', $psgc_code)
    //             ->where('accounts.created_at', '<', $startDate)
    //             ->orderBy('accounts.id', 'asc')
    //             ->orderBy('accounts.created_at', 'desc')
    //             ->get();
    //     } else {
    //         $startDate = Carbon::now()->startOfMonth();
    //         $endDate = Carbon::now()->addMonth()->startOfMonth();

    //         $contactRecording = ContactRecordingQuery::contactRecordedMonthly(
    //             $startDate,
    //             $endDate
    //         );

    //         return Account::leftJoinSub(
    //             $contactRecording,
    //             'contact_recordings',
    //             'accounts.id',
    //             '=',
    //             'contact_recordings.account_id'
    //         )
    //             ->join(
    //                 'cms_users',
    //                 'accounts.assigned_cc_id',
    //                 '=',
    //                 'cms_users.id'
    //             )
    //             ->join(
    //                 'repossession_requests',
    //                 'contact_recordings.contact_recording_id',
    //                 '=',
    //                 'repossession_requests.contact_recording_id'
    //             )
    //             ->select([
    //                 'cms_users.name AS credit_counselor_name',
    //                 'accounts.customer_name',
    //                 'accounts.account_number',
    //                 'accounts.days_past_due',
    //                 'accounts.monthly_amortization',
    //                 'contact_recordings.action_taken',
    //                 'contact_recordings.next_action_plan',
    //                 'contact_recordings.contact_date',
    //                 'repossession_requests.status',
    //                 'contact_recordings.remarks',
    //                 'repossession_requests.remarks AS remark_by_ch'
    //             ])
    //             ->where('accounts.assigned_ch_id', $userId)
    //             ->where('accounts.psgc_code', $psgc_code)
    //             ->where('accounts.created_at', '>=', $startDate)
    //             ->where('accounts.created_at', '<', $endDate)
    //             ->orderBy('accounts.id', 'asc')
    //             ->orderBy('accounts.created_at', 'desc')
    //             ->get();
    //     }
    // }
    // public function getPaymentCollected(int $userId, string $psgc_code, string $period)
    // {
    //     if ($period === Constants::PERIOD_DAILY) {
    //         $startDate = Carbon::today()->addDay();

    //         $contactRecording = ContactRecordingQuery::contactRecordedDaily(
    //             $startDate
    //         );

    //         return Account::leftJoinSub(
    //             $contactRecording,
    //             'contact_recordings',
    //             'accounts.id',
    //             '=',
    //             'contact_recordings.account_id'
    //         )
    //             ->join(
    //                 'cms_users',
    //                 'accounts.assigned_cc_id',
    //                 '=',
    //                 'cms_users.id'
    //             )
    //             ->leftJoin(
    //                 'receipt_encodings',
    //                 'contact_recordings.contact_recording_id',
    //                 '=',
    //                 'receipt_encodings.contact_recording_id'
    //             )
    //             ->leftJoin(
    //                 'deposit_encodings',
    //                 'receipt_encodings.collection_id',
    //                 '=',
    //                 'deposit_encodings.collection_id'
    //             )
    //             ->select([
    //                 'cms_users.id as user_id',
    //                 'cms_users.name AS  credit_counselor_name',
    //                 'accounts.customer_name',
    //                 'accounts.account_number',
    //                 'accounts.days_past_due',
    //                 'accounts.monthly_amortization',
    //                 'accounts.due_date',
    //                 'contact_recordings.action_taken',
    //                 'contact_recordings.next_action_plan',
    //                 'contact_recordings.contact_date',
    //                 'contact_recordings.remarks',
    //                 'receipt_encodings.id AS receipt_encodings_id',
    //                 'receipt_encodings.ar_number',
    //                 'receipt_encodings.ar_date',
    //                 'receipt_encodings.amount AS receipt_amount',
    //                 'receipt_encodings.receipt_image_path',
    //                 'receipt_encodings.collection_id',
    //                 'deposit_encodings.deposit_amount',
    //                 'deposit_encodings.status AS deposit_status'
    //             ])
    //             ->where('accounts.assigned_ch_id', $userId)
    //             ->where('accounts.psgc_code', $psgc_code)
    //             ->where('accounts.created_at', '<', $startDate)
    //             ->orderBy('cms_users.id', 'desc')
    //             ->orderBy('receipt_encodings.id', 'desc')
    //             ->get();
    //     } else {
    //         $startDate = Carbon::now()->startOfMonth();
    //         $endDate = Carbon::now()->addMonth()->startOfMonth();

    //         $contactRecording = ContactRecordingQuery::contactRecordedMonthly(
    //             $startDate,
    //             $endDate
    //         );

    //         return Account::leftJoinSub(
    //             $contactRecording,
    //             'contact_recordings',
    //             'accounts.id',
    //             '=',
    //             'contact_recordings.account_id'
    //         )
    //             ->join(
    //                 'cms_users',
    //                 'accounts.assigned_cc_id',
    //                 '=',
    //                 'cms_users.id'
    //             )
    //             ->leftJoin(
    //                 'receipt_encodings',
    //                 'contact_recordings.contact_recording_id',
    //                 '=',
    //                 'receipt_encodings.contact_recording_id'
    //             )
    //             ->leftJoin(
    //                 'deposit_encodings',
    //                 'receipt_encodings.collection_id',
    //                 '=',
    //                 'deposit_encodings.collection_id'
    //             )
    //             ->select([
    //                 'cms_users.id as user_id',
    //                 'cms_users.name AS  credit_counselor_name',
    //                 'accounts.customer_name',
    //                 'accounts.account_number',
    //                 'accounts.days_past_due',
    //                 'accounts.monthly_amortization',
    //                 'accounts.due_date',
    //                 'contact_recordings.action_taken',
    //                 'contact_recordings.next_action_plan',
    //                 'contact_recordings.contact_date',
    //                 'contact_recordings.remarks',
    //                 'receipt_encodings.id AS receipt_encodings_id',
    //                 'receipt_encodings.ar_number',
    //                 'receipt_encodings.ar_date',
    //                 'receipt_encodings.amount AS receipt_amount',
    //                 'receipt_encodings.receipt_image_path',
    //                 'receipt_encodings.collection_id',
    //                 'deposit_encodings.deposit_amount',
    //                 'deposit_encodings.status AS deposit_status'
    //             ])
    //             ->where('accounts.assigned_ch_id', $userId)
    //             ->where('accounts.psgc_code', $psgc_code)
    //             ->where('accounts.created_at', '>=', $startDate)
    //             ->where('accounts.created_at', '<', $endDate)
    //             ->orderBy('cms_users.id', 'desc')
    //             ->orderBy('receipt_encodings.id', 'desc')
    //             ->get();
    //     }
    // }
    // public function getFecAccounts(string $psgc_code, string $period)
    // {
    //     if ($period === Constants::PERIOD_DAILY) {
    //         $startDate = Carbon::today()->addDay();

    //         return FecAccount::join(
    //             'cms_users',
    //             'fec_accounts.assigned_cc_id',
    //             '=',
    //             'cms_users.id'
    //         )
    //             ->leftJoin(
    //                 'fec_customer_evaluations',
    //                 'fec_accounts.id',
    //                 '=',
    //                 'fec_customer_evaluations.fec_accounts_id'
    //             )
    //             ->select([
    //                 'cms_users.id AS user_id',
    //                 'cms_users.name AS  credit_counselor_name',
    //                 'fec_accounts.account_number',
    //                 'fec_accounts.customer_name',
    //                 'fec_customer_evaluations.table_a_section_1',
    //                 'fec_customer_evaluations.table_a_section_2',
    //                 'fec_customer_evaluations.table_a_section_3',
    //                 'fec_customer_evaluations.table_a_section_4',
    //                 'fec_customer_evaluations.table_b_section_5',
    //                 'fec_customer_evaluations.table_b_section_6',
    //                 'fec_customer_evaluations.due_date',
    //                 'fec_customer_evaluations.submit_date',
    //                 'fec_customer_evaluations.remarks',
    //                 'fec_customer_evaluations.status'
    //             ])
    //             ->where('fec_accounts.psgc_code', $psgc_code)
    //             ->where('fec_accounts.created_at', '<', $startDate)
    //             ->get();
    //     } else {
    //         $startDate = Carbon::now()->startOfMonth();
    //         $endDate = Carbon::now()->addMonth()->startOfMonth();

    //         return FecAccount::join(
    //             'cms_users',
    //             'fec_accounts.assigned_cc_id',
    //             '=',
    //             'cms_users.id'
    //         )
    //             ->leftJoin(
    //                 'fec_customer_evaluations',
    //                 'fec_accounts.id',
    //                 '=',
    //                 'fec_customer_evaluations.fec_accounts_id'
    //             )
    //             ->select([
    //                 'cms_users.id AS user_id',
    //                 'cms_users.name AS  credit_counselor_name',
    //                 'fec_accounts.account_number',
    //                 'fec_accounts.customer_name',
    //                 'fec_customer_evaluations.table_a_section_1',
    //                 'fec_customer_evaluations.table_a_section_2',
    //                 'fec_customer_evaluations.table_a_section_3',
    //                 'fec_customer_evaluations.table_a_section_4',
    //                 'fec_customer_evaluations.table_b_section_5',
    //                 'fec_customer_evaluations.table_b_section_6',
    //                 'fec_customer_evaluations.due_date',
    //                 'fec_customer_evaluations.submit_date',
    //                 'fec_customer_evaluations.remarks',
    //                 'fec_customer_evaluations.status'
    //             ])
    //             ->where('fec_accounts.psgc_code', $psgc_code)
    //             ->where('fec_accounts.created_at', '>=', $startDate)
    //             ->where('fec_accounts.created_at', '<', $endDate)
    //             ->get();
    //     }
    // }
}
