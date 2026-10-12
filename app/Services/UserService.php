<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAttestation;

class UserService
{
    /**
     * Record an attestation event in the audit trail using the authenticated User model.
     *
     * @param array<string, mixed> $validated
     * @param User $user
     * @return array{is_attested: bool, message: string}
     */
    public function attestation(array $validated, User $user): array
    {
        UserAttestation::create([
            'user_id' => $user->id,
            'is_attested' => (bool) $validated['is_attested'],
        ]);

        return [
            'is_attested' => (bool) $validated['is_attested'],
            'message' => $validated['is_attested'] ? 'Attestation Accepted.' : 'Attestation Declined.',
        ];
    }
}
