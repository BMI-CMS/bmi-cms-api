<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FecAccountVisit;

class FecAccountVisitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FecAccountVisit::create([
            'fec_accounts_id' => 1,
            'is_customer_available' => 1,
            'next_visit_date' => null,
            'remarks' => 'TEST REMARKS'
        ]);

        FecAccountVisit::create([
            'fec_accounts_id' => 2,
            'is_customer_available' => 1,
            'next_visit_date' =>  null,
            'remarks' => 'TEST REMARKS'
        ]);

        FecAccountVisit::create([
            'fec_accounts_id' => 3,
            'is_customer_available' => 1,
            'next_visit_date' =>  null,
            'remarks' => 'TEST REMARKS'
        ]);
    }
}
