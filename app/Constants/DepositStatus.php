<?php

namespace App\Constants;

class DepositStatus
{
    public const UNDEPOSITED  = 1;
    public const FOR_AUTHORIZATION  = 2;
    public const AUTHORIZED  = 3;
    public const SEND_BACK  = 4;
}
