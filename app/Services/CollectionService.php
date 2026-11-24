<?php

namespace App\Services;

use App\Repositories\CollectionRepository;
use App\Models\Collection;
use App\Models\ReceiptEncoding;
use App\Models\DepositEncoding;
use App\Constants\ReceiptStatus;
use App\Constants\DepositStatus;
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
                'total_accounts' => count($data['receipt_ids']),
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

            return [
                'success' => true,
                'data' => [
                    'id' => $collection->id,
                    'data' => $data,
                ],
            ];
        });
    }

    public function cutoffHistory()
    {
        $cutoffHistory = $this->collectionRepository->cutoffHistory();

        $formattedCutoffHistory = [];
        foreach ($cutoffHistory as $cutoff) {
            $collection_id = $cutoff->id;

            if (!isset($formattedCutoffHistory[$collection_id])) {
                $hasDeposit = $cutoff->deposit_id !== null;

                $formattedCutoffHistory[$collection_id] = [
                    'collection_id' => $collection_id,
                    'amount' => $cutoff->total_amount,
                    'status' => $hasDeposit ? $cutoff->status : DepositStatus::UNDEPOSITED,
                    'process_date' => $cutoff->created_at,
                    'deposit_details' => $hasDeposit ? [
                        'payment_channel' => $cutoff->payment_channel,
                        'depository_account' => $cutoff->depository_account,
                        'transaction_reference_number' => $cutoff->transaction_reference_number,
                        'deposit_date' => $cutoff->deposit_date,
                        'total_deposit_amount' => $cutoff->total_deposit_amount,
                        'deposit_charge' => $cutoff->deposit_charge,
                        'deposit_amount' => $cutoff->deposit_amount,
                        'deposit_slip' => $cutoff->deposit_slip,
                        'status' => $cutoff->deposit_status,
                        'remarks' => $cutoff->remarks,
                    ] : null,
                    'payment_history' => [],
                ];
            }

            $formattedCutoffHistory[$collection_id]['payment_history'][] = [
                'receipt_id' =>  $cutoff->receipt_id,
                'payment_type' => $cutoff->payment_type,
                'ar_number' => $cutoff->ar_number,
                'ar_date' => $cutoff->ar_date,
                'amount' => $cutoff->amount,
                'receipt_image_path' => $cutoff->receipt_image_path,
            ];
        }

        return array_values($formattedCutoffHistory);
    }

    public function deposit(array $data)
    {
        DepositEncoding::create([
            'collection_id' => $data['collection_id'],
            'payment_channel' => $data['payment_channel'],
            'depository_account' => $data['depository_account'],
            'transaction_reference_number' => $data['transaction_reference_number'],
            'deposit_date' => $data['deposit_date'],
            'total_deposit_amount' => $data['total_deposit_amount'],
            'deposit_charge' => $data['deposit_charge'],
            'deposit_amount' => $data['deposit_amount'],
            'deposit_slip' => $data['deposit_slip'],
        ]);

        $test = "BDO-374-20261124";
        ReceiptEncoding::where('collection_id', $data['collection_id'])
            ->update([
                'reference_number' =>  $test,
            ]);

        return "nice";
    }
}
