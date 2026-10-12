<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds the Render (production) database so the auth, user and dashboard routes return data.
 *
 * - Runs the same seeders as DatabaseSeeder, but only once: if companies already exist the
 *   seeding is skipped, because most seeders use create() and would duplicate rows.
 * - Replaces the shared development password of every seeded login with a random one.
 *   The new passwords are printed once to the seeder output (the deploy log). Save them then.
 * - Runs in one transaction: a failure leaves the database as it was.
 *
 * Run with RUN_SEEDERS=true on Render (see scripts/00-laravel-deploy.sh), then set it back to false.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        if (Company::query()->exists()) {
            $this->command?->warn('ProductionSeeder skipped: companies already exist. Seeding runs only once.');

            return;
        }

        $credentials = DB::transaction(function (): array {
            $this->call([
                CompanySeeder::class,
                RoleSeeder::class,
                CmsUserSeeder::class,
                AccountClassificationRuleSeeder::class,
                AccountSeeder::class,
                UserSeeder::class,
                ContactRecordingSeeder::class,
                CustomerDetailSeeder::class,
                CollectionAddressSeeder::class,
            ]);

            // UserSeeder logs in with the shared development password. Replace it for every user.
            $credentials = [];
            foreach (User::query()->orderBy('id')->get() as $user) {
                $plain = Str::password(16);
                $user->password = $plain;
                $user->save();
                $credentials[$user->username] = $plain;
            }

            return $credentials;
        });

        $this->command?->warn('Seeded login credentials (shown once, save them now):');
        foreach ($credentials as $username => $password) {
            $this->command?->line("  {$username}: {$password}");
        }
    }
}
