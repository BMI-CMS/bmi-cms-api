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
            'account_id' => 5,
            'contact_recording_id' => 5,
            'original_balance' => 56300.00,
            'restructured_balance' => 53000.00,
            'original_monthly_amortization' => 7100.00,
            'new_monthly_amortization' => 5200.00,
            'original_interest_rate' => 16.00,
            'new_interest_rate' => 12.50,
            'remarks' => 'Long-term restructuring approved due to prolonged delinquency.',
        ]);
    }
}
