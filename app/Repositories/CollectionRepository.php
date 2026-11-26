<?php

namespace App\Repositories;

use App\Models\Account;
use App\Models\ReceiptEncoding;

use Illuminate\Database\Eloquent\Collection;
use App\Models\Collection as CollectionModel;

class CollectionRepository
{
    public function getUndepositedPayments(int $userId): Collection
    {
        return ReceiptEncoding::query()
            ->select([
                'accounts.id',
                'receipt_encodings.id as receipt_id',
                'accounts.customer_name',
                'accounts.account_number',
                'accounts.monthly_amortization',
                'accounts.outstanding_balance',
                'accounts.past_due_balance',
                'receipt_encodings.payment_type',
                'receipt_encodings.ar_number',
                'receipt_encodings.ar_date',
                'receipt_encodings.amount',
                'receipt_encodings.receipt_image_path'
            ])
            ->join(
                'contact_recordings',
                'contact_recordings.id',
                '=',
                'receipt_encodings.contact_recording_id'
            )
            ->join(
                'accounts',
                'accounts.id',
                '=',
                'receipt_encodings.account_id'
            )
            ->where('contact_recordings.recorded_by', $userId)
            ->whereNull('receipt_encodings.collection_id')
            ->orderBy('receipt_encodings.id')
            ->get();
    }

    public function cutoffHistory(): Collection
    {
        return CollectionModel::query()
            ->select([
                'collections.id',
                'collections.total_amount',
                'collections.total_accounts',
                'collections.created_at',
                'deposit_encodings.id as deposit_id',
                'deposit_encodings.payment_channel',
                'deposit_encodings.depository_account',
                'deposit_encodings.transaction_reference_number',
                'deposit_encodings.deposit_date',
                'deposit_encodings.total_deposit_amount',
                'deposit_encodings.deposit_charge',
                'deposit_encodings.deposit_amount',
                'deposit_encodings.deposit_slip',
                'deposit_encodings.status',
                'deposit_encodings.remarks',
                'receipt_encodings.id as receipt_id',
                'receipt_encodings.payment_type',
                'receipt_encodings.ar_number',
                'receipt_encodings.ar_date',
                'receipt_encodings.amount',
                'receipt_encodings.receipt_image_path'
            ])
            ->leftJoin(
                'deposit_encodings',
                'collections.id',
                '=',
                'deposit_encodings.collection_id'
            )
            ->join(
                'receipt_encodings',
                'collections.id',
                '=',
                'receipt_encodings.collection_id'
            )
            ->get();
    }
}
