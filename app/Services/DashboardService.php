<?php

namespace App\Services;

use Carbon\Carbon;
use App\Repositories\DashboardRepository;
use App\Constants\Constants;
use App\Helpers\AllocateAccountsHelper;

class DashboardService
{
    public function __construct(
        protected DashboardRepository $dashboardRepository
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
                "repossessed" => $getAccountPerformance?->total_restructured_amount ?? 0,
                "restructured" => $getAccountPerformance?->total_repossessed_amount ?? 0,
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
        foreach ($getAccounts as $item) {
            $accountId = $item->id;

            if (!isset($accounts[$accountId])) {
                $accounts[$accountId] = [
                    'accounts_id' => $item->id,
                    'account_number' => $item->account_number,
                    'customer_name' => $item->customer_name,
                    'monthly_amortization' => $item->monthly_amortization,
                    'non_starter_payment_id' => $item->non_starter_payment_id,
                    'total_payment' => $item->total_payment,
                    'shortfall_amount' => $item->shortfall_amount,
                    'past_due_balance' => $item->past_due_balance,
                    'days_past_due' => $item->days_past_due,
                    'dpd_bucket' => $item->dpd_bucket,
                    'no_of_non_payments' => $item->no_of_non_payments,
                    'outstanding_balance' => $item->outstanding_balance,
                    'last_payment_date' => $item->last_payment_date,
                    'asset' => $item->asset,
                    'is_force_prioritized' => $item->is_force_prioritized,
                    'psgc_code' => $item->psgc_code,
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

        $accountAllocation = AllocateAccountsHelper::accountsClassification($accounts);
        return $accountAllocation;
    }

    public function assignedAccountsByPSGC(int $userId)
    {
        $getAccounts = $this->dashboardRepository->getMonthlyAccounts($userId);

        $accountsByPSGC = [];

        foreach ($getAccounts as $item) {
            $groupKey = $item->psgc_code . ' - ' . $item->city_municipality;
            $accountId = $item->id;

            if (!isset($accountsByPSGC[$groupKey][$accountId])) {
                $accountsByPSGC[$groupKey][$accountId] = [
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
                    'psgc_code' => $item->psgc_code,
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
                    'account_classification' => AllocateAccountsHelper::accountsClassificationPSGC($item),
                    'activity_records' => [],
                ];
            }

            if ($item->contact_recording_id) {
                $accountsByPSGC[$groupKey][$accountId]['activity_records'][] = [
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

        foreach ($accountsByPSGC as $groupKey => $item) {
            $accountsByPSGC[$groupKey] = array_values($item);
        }

        return $accountsByPSGC;
    }
}
