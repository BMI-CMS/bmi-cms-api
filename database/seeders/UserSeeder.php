<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::records() as $user) {
            User::create([
                'username' => $user['username'],
                'password' => Hash::make($user['password']),
                'cms_user_id' => $user['cms_user_id'],
                'phone_imei' => $user['phone_imei'],
            ]);
        }
    }

    /**
     * The login list. SyncUsersSeeder reads it too.
     * cms_user_id is the 1-based position of the profile in CmsUserSeeder::records().
     *
     * @return list<array{username: string, password: string, cms_user_id: int, phone_imei: string}>
     */
    public static function records(): array
    {
        return [
            ['username' => 'chinkeetan',   'password' => 'password123', 'cms_user_id' => 1,  'phone_imei' => '123456789123456'],
            ['username' => 'henrysy',      'password' => 'password123', 'cms_user_id' => 2,  'phone_imei' => '123456789123457'],
            ['username' => 'mikemorales',  'password' => 'password123', 'cms_user_id' => 3,  'phone_imei' => '123456789123458'],
            ['username' => 'ayala',        'password' => 'password123', 'cms_user_id' => 4,  'phone_imei' => '123456789123459'],
            ['username' => 'edsa',         'password' => 'password123', 'cms_user_id' => 5,  'phone_imei' => '123456789123450'],
            ['username' => 'catherine',    'password' => 'password123', 'cms_user_id' => 6,  'phone_imei' => '123456789123456'],
            ['username' => 'pedrogil',     'password' => 'password123', 'cms_user_id' => 7,  'phone_imei' => '223456789123456'],
            ['username' => 'baclaran',     'password' => 'password123', 'cms_user_id' => 8,  'phone_imei' => '323456789123456'],
            ['username' => 'shaw',         'password' => 'password123', 'cms_user_id' => 9,  'phone_imei' => '423456789123456'],
            ['username' => 'boni',         'password' => 'password123', 'cms_user_id' => 10, 'phone_imei' => '523456789123456'],
            ['username' => 'asia',         'password' => 'password123', 'cms_user_id' => 11, 'phone_imei' => '623456789123456'],
            ['username' => 'libertad',     'password' => 'password123', 'cms_user_id' => 12, 'phone_imei' => '723456789123456'],
            ['username' => 'ortigas',      'password' => 'password123', 'cms_user_id' => 13, 'phone_imei' => '823456789123456'],
            ['username' => 'gilpuyat',     'password' => 'password123', 'cms_user_id' => 14, 'phone_imei' => '923456789123456'],
            ['username' => 'taftavenue',   'password' => 'password123', 'cms_user_id' => 15, 'phone_imei' => '133456789123456'],
            ['username' => 'miaroad',      'password' => 'password123', 'cms_user_id' => 16, 'phone_imei' => '143456789123456'],
            ['username' => 'evanbuckley',  'password' => 'Test@123',    'cms_user_id' => 17, 'phone_imei' => 'ca98ad9edaaa9f75'],
            ['username' => 'eddiediaz',    'password' => 'Pass@123',    'cms_user_id' => 18, 'phone_imei' => '22a617969c4cc223'],
        ];
    }
}
