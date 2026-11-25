<?php

namespace App\Console\Commands;

use App\Repositories\IntegrationOutboxRepository;
use App\Services\Integration\IntegrationProcessorService;
use Illuminate\Console\Command;

class ProcessIntegrationOutbox extends Command
{
    protected $signature = 'integration:process-outbox';

    protected $description = 'Process pending outbound integration events';

    public function handle(
        IntegrationOutboxRepository $repository,
        IntegrationProcessorService $processor
    ): int {

        $repository->markExpiredAttempts();

        $batchSize = config(
            'integrations.retry.batch_size',
            50
        );

        $processed = 0;

        for ($index = 0; $index < $batchSize; $index++) {

            $outbox = $repository->claimNext();

            if (!$outbox) {
                break;
            }

            $processor->process($outbox);

            $processed++;
        }

        $this->info(
            "Processed {$processed} integration event(s)."
        );

        return self::SUCCESS;
    }
}
