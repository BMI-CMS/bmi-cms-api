<?php

namespace App\Repositories;

use App\Models\IntegrationInbox;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class IntegrationInboxRepository
{
    public function register(array $data): IntegrationInbox
    {
        try {

            return DB::transaction(function () use ($data) {

                return IntegrationInbox::create($data);
            });
        } catch (UniqueConstraintViolationException $exception) {

            $inbox = IntegrationInbox::query()
                ->where('source_system', $data['source_system'])
                ->where('idempotency_key', $data['idempotency_key'])
                ->lockForUpdate()
                ->first();

            if (!$inbox) {
                throw $exception;
            }

            return $inbox;
        }
    }

    public function markAsProcessed(
        IntegrationInbox $inbox,
        array $response
    ): void {

        $inbox->update([
            'response' => $response,
            'processed_at' => now(),
        ]);
    }
}
