<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
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
        $result = $this->authService->login($request->validated(), $request->ip());

        if (! $result['success']) {
            $data = $result['data'] ?? [];
            $headers = isset($data['retry_after']) ? ['Retry-After' => $data['retry_after']] : [];

            return response()->json([
                'message' => $result['message'],
                ...$data,
            ], $result['code'], $headers);
        }

        return response()->json([
            'message' => 'Authenticated successfully.',
            'data' => $result['data'],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user->load('CmsUser:id,name,role_id,company_id,level,psgc_code');

        return response()->json($user->CmsUser);
    }

    public function verifyToken(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();
        $cms_user = $user->CmsUser;

        return response()->json([
            'message' => 'Token successfully validated',
            'data' => [
                'valid' => true,
                'id' => $user->id,
                'username' => $user->username,
                'name' => $cms_user->name,
                'role_id' => $cms_user->role_id,
                'company_name' => $cms_user->company->name,
                'psgc_code' => $cms_user->psgc_code,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $this->authService->logout($user);
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
