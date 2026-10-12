<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\PromiseToPay;

class PromiseToPaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now()->addDays(7);;

        PromiseToPay::create([
            'account_id' => 14,
            'contact_recording_id' => 9,
            'ptp_amount' => 12000,
            'ptp_date' => $now,
            'remarks' => 'HAHAHAHAHAHAHHAHAHAHAHAHHAHAHAH'
        ]);
    }
}
