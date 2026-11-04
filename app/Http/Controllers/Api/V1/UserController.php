<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttestationRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function attestation(AttestationRequest $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->userService->attestation($request->validated(), Auth::user()->cmsUser->id);

        if (!$result['is_attested']) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => $result['message'],
            'data' => $result['is_attested'],
        ], 200);
    }
}
