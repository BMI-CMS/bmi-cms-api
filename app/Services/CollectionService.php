<?php

namespace App\Services;

use App\Repositories\CollectionRepository;
use App\Models\Collection;
use App\Models\ReceiptEncoding;
use App\Constants\ReceiptStatus;
use Illuminate\Support\Facades\DB;

class CollectionService
{

    public function __construct(
        protected CollectionRepository $collectionRepository
    ) {}

    public function getCollections(int $userId): array
    {
        $collections = $this->collectionRepository->getUndepositedPayments($userId);


        $collectionByAccountNumber = [];
        $totalAmount = '0.00';
        $nice = [];
        foreach ($collections as $collection) {

            $collectionByAccountNumber[] = [
                'receipt_id' => $collection->receipt_id,
                'account_number' => $collection->account_number,
                'customer_name' => $collection->customer_name,
                'monthly_amortization' => $collection->monthly_amortization,
                'outstanding_balance' => $collection->outstanding_balance,
                'past_due_balance' => $collection->past_due_balance,
                'receipt_details' => [
                    'payment_type' => $collection->payment_type,
                    'ar_number' => $collection->ar_number,
                    'ar_date' => $collection->ar_date,
                    'amount' => $collection->amount,
                    'receipt_image_path' => $collection->receipt_image_path,
                ],
            ];

            $nice[] = $collection->receipt_id;

            $totalAmount = bcadd(
                $totalAmount,
                $collection->amount,
                2
            );
        }

        return [
            'test' => $nice,
            'total_amount' => $totalAmount,
            'collection_list' => $collectionByAccountNumber,
        ];
    }

    public function processCutoff(array $data)
    {
        return DB::transaction(function () use ($data) {

            $collection = Collection::create([
                'total_amount' => $data['total_amount'],
            ]);

            $affectedRows = ReceiptEncoding::whereIn('id', $data['receipt_ids'])
                ->where('status', ReceiptStatus::PENDING_CUTOFF)
                ->update([
                    'collection_id' => $collection->id,
                    'status' => ReceiptStatus::CUTOFF_PROCESSED,
                ]);

            if ($affectedRows !== count($data['receipt_ids'])) {
                throw new \RuntimeException(
                    'One or more receipts do not exist or are not pending cutoff.'
                );
            }

            return 'nice one';
        });
    }
}
