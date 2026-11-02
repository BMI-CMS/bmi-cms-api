<?php

namespace App\Observers;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class UserObserver
{
    private const EXCLUDED_FIELDS = [
        'password'
    ];

    public function created(User $user): void
    {
        $this->logActivity(
            user: $user,
            event: 'created',
            newValues: $user->getAttributes()
        );
    }

    public function updated(User $user): void
    {
        $changes = $user->getChanges();

        $oldValues = Arr::only(
            $user->getRawOriginal(),
            array_keys($changes)
        );

        $event = 'updated';

        // Check whether the account was permanently locked.
        if (
            $user->is_permanently_locked &&
            !($oldValues['is_permanently_locked'] ?? false)
        ) {
            $event = 'user_permanently_locked';
        }

        // Check whether the account was temporarily locked.
        elseif (
            $user->locked_until &&
            !$user->is_permanently_locked &&
            empty($oldValues['locked_until'])
        ) {
            $event = 'user_temporarily_locked';
        }

        // Check whether the account was unlocked.
        elseif (
            !$user->is_permanently_locked &&
            is_null($user->locked_until) &&
            (
                ($oldValues['is_permanently_locked'] ?? false) ||
                !empty($oldValues['locked_until'])
            )
        ) {
            $event = 'user_unlocked';
        }

        $this->logActivity(
            user: $user,
            event: $event,
            oldValues: $oldValues,
            newValues: $changes
        );
    }

    public function deleted(User $user): void
    {
        $this->logActivity(
            user: $user,
            event: 'deleted',
            oldValues: $user->getAttributes()
        );
    }

    private function logActivity(
        User $user,
        string $event,
        array $oldValues = [],
        array $newValues = []
    ): void {
        $authUser = Auth::user();

        $oldValues = Arr::except(
            $oldValues,
            self::EXCLUDED_FIELDS
        );

        $newValues = Arr::except(
            $newValues,
            self::EXCLUDED_FIELDS
        );

        ActivityLog::create([
            'user_id' => $authUser?->id,
            'device_id' => request()->input('device_id'),
            'ip_address' => request()->ip(),
            'subject_type' => $user::class,
            'row_id' => $user->getKey(),
            'event' => $event,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
        ]);
    }
}
