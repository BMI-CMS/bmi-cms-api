<?php

namespace App\Queries;

use App\Models\ReceiptEncoding;

class ReceiptEncodingQuery
{
    public static function forDateRange(string  $startDate,  string $endDate)
    {
        return ReceiptEncoding::select([
            'account_id',
            'amount',
        ])
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<', $endDate);
    }
}
