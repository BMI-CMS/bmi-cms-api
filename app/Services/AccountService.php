<?php

namespace App\Services;

class AccountService
{
    public function getSummary(): array
    {
        return [
            "accounts" => 100,
            "first_encounters" => 10,
            "collections" => 5
        ];
    }
}
