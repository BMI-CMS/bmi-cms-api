<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
