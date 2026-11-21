<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Services\LucasIntegrationService;

class LucasIntegrationController extends Controller
{
    public function __construct(
        protected LucasIntegrationService $lucasIntegrationService
    ) {}

    public function forRestructuring()
    {
        try {
            $result = $this->lucasIntegrationService->forRestructuring();

            return response()->json([
                'success' => true,
                'message' => $result->isEmpty()
                    ? 'No pending restructuring accounts found.'
                    : 'Restructuring accounts retrieved successfully.',
                'data' => $result,
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

    public function earlyEndorsed()
    {
        try {
            $result = $this->lucasIntegrationService->earlyEndorsed();

            return response()->json([
                'success' => true,
                'message' => $result->isEmpty()
                    ? 'No pending restructuring accounts found.'
                    : 'Early endorsed accounts retrieved successfully.',
                'data' => $result,
            ], 200);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve restructuring accounts.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve early endorsed accounts',
                'data' => null,
            ], 500);
        }
    }

    public function noticeAndDemand()
    {
        try {
            $result = $this->lucasIntegrationService->noticeAndDemand();

            return response()->json([
                'success' => true,
                'message' => $result->isEmpty()
                    ? 'No pending restructuring accounts found.'
                    : 'Notice and demand accounts retrieved successfully.',
                'data' => $result,
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
}
