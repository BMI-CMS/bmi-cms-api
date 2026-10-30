<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Console\Command;

class UnlockUserLogin extends Command
{
    protected $signature = 'user:unlock {username : Username of the locked or frozen account}';

    protected $description = 'Unlock a login that was locked or frozen after failed login attempts';

    public function handle(AuthService $authService): int
    {
        $username = $this->argument('username');
        $user = User::where('username', $username)->first();

        if (!$user) {
            $this->error("User \"{$username}\" was not found.");

            return self::FAILURE;
        }

        if (!$authService->unlockLogin($user)) {
            $this->info("User \"{$username}\" is not locked or frozen. Nothing was changed.");

            return self::SUCCESS;
        }

        $this->info("User \"{$username}\" was unlocked. Failed login attempts were reset to 0.");

        return self::SUCCESS;
    }
}
