<?php

namespace App\Services\Integration;

use App\Models\IntegrationOutbox;
use App\Repositories\IntegrationOutboxRepository;
use App\Enums\Integration\IntegrationStatus;
use App\Exceptions\Integration\PermanentIntegrationException;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class IntegrationProcessorService
{
    public function __construct(
        private IntegrationOutboxRepository $repository,
        private HmacClientService $hmacClientService
    ) {}

    public function process(IntegrationOutbox $outbox): void
    {
        try {

            $destination = $outbox->destination_system->value;
            $event = $outbox->event_type->value;

            $configuration = config(
                "integrations.events.{$destination}.{$event}"
            );

            if (!$configuration) {
                throw new PermanentIntegrationException(
                    'Integration event configuration not found.'
                );
            }

            $endpoint = str_replace(
                '{id}',
                (string) $outbox->reference_id,
                $configuration['endpoint']
            );

            $response = $this->hmacClientService->send(
                destinationSystem: $outbox->destination_system,
                method: $configuration['method'],
                endpoint: $endpoint,
                payload: $outbox->payload,
                idempotencyKey: $outbox->idempotency_key
            );

            if ($response->successful()) {

                $this->repository->complete($outbox, [
                    'status' => IntegrationStatus::SENT->value,
                    'sent_at' => now(),
                    'last_error' => null,
                    'next_retry_at' => null,
                ]);

                return;
            }

            $statusCode = $response->status();

            if (
                $statusCode >= 500 ||
                in_array($statusCode, [408, 425, 429], true)
            ) {
                throw new RuntimeException(
                    "Temporary integration error: HTTP {$statusCode}"
                );
            }

            throw new PermanentIntegrationException(
                "Integration request rejected: HTTP {$statusCode}"
            );
        } catch (PermanentIntegrationException $exception) {

            $this->repository->complete($outbox, [
                'status' => IntegrationStatus::REJECTED->value,
                'last_error' => $exception->getMessage(),
                'next_retry_at' => null,
            ]);

            Log::warning('Integration request rejected.', [
                'outbox_id' => $outbox->id,
                'destination' => $outbox->destination_system->value,
                'event' => $outbox->event_type->value,
            ]);
        } catch (Throwable $exception) {

            $this->handleFailure($outbox, $exception);
        }
    }

    private function handleFailure(
        IntegrationOutbox $outbox,
        Throwable $exception
    ): void {

        $exhausted = $outbox->attempts >= $outbox->max_attempts;

        $delay = min(
            config('integrations.retry.delay_seconds', 60)
                * (2 ** min($outbox->attempts - 1, 10)),
            config('integrations.retry.max_delay_seconds', 3600)
        );

        $this->repository->complete($outbox, [
            'status' => $exhausted
                ? IntegrationStatus::EXHAUSTED->value
                : IntegrationStatus::FAILED->value,

            'last_error' => 'Integration delivery failed.',

            'next_retry_at' => $exhausted
                ? null
                : now()->addSeconds($delay),
        ]);

        Log::error('Integration delivery failed.', [
            'outbox_id' => $outbox->id,
            'destination' => $outbox->destination_system->value,
            'event' => $outbox->event_type->value,
            'attempts' => $outbox->attempts,
            'exception_type' => get_class($exception),
        ]);
    }
}
