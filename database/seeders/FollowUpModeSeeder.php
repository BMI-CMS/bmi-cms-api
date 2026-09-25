<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FollowUpMode;

class FollowUpModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FollowUpMode::create([
            'text' => 'CALL',
        ]);

        FollowUpMode::create([
            'text' => 'FIELD VISIT',
        ]);

        FollowUpMode::create([
            'text' => 'SMS',
        ]);

        FollowUpMode::create([
            'text' => 'MESSENGER',
        ]);

        FollowUpMode::create([
            'text' => 'TIKTOK',
        ]);

        FollowUpMode::create([
            'text' => 'VIBER',
        ]);

        FollowUpMode::create([
            'text' => 'EMAIL',
        ]);
    }
}
