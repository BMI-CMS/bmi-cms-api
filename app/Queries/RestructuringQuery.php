<?php

namespace App\Queries;

use App\Models\Restructuring;

class RestructuringQuery
{
    public static function forDateRange(string  $startDate, string  $endDate)
    {
        return Restructuring::select([
            'account_id',
            'new_monthly_amortization',
        ])
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate);
    }
}
