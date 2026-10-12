<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DepositEncoding;
use Carbon\Carbon;

class DepositEncodingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        //FIRST COLLECTION DATA
        DepositEncoding::create([
            'collection_id' => 1,
            'payment_channel' => 'BDO',
            'depository_account' => 'BDO 374',
            'transaction_reference_number' => 'AR-1111111111',
            'deposit_date' => $now->copy()->subDays(),
            'total_deposit_amount' => 9750.5,
            'deposit_charge' => 50.5,
            'deposit_amount' => 9700,
            'deposit_slip' => 'images',
            'status' => 'UNDEPOSITED',
            'remarks' => 'TEST'
        ]);

        DepositEncoding::create([
            'collection_id' => 2,
            'payment_channel' => 'BDO',
            'depository_account' => 'BDO 374',
            'transaction_reference_number' => 'AR-2222222222222',
            'deposit_date' => $now->copy()->subDays(),
            'total_deposit_amount' => 3602.25,
            'deposit_charge' => 2.25,
            'deposit_amount' => 3600,
            'deposit_slip' => 'images',
            'status' => 'AUTHORIZED',
            'remarks' => 'TEST'
        ]);
    }
}
