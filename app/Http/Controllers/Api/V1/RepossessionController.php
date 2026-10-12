<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RepossessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepossessionController extends Controller
{
    public function __construct(
        protected RepossessionService $repossessionService
    ) {}

    /**
     * Display a listing of accounts endorsed for repossession.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $result = $this->repossessionService->getRepossessionList($filters);

        return response()->json([
            'message' => 'Repossession list retrieved successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Display full dossier for a repossession case.
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->repossessionService->getRepossessionDetail($id);

        return response()->json([
            'message' => 'Repossession case details retrieved successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Submit a new repossession endorsement request.
     */
    public function createRequest(Request $request): JsonResponse
    {
        $data = $request->all();
        $result = $this->repossessionService->createRepossessionRequest($data);

        return response()->json([
            'message' => 'Repossession request submitted successfully.',
            'data' => $result,
        ], 201);
    }

    /**
     * Update repossession pull-out or stockyard delivery status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $result = $this->repossessionService->updateRepossessionStatus($id, $data);

        return response()->json([
            'message' => 'Repossession status updated successfully.',
            'data' => $result,
        ]);
    }
}
