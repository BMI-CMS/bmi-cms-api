<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CompanyFollowUpMode;

class CompanyFollowUpModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //BFC
        CompanyFollowUpMode::create([
            'company_id' => 1,
            'follow_up_mode_id' => 1,
            'is_available' => 1
        ]);

        CompanyFollowUpMode::create([
            'company_id' => 1,
            'follow_up_mode_id' => 2,
            'is_available' => 1
        ]);

        CompanyFollowUpMode::create([
            'company_id' => 1,
            'follow_up_mode_id' => 3,
            'is_available' => 1
        ]);

        CompanyFollowUpMode::create([
            'company_id' => 1,
            'follow_up_mode_id' => 4,
            'is_available' => 1
        ]);

        CompanyFollowUpMode::create([
            'company_id' => 1,
            'follow_up_mode_id' => 5,
            'is_available' => 1
        ]);

        CompanyFollowUpMode::create([
            'company_id' => 1,
            'follow_up_mode_id' => 6,
            'is_available' => 1
        ]);

        //BMI
        CompanyFollowUpMode::create([
            'company_id' => 2,
            'follow_up_mode_id' => 1,
            'is_available' => 1
        ]);

        CompanyFollowUpMode::create([
            'company_id' => 2,
            'follow_up_mode_id' => 2,
            'is_available' => 1
        ]);

        CompanyFollowUpMode::create([
            'company_id' => 2,
            'follow_up_mode_id' => 3,
            'is_available' => 1
        ]);

        CompanyFollowUpMode::create([
            'company_id' => 2,
            'follow_up_mode_id' => 4,
            'is_available' => 1
        ]);

        CompanyFollowUpMode::create([
            'company_id' => 2,
            'follow_up_mode_id' => 5,
            'is_available' => 1
        ]);

        CompanyFollowUpMode::create([
            'company_id' => 2,
            'follow_up_mode_id' => 7,
            'is_available' => 1
        ]);
    }
}
