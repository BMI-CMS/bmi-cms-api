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
        $userId = $request->route('userId');

        $summary = $this->dashboardService->getSummary($userId);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' => $summary,
        ]);
    }

    public function assignedAccounts(AssignedAccountRequest $request)
    {
        $userId = $request->route('userId');

        $period = $request->route('period');

        $summary = $this->dashboardService->getAssignedAccounts($userId, $period);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' =>  $summary
        ]);
    }

    public function assignedAccountsByPSGC(UserIdRequest $request)
    {
        $userId = $request->route('userId');

        $summary = $this->dashboardService->assignedAccountsByPSGC($userId);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' =>  $summary
        ]);
    }
}
