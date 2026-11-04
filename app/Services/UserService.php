<?php

namespace App\Services;

use App\Models\UserAttestation;

class UserService
{
    /**
     * Record an attestation event in the audit trail using the authenticated User model.
     *
     * @param array<string, mixed> $validated
     * @return array{is_attested: bool, message: string}
     */
    public function attestation(array $validated, int $userId): array
    {
        UserAttestation::create([
            'user_id' => $userId,
            'is_attested' => (bool) $validated['is_attested'],
        ]);

        return [
            'is_attested' => (bool) $validated['is_attested'],
            'message' => $validated['is_attested'] ? 'Attestation Accepted.' : 'Attestation Declined.',
        ];
    }
}
