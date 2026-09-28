<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\NonStarterPayment;
use Carbon\Carbon;

class NonStarterPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        NonStarterPayment::create([
            'account_id' => 11,
            'contact_recording_id' => 12,
            'monthly_amortization' => 6000,
            'total_payment' => 3000,
            'shortfall_amount' => 3000
        ]);
    }
}
