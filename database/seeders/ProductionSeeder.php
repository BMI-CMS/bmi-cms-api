<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        $this->resetEmptyTableSequences();

        DB::transaction(function (): void {
            $this->call([
                CompanySeeder::class,
                RoleSeeder::class,
                CmsUserSeeder::class,
                AccountClassificationRuleSeeder::class,
                AccountSeeder::class,
                ContactInformationSeeder::class,
                UserSeeder::class,
                ContactRecordingSeeder::class,
                CustomerDetailSeeder::class,
                CollectionAddressSeeder::class,
            ]);
        });
    }

    /**
     * PostgreSQL does not roll back auto-increment counters: a failed or rolled-back seeding run
     * leaves every table's counter advanced, so the next run would not start at id 1. The seeders
     * refer to fixed ids (role_id 1, cms_user_id 1, ...), so restart the counter of every table
     * that is still empty. Tables that already hold rows are never touched. Not needed on MySQL.
     */
    private function resetEmptyTableSequences(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (Schema::getTables() as $table) {
            $name = $table['name'];

            if (($table['schema'] ?? 'public') !== 'public'
                || !Schema::hasColumn($name, 'id')
                || DB::table($name)->exists()
            ) {
                continue;
            }

            // Returns NULL (and does nothing) for tables whose id has no sequence, such as sessions.
            DB::statement("SELECT setval(pg_get_serial_sequence(?, 'id'), 1, false)", [$name]);
        }
    }
}
