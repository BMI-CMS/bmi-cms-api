<?php

namespace App\Helpers;

use Carbon\Carbon;

class ReferenceNumberHelper
{
    public static function format(
        string $date,
        ?string $depositoryAccount,
        string $transactionReference
    ): string {
        if (empty($depositoryAccount)) {
            return $transactionReference;
        }

        $formattedDate = Carbon::parse($date)->format('Ymd');

        $formattedAccount = str_replace(' ', '-', trim($depositoryAccount));

        return "{$formattedAccount}-{$formattedDate}";
    }
}
