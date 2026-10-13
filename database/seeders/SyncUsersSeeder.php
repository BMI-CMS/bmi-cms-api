<?php

namespace Database\Seeders;

use App\Models\CmsUser;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Adds the users from CmsUserSeeder and UserSeeder that do not exist yet.
 *
 * - Profiles are matched by name, logins by username (soft-deleted rows count as existing).
 * - Existing rows are never changed: passwords, phone_imei and profile fields stay as they are.
 * - Logins take their profile from the position in CmsUserSeeder::records(), so the ids on
 *   Render do not need to match the seeder.
 * - Runs in one transaction. Safe to rerun.
 *
 * Run with RUN_USER_SYNC=true on Render (see scripts/00-laravel-deploy.sh), then set it back to false.
 */
class SyncUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! Company::query()->exists() || ! Role::query()->exists()) {
            throw new RuntimeException('SyncUsersSeeder aborted: companies and roles must be seeded first.');
        }

        DB::transaction(function (): void {
            [$profileIdsByName, $profileNamesByPosition] = $this->syncProfiles();
            $this->syncLogins($profileIdsByName, $profileNamesByPosition);
        });
    }

    /**
     * @return array{0: array<string, int>, 1: list<string>}
     */
    private function syncProfiles(): array
    {
        $created = 0;
        $skipped = 0;
        $idsByName = [];
        $namesByPosition = [];

        foreach (CmsUserSeeder::records() as $record) {
            $namesByPosition[] = $record['name'];

            $profile = CmsUser::withTrashed()->where('name', $record['name'])->first();

            if ($profile) {
                $skipped++;
            } else {
                $profile = CmsUser::query()->forceCreate([
                    'name' => $record['name'],
                    'role_id' => $record['role_id'],
                    'company_id' => $record['company'],
                    'level' => $record['level'],
                    'psgc_code' => $record['psgc_code'],
                ]);
                $created++;
            }

            $idsByName[$record['name']] = $profile->id;
        }

        $this->command?->info("Profiles: {$created} created, {$skipped} skipped.");

        return [$idsByName, $namesByPosition];
    }

    /**
     * @param  array<string, int>  $profileIdsByName
     * @param  list<string>  $profileNamesByPosition
     */
    private function syncLogins(array $profileIdsByName, array $profileNamesByPosition): void
    {
        $created = 0;
        $skipped = 0;

        foreach (UserSeeder::records() as $record) {
            $profileName = $profileNamesByPosition[$record['cms_user_id'] - 1] ?? null;

            if ($profileName === null) {
                throw new RuntimeException("SyncUsersSeeder aborted: login '{$record['username']}' points to a missing profile position.");
            }

            if (User::withTrashed()->where('username', $record['username'])->exists()) {
                $skipped++;

                continue;
            }

            User::query()->forceCreate([
                'username' => $record['username'],
                'password' => Hash::make($record['password']),
                'cms_user_id' => $profileIdsByName[$profileName],
                'phone_imei' => $record['phone_imei'],
            ]);
            $created++;
        }

        $this->command?->info("Logins: {$created} created, {$skipped} skipped.");
    }
}
