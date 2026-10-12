<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the Render (production) database so the auth, user and dashboard routes return data.
 *
 * - Runs the same seeders as DatabaseSeeder, but only once: if companies already exist the
 *   seeding is skipped, because most seeders use create() and would duplicate rows.
 * - Seeded logins use the password set by UserSeeder (password123), as requested.
 *   To reset the passwords of users that already exist, see ResetUserPasswordsSeeder.
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

        DB::transaction(function (): void {
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
        });
    }
}
