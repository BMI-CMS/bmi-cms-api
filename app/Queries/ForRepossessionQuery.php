<?php

namespace App\Queries;

use App\Models\ForRepossession;

class ForRepossessionQuery
{
    public static function withReceiptAmount($startDate, $endDate)
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
            ->where('receipt_encodings.created_at', '>=', $startDate)
            ->where('receipt_encodings.created_at', '<', $endDate);
    }
}
