<?php

namespace App\Queries;

use App\Models\ContactInformation;

class ContactInformationQuery
{
    public static function contactInformation(string  $startDate)
    {
        return ContactInformation::select([
            'id',
            'account_id',
            'contact_number',
            'email',
            'social_media_account'
        ])
            ->where('next_action_date', '<', $startDate);
    }
}
