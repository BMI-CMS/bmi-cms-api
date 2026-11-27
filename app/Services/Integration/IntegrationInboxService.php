<?php

namespace App\Services\Integration;

use App\Enums\Integration\IntegrationSystem;
use App\Enums\Integration\IntegrationEvent;
use App\Repositories\IntegrationInboxRepository;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Closure;
use LogicException;

class IntegrationInboxService
{
    public function __construct(
        private IntegrationInboxRepository $repository
    ) {}

    public function process(
        IntegrationSystem $sourceSystem,
        IntegrationEvent $eventType,
        string $idempotencyKey,
        string $referenceType,
        int $referenceId,
        array $payload,
        Closure $handler
    ): array {

        $payloadHash = $this->generatePayloadHash([
            'event_type' => $eventType->value,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'payload' => $payload,
        ]);

        return DB::transaction(function () use (
            $sourceSystem,
            $eventType,
            $idempotencyKey,
            $referenceType,
            $referenceId,
            $payload,
            $payloadHash,
            $handler
        ) {

            $inbox = $this->repository->register([
                'source_system' => $sourceSystem->value,
                'event_type' => $eventType->value,
                'idempotency_key' => $idempotencyKey,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'payload' => $payload,
                'payload_hash' => $payloadHash,
            ]);

            if (!hash_equals(
                $inbox->payload_hash,
                $payloadHash
            )) {
                throw new ConflictHttpException(
                    'Idempotency key has already been used with a different payload.'
                );
            }

            if ($inbox->processed_at !== null) {

                return $inbox->response;
            }

            if (!$inbox->wasRecentlyCreated) {
                throw new LogicException(
                    'An unprocessed inbox record already exists.'
                );
            }

            $response = $handler();

            $this->repository->markAsProcessed(
                $inbox,
                $response
            );

            return $response;
        }, 3);
    }

    private function generatePayloadHash(array $payload): string
    {
        $normalizedPayload = $this->normalizePayload($payload);

        return hash(
            'sha256',
            json_encode(
                $normalizedPayload,
                JSON_THROW_ON_ERROR |
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES |
                    JSON_PRESERVE_ZERO_FRACTION
            )
        );
    }

    private function normalizePayload(array $payload): array
    {
        if (!array_is_list($payload)) {
            ksort($payload, SORT_STRING);
        }

        foreach ($payload as $key => $value) {

            if (is_array($value)) {
                $payload[$key] = $this->normalizePayload($value);
            }
        }

        return $payload;
    }
}
