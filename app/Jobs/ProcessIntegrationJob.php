<?php

namespace App\Jobs;

use App\Enums\Integration\IntegrationStatus;
use App\Repositories\IntegrationOutboxRepository;
use App\Services\Integration\IntegrationProcessorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Queue\Queueable;

class ProcessIntegrationJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 0;

    public int $timeout = 45;

    public int $uniqueFor = 86400;

    public function __construct(
        public int $outboxId
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->outboxId;
    }

    public function handle(
        IntegrationOutboxRepository $repository,
        IntegrationProcessorService $processor
    ): void {

        $outbox = $repository->claimById($this->outboxId);

        if (!$outbox) {
            return;
        }

        $processor->process($outbox);

        $outbox->refresh();

        if (
            $outbox->status === IntegrationStatus::FAILED &&
            $outbox->attempts < $outbox->max_attempts &&
            $outbox->next_retry_at !== null
        ) {
            $this->release($outbox->next_retry_at);

            return;
        }
    }
}
