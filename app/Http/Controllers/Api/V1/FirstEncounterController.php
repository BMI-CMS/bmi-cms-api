<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FirstEncounterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FirstEncounterController extends Controller
{
    public function __construct(
        protected FirstEncounterService $firstEncounterService
    ) {}

    /**
     * List all accounts assigned for First Encounter Checklist.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $result = $this->firstEncounterService->getFecAccounts($filters);

        return response()->json([
            'message' => 'First Encounter accounts retrieved successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Show First Encounter Checklist details and questionnaire template.
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->firstEncounterService->getFecAccountDetail($id);

        return response()->json([
            'message' => 'First Encounter account details retrieved successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Submit completed FEC customer evaluation checklist.
     */
    public function submitEvaluation(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $result = $this->firstEncounterService->submitEvaluation($id, $data);

        return response()->json([
            'message' => 'First Encounter evaluation submitted successfully.',
            'data' => $result,
        ], 201);
    }

    /**
     * Log a visit attempt for FEC account.
     */
    public function logVisit(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $result = $this->firstEncounterService->logVisitAttempt($id, $data);

        return response()->json([
            'message' => 'First Encounter visit attempt logged successfully.',
            'data' => $result,
        ], 201);
    }
}
