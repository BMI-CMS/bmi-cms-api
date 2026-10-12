<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Sets the password of every existing user to password123 (the development password).
 *
 * Safe to rerun. It only updates the password column; no users are created or removed.
 * Run with RESET_USER_PASSWORDS=true on Render (see scripts/00-laravel-deploy.sh),
 * then set the variable back to false.
 */
class ResetUserPasswordsSeeder extends Seeder
{
    private const PASSWORD = 'password123';

    public function run(): void
    {
        $count = DB::transaction(function (): int {
            $count = 0;

            // The User model hashes the password through its 'hashed' cast.
            foreach (User::query()->get() as $user) {
                $user->password = self::PASSWORD;
                $user->save();
                $count++;
            }

            return $count;
        });

        $this->command?->info("Reset the password of {$count} user(s).");
    }
}
