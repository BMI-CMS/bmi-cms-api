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
            'company_id' => 1,
            'name' => 'Credit Counselors',
        ]);

        Role::create([
            'company_id' => 1,
            'name' => 'Tele CC',
        ]);

        Role::create([
            'company_id' => 1,
            'name' => 'Handling CC',
        ]);

        Role::create([
            'company_id' => 1,
            'name' => 'Support CC',
        ]);

        Role::create([
            'company_id' => 1,
            'name' => 'Remedial Officer',
        ]);

        Role::create([
            'company_id' => 1,
            'name' => 'Cluster Heads',
        ]);

        Role::create([
            'company_id' => 1,
            'name' => 'Senior Cluster Head',
        ]);

        Role::create([
            'company_id' => 1,
            'name' => 'Area Manager',
        ]);

        Role::create([
            'company_id' => 1,
            'name' => 'Regional Manager',
        ]);

        Role::create([
            'company_id' => 1,
            'name' => 'Group Head',
        ]);

        Role::create([
            'company_id' => 1,
            'name' => 'Admin',
        ]);

        //RCD
        Role::create([
            'company_id' => 2,
            'name' => 'Remedial Specialist',
        ]);

        Role::create([
            'company_id' => 2,
            'name' => 'Remedial Officer',
        ]);

        Role::create([
            'company_id' => 2,
            'name' => 'Area Remedial Manager',
        ]);

        Role::create([
            'company_id' => 2,
            'name' => 'Department Head',
        ]);
    }
}
