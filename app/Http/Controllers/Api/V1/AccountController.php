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

        // $result = $this->userService->attestation($request->validated(), $user);

        // if (!$result['is_attested']) {
        //     $user->currentAccessToken()->delete();
        // }

        // return response()->json([
        //     'message' => $result['message'],
        //     'data' => $result['is_attested'],
        // ], 200);
    }
}
