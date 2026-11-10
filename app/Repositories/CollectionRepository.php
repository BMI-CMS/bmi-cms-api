<?php

namespace App\Repositories;

use App\Models\Account;


class CollectionRepository
{
    public function getUndepositedPayments(int $userId): array
    {
        return Account::query()
            ->select([
                'accounts.id',
                'accounts.customer_id',
                'accounts.account_number',
                'accounts.monthly_amortization',
                'accounts.outstanding_balance',
                'accounts.past_due_balance',
                'receipt_encodings.ar_date',
                'receipt_encodings.ar_number',
                'receipt_encodings.amount',
                'receipt_encodings.receipt_image_path'
            ])
            ->join('receipt_encodings', 'account.id', '=', 'receipt_encodings.account_id')
            ->where('accounts.assigned_cc_id', $userId)
            ->whereNull('receipt_encodings.collection_id')
            ->get();
    }
}
