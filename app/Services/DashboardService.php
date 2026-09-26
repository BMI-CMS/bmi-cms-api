<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Account;
use App\Repositories\DashboardRepository;

class DashboardService
{
    public function __construct(
        protected DashboardRepository $dashboardRepository
    ) {}
    public function getSummary(int $userId)
    {
        $getAccountPerformance = $this->dashboardRepository->getAccountPerformance($userId);

        $paidCount = 0;
        $repossessedCount = 0;
        $restructuredCount = 0;
        $totalTargetDue = 0;
        $totalPaid = 0;
        $totalRepossessed = 0;
        $totalRestructured = 0;

        foreach ($getAccountPerformance as $item) {
            if ($item->receipt_encodings_id) {
                $paidCount++;
                $totalPaid +=  $item->receipt_encode_amount;
            };

            if ($item->for_repossessions_id) {
                $repossessedCount++;
                $totalRepossessed +=  $item->repossessed_amount;
            };

            if ($item->restructurings_id) {
                $restructuredCount++;
                $totalRestructured +=  $item->new_monthly_amortization;
            };

            $totalTargetDue +=  $item->past_due_balance;
        }

        return [
            "account_performance" =>  [
                "total_accounts" => $getAccountPerformance->count(),
                "paid" => $paidCount,
                "repossessed" => $repossessedCount,
                "restructured" => $restructuredCount
            ],
            "collection_target" => [
                "target_due" => $totalTargetDue,
                "paid" => $totalPaid,
                "repossessed" => $totalRepossessed,
                "restructured" => $totalRestructured
            ],
            "accounts" => $getAccountPerformance->count(),
            "fec_accounts" => $getAccountPerformance->count(),
            "collections" => $paidCount
        ];
    }
}
