<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use App\Http\Requests\ForceAssignmentRequest;

class AccountController extends Controller
{
    public function __construct(
        protected AccountService $accountService
    ) {}

    public function markAsForcePrioritized(ForceAssignmentRequest $request)
    {
        $validated = $request->validated();

        $result = $this->accountService->markAsForcePrioritized($validated['account_id'], $validated['account_number']);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['code']);
        }

        return response()->json([
            'message' => 'Account marked as force prioritized successfully.',
            'data' => $result['data'],
        ]);
    }
}
