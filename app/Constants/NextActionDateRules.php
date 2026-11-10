<?php

namespace App\Constants;

class NextActionDateRules
{
    public const EXCLUDED_ACTION_TAKEN = [
        'FOR SERVICE OF COLLECTION NOTICE' => true,
        'FOR SERVICE OF ATTORNEY LETTER' => true,
    ];

    public const EXCLUDED_NEXT_ACTION = [
        'FOR CH VALIDATION' => true,
        'For Repo' => true
    ];
}
