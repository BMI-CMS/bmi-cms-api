<?php

namespace App\Constants;

class Login
{
    public const ATTEMPTS_BEFORE_FREEZE = 3;
    public const FREEZE_MINUTES = 5;
    public const ATTEMPTS_BEFORE_LOCK = 5;
}
