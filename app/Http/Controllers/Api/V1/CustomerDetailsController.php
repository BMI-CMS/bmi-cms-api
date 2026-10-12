<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CustomerDetailsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerDetailsController extends Controller
{
    public function __construct(
        protected CustomerDetailsService $customerDetailsService
    ) {}

    /**
     * Display detailed profile of a specific customer.
     */
    public function show(int $id): JsonResponse
    {
        $result = $this->customerDetailsService->getCustomerDetails($id);

        return response()->json([
            'message' => 'Customer details retrieved successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Update customer contact and social media information.
     */
    public function updateContact(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $result = $this->customerDetailsService->updateContact($id, $data);

        return response()->json([
            'message' => 'Customer contact information updated successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Retrieve customer loan and financial balance profile.
     */
    public function financialProfile(int $id): JsonResponse
    {
        $result = $this->customerDetailsService->getFinancialProfile($id);

        return response()->json([
            'message' => 'Customer financial profile retrieved successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Retrieve interaction and contact history for the customer.
     */
    public function interactionHistory(int $id): JsonResponse
    {
        $result = $this->customerDetailsService->getInteractionHistory($id);

        return response()->json([
            'message' => 'Customer interaction history retrieved successfully.',
            'data' => $result,
        ]);
    }
}
