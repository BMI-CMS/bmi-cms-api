<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UserIdRequest;
use App\Http\Requests\Dashboard\AssignedAccountRequest;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function accountSummary(UserIdRequest $request)
    {
        $validated = $request->validated();

        $summary = $this->dashboardService->getSummary($validated['user_id']);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' => $summary,
        ]);
    }

    public function assignedAccounts(AssignedAccountRequest $request)
    {
        $validated = $request->validated();

        $summary = $this->dashboardService->getAssignedAccounts($validated['user_id'], $validated['period']);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' =>  $summary
        ]);
    }

    public function assignedAccountsByPSGC(UserIdRequest $request)
    {
        $validated = $request->validated();

        $summary = $this->dashboardService->assignedAccountsByPSGC($validated['user_id']);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' =>  $summary
        ]);
    }
}
