<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CollectionService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\DepositRequest;
use Illuminate\Support\Facades\Auth;
// use App\Http\Requests\CollectectionRequest;

class CollectionController extends Controller
{
    public function __construct(
        protected CollectionService $collectionService
    ) {}

    public function index(): JsonResponse
    {
        $collectionList = $this->collectionService->getCollections(Auth::user()->cmsUser->id);

        return response()->json([
            'message' => 'Collection accounts retrieved successfully.',
            'data' => $collectionList
        ]);
    }

    public function processCutoff()
    {
        $result = $this->collectionService->processCutoff(Auth::user()->cmsUser->id);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['code']);
        }

        return response()->json([
            'message' => 'Successfully Saved',
            'data' => $result['data'],
        ]);
    }

    public function cutoffHistory()
    {
        $cutoffHistory = $this->collectionService->cutoffHistory();

        return response()->json([
            'message' => 'Cut off history retrieved successfully.',
            'data' =>  $cutoffHistory
        ]);
    }

    public function deposit(DepositRequest $request)
    {
        $validated = $request->validated();

        $result = $this->collectionService->deposit($validated);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['code']);
        }

        return response()->json([
            'message' => 'Successfully Saved',
            'data' =>  $result
        ]);
    }
}
