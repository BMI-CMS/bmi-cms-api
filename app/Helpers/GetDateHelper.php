<?php

namespace App\Helpers;

use App\Constants\NextActionDateRules;

class GetDateHelper
{
    public static function nextActionDate(string $actionTaken, string $nextAction, string $date)
    {
        if (
            isset(NextActionDateRules::EXCLUDED_ACTION_TAKEN[$actionTaken]) ||
            isset(NextActionDateRules::EXCLUDED_NEXT_ACTION[$nextAction])
        ) {
            return null;
        }

        return $date;
    }
}
