<?php

namespace App\Repositories;

use App\Models\IntegrationOutbox;
use App\Enums\Integration\IntegrationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class IntegrationOutboxRepository
{
    public function create(array $data): IntegrationOutbox
    {
        return IntegrationOutbox::create($data);
    }

    public function claimNext(?int $id = null): ?IntegrationOutbox
    {
        return DB::transaction(function () use ($id) {

            $outbox = IntegrationOutbox::query()
                ->when($id !== null, function ($query) use ($id) {
                    $query->where('id', $id);
                })
                ->whereColumn('attempts', '<', 'max_attempts')
                ->where(function ($query) {

                    $query->where(function ($query) {
                        $query->whereIn('status', [
                            IntegrationStatus::PENDING->value,
                            IntegrationStatus::FAILED->value,
                        ])
                            ->where('next_retry_at', '<=', now());
                    })

                        ->orWhere(function ($query) {
                            $query->where(
                                'status',
                                IntegrationStatus::PROCESSING->value
                            )
                                ->where('locked_until', '<=', now());
                        });
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (!$outbox) {
                return null;
            }

            if (!$outbox->update([
                'status' => IntegrationStatus::PROCESSING,
                'attempts' => $outbox->attempts + 1,
                'processing_token' => (string) Str::uuid(),
                'locked_until' => now()->addSeconds(
                    config('integrations.retry.lease_seconds', 120)
                ),
            ])) {
                throw new RuntimeException(
                    'Failed to claim integration event.'
                );
            }

            return $outbox;
        });
    }

    public function claimById(int $id): ?IntegrationOutbox
    {
        return $this->claimNext($id);
    }

    public function findById(int $id): IntegrationOutbox
    {
        return IntegrationOutbox::findOrFail($id);
    }

    public function complete(
        IntegrationOutbox $outbox,
        array $attributes
    ): bool {

        $attributes['processing_token'] = null;
        $attributes['locked_until'] = null;
        $attributes['updated_at'] = now();

        return IntegrationOutbox::query()
            ->where('id', $outbox->id)
            ->where(
                'status',
                IntegrationStatus::PROCESSING->value
            )
            ->where(
                'processing_token',
                $outbox->processing_token
            )
            ->update($attributes) === 1;
    }

    public function markExpiredAttempts(): int
    {
        return IntegrationOutbox::query()
            ->where(
                'status',
                IntegrationStatus::PROCESSING->value
            )
            ->where('locked_until', '<=', now())
            ->whereColumn('attempts', '>=', 'max_attempts')
            ->update([
                'status' => IntegrationStatus::EXHAUSTED->value,
                'last_error' => 'Processing lease expired on final attempt.',
                'processing_token' => null,
                'locked_until' => null,
                'next_retry_at' => null,
            ]);
    }
}
