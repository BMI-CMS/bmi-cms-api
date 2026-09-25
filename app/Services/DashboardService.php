<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Account;
use App\Repositories\DashboardRepository;

class DashboardService
{
    public function __construct(
        protected DashboardRepository $dashboardRepository
    ) {}
    public function getSummary()
    {
        return $this->dashboardRepository->getAccountPerformance();
    }
}
