<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\UserAttestation;
use Illuminate\Support\Facades\Auth;

class UserAttestationObserver
{
    public function created(UserAttestation $userAttestation): void
    {
        $this->logActivity(
            userAttestation: $userAttestation,
            event: 'created',
            newValues: $userAttestation->getAttributes()
        );
    }

    private function logActivity(
        UserAttestation $userAttestation,
        string $event,
        array $oldValues = [],
        array $newValues = []
    ): void {
        $authUser = Auth::user();

        ActivityLog::create([
            'user_id' => $authUser?->id,
            'device_id' => request()->input('device_id'),
            'ip_address' => request()->ip(),
            'subject_type' => $userAttestation::class,
            'row_id' => $userAttestation->getKey(),
            'event' => $event,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
        ]);
    }
}
