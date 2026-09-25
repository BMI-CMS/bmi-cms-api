<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AccountService;

class AccountController extends Controller
{
    public function __construct(
        protected AccountService $accountService
    ) {}

    public function dashboard()
    {
        $summary = $this->accountService->getSummary();

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' => $summary,
        ]);
    }
}
