<?php

namespace App\Services;

use App\Repositories\CollectionRepository;

class CollectionService
{
    protected CollectionRepository $collectionRepository;

    public function getCollections(int $userId): array
    {
        $getUndepositedPayments = $this->collectionRepository->getUndepositedPayments($userId);

        return $getUndepositedPayments;
    }
}
