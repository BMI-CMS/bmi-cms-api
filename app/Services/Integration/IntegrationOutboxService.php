<?php

namespace App\Services\Integration;

use App\Models\IntegrationOutbox;
use App\Repositories\IntegrationOutboxRepository;
use App\Enums\Integration\IntegrationEvent;
use App\Enums\Integration\IntegrationStatus;
use App\Enums\Integration\IntegrationSystem;
use App\Jobs\ProcessIntegrationJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Bus;
use LogicException;
use Throwable;

class IntegrationOutboxService
{
    public function __construct(
        private IntegrationOutboxRepository $repository
    ) {}

    public function enqueue(
        IntegrationSystem $destinationSystem,
        IntegrationEvent $eventType,
        string $referenceType,
        int $referenceId,
        array $payload
    ): IntegrationOutbox {

        if (DB::transactionLevel() === 0) {
            throw new LogicException(
                'Integration events must be created inside a database transaction.'
            );
        }

        $destination = $destinationSystem->value;
        $event = $eventType->value;

        if (!config("integrations.events.{$destination}.{$event}")) {
            throw new LogicException(
                "Integration event {$event} is not configured for {$destination}."
            );
        }

        $outbox = $this->repository->create([
            'idempotency_key' => (string) Str::uuid(),
            'destination_system' => $destinationSystem,
            'event_type' => $eventType,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'payload' => $payload,
            'status' => IntegrationStatus::PENDING,
            'attempts' => 0,
            'max_attempts' => config(
                'integrations.retry.max_attempts',
                10
            ),
            'next_retry_at' => now(),
        ]);

        $outboxId = $outbox->id;

        DB::afterCommit(function () use ($outboxId) {

            try {

                ProcessIntegrationJob::dispatch($outboxId)
                    ->onQueue('integrations');
            } catch (Throwable $exception) {

                Log::error('Integration job dispatch failed.', [
                    'outbox_id' => $outboxId,
                    'exception_type' => get_class($exception),
                ]);
            }
        });

        return $outbox;
    }
}
