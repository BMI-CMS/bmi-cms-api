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
            ['name' => 'CHINKEE TAN',        'company' => 1, 'role_id' => 1, 'level' => 1, 'psgc_code' => '137404000'],
            ['name' => 'HENRY SY',           'company' => 1, 'role_id' => 1, 'level' => 1, 'psgc_code' => '1234567890'],
            ['name' => 'MIKE MORALES',       'company' => 1, 'role_id' => 1, 'level' => 1, 'psgc_code' => '1234567890'],
            ['name' => 'AYALA',              'company' => 1, 'role_id' => 1, 'level' => 1, 'psgc_code' => '1234567890'],
            ['name' => 'EDSA',               'company' => 1, 'role_id' => 1, 'level' => 1, 'psgc_code' => '1234567890'],
            ['name' => 'CATHERINE DE JESUS', 'company' => 1, 'role_id' => 2, 'level' => 2, 'psgc_code' => '137404000'],
            ['name' => 'PEDRO GIL',          'company' => 1, 'role_id' => 3, 'level' => 3, 'psgc_code' => '137404000'],
            ['name' => 'BACLARAN',           'company' => 1, 'role_id' => 4, 'level' => 4, 'psgc_code' => '1234567890'],
            ['name' => 'SHAW',               'company' => 2, 'role_id' => 1, 'level' => 1, 'psgc_code' => '1234567890'],
            ['name' => 'BONI',               'company' => 2, 'role_id' => 1, 'level' => 1, 'psgc_code' => '1234567890'],
            ['name' => 'ASIA WORLD',         'company' => 2, 'role_id' => 1, 'level' => 1, 'psgc_code' => '1234567890'],
            ['name' => 'LIBERTAD',           'company' => 2, 'role_id' => 1, 'level' => 1, 'psgc_code' => '1234567890'],
            ['name' => 'ORTIGAS',            'company' => 2, 'role_id' => 1, 'level' => 1, 'psgc_code' => '1234567890'],
            ['name' => 'GIL PUYAT',          'company' => 2, 'role_id' => 2, 'level' => 2, 'psgc_code' => '1234567890'],
            ['name' => 'TAFT AVENUE',        'company' => 2, 'role_id' => 3, 'level' => 3, 'psgc_code' => '1234567890'],
            ['name' => 'MIA ROAD',           'company' => 2, 'role_id' => 4, 'level' => 4, 'psgc_code' => '1234567890'],
            ['name' => 'EVAN BUCKLEY',        'company' => 1, 'role_id' => 1, 'level' => 1, 'psgc_code' => '137404000'],
            ['name' => 'EDDIE DIAZ', 'company' => 1, 'role_id' => 2, 'level' => 2, 'psgc_code' => '137404000'],
        ];
    }
}
