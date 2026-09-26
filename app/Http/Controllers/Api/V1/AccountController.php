<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AccountService;

class AccountController extends Controller
{
    public function __construct(
        protected AccountService $accountService
    ) {}

    public function show(int $user_id)
    {
        $summary = $this->accountService->getAccounts($user_id);

        return response()->json([
            'message' => 'Dashboard summary retrieved successfully.',
            'data' => $summary,
        ]);
    }
}
