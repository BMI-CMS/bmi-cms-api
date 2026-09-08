<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    /**
     * Authenticate user credentials and verify registered device IMEI.
     *
     * @param array<string, mixed> $credentials
     * @return array{success: bool, message?: string, code?: int, token?: string, user?: User}
     */
    public function login(array $credentials): array
    {
        $authAttempt = [
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ];

        if (!Auth::attempt($authAttempt)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials.',
                'code' => 401,
            ];
        }

        /** @var User $user */
        $user = Auth::user();

        if (empty($user->phone_imei) || $user->phone_imei !== $credentials['phone_imei']) {
            Auth::logout();

            return [
                'success' => false,
                'message' => 'Device not recognized. Please use your registered device.',
                'code' => 403,
            ];
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'user_level' => $user->user_level
            ],
        ];
    }

    /**
     * Revoke the user's current access token.
     *
     * @param User $user
     * @return void
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
