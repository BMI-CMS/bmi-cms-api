<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function __construct(
        protected CollectionService $collectionService
    ) {}

    /**
     * Display a listing of assigned collection accounts for demo.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'dpd_bucket', 'search']);
        $result = $this->collectionService->getCollections($filters);

        return response()->json([
            'message' => 'Collection accounts retrieved successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Display the specified collection account details.
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->collectionService->getCollectionDetail($id);

        return response()->json([
            'message' => 'Collection account details retrieved successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Record a collection action / field visit activity.
     */
    public function recordAction(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $result = $this->collectionService->recordAction($id, $data);

        return response()->json([
            'message' => 'Collection action recorded successfully.',
            'data' => $result,
        ], 201);
    }

    /**
     * Record a field collection payment receipt.
     */
    public function recordReceipt(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $result = $this->collectionService->recordReceipt($id, $data);

        return response()->json([
            'message' => 'Payment receipt recorded successfully.',
            'data' => $result,
        ], 201);
    }
}
