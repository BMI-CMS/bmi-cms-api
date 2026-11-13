<?php

namespace App\Queries;

use App\Models\Repossession;

class ForRepossessionQuery
{
    public static function forRepossessed(string  $startDate, string  $endDate)
    {
        return Repossession::leftJoin(
            'receipt_encodings',
            'repossessions.id',
            '=',
            'receipt_encodings.repossession_id'
        )
            ->select([
                'repossessions.account_id',
                'receipt_encodings.amount',
            ])
            ->where('repossessions.status', 1)
            ->where('receipt_encodings.created_at', '>=', $startDate)
            ->where('receipt_encodings.created_at', '<', $endDate);
    }
}
