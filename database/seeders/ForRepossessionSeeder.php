<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ForRepossession;

class ForRepossessionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ForRepossession::create([
            'account_id' => 5,
            'repossession_request_id' => 1,
            'stockyard' => '1004 MNC AROROY',
            'payment_status' => 'NO PAYMENT',
            'reason_for_unrepossessed' => '',
            'status_name' => 'Repossessed',
            'status' => true
        ]);

        ForRepossession::create([
            'account_id' => 6,
            'repossession_request_id' => 2,
            'stockyard' => '1004 MNC AROROY',
            'payment_status' => 'NO PAYMENT',
            'reason_for_unrepossessed' => '',
            'status_name' => 'Repossessed',
            'status' => true
        ]);

        ForRepossession::create([
            'account_id' => 7,
            'repossession_request_id' => 3,
            'stockyard' => '1004 MNC AROROY',
            'payment_status' => 'NO PAYMENT',
            'reason_for_unrepossessed' => '',
            'status_name' => 'Repossessed',
            'status' => true
        ]);
    }
}
