<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\NextActionPlan;

class NextActionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NextActionPlan::create([
            'text' => 'FOR REVISIT/CALL',
        ]);

        NextActionPlan::create([
            'text' => 'FOR COLLECTION',
        ]);

        NextActionPlan::create([
            'text' => 'FOR SUPPORT CC VISIT',
        ]);

        NextActionPlan::create([
            'text' => "FOR SERVICE OF COLLECTION NOTICE/ATTY'S LETTER",
        ]);

        NextActionPlan::create([
            'text' => 'FOR RESTRUCTURING',
        ]);

        NextActionPlan::create([
            'text' => 'FOR ASSUMPTION OF UNIT',
        ]);

        NextActionPlan::create([
            'text' => 'FOR FILING BRGY BLOTTER/COMPLAIN',
        ]);

        NextActionPlan::create([
            'text' => 'TO ATTEND BARANGAY HEARING',
        ]);

        NextActionPlan::create([
            'text' => 'FOR DISCREPANCY ADJUSTMENT',
        ]);

        NextActionPlan::create([
            'text' => 'FOR INSURANCE CLAIM',
        ]);

        NextActionPlan::create([
            'text' => 'FOR SKIPTRACE',
        ]);

        NextActionPlan::create([
            'text' => 'FOR LEGAL/SMALL CLAIMS',
        ]);

        NextActionPlan::create([
            'text' => 'FOR EXTERNAL AGENCY',
        ]);

        NextActionPlan::create([
            'text' => 'FOR REPO',
        ]);

        NextActionPlan::create([
            'text' => 'FOR ENDORSEMENT TO RCD',
        ]);

        NextActionPlan::create([
            'text' => 'FOR CH VALIDATION',
        ]);

        NextActionPlan::create([
            'text' => 'FOR FIELD VISIT',
        ]);

        NextActionPlan::create([
            'text' => 'FOR SERVICE OF COLLECTION NOTICE',
        ]);

        NextActionPlan::create([
            'text' => 'FOR ASSUMPTION OF UNIT',
        ]);

        NextActionPlan::create([
            'text' => 'FOR WRITE OFF(MINIMAL BALANCE)',
        ]);
    }
}
