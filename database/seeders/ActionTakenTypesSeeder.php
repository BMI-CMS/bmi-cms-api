<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ActionTakenType;

class ActionTakenTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ActionTakenType::create([
            'text' => 'FOLLOW UP PAYMENT',
        ]);

        ActionTakenType::create([
            'text' => 'SERVED COLLECTION NOTICE',
        ]);

        ActionTakenType::create([
            'text' => 'OFFER RESTRUCTURING',
        ]);

        ActionTakenType::create([
            'text' => 'FILED BRGY BLOTTER/COMPLAIN',
        ]);

        ActionTakenType::create([
            'text' => 'ATTEND BRGY HEARING',
        ]);

        ActionTakenType::create([
            'text' => 'SKIPTRACE',
        ]);

        ActionTakenType::create([
            'text' => 'PROCESS DISCREPANCY ADJUSTMENT',
        ]);

        ActionTakenType::create([
            'text' => 'PROCESS INSURANCE CLAIM',
        ]);

        ActionTakenType::create([
            'text' => 'REPOSSESSED',
        ]);

        ActionTakenType::create([
            'text' => 'ENDORSED TO RCD',
        ]);

        ActionTakenType::create([
            'text' => 'ENDORSED TO LEGAL',
        ]);

        ActionTakenType::create([
            'text' => 'ENDORSED TO EXTERNAL AGENCY',
        ]);

        ActionTakenType::create([
            'text' => 'COLLECTED',
        ]);

        ActionTakenType::create([
            'text' => 'FOR WRITE OFF(MINIMAL BALANCE)',
        ]);
    }
}
