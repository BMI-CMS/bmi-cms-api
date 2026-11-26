<?php

namespace App\Repositories;

use App\Models\IntegrationOutbox;
use App\Enums\Integration\IntegrationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IntegrationOutboxRepository
{
    public function create(array $data): IntegrationOutbox
    {
        return IntegrationOutbox::create($data);
    }

    public function claimById(int $id): ?IntegrationOutbox
    {
        return DB::transaction(function () use ($id) {

            $outbox = $this->eligibleQuery()
                ->where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$outbox) {
                return null;
            }

            $outbox->update([
                'status' => IntegrationStatus::PROCESSING,
                'attempts' => $outbox->attempts + 1,
                'processing_token' => (string) Str::uuid(),
                'locked_until' => now()->addSeconds(
                    config('integrations.retry.lease_seconds', 120)
                ),
            ]);

            return $outbox;
        });
    }

    public function eligibleQuery(): Builder
    {
        return IntegrationOutbox::query()
            ->whereColumn('attempts', '<', 'max_attempts')
            ->where(function ($query) {

                $query->where(function ($query) {

                    $query->whereIn('status', [
                        IntegrationStatus::PENDING->value,
                        IntegrationStatus::FAILED->value,
                    ])
                        ->where('next_retry_at', '<=', now());
                })->orWhere(function ($query) {

                    $query->where(
                        'status',
                        IntegrationStatus::PROCESSING->value
                    )
                        ->where('locked_until', '<=', now());
                });
            });
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
                'last_error' =>
                'Processing lease expired on final attempt.',
                'processing_token' => null,
                'locked_until' => null,
                'next_retry_at' => null,
                'updated_at' => now(),
            ]);
    }
}
