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
            'principal_outstanding' => 5200.00,
            'monthly_amortization' => 16.00,
            'terms' => 12.50,
            'remarks' => 'Long-term restructuring approved due to prolonged delinquency.',
        ]);
    }
}
