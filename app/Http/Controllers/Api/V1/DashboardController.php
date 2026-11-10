<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\AssignedAccountRequest;
use App\Http\Requests\TeamAssignedAccountsRequest;
use App\Http\Requests\Dashboard\ChIdRequest;
use App\Services\DashboardService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function accountSummary()
    {
        $summary = $this->dashboardService->getSummary(Auth::user()->cmsUser->id);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' => $summary,
        ]);
    }

    public function accountDetails(AssignedAccountRequest $request)
    {
        $validated = $request->validated();

        $summary = $this->dashboardService->getAssignedAccounts(Auth::user()->cmsUser, $validated['period']);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' =>   $summary
        ]);
    }

    public function assignedAccounts(AssignedAccountRequest $request)
    {
        $validated = $request->validated();

        $summary = $this->dashboardService->getAssignedAccounts(Auth::user()->cmsUser, $validated['period']);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' =>   $summary
        ]);
    }

    public function assignedAccountsByPSGC()
    {
        $summary = $this->dashboardService->assignedAccountsByPSGC(Auth::user()->cmsUser->id);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' =>  $summary
        ]);
    }

    public function supportCCList(ChIdRequest $request)
    {
        $validated = $request->validated();

        $listOfCC = $this->dashboardService->getListOfSupportCCbyItCH(Auth::user()->cmsUser->id, $validated["ch_id"]);

        return response()->json([
            'message' => 'Support list retrieved successfully.',
            'data' =>  $listOfCC
        ]);
    }

    // public function teamAssignedAccounts(TeamVisitPlanRequest $request)
    // {
    //     $validated = $request->validated();

    //     $summary = $this->dashboardService->teamAssignedAccounts(
    //         $request->user()->cmsUser->level,
    //         $validated['user_id'],
    //         $validated['psgc_code'],
    //         $validated['period'],
    //         (int) ($validated['page'] ?? 1),
    //         (int) ($validated['per_page'] ?? Constants::TEAM_ACCOUNTS_PER_PAGE)
    //     );

    //     return response()->json([
    //         'message' => 'Dashboard summary retrieved successfully.',
    //         'data' =>  $summary
    //     ]);
    // }

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
