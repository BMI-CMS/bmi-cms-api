<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RepossessionRequest;
use Carbon\Carbon;

class RepossessionRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        RepossessionRequest::create([
            'contact_recording_id' => 5,
            'is_first_level_approved' => true,
            'first_level_approved_by' => 6,
            'first_level_review_at' => $now,
            'first_level_approved_remarks' => 'test',
            'is_second_level_approved' => true,
            'second_level_approved_by' => 7,
            'second_level_review_at' =>  $now,
            'second_level_approved_remarks' => 'test',
            'is_third_level_approved' => true,
            'third_level_approved_by' => 8,
            'third_level_review_at' =>  $now,
            'third_level_approved_remarks' => 'test',
        ]);

        RepossessionRequest::create([
            'contact_recording_id' => 6,
            'is_first_level_approved' => true,
            'first_level_approved_by' => 6,
            'first_level_review_at' => $now,
            'first_level_approved_remarks' => 'test',
            'is_second_level_approved' => true,
            'second_level_approved_by' => 7,
            'second_level_review_at' =>  $now,
            'second_level_approved_remarks' => 'test',
            'is_third_level_approved' => true,
            'third_level_approved_by' => 8,
            'third_level_review_at' =>  $now,
            'third_level_approved_remarks' => 'test',
        ]);

        RepossessionRequest::create([
            'contact_recording_id' => 7,
            'is_first_level_approved' => true,
            'first_level_approved_by' => 6,
            'first_level_review_at' => $now,
            'first_level_approved_remarks' => 'test',
            'is_second_level_approved' => true,
            'second_level_approved_by' => 7,
            'second_level_review_at' =>  $now,
            'second_level_approved_remarks' => 'test',
            'is_third_level_approved' => true,
            'third_level_approved_by' => 8,
            'third_level_review_at' =>  $now,
            'third_level_approved_remarks' => 'test',
        ]);
    }
}
