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
            ['username' => 'evanbuckleycc', 'password' => 'Test@123', 'cms_user_id' => 1, 'phone_imei' => 'ca98ad9edaaa9f75'],
            ['username' => 'evanbuckleych', 'password' => 'Test@123', 'cms_user_id' => 2, 'phone_imei' => 'ca98ad9edaaa9f75'],
            ['username' => 'evanbuckleyam', 'password' => 'Test@123', 'cms_user_id' => 3, 'phone_imei' => 'ca98ad9edaaa9f75'],
            ['username' => 'evanbuckleygh', 'password' => 'Test@123', 'cms_user_id' => 4, 'phone_imei' => 'ca98ad9edaaa9f75'],

            ['username' => 'evanbuckleyrs', 'password' => 'Test@123', 'cms_user_id' => 5, 'phone_imei' => 'ca98ad9edaaa9f75'],
            ['username' => 'evanbuckleyro', 'password' => 'Test@123', 'cms_user_id' => 6, 'phone_imei' => 'ca98ad9edaaa9f75'],
            ['username' => 'evanbuckleyarm', 'password' => 'Test@123', 'cms_user_id' => 7, 'phone_imei' => 'ca98ad9edaaa9f75'],
            ['username' => 'evanbuckleydh', 'password' => 'Test@123', 'cms_user_id' => 8, 'phone_imei' => 'ca98ad9edaaa9f75'],

            ['username' => 'eddiediazcc', 'password' => 'Pass@123', 'cms_user_id' => 9, 'phone_imei' => '22a617969c4cc223'],
            ['username' => 'eddiediazch', 'password' => 'Pass@123', 'cms_user_id' => 10, 'phone_imei' => '22a617969c4cc223'],
            ['username' => 'eddiediazam', 'password' => 'Pass@123', 'cms_user_id' => 11, 'phone_imei' => '22a617969c4cc223'],
            ['username' => 'eddiediazgh', 'password' => 'Pass@123', 'cms_user_id' => 12, 'phone_imei' => '22a617969c4cc223'],

            ['username' => 'eddiediazrs', 'password' => 'Pass@123', 'cms_user_id' => 13, 'phone_imei' => '22a617969c4cc223'],
            ['username' => 'eddiediazro', 'password' => 'Pass@123', 'cms_user_id' => 14, 'phone_imei' => '22a617969c4cc223'],
            ['username' => 'eddiediazarm', 'password' => 'Pass@123', 'cms_user_id' => 15, 'phone_imei' => '22a617969c4cc223'],
            ['username' => 'eddiediazdh', 'password' => 'Pass@123', 'cms_user_id' => 16, 'phone_imei' => '22a617969c4cc223'],

            ['username' => 'emmancc', 'password' => 'dev123', 'cms_user_id' => 17, 'phone_imei' => '957826f50488decf'],
            ['username' => 'emmanch', 'password' => 'dev123', 'cms_user_id' => 18, 'phone_imei' => '957826f50488decf'],
            ['username' => 'emmanam', 'password' => 'dev123', 'cms_user_id' => 19, 'phone_imei' => '957826f50488decf'],
            ['username' => 'emmangh', 'password' => 'dev123', 'cms_user_id' => 20, 'phone_imei' => '957826f50488decf'],

            ['username' => 'emmanrs', 'password' => 'dev123', 'cms_user_id' => 21, 'phone_imei' => '957826f50488decf'],
            ['username' => 'emmanro', 'password' => 'dev123', 'cms_user_id' => 22, 'phone_imei' => '957826f50488decf'],
            ['username' => 'emmanarm', 'password' => 'dev123', 'cms_user_id' => 23, 'phone_imei' => '957826f50488decf'],
            ['username' => 'emmandh', 'password' => 'dev123', 'cms_user_id' => 24, 'phone_imei' => '957826f50488decf'],

            ['username' => 'jayrencc', 'password' => 'password123', 'cms_user_id' => 25,  'phone_imei' => '223456789123456'],
            ['username' => 'jayrench', 'password' => 'password123', 'cms_user_id' => 26,  'phone_imei' => '323456789123456'],
        ];
    }
}
