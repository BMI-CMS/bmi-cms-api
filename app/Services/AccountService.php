<?php

namespace App\Services;

use App\Models\Account;

class AccountService
{
    public function markAsForcePrioritized(int $account_id, string $account_number): array
    {
        $account = Account::where('id', $account_id)
            ->where('account_number', $account_number)
            ->first();

        if (!$account) {
            return [
                'success' => false,
                'message' => 'Account not found.',
                'data' => null,
            ];
        }

        $account->update([
            'is_force_prioritized' => true,
        ]);

        return [
            'success' => true,
            'message' => true
                ? 'Account has been force prioritized successfully.'
                : 'Account force priority has been removed successfully.',
            'data' => [
                'id' => $account->id,
                'account_number' => $account->account_number,
                'is_force_prioritized' => $account->is_force_prioritized,
            ],
        ];
    }
}
