<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['code']);
        }

        return response()->json([
            'message' => 'Authenticated successfully.',
            'data' => $result['data'],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user->load('CmsUser:id,name,role_id,company_id,level,psgc_code');

        \Log::info($user);

        return response()->json($user);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if ($user) {
            $this->authService->logout($user);
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
