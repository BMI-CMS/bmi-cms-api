<?php

namespace App\Constants;

class NextActionDateRules
{
    public const EXCLUDED_ACTION_TAKEN = [
        'FOR SERVICE OF COLLECTION NOTICE' => true,
        'FOR SERVICE OF ATTORNEY LETTER' => true,
        'ENDORSED TO RCD' => true,
        'ENDORSED TO LEGAL' => true,
        'ENDORSED TO EXTERNAL AGENCY' => true,
    ];

    public const EXCLUDED_NEXT_ACTION = [
        'FOR CH VALIDATION' => true,
        'FOR REPO' => true
    ];
}
