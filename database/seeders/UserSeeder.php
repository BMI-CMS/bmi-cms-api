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


        User::create([
            'username' => 'chinkeetan',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '123456789123456'
        ]);

        User::create([
            'username' => 'henrysy',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '123456789123457'
        ]);

        User::create([
            'username' => 'mikemorales',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '123456789123458'
        ]);

        User::create([
            'username' => 'ayala',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '123456789123459'
        ]);

        User::create([
            'username' => 'edsa',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '123456789123450'
        ]);

        User::create([
            'username' => 'catherine',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '123456789123456'
        ]);

        User::create([
            'username' => 'pedrogil',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '223456789123456'
        ]);

        User::create([
            'username' => 'baclaran',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '323456789123456'
        ]);

        User::create([
            'username' => 'shaw',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '423456789123456'
        ]);

        User::create([
            'username' => 'boni',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '523456789123456'
        ]);

        User::create([
            'username' => 'asia',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '623456789123456'
        ]);

        User::create([
            'username' => 'libertad',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '723456789123456'
        ]);

        User::create([
            'username' => 'ortigas',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '823456789123456'
        ]);

        User::create([
            'username' => 'gilpuyat',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '923456789123456'
        ]);

        User::create([
            'username' => 'taftavenue',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '133456789123456'
        ]);

        User::create([
            'username' => 'miaroad',
            'password' => Hash::make('password123'),
            'cms_user_id' => 1,
            'phone_imei' => '143456789123456'
        ]);
    }
}
