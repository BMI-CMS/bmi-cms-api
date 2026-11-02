<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;


class ManageLockedAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:permanently-locked  
                            {--unlock= : Unlock an account by user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List and manage permanently locked users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('unlock') !== null) {
            return $this->unlockAccount((int) $this->option('unlock'));
        }

        $users = User::query()
            ->where('is_permanently_locked', true)
            ->orderBy('id')
            ->get([
                'id',
                'username',
                'failed_login_attempts',
            ]);

        if ($users->isEmpty()) {
            $this->info('No permanently locked accounts found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Username', 'Failed Attempts'],
            $users->map(fn(User $user) => [
                $user->id,
                $user->username,
                $user->failed_login_attempts,
            ])->all()
        );

        $this->info(
            'Total permanently locked accounts: ' . $users->count()
        );

        return self::SUCCESS;
    }

    private function unlockAccount(int $id): int
    {
        $user = User::query()
            ->where('id', $id)
            ->where('is_permanently_locked', true)
            ->first();

        if (!$user) {
            $this->error(
                "Permanently locked account with ID {$id} was not found."
            );

            return self::FAILURE;
        }

        $this->table(
            ['ID', 'Username', 'Failed Attempts'],
            [[
                $user->id,
                $user->username,
                $user->failed_login_attempts,
            ]]
        );

        if (!$this->confirm('Unlock this permanently locked account?')) {
            $this->warn('Unlock cancelled.');

            return self::SUCCESS;
        }

        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->is_permanently_locked = false;
        $user->save();

        $this->info("Account ID {$id} has been unlocked successfully.");

        return self::SUCCESS;
    }
}
