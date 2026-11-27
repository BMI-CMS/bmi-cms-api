<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Throwable;
use App\Services\LucasIntegrationService;
use App\Http\Requests\Integration\EarlyEndorsedRequest;
use App\Http\Requests\Integration\DepositStatusRequest;



class LucasIntegrationController extends Controller
{
    public function __construct(
        protected LucasIntegrationService $lucasIntegrationService
    ) {}

    public function forRestructuring(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => [
                'nullable',
                Rule::in([
                    'account_no',
                    'customer_name',
                    'principal_outstanding',
                    'old_amortization',
                    'restructuring_term',
                    'restructuring_date',
                    'conducted_by',
                    'restructiring_status',
                ]),
            ],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $result = $this->lucasIntegrationService
                ->forRestructuring($filters);

            return response()->json([
                'success' => true,
                'message' => $result->isEmpty()
                    ? 'No pending restructuring accounts found.'
                    : 'Restructuring accounts retrieved successfully.',

                'data' => $result->items(),
                'pagination' => [
                    'current_page' => $result->currentPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                    'last_page' => $result->lastPage(),
                    'from' => $result->firstItem(),
                    'to' => $result->lastItem(),
                ],
                'filters' => [
                    'search' => $filters['search'] ?? '',
                ],
                'sort' => [
                    'column' => $filters['sort'] ?? 'restructuring_date',
                    'direction' => $filters['direction'] ?? 'desc',
                ],
            ], 200);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve restructuring accounts.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve restructuring accounts.',
                'data' => null,
            ], 500);
        }
    }

    public function earlyEndorsed(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => [
                'nullable',
                Rule::in([
                    'account_no',
                    'customer_name',
                    'outstanding_balance',
                    'approval_status',
                    'requested_date'
                ]),
            ],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $result = $this->lucasIntegrationService
                ->earlyEndorsed($filters);

            return response()->json([
                'success' => true,
                'message' => $result->isEmpty()
                    ? 'No pending early endorsed accounts found.'
                    : 'Early endorsed accounts retrieved successfully.',

                'data' => $result->items(),
                'pagination' => [
                    'current_page' => $result->currentPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                    'last_page' => $result->lastPage(),
                    'from' => $result->firstItem(),
                    'to' => $result->lastItem(),
                ],
                'filters' => [
                    'search' => $filters['search'] ?? '',
                ],
                'sort' => [
                    'column' => $filters['sort'] ?? 'requested_date',
                    'direction' => $filters['direction'] ?? 'desc',
                ],
            ], 200);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve early endorsed accounts.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve early endorsed accounts',
                'data' => null,
            ], 500);
        }
    }

    public function updateEarlyEndorsedApprovalStatus(EarlyEndorsedRequest $request)
    {
        $validated = $request->validated();

        $this->lucasIntegrationService
            ->updateEarlyEndorsedApprovalStatus($validated);

        return response()->json([
            'success' => true,
            'message' => 'Early endorsed account approval status updated successfully.',
        ], 200);
    }

    public function noticeAndDemand(Request $request): JsonResponse
    {

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => [
                'nullable',
                Rule::in([
                    'account_no',
                    'customer_name',
                    'outstanding_balance',
                    'demand_letter_type',
                    'reason_for_notice',
                    'status',
                    'requested_date'
                ]),
            ],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        try {
            $result = $this->lucasIntegrationService
                ->noticeAndDemand($filters);

            return response()->json([
                'success' => true,
                'message' => $result->isEmpty()
                    ? 'No pending notice and demand accounts found.'
                    : 'Notice and demand accounts retrieved successfully.',

                'data' => $result->items(),
                'pagination' => [
                    'current_page' => $result->currentPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                    'last_page' => $result->lastPage(),
                    'from' => $result->firstItem(),
                    'to' => $result->lastItem(),
                ],
                'filters' => [
                    'search' => $filters['search'] ?? '',
                ],
                'sort' => [
                    'column' => $filters['sort'] ?? 'requested_date',
                    'direction' => $filters['direction'] ?? 'desc',
                ],
            ], 200);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve restructuring accounts.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notice and demand accounts',
                'data' => null,
            ], 500);
        }
    }

    public function updateDepositStatus(DepositStatusRequest $request): JsonResponse
    {

        $data = $request->validated();

        $idempotencyKey = $data['idempotency_key'];

        unset($data['idempotency_key']);

        $result = $this->lucasIntegrationService->updateDepositStatus(
            $data,
            $idempotencyKey
        );

        return response()->json($result, 200);
    }
}
