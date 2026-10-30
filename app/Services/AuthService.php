<?php

namespace App\Services;

use App\Constants\Constants;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Authenticate user credentials, verify the registered device IMEI and apply the login lockout.
     *
     * @param array<string, mixed> $credentials
     * @param string|null $ipAddress
     * @return array{success: bool, message?: string, code?: int, data?: array<string, mixed>}
     */
    public function login(array $credentials, ?string $ipAddress = null): array
    {
        $account = User::where('username', $credentials['username'])->first();

        if ($account && ($blocked = $this->blockedResult($account))) {
            return $blocked;
        }

        $authAttempt = [
            'username' => $credentials['username'],
            'password' => $credentials['password'],
        ];

        if (!Auth::attempt($authAttempt)) {
            $lockout = $account
                ? $this->recordFailedAttempt($account, $ipAddress, $credentials['phone_imei'])
                : null;

            return $lockout ?? [
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

        $this->clearFailedAttempts($user);

        $user->tokens()->delete();

        $token = $user->createToken('auth-token')->plainTextToken;


        $user->load([
            'CmsUser:id,name,role_id,company_id,level,psgc_code',
            'CmsUser.company:id,name',
            'CmsUser.role:id,name'
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
                    'psgc_code' => $cms_user->psgc_code
                ]
            ]
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

    /**
     * Unlock a locked or frozen login and reset its failed attempts.
     *
     * @param User $user
     * @return bool
     */
    public function unlockLogin(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->locked_at === null && !$user->login_frozen_until?->isFuture()) {
                return false;
            }

            $oldValues = $this->lockoutState($user);

            $user->forceFill([
                'failed_login_attempts' => 0,
                'login_frozen_until' => null,
                'locked_at' => null,
            ])->save();

            $this->writeAuditLog('login_unlocked', $user, $oldValues);

            return true;
        });
    }

    /**
     * Return the failed result for a locked or frozen account, or null.
     *
     * @param User $user
     * @return array{success: bool, message: string, code: int, data?: array<string, mixed>}|null
     */
    private function blockedResult(User $user): ?array
    {
        if ($user->locked_at !== null) {
            return [
                'success' => false,
                'message' => 'Your account is locked due to too many failed login attempts. Please contact your administrator.',
                'code' => 423,
            ];
        }

        if ($user->login_frozen_until?->isFuture()) {
            $retryAfter = (int) ceil(now()->diffInSeconds($user->login_frozen_until, true));
            $minutes = (int) ceil($retryAfter / 60);

            return [
                'success' => false,
                'message' => "Too many failed login attempts. Please try again in {$minutes} " . Str::plural('minute', $minutes) . '.',
                'code' => 429,
                'data' => ['retry_after' => $retryAfter],
            ];
        }

        return null;
    }

    /**
     * Count a wrong password and freeze or lock the account when a limit is reached.
     *
     * @param User $user
     * @param string|null $ipAddress
     * @param string|null $deviceId
     * @return array{success: bool, message: string, code: int, data?: array<string, mixed>}|null
     */
    private function recordFailedAttempt(User $user, ?string $ipAddress, ?string $deviceId): ?array
    {
        return DB::transaction(function () use ($user, $ipAddress, $deviceId) {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($blocked = $this->blockedResult($user)) {
                return $blocked;
            }

            $oldValues = $this->lockoutState($user);
            $attempts = $user->failed_login_attempts + 1;
            $action = null;

            $user->failed_login_attempts = $attempts;

            if ($attempts >= Constants::LOGIN_ATTEMPTS_BEFORE_FREEZE + Constants::LOGIN_ATTEMPTS_AFTER_FREEZE) {
                $user->locked_at = now();
                $user->login_frozen_until = null;
                $action = 'login_locked';
            } elseif ($attempts === Constants::LOGIN_ATTEMPTS_BEFORE_FREEZE) {
                $user->login_frozen_until = now()->addMinutes(Constants::LOGIN_FREEZE_MINUTES);
                $action = 'login_frozen';
            }

            $user->save();

            if ($action === null) {
                return null;
            }

            $this->writeAuditLog($action, $user, $oldValues, $user->id, $ipAddress, $deviceId);

            return $this->blockedResult($user);
        });
    }

    /**
     * Reset the failed login attempts after a successful login.
     *
     * @param User $user
     * @return void
     */
    private function clearFailedAttempts(User $user): void
    {
        if (!$user->failed_login_attempts && $user->login_frozen_until === null) {
            return;
        }

        $user->forceFill([
            'failed_login_attempts' => 0,
            'login_frozen_until' => null,
        ])->save();
    }

    /**
     * Get the account's lockout columns for the audit log.
     *
     * @param User $user
     * @return array{failed_login_attempts: int, login_frozen_until: string|null, locked_at: string|null}
     */
    private function lockoutState(User $user): array
    {
        return [
            'failed_login_attempts' => (int) $user->failed_login_attempts,
            'login_frozen_until' => $user->login_frozen_until?->toDateTimeString(),
            'locked_at' => $user->locked_at?->toDateTimeString(),
        ];
    }

    /**
     * Write a login lockout event to the audit log.
     *
     * @param string $action
     * @param User $user
     * @param array<string, mixed> $oldValues
     * @param int|null $actorId
     * @param string|null $ipAddress
     * @param string|null $deviceId
     * @return void
     */
    private function writeAuditLog(
        string $action,
        User $user,
        array $oldValues,
        ?int $actorId = null,
        ?string $ipAddress = null,
        ?string $deviceId = null
    ): void {
        AuditLog::create([
            'user_id' => $actorId,
            'device_id' => $deviceId !== null ? mb_substr($deviceId, 0, 255) : null,
            'ip_address' => $ipAddress,
            'action' => $action,
            'table_name' => $user->getTable(),
            'record_id' => $user->id,
            'old_values' => $oldValues,
            'new_values' => $this->lockoutState($user),
        ]);
    }
}
