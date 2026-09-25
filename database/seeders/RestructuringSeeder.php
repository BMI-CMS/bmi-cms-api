<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Restructuring;

class RestructuringSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Restructuring::create([
            'account_id' => 11,
            'new_monthly_amortization' => 120000,
        ]);
    }
}
