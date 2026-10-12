<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ReasonForDefault;

class ReasonForDefaultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ReasonForDefault::create([
            'text' => 'ACCIDENT/SICKNESS',
        ]);

        ReasonForDefault::create([
            'text' => 'BORROWER HIDING',
        ]);

        ReasonForDefault::create([
            'text' => 'BUSINESS RELATED',
        ]);

        ReasonForDefault::create([
            'text' => 'CALAMITY / ACTS OF NATURE RELATED',
        ]);

        ReasonForDefault::create([
            'text' => 'DEATH OF THE BORROWER',
        ]);

        ReasonForDefault::create([
            'text' => 'DIFFERENT USER OF THE UNIT',
        ]);

        ReasonForDefault::create([
            'text' => 'DISPUTE',
        ]);

        ReasonForDefault::create([
            'text' => 'FRAUD RELATED',
        ]);

        ReasonForDefault::create([
            'text' => 'INCAPACITY DUE TO VICES/LIFESTYLE',
        ]);

        ReasonForDefault::create([
            'text' => 'JOB/SALARY RELATED',
        ]);

        ReasonForDefault::create([
            'text' => 'LEGAL RELATED - SUSPECT',
        ]);

        ReasonForDefault::create([
            'text' => 'MARITAL PROBLEM AND RELATED ISSUES',
        ]);

        ReasonForDefault::create([
            'text' => 'MISSING',
        ]);

        ReasonForDefault::create([
            'text' => 'MOVED OUT',
        ]);

        ReasonForDefault::create([
            'text' => 'REMITTANCE PROBLEM',
        ]);

        ReasonForDefault::create([
            'text' => 'COLLECTION ACTIVITY RESTRICTION',
        ]);

        ReasonForDefault::create([
            'text' => 'HOUSEHOLD EXPENSES',
        ]);

        ReasonForDefault::create([
            'text' => 'UNIT RELATED',
        ]);

        ReasonForDefault::create([
            'text' => 'UNLOCATED ADDRESS/SUBJECT',
        ]);
    }
}
