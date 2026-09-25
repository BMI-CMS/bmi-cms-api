<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //BFC
        Role::create([
            'name' => 'Credit Counselors',
        ]);

        Role::create([
            'name' => 'Tele CC',
        ]);

        Role::create([
            'name' => 'Handling CC',
        ]);

        Role::create([
            'name' => 'Support CC',
        ]);

        Role::create([
            'name' => 'Remedial Officer',
        ]);

        Role::create([
            'name' => 'Cluster Heads',
        ]);

        Role::create([
            'name' => 'Senior CH',
        ]);

        Role::create([
            'name' => 'Area Manager',
        ]);

        Role::create([
            'name' => 'Admin',
        ]);

        Role::create([
            'name' => 'Regional Manager',
        ]);

        //RCD
        Role::create([
            'name' => 'Remedial Specialist',
        ]);

        Role::create([
            'name' => 'Area Remedial Manager',
        ]);

        Role::create([
            'name' => 'Department Head',
        ]);
    }
}
