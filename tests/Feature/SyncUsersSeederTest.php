<?php

namespace Tests\Feature;

use App\Models\CmsUser;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SyncUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class SyncUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Creates roles 1-4 and companies 1-2 with fixed ids. The seeder refers to these ids, and
     * MySQL does not reset AUTO_INCREMENT when RefreshDatabase rolls back, so the seeders
     * cannot be relied on to produce them.
     */
    private function seedReferenceData(): void
    {
        foreach ([1, 2, 3, 4] as $id) {
            Role::query()->forceCreate(['id' => $id, 'name' => "Role {$id}"]);
        }

        foreach ([1, 2] as $id) {
            Company::query()->forceCreate(['id' => $id, 'name' => "Company {$id}"]);
        }
    }

    public function test_sync_creates_all_seeded_users_and_is_idempotent(): void
    {
        $this->seedReferenceData();

        $this->seed(SyncUsersSeeder::class);

        $this->assertSame(18, CmsUser::count());
        $this->assertSame(18, User::count());

        $this->seed(SyncUsersSeeder::class);

        $this->assertSame(18, CmsUser::count());
        $this->assertSame(18, User::count());
    }

    public function test_sync_links_each_login_to_its_profile_by_name(): void
    {
        $this->seedReferenceData();

        $this->seed(SyncUsersSeeder::class);

        $login = User::where('username', 'eddiediaz')->firstOrFail();
        $profile = CmsUser::findOrFail($login->cms_user_id);

        $this->assertSame('EDDIE DIAZ', $profile->name);
        $this->assertSame(2, $profile->role_id);
        $this->assertSame(1, $profile->company_id);
        $this->assertSame(2, $profile->level);
        $this->assertSame('22a617969c4cc223', $login->phone_imei);
    }

    public function test_sync_does_not_change_existing_users(): void
    {
        $this->seedReferenceData();

        $existingProfile = CmsUser::query()->forceCreate([
            'name' => 'AYALA',
            'role_id' => 4,
            'company_id' => 2,
            'level' => 4,
            'psgc_code' => '999',
        ]);

        User::query()->forceCreate([
            'username' => 'ayala',
            'password' => Hash::make('changed-by-someone'),
            'cms_user_id' => $existingProfile->id,
            'phone_imei' => 'device-on-render',
        ]);

        $this->seed(SyncUsersSeeder::class);

        $login = User::where('username', 'ayala')->firstOrFail();
        $this->assertSame('device-on-render', $login->phone_imei);
        $this->assertTrue(Hash::check('changed-by-someone', $login->password));
        $this->assertSame($existingProfile->id, $login->cms_user_id);

        $profile = CmsUser::findOrFail($existingProfile->id);
        $this->assertSame(4, $profile->role_id);
        $this->assertSame(2, $profile->company_id);
        $this->assertSame('999', $profile->psgc_code);

        $this->assertSame(1, CmsUser::where('name', 'AYALA')->count());
        $this->assertSame(1, User::where('username', 'ayala')->count());
    }

    public function test_sync_does_not_recreate_soft_deleted_users(): void
    {
        $this->seedReferenceData();

        $this->seed(SyncUsersSeeder::class);
        User::where('username', 'shaw')->firstOrFail()->delete();

        $this->seed(SyncUsersSeeder::class);

        $this->assertSame(1, User::onlyTrashed()->where('username', 'shaw')->count());
        $this->assertSame(1, User::withTrashed()->where('username', 'shaw')->count());
    }

    public function test_sync_aborts_when_companies_or_roles_are_missing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('companies and roles must be seeded first');

        $this->seed(SyncUsersSeeder::class);
    }
}
