<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\UserAttestation;
use App\Observers\UserObserver;
use App\Observers\UserAttestationObserver;
use App\Models\Account;
use App\Observers\AccountObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        UserAttestation::observe(UserAttestationObserver::class);
        Account::observe(AccountObserver::class);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
