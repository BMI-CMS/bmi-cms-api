<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Re-seeds the Visit Plan accounts (Faker customer names) and their contact information.
 *
 * - AccountSeeder matches accounts by account_number and overwrites every seeded column.
 * - ContactInformationSeeder keeps exactly one contact row per account.
 * - Runs in one transaction. Safe to rerun.
 *
 * Run with RUN_ACCOUNT_SYNC=true on Render (see scripts/00-laravel-deploy.sh), then set it back to false.
 */
class SyncAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (! Company::query()->exists() || ! Role::query()->exists()) {
            throw new RuntimeException('SyncAccountsSeeder aborted: companies and roles must be seeded first.');
        }

        DB::transaction(function (): void {
            $this->call([
                AccountSeeder::class,
                ContactInformationSeeder::class,
            ]);
        });
    }
}
