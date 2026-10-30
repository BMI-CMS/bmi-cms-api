<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CmsUser;

class CmsUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::records() as $user) {
            CmsUser::create([
                'name' => $user['name'],
                'role_id' => $user['role_id'],
                'company_id' => $user['company'],
                'level' => $user['level'],
                'psgc_code' => $user['psgc_code'],
            ]);
        };
    }

    /**
     * The user list. SyncUsersSeeder and UserSeeder read it too.
     * UserSeeder's cms_user_id refers to the 1-based position in this list.
     *
     * @return list<array{name: string, company: int, role_id: int, level: int, psgc_code: string}>
     */
    public static function records(): array
    {
        return [
            ['name' => 'EVAN BUCKLEY CC', 'company' => 1, 'role_id' => 1, 'level' => 1, 'psgc_code' => '137404000'],
            ['name' => 'EVAN BUCKLEY CH', 'company' => 1, 'role_id' => 6, 'level' => 2, 'psgc_code' => '137404000'],
            ['name' => 'EVAN BUCKLEY AM', 'company' => 1, 'role_id' => 8, 'level' => 3, 'psgc_code' => '137404000'],
            ['name' => 'EVAN BUCKLEY GH', 'company' => 1, 'role_id' => 10, 'level' => 4, 'psgc_code' => '137404000'],

            ['name' => 'EVAN BUCKLEY RS', 'company' => 2, 'role_id' => 12, 'level' => 1, 'psgc_code' => '137404000'],
            ['name' => 'EVAN BUCKLEY RO', 'company' => 2, 'role_id' => 13, 'level' => 2, 'psgc_code' => '137404000'],
            ['name' => 'EVAN BUCKLEY ARM', 'company' => 2, 'role_id' => 14, 'level' => 3, 'psgc_code' => '137404000'],
            ['name' => 'EVAN BUCKLEY DH', 'company' => 2, 'role_id' => 15, 'level' => 4, 'psgc_code' => '137404000'],

            ['name' => 'EDDIE DIAZ CC', 'company' => 1, 'role_id' => 1, 'level' => 1, 'psgc_code' => '137404000'],
            ['name' => 'EDDIE DIAZ CH', 'company' => 1, 'role_id' => 6, 'level' => 2, 'psgc_code' => '137404000'],
            ['name' => 'EDDIE DIAZ AM', 'company' => 1, 'role_id' => 8, 'level' => 3, 'psgc_code' => '137404000'],
            ['name' => 'EDDIE DIAZ GH', 'company' => 1, 'role_id' => 10, 'level' => 4, 'psgc_code' => '137404000'],

            ['name' => 'EDDIE DIAZ RS', 'company' => 2, 'role_id' => 12, 'level' => 1, 'psgc_code' => '137404000'],
            ['name' => 'EDDIE DIAZ RO', 'company' => 2, 'role_id' => 13, 'level' => 2, 'psgc_code' => '137404000'],
            ['name' => 'EDDIE DIAZ ARM', 'company' => 2, 'role_id' => 14, 'level' => 3, 'psgc_code' => '137404000'],
            ['name' => 'EDDIE DIAZ DH', 'company' => 2, 'role_id' => 15, 'level' => 4, 'psgc_code' => '137404000'],

            ['name' => 'EMMAN CC', 'company' => 1, 'role_id' => 1, 'level' => 1, 'psgc_code' => '137404000'],
            ['name' => 'EMMAN CH', 'company' => 1, 'role_id' => 6, 'level' => 2, 'psgc_code' => '137404000'],
            ['name' => 'EMMAN AM', 'company' => 1, 'role_id' => 8, 'level' => 3, 'psgc_code' => '137404000'],
            ['name' => 'EMMAN GH', 'company' => 1, 'role_id' => 10, 'level' => 4, 'psgc_code' => '137404000'],

            ['name' => 'EMMAN RS', 'company' => 2, 'role_id' => 12, 'level' => 1, 'psgc_code' => '137404000'],
            ['name' => 'EMMAN RO', 'company' => 2, 'role_id' => 13, 'level' => 2, 'psgc_code' => '137404000'],
            ['name' => 'EMMAN ARM', 'company' => 2, 'role_id' => 14, 'level' => 3, 'psgc_code' => '137404000'],
            ['name' => 'EMMAN DH', 'company' => 2, 'role_id' => 15, 'level' => 4, 'psgc_code' => '137404000'],

            ['name' => 'JAYREN CC', 'company' => 1, 'role_id' => 3, 'level' => 3, 'psgc_code' => '137404000'],
            ['name' => 'JAYREN CH', 'company' => 1, 'role_id' => 4, 'level' => 4, 'psgc_code' => '1234567890'],
        ];
    }
}
