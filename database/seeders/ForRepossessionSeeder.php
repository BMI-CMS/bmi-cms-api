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
    {   //approved
        ForRepossession::create([
            'account_id' => 6,
            'contact_recording_id' => 6,
            'repossession_request_id' => 1,
            'stockyard' => '1004 MNC AROROY',
            'payment_status' => 'NO PAYMENT',
            'reason_for_unrepossessed' => '',
            'status' => true
        ]);

        // //direct plan to for repo
        ForRepossession::create([
            'account_id' => 7,
            'contact_recording_id' => NULL,
            'repossession_request_id' => NULL,
            'stockyard' => '1002 MNC AROROY',
            'payment_status' => 'NO PAYMENT',
            'reason_for_unrepossessed' => '',
            'status' => true
        ]);
    }
}
