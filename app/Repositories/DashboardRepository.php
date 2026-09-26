<?php

namespace App\Repositories;

use App\Models\Account;
use App\Queries\ReceiptEncodingQuery;
use App\Queries\RestructuringQuery;
use App\Queries\ForRepossessionQuery;
use Carbon\Carbon;

class DashboardRepository
{
    public function getAccountPerformance(int $userId)
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $receiptEncodings = ReceiptEncodingQuery::forDateRange(
            $startDate,
            $endDate
        );

        $restructurings = RestructuringQuery::forDateRange(
            $startDate,
            $endDate
        );

        $forRepossessions = ForRepossessionQuery::withReceiptAmount(
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
            ->select([
                'accounts.id',
                'accounts.account_number',
                'accounts.customer_name',
                'accounts.past_due_balance',
                'receipt_encodings.account_id as receipt_encodings_id',
                'receipt_encodings.amount as receipt_encode_amount',
                'restructurings.account_id as restructurings_id',
                'restructurings.new_monthly_amortization',
                'for_repossessions.account_id as for_repossessions_id',
                'for_repossessions.amount as repossessed_amount',
            ])
            ->where('accounts.assigned_cc_id', $userId)
            ->where('accounts.created_at', '>=', $startDate)
            ->where('accounts.created_at', '<', $endDate)
            ->get();
    }
}
