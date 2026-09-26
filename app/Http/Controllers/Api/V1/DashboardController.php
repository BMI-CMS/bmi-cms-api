<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function show(int $userId)
    {
        $summary = $this->dashboardService->getSummary($userId);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' => $summary,
        ]);
    }
}
