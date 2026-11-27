<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Restructuring;
use App\Models\EarlyEndorsed;
use App\Models\NoticedAccount;
use App\Models\ContactRecording;
use App\Models\DepositEncoding;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Enums\Integration\IntegrationSystem;
use App\Enums\Integration\IntegrationEvent;
use App\Services\Integration\IntegrationInboxService;
use Illuminate\Validation\ValidationException;


class LucasIntegrationService
{
    public function __construct(
        private IntegrationInboxService $inboxService
    ) {}

    public function forRestructuring(array $filters = []): LengthAwarePaginator
    {
        $sortColumns = [
            'account_no' => 'accounts.account_number',
            'customer_name' => 'accounts.customer_name',
            'principal_outstanding' => 'restructurings.outstanding_balance',
            'old_amortization' => 'restructurings.monthly_amortization',
            'restructuring_term' => 'restructurings.terms',
            'restructuring_date' => 'restructurings.created_at',
            'conducted_by' => 'cms_users.name',
            'restructiring_status' => 'restructurings.status',
        ];

        $search = $filters['search'] ?? null;
        $sort = $filters['sort'] ?? 'restructuring_date';
        $direction = $filters['direction'] ?? 'desc';
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 10);

        $sortColumn = $sortColumns[$sort] ?? 'restructurings.created_at';

        return Restructuring::query()
            ->select([
                'restructurings.id',
                'accounts.account_number',
                'accounts.customer_name',
                'restructurings.outstanding_balance',
                'restructurings.monthly_amortization',
                'restructurings.terms',
                'restructurings.new_monthly_amortization',
                'restructurings.restructuring_form',
                'restructurings.remarks',
                'restructurings.status',
                'cms_users.name as recorded_by_name',
                'restructurings.created_at',
            ])
            ->join(
                'contact_recordings',
                'contact_recordings.id',
                '=',
                'restructurings.contact_recording_id'
            )
            ->join(
                'accounts',
                'accounts.id',
                '=',
                'contact_recordings.account_id'
            )
            ->join(
                'cms_users',
                'cms_users.id',
                '=',
                'contact_recordings.recorded_by'
            )
            ->where('restructurings.status', 'pending')
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'accounts.customer_name',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'accounts.account_number',
                        'like',
                        "%{$search}%"
                    );
                });
            })
            ->orderBy(
                $sortColumn,
                in_array($direction, ['asc', 'desc'], true)
                    ? $direction
                    : 'desc'
            )
            ->orderBy('restructurings.id', 'desc')
            ->paginate(
                min(max($perPage, 1), 100),
                ['*'],
                'page',
                max($page, 1)
            );
    }

    public function earlyEndorsed(array $filters = []): LengthAwarePaginator
    {

        $startOfTheMonth = Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $endOfTheMonth = Carbon::now()->endOfMonth()->format('Y-m-d H:i:s');

        $sortColumns = [
            'account_no' => 'accounts.account_number',
            'customer_name' => 'accounts.customer_name',
            'outstanding_balance' => 'accounts.outstanding_balance',
            'approval_status' => 'early_endorseds.status',
            'requested_date' => 'early_endorseds.created_at',
        ];

        $search = $filters['search'] ?? null;
        $sort = $filters['sort'] ?? 'requested_date';
        $direction = $filters['direction'] ?? 'desc';
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 10);

        $sortColumn = $sortColumns[$sort] ?? 'early_endorseds.created_at';

        return EarlyEndorsed::query()
            ->select([
                'early_endorseds.id',
                'early_endorseds.contact_recording_id',
                'accounts.account_number',
                'accounts.customer_name',
                'accounts.outstanding_balance',
                'early_endorseds.reason_of_endorsement',
                'cms_users.name as recorded_by_name',
                'early_endorseds.created_at',
                'early_endorseds.status',
            ])
            ->join(
                'contact_recordings',
                'contact_recordings.id',
                '=',
                'early_endorseds.contact_recording_id'
            )
            ->join(
                'accounts',
                'accounts.id',
                '=',
                'contact_recordings.account_id'
            )
            ->join(
                'cms_users',
                'cms_users.id',
                '=',
                'contact_recordings.recorded_by'
            )
            ->where('early_endorseds.created_at', '>=', $startOfTheMonth)
            ->where('early_endorseds.created_at', '<=', $endOfTheMonth)
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'accounts.customer_name',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'accounts.account_number',
                        'like',
                        "%{$search}%"
                    );
                });
            })
            ->orderBy(
                $sortColumn,
                in_array($direction, ['asc', 'desc'], true)
                    ? $direction
                    : 'desc'
            )
            ->orderBy('early_endorseds.id', 'desc')
            ->paginate(
                min(max($perPage, 1), 100),
                ['*'],
                'page',
                max($page, 1)
            );
    }

    public function updateEarlyEndorsedApprovalStatus(array $data): void
    {
        DB::transaction(function () use ($data) {

            $earlyEndorsed = EarlyEndorsed::findOrFail($data['id']);

            if ($data['status'] === 'DISAPPROVED') {

                $previousData = ContactRecording::findOrFail(
                    $data['contact_recording_id']
                );

                ContactRecording::create([
                    'account_id' => $previousData->account_id,
                    'current_category' => $previousData->current_category,
                    'follow_up_mode' => $previousData->follow_up_mode,
                    'action_taken' => $previousData->action_taken,
                    'sub_action_taken' => $previousData->sub_action_taken,
                    'reason_for_default' => $previousData->reason_for_default,
                    'sub_reason_for_default' => $previousData->sub_reason_for_default,
                    'next_action_plan' => $data['next_action_plan'],
                    'remarks' => $data['remarks'],
                    'geotagging' => $previousData->geotagging,
                    'recorded_by' => $data['employee_id'],
                    'next_action_date' => $data['next_action_date'],
                ]);
            }

            $earlyEndorsed->update([
                'status' => $data['status'],
            ]);
        });
    }

    public function noticeAndDemand(array $filters = []): LengthAwarePaginator
    {
        $sortColumns = [
            'account_no' => 'accounts.account_number',
            'customer_name' => 'accounts.customer_name',
            'outstanding_balance' => 'accounts.outstanding_balance',
            'demand_letter_type' => 'noticed_accounts.letter_type',
            'reason_for_notice' => 'noticed_accounts.reason_for_notice',
            'status' => 'noticed_accounts.status',
            'requested_date' => 'noticed_accounts.created_at',
        ];

        $search = $filters['search'] ?? null;
        $sort = $filters['sort'] ?? 'requested_date';
        $direction = $filters['direction'] ?? 'desc';
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 10);

        $sortColumn = $sortColumns[$sort] ?? 'noticed_accounts.created_at';

        return NoticedAccount::query()
            ->select([
                'noticed_accounts.id',
                'accounts.account_number',
                'accounts.customer_name',
                'accounts.outstanding_balance',
                'noticed_accounts.letter_type',
                'noticed_accounts.reason_for_notice',
                'cms_users.name as recorded_by_name',
                'noticed_accounts.created_at',
            ])
            ->join(
                'contact_recordings',
                'contact_recordings.id',
                '=',
                'noticed_accounts.contact_recording_id'
            )
            ->join(
                'accounts',
                'accounts.id',
                '=',
                'contact_recordings.account_id'
            )
            ->join(
                'cms_users',
                'cms_users.id',
                '=',
                'contact_recordings.recorded_by'
            )
            ->where('noticed_accounts.status', 'Unserved')
            ->when($search !== null && $search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'accounts.customer_name',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'accounts.account_number',
                        'like',
                        "%{$search}%"
                    );
                });
            })
            ->orderBy(
                $sortColumn,
                in_array($direction, ['asc', 'desc'], true)
                    ? $direction
                    : 'desc'
            )
            ->orderBy('noticed_accounts.id', 'desc')
            ->paginate(
                min(max($perPage, 1), 100),
                ['*'],
                'page',
                max($page, 1)
            );
    }

    public function updateDepositStatus(
        array $data,
        string $idempotencyKey
    ): array {

        $depositId = (int) $data['deposit_id'];

        $payload = [
            'deposit_id' => $depositId,
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? null,
        ];

        return $this->inboxService->process(
            sourceSystem: IntegrationSystem::LUCAS,
            eventType: IntegrationEvent::DEPOSIT_STATUS_UPDATED,
            idempotencyKey: $idempotencyKey,
            referenceType: 'DEPOSIT',
            referenceId: $depositId,
            payload: $payload,

            handler: function () use ($payload, $depositId) {

                $deposit = DepositEncoding::query()
                    ->where('id', $depositId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($deposit->status !== 'FOR_AUTHORIZATION') {

                    throw ValidationException::withMessages([
                        'status' => 'This deposit is not eligible for a status update.',
                    ]);
                }

                $deposit->update([
                    'status' => $payload['status'],
                    'remarks' => $payload['remarks'],
                ]);

                return [
                    'success' => true,
                    'message' => 'Deposit status updated successfully.',
                    'data' => [
                        'deposit_id' => $deposit->id,
                        'status' => $deposit->status,
                    ],
                ];
            }
        );
    }
}
