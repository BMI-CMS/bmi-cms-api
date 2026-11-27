<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Repositories\CollectionRepository;
use App\Models\Collection;
use App\Models\ReceiptEncoding;
use App\Models\DepositEncoding;
use App\Constants\ReceiptStatus;
use App\Constants\DepositStatus;
use App\Helpers\ReferenceNumberHelper;
use App\Services\Integration\IntegrationOutboxService;
use App\Enums\Integration\IntegrationSystem;
use App\Enums\Integration\IntegrationEvent;


class CollectionService
{

    public function __construct(
        protected CollectionRepository $collectionRepository,
        private IntegrationOutboxService $integrationOutboxService
    ) {}

    public function getCollections(int $userId): array
    {
        $collections = $this->collectionRepository->getUndepositedPayments($userId);

        $collectionByAccountNumber = [];
        $totalAmount = '0.00';
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
                    'collection_date' => $collection->created_at
                ],
            ];

            $totalAmount = bcadd(
                $totalAmount,
                $collection->amount,
                2
            );
        }

        return [
            'total_amount' => $totalAmount,
            'collection_list' => $collectionByAccountNumber,
        ];
    }

    public function processCutoff(int $userId)
    {
        return DB::transaction(function () use ($userId) {

            $receipts = ReceiptEncoding::query()
                ->select('receipt_encodings.*')
                ->join(
                    'contact_recordings',
                    'contact_recordings.id',
                    '=',
                    'receipt_encodings.contact_recording_id'
                )
                ->where('contact_recordings.recorded_by', $userId)
                ->whereNull('receipt_encodings.collection_id')
                ->orderBy('receipt_encodings.id')
                ->lockForUpdate()
                ->get();

            if ($receipts->isEmpty()) {
                throw ValidationException::withMessages([
                    'receipts' => 'No available receipts to process.',
                ]);
            }

            $totalAmount = '0.00';

            foreach ($receipts as $receipt) {
                $totalAmount = bcadd(
                    $totalAmount,
                    $receipt->amount,
                    2
                );
            }

            $collection = Collection::create([
                'total_amount' => $totalAmount,
                'total_accounts' => $receipts->count(),
            ]);

            $affectedRows = ReceiptEncoding::query()
                ->whereIn('id', $receipts->modelKeys())
                ->whereNull('collection_id')
                ->update([
                    'collection_id' => $collection->id,
                    'status' => ReceiptStatus::CUTOFF_PROCESSED,
                ]);

            if ($affectedRows !== $receipts->count()) {
                throw ValidationException::withMessages([
                    'receipts' => 'Failed to process all receipts.',
                ]);
            }

            return [
                'success' => true,
                'data' => [
                    'data' => $collection,
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
                'customer_name' => $cutoff->customer_name,
                'payment_type' => $cutoff->payment_type,
                'ar_number' => $cutoff->ar_number,
                'ar_date' => $cutoff->ar_date,
                'amount' => $cutoff->amount,
                'receipt_image_path' => $cutoff->receipt_image_path,
            ];
        }

        return array_values($formattedCutoffHistory);
    }

    public function deposit(array $data): array
    {
        return DB::transaction(function () use ($data) {

            $collection = Collection::query()
                ->where('id', $data['collection_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (DepositEncoding::where('collection_id', $collection->id)->exists()) {
                throw ValidationException::withMessages([
                    'collection_id' => 'This collection already has a deposit.',
                ]);
            }

            $receipts = ReceiptEncoding::select([
                'receipt_encodings.id',
                'accounts.account_number',
                'accounts.customer_name',
                'receipt_encodings.collection_id',
                'receipt_encodings.payment_type',
                'receipt_encodings.ar_number',
                'receipt_encodings.ar_date',
                'receipt_encodings.amount',
                'receipt_encodings.reference_number',
            ])
                ->join(
                    'accounts',
                    'accounts.id',
                    '=',
                    'receipt_encodings.account_id'
                )
                ->where('collection_id', $collection->id)
                ->orderBy('id')
                ->lock('FOR UPDATE OF receipt_encodings')
                ->get();

            if ($receipts->isEmpty()) {
                throw new \RuntimeException(
                    'No receipts found for the specified collection.'
                );
            }

            $depositAmount = bcsub($collection->total_amount, $data['deposit_charge'], 2);

            $deposit = DepositEncoding::create([
                'collection_id' => $collection->id,
                'payment_channel' => $data['payment_channel'],
                'depository_account' => $data['depository_account'] ?? null,
                'transaction_reference_number' => $data['transaction_reference_number'],
                'deposit_date' => $data['deposit_date'],
                'total_deposit_amount' => $collection->total_amount,
                'deposit_charge' => $data['deposit_charge'],
                'deposit_amount' => $depositAmount,
                'deposit_slip' => $data['deposit_slip'] ?? null,
                'status' => 'FOR_AUTHORIZATION',
                'submission_version' => 1,
            ]);

            $referenceNumber = ReferenceNumberHelper::format(
                $data['deposit_date'],
                $data['depository_account'] ?? null,
                $data['transaction_reference_number']
            );

            foreach ($receipts as $index => $receipt) {

                if (!$receipt->update([
                    'reference_number' => $referenceNumber . '-' . sprintf('%03d', $index + 1),
                ])) {
                    throw new \RuntimeException(
                        "Failed to update receipt ID: {$receipt->id}"
                    );
                }
            }

            $payload = [
                'deposit' => $deposit->only([
                    'id',
                    'collection_id',
                    'payment_channel',
                    'depository_account',
                    'transaction_reference_number',
                    'deposit_date',
                    'total_deposit_amount',
                    'deposit_charge',
                    'deposit_amount',
                    'deposit_slip',
                    'submission_version',
                ]),

                'receipts' => $receipts->map(function ($receipt) {
                    return $receipt->only([
                        'id',
                        'account_number',
                        'customer_name',
                        'collection_id',
                        'payment_type',
                        'ar_number',
                        'ar_date',
                        'amount',
                        'reference_number',
                    ]);
                })->values()->all(),
            ];

            $outbox = $this->integrationOutboxService->enqueue(
                destinationSystem: IntegrationSystem::LUCAS,
                eventType: IntegrationEvent::DEPOSIT_CREATED,
                referenceType: 'DEPOSIT',
                referenceId: $deposit->id,
                payload: $payload
            );

            return [
                'success' => true,
                'data' => [
                    'deposit_id' => $deposit->id,
                    'integration_id' => $outbox->id,
                    'integration_status' => $outbox->status->value,
                ],
            ];
        });
    }

    public function sendBackStatus()
    {
        return 'test';
    }
}
