<?php

namespace App\Services;

use App\Constants\Login;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AuthService
{
    /**
     * Authenticate user credentials, verify the registered device IMEI and apply the login lockout.
     *
     * @param array<string, mixed> $credentials
     * @return array{success: bool, message?: string, code?: int, data?: array<string, mixed>}
     */

    public function login(array $credentials): array
    {
        $user = User::where('username', $credentials['username'])->first();

        if ($user && $user->is_permanently_locked) {
            return [
                'success' => false,
                'message' => 'Your account has been permanently locked. Please contact your administrator.',
                'code' => 423,
            ];
        }

        if ($user && $user->locked_until && now()->lt($user->locked_until)) {
            $datetime = Carbon::parse($user->locked_until);
            $currentDate = now();
            $different  = $currentDate->diff($datetime)->i;

            return [
                'success' => false,
                'message' => "Account temporarily locked. Please try again after $different minutes",
                'code' => 423,
            ];
        }

        if ($user && $user->locked_until && now()->gte($user->locked_until)) {
            $user->locked_until = null;
            $user->save();
        }

        $authAttempt = [
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ];

        if (!Auth::attempt($authAttempt)) {
            if ($user) {
                $user->failed_login_attempts++;

                if ($user->failed_login_attempts >= Login::ATTEMPTS_BEFORE_LOCK) {
                    $user->is_permanently_locked = true;
                    $user->locked_until = null;
                    $user->save();

                    return [
                        'success' => false,
                        'message' => 'Your account has been permanently locked. Please contact your administrator.',
                        'code' => 423,
                    ];
                }

                if ($user->failed_login_attempts == Login::ATTEMPTS_BEFORE_FREEZE) {
                    $user->locked_until = now()->addMinutes(Login::FREEZE_MINUTES);
                    $user->save();
                    return [
                        'success' => false,
                        'message' => 'Account temporarily locked. Please try again after 15 minutes',
                        'code' => 423,
                    ];
                }

                $user->save();
            }

            return [
                'success' => false,
                'message' => 'Invalid credentials.',
                'code' => 401,
            ];
        }

        /** @var User $user */
        $user = Auth::user();

        if (empty($user->phone_imei) || $user->phone_imei !== $credentials['phone_imei']) {
            $user->is_permanently_locked = true;
            $user->locked_until = null;
            $user->save();

            Auth::logout();

            return [
                'success' => false,
                'message' => 'Device not recognized. Please contact your supervisor.',
                'code' => 403,
            ];
        }


        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();

        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;

        $user->load([
            'CmsUser:id,name,role_id,company_id,level,psgc_code',
            'CmsUser.company:id,name',
            'CmsUser.role:id,name',
        ]);

        $cms_user = $user->CmsUser;

        return [
            'success' => true,
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $cms_user->name,
                    'username' => $user->username,
                    'role_id' => $cms_user->role_id,
                    'role' => $cms_user->role->name,
                    'company_id' => $cms_user->company_id,
                    'company_name' => $cms_user->company->name,
                    'level' => $cms_user->level,
                    'psgc_code' => $cms_user->psgc_code,
                ],
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
