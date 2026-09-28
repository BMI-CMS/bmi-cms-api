<?php

namespace App\Queries;

use App\Models\ForRepossession;

class ForRepossessionQuery
{
    public static function forRepossessed(string  $startDate, string  $endDate)
    {
        return ForRepossession::leftJoin(
            'receipt_encodings',
            'for_repossessions.id',
            '=',
            'receipt_encodings.for_repossession_id'
        )
            ->select([
                'for_repossessions.account_id',
                'receipt_encodings.amount',
            ])
            ->where('for_repossessions.status', 1)
            ->where('receipt_encodings.created_at', '>=', $startDate)
            ->where('receipt_encodings.created_at', '<', $endDate);
    }
}
