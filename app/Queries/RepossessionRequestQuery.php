<?php

namespace App\Queries;

use App\Models\RepossessionRequest;

class RepossessionRequestQuery
{
    public static function repossesionRequest(string  $startDate,  string $endDate)
    {
        return RepossessionRequest::select([
            'contact_recording_id',
            'customer_capacity',
            'unit_condition',
            'customer_character',
            'is_first_level_approved',
            'first_level_approved_by',
            'first_level_review_at',
            'first_level_approved_remarks',
            'is_second_level_approved',
            'second_level_approved_by',
            'second_level_review_at',
            'second_level_approved_remarks',
            'is_third_level_approved',
            'third_level_approved_by',
            'third_level_review_at',
            'third_level_approved_remarks'
        ]);
        // ->where('created_at', '>=', $startDate)
        // ->where('created_at', '<', $endDate);
    }
}
