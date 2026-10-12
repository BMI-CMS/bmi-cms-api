<?php

namespace App\Services;


use App\Repositories\DashboardRepository;
use App\Constants\Constants;
use App\Helpers\AllocateAccountsHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardService
{
    public function __construct(
        protected DashboardRepository $dashboardRepository,
        protected SettingService $settingService
    ) {}

    public function getSummary(int $userId)
    {
        $getAccountPerformance = $this->dashboardRepository->getAccountPerformance($userId);

        return [
            "account_performance" =>  [
                "total_accounts" => $getAccountPerformance?->total_accounts ?? 0,
                "paid" => $getAccountPerformance?->total_paid_accounts ?? 0,
                "repossessed" => $getAccountPerformance?->total_repossessed_accounts ?? 0,
                "restructured" => $getAccountPerformance?->total_restructured_accounts ?? 0,
            ],
            "collection_target" => [
                "target_due" => $getAccountPerformance?->total_past_due_amount ?? 0,
                "paid" => $getAccountPerformance?->total_receipt_encode_amount ?? 0,
                "repossessed" => $getAccountPerformance?->total_repossessed_amount ?? 0,
                "restructured" => $getAccountPerformance?->total_restructured_amount ?? 0,
            ],
            "accounts" => $getAccountPerformance?->total_accounts ?? 0,
            "fec_accounts" => $getAccountPerformance?->total_accounts ?? 0,
            "collections" => $getAccountPerformance?->total_paid_accounts ?? 0,
        ];
    }

    public function getAssignedAccounts(int $userId, string $period)
    {
        if ($period === Constants::PERIOD_DAILY) {
            $getAccounts = $this->dashboardRepository->getDailyAccounts($userId);
        } else {
            $getAccounts = $this->dashboardRepository->getMonthlyAccounts($userId);
        }

        $accounts = [];
        foreach ($getAccounts['for_visit'] as $item) {
            $accountId = $item->id;

            if (!isset($accounts[$accountId])) {
                $accounts[$accountId] = [
                    'accounts_id' => $item->id,
                    'account_number' => $item->account_number,
                    'customer_name' => $item->customer_name,
                    'monthly_amortization' => $item->monthly_amortization,
                    'past_due_balance' => $item->past_due_balance,
                    'days_past_due' => $item->days_past_due,
                    'dpd_bucket' => $item->dpd_bucket,
                    'no_of_non_payments' => $item->no_of_non_payments,
                    'outstanding_balance' => $item->outstanding_balance,
                    'last_payment_date' => $item->last_payment_date,
                    'asset' => $item->asset,
                    'payment_status' => $item->payment_status,
                    'is_force_prioritized' => $item->is_force_prioritized,
                    'psgc_code' => $item->psgc_code,
                    'is_contacted' => $item->is_contacted,
                    'visit_date' => $item->visit_date,
                    'assigned_cc_id' => $item->assigned_cc_id,
                    'assigned_ch_id' => $item->assigned_ch_id,
                    'assigned_am_id' => $item->assigned_am_id,
                    'assigned_dh_id' => $item->assigned_dh_id,
                    'unit_lot_block' => $item->unit_lot_block,
                    'street_name' => $item->street_name,
                    'subdivision_village' => $item->subdivision_village,
                    'province' => $item->province,
                    'postal_code' => $item->postal_code,
                    'barangay' => $item->barangay,
                    'city_municipality' => $item->city_municipality,
                    'region' => $item->region,
                    'contact_number' => $item->contact_number,
                    'email' => $item->email,
                    'social_media_account' => $item->social_media_account,
                    'next_action_date' => $item->next_action_date,
                    'next_action_plan' => $item->next_action_plan,
                    'activity_records' => [],
                ];
            }

            if ($item->contact_recording_id) {
                $accounts[$accountId]['activity_records'][] = [
                    'current_category' => $item->current_category,
                    'follow_up_mode' => $item->follow_up_mode,
                    'reason_for_default' => $item->reason_for_default,
                    'action_taken' => $item->action_taken,
                    'next_action_plan' => $item->next_action_plan,
                    'remarks' => $item->remarks,
                    'geotagging' => $item->geotagging,
                    'recorded_by' => $item->recorded_by,
                    'contact_date' => $item->contact_date,
                ];
            }
        }

        $accountAllocation = $this->settingService->classify($accounts);

        return [...$accountAllocation, "visited" => $getAccounts['visited']];
    }

    /**
     * The monthly Visit Plan accounts of the credit counselor (the same accounts as assigned-accounts
     * for the monthly period), grouped by "psgc_code - city_municipality". Each account carries its
     * rules-table classification, or null when no rule classifies it (it is kept, not dropped).
     */
    public function assignedAccountsByPSGC(int $userId)
    {
        $getAccounts = $this->dashboardRepository->getVisitPlanAccounts($userId, Carbon::now(), Constants::PERIOD_MONTHLY);

        $accountsByPSGC = [];
        $unclassified = [];

        // One row per account, with its latest contact recording.
        foreach ($getAccounts as $item) {
            $account = [
                'accounts_id' => $item->id,
                'account_number' => $item->account_number,
                'customer_name' => $item->customer_name,
                'monthly_amortization' => $item->monthly_amortization,
                'past_due_balance' => $item->past_due_balance,
                'days_past_due' => $item->days_past_due,
                'dpd_bucket' => $item->dpd_bucket,
                'no_of_non_payments' => $item->no_of_non_payments,
                'outstanding_balance' => $item->outstanding_balance,
                'last_payment_date' => $item->last_payment_date,
                'asset' => $item->asset,
                'payment_status' => $item->payment_status,
                'is_force_prioritized' => $item->is_force_prioritized,
                'psgc_code' => $item->psgc_code,
                'is_contacted' => $item->is_contacted,
                'visit_date' => $item->visit_date,
                'assigned_cc_id' => $item->assigned_cc_id,
                'assigned_ch_id' => $item->assigned_ch_id,
                'assigned_am_id' => $item->assigned_am_ar_id,
                'assigned_dh_id' => $item->assigned_rm__dh_id,
                'unit_lot_block' => $item->unit_lot_block,
                'street_name' => $item->street_name,
                'subdivision_village' => $item->subdivision_village,
                'province' => $item->province,
                'postal_code' => $item->postal_code,
                'barangay' => $item->barangay,
                'city_municipality' => $item->city_municipality,
                'region' => $item->region,
                'contact_number' => $item->contact_number,
                'email' => $item->email,
                'social_media_account' => $item->social_media_account,
                'next_action_date' => $item->next_action_date,
                'next_action_plan' => $item->next_action_plan,
                'activity_records' => [],
            ];

            if ($item->contact_recording_id) {
                $account['activity_records'][] = [
                    'current_category' => $item->current_category,
                    'follow_up_mode' => $item->follow_up_mode,
                    'reason_for_default' => $item->reason_for_default,
                    'action_taken' => $item->action_taken,
                    'next_action_plan' => $item->next_action_plan,
                    'remarks' => $item->remarks,
                    'geotagging' => $item->geotagging,
                    'recorded_by' => $item->recorded_by,
                    'contact_date' => $item->contact_date,
                ];
            }

            $account['account_classification'] = $this->settingService->classifyAccount($account);

            if ($account['account_classification'] === null) {
                $unclassified[] = $account['account_number'];
            }

            $accountsByPSGC[$item->psgc_code . ' - ' . $item->city_municipality][] = $account;
        }

        if ($unclassified !== []) {
            Log::warning('Visit Plan accounts could not be classified: no active classification rule matches them.', [
                'count' => count($unclassified),
                'account_numbers' => array_slice($unclassified, 0, 50),
            ]);
        }

        return $accountsByPSGC;
    }

    //Higher ups
    public function teamAssignedAccounts(int $level, int $userId, string $psgc_code, string $period, int $page = 1, int $perPage = Constants::TEAM_ACCOUNTS_PER_PAGE)
    {
        $now = Carbon::now();

        $from = $period === Constants::PERIOD_DAILY ? $now->copy()->startOfDay() : $now->copy()->startOfMonth();
        $until = $period === Constants::PERIOD_DAILY ? $from->copy()->addDay() : $from->copy()->addMonth();

        // Pagination is by credit counselor, so each entry keeps all of its accounts and a correct total_accounts.
        $counselorIds = $this->dashboardRepository->getTeamCounselorIds($level, $userId, $psgc_code, $now, $period, $from, $until);
        $pageIds = array_slice($counselorIds, ($page - 1) * $perPage, $perPage);

        $visitPlanRows = $this->dashboardRepository->getTeamVisitPlanAccounts($level, $userId, $psgc_code, $now, $period, $pageIds);
        $visitedRows = $this->dashboardRepository->getTeamVisitedAccounts($level, $userId, $psgc_code, $from, $until, $pageIds);

        $counselors = [];
        foreach ($visitPlanRows as $item) {
            $counselors[$item->assigned_cc_id]['name'] = $item->credit_counselor_name;
            $counselors[$item->assigned_cc_id]['accounts'][$item->id] = $this->teamAccountEntry($item);
        }

        $visited = [];
        foreach ($visitedRows as $item) {
            $counselors[$item->assigned_cc_id]['name'] ??= $item->credit_counselor_name;
            $visited[$item->assigned_cc_id][] = $item;
        }

        $allocatedAccounts = [];
        foreach ($pageIds as $counselorId) {
            $counselor = $counselors[$counselorId];
            $classified = $this->settingService->classify($counselor['accounts'] ?? []);

            $allocatedAccounts[] = [
                "credit_counselor_name" => $counselor['name'],
                "total_accounts" => $this->countClassified($classified),
                "accounts" => [...$classified, "visited" => $visited[$counselorId] ?? []],
            ];
        }

        return [
            "repossession_request" => 5,
            "visit_plan" => $allocatedAccounts,
            "fec_approval" =>  3,
            "collections" => 5,
            "pagination" => [
                "current_page" => $page,
                "per_page" => $perPage,
                "total" => count($counselorIds),
                "last_page" => max(1, (int) ceil(count($counselorIds) / $perPage)),
            ],
        ];
    }

    private function teamAccountEntry($item): array
    {
        $account = [
            'accounts_id' => $item->id,
            'account_number' => $item->account_number,
            'customer_name' => $item->customer_name,
            'monthly_amortization' => $item->monthly_amortization,
            'past_due_balance' => $item->past_due_balance,
            'days_past_due' => $item->days_past_due,
            'dpd_bucket' => $item->dpd_bucket,
            'no_of_non_payments' => $item->no_of_non_payments,
            'outstanding_balance' => $item->outstanding_balance,
            'last_payment_date' => $item->last_payment_date,
            'asset' => $item->asset,
            'payment_status' => $item->payment_status,
            'is_force_prioritized' => $item->is_force_prioritized,
            'psgc_code' => $item->psgc_code,
            'is_contacted' => $item->is_contacted,
            'visit_date' => $item->visit_date,
            'assigned_cc_id' => $item->assigned_cc_id,
            'assigned_ch_id' => $item->assigned_ch_id,
            'assigned_am_id' => $item->assigned_am_ar_id,
            'assigned_dh_id' => $item->assigned_rm__dh_id,
            'unit_lot_block' => $item->unit_lot_block,
            'street_name' => $item->street_name,
            'subdivision_village' => $item->subdivision_village,
            'province' => $item->province,
            'postal_code' => $item->postal_code,
            'barangay' => $item->barangay,
            'city_municipality' => $item->city_municipality,
            'region' => $item->region,
            'contact_number' => $item->contact_number,
            'email' => $item->email,
            'social_media_account' => $item->social_media_account,
            'next_action_date' => $item->next_action_date,
            'next_action_plan' => $item->next_action_plan,
            'activity_records' => [],
        ];

        if ($item->contact_recording_id) {
            $account['activity_records'][] = [
                'current_category' => $item->current_category,
                'follow_up_mode' => $item->follow_up_mode,
                'reason_for_default' => $item->reason_for_default,
                'action_taken' => $item->action_taken,
                'next_action_plan' => $item->next_action_plan,
                'remarks' => $item->remarks,
                'geotagging' => $item->geotagging,
                'recorded_by' => $item->recorded_by,
                'contact_date' => $item->contact_date,
            ];
        }

        return $account;
    }

    private function countClassified(array $node): int
    {
        if (array_is_list($node)) {
            return count($node);
        }

        return array_sum(array_map(fn (array $child) => $this->countClassified($child), $node));
    }

    public function teamAssignedAccountsPSGC(int $userId, string $psgc_code, string $period)
    {
        if ($period === Constants::PERIOD_DAILY) {
            $getTeamAccounts = $this->dashboardRepository->getDailyTeamAccounts($userId, $psgc_code, $period);
        } else {
            $getTeamAccounts = $this->dashboardRepository->getMonthlyTeamAccounts($userId, $psgc_code, $period);
        }

        $accounts = [];
        foreach ($getTeamAccounts as $item) {
            $psgc_code = "$item->psgc_code - $item->city_municipality";
            $accountId = $item->account_id;
            $name = $item->credit_counselor_name;

            if (!isset($accounts[$psgc_code][$name][$accountId])) {
                $accounts[$psgc_code][$name][$accountId] = [
                    ...$item->toArray(),
                    'activity_records' => [],
                ];
            }

            if ($item->contact_recording_id) {
                $accounts[$psgc_code][$name][$accountId]['activity_records'][] = [
                    'follow_up_mode' => $item->follow_up_mode,
                    'reason_for_default' => $item->reason_for_default,
                    'action_taken' => $item->action_taken,
                    'next_action_plan' => $item->next_action_plan,
                    'remarks' => $item->remarks,
                    'geotagging' => $item->geotagging,
                    'recorded_by' => $item->recorded_by,
                    'contact_date' => $item->contact_date,
                ];
            };
        }

        $allocatedAccounts = [];
        foreach ($accounts as $groupKey => $counselors) {
            foreach ($counselors as $counselorName => $items) {
                $classifiedAccounts = AllocateAccountsHelper::accountsClassification(array_values($items));
                if (count($classifiedAccounts) > 0) {
                    $allocatedAccounts[$groupKey][] = [
                        'credit_counselor_name' => $counselorName,
                        'total_accounts' => count($classifiedAccounts),
                        'accounts' => $classifiedAccounts

                    ];
                }
            }
        }

        return [
            "repossession_request" => 5,
            "visit_plan" => $allocatedAccounts,
            "fec_approval" =>  3,
            "collections" => 5,
        ];
        // $getRepossessionRequests = $this->dashboardRepository->getRepossessionRequests($userId, $psgc_code, $period);
        // $getPaymentCollected = $this->dashboardRepository->getPaymentCollected($userId, $psgc_code, $period);

        // $totalCollected = 0;
        // $totalDeposited = 0;
        // $collectedAccounts = [];

        // foreach ($getPaymentCollected as $account) {
        //     $userId = $account->user_id;

        //     if (!isset($collectedAccounts[$userId])) {
        //         $collectedAccounts[$userId] = [
        //             'credit_counselor_name' => $account->credit_counselor_name,
        //             'total_amount_collected' => 0,
        //             'receipt_count' => 0,
        //             'collected_accounts' => [],
        //         ];

        //         if ($account->deposit_status === 'AUTHORIZED') {
        //             $totalDeposited += (float) $account->deposit_amount;
        //         }
        //     }

        //     if (!$account->receipt_encodings_id) {
        //         continue;
        //     }

        //     $receiptAmount = (float) $account->receipt_amount;

        //     $totalCollected += $receiptAmount;

        //     $collectedAccounts[$userId]['total_amount_collected'] += $receiptAmount;
        //     $collectedAccounts[$userId]['receipt_count']++;
        //     $collectedAccounts[$userId]['collected_accounts'][] = $account;
        // }

        // $collectedPayments = [
        //     'total_collected' => $totalCollected,
        //     'total_deposited' => $totalDeposited,
        //     'collected_accounts' => array_values($collectedAccounts),
        // ];

        // $getFecAccounts = $this->dashboardRepository->getFecAccounts($psgc_code, $period);

    }
}
