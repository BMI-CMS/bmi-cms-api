<?php

namespace App\Http\Controllers\Api\V1;

use App\Constants\Constants;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\UserIdRequest;
use App\Http\Requests\Dashboard\AssignedAccountRequest;
use App\Http\Requests\TeamAssignedAccountsRequest;
use App\Http\Requests\TeamVisitPlanRequest;
use App\Services\DashboardService;
use Carbon\Carbon;

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
            'data' =>   $summary
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

    public function teamAssignedAccounts(TeamVisitPlanRequest $request)
    {
        $validated = $request->validated();

        $summary = $this->dashboardService->teamAssignedAccounts(
            $request->user()->cmsUser->level,
            $validated['user_id'],
            $validated['psgc_code'],
            $validated['period'],
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? Constants::TEAM_ACCOUNTS_PER_PAGE)
        );

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' =>  $summary
        ]);
    }

    public function teamAssignedAccountsPSGC(TeamAssignedAccountsRequest $request)
    {
        $validated = $request->validated();

        $summary = $this->dashboardService->teamAssignedAccountsPSGC($validated['user_id'], $validated['psgc_code'], $validated['period']);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' =>  $summary
        ]);
    }
}
