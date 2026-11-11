<?php

namespace App\Services;

use App\Repositories\CollectionRepository;

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
        foreach ($collections as $collection) {
            $accountNumber = $collection->account_number;

            if (!isset($collectionByAccountNumber[$accountNumber])) {
                $collectionByAccountNumber[$accountNumber] = [
                    'account_number' => $accountNumber,
                    'customer_name' => $collection->customer_name,
                    'monthly_amortization' => $collection->monthly_amortization,
                    'outstanding_balance' => $collection->outstanding_balance,
                    'past_due_balance' => $collection->past_due_balance,
                    'payment_history' => [],
                ];
            }

            $collectionByAccountNumber[$accountNumber]['payment_history'][] = [
                'payment_type' => $collection->payment_type,
                'ar_number' => $collection->ar_number,
                'ar_date' => $collection->ar_date,
                'amount' => $collection->amount,
                'receipt_image_path' => $collection->receipt_image_path,
            ];

            $totalAmount = bcadd(
                $totalAmount,
                $collection->amount,
                2
            );
        }

        return [
            'total_amount' => $totalAmount,
            'collection_list' => array_values($collectionByAccountNumber),
        ];
    }
}
