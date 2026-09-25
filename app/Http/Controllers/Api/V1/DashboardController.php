<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function getSummary()
    {
        $summary = $this->dashboardService->getSummary();

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' => $summary,
        ]);
    }
}
