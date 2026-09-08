<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'username' => 'jayren',
            'password' => Hash::make('password123'),
            'name' => 'Jayren',
            'user_level' => 1,
            'phone_imei' => '123456789123456'
        ]);

        User::create([
            'username' => 'emman',
            'password' => Hash::make('password123'),
            'name' => 'emman',
            'user_level' => 1,
            'phone_imei' => '123456789123456'
        ]);
    }
}
