<?php

namespace App\Console\Commands;

use App\Jobs\ProcessIntegrationJob;
use App\Repositories\IntegrationOutboxRepository;
use Illuminate\Console\Command;

class RecoverIntegrationOutbox extends Command
{
    protected $signature = 'integration:recover-outbox';

    protected $description =
    'Redispatch eligible outbound integration events';

    public function handle(
        IntegrationOutboxRepository $repository
    ): int {

        $repository->markExpiredAttempts();

        $ids = $repository->eligibleQuery()
            ->orderBy('id')
            ->limit(
                config('integrations.retry.batch_size', 50)
            )
            ->pluck('id');

        foreach ($ids as $id) {

            ProcessIntegrationJob::dispatch((int) $id)
                ->onQueue('integrations');
        }

        $this->info(
            'Recovery scan completed; candidates: '
                . $ids->count()
        );

        return self::SUCCESS;
    }
}
