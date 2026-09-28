<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ContactRecording;
use Carbon\Carbon;

class ContactRecordingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accountIds = [1, 2, 3, 4];
        $now = Carbon::now();
        $prev = Carbon::today()->subDay(3);

        $records = [];

        foreach ($accountIds as $accountId) {
            $records[] = [
                'account_id'          => $accountId,
                'follow_up_mode'      => 'Field Visit',
                'reason_for_default'  => 'CALAMITY / ACTS OF NATURE RELATED',
                'action_taken'        => 'COLLECTED',
                'next_action_plan'    => 'COLLECTED',
                // 'assigned_support_cc' => '',
                'remarks'             => 'Follow-up completed successfully.',
                'geotagging'          => 'my address',
                'recorded_by'         => 1,
                'contact_date'        => $now,
                'next_action_date'    => $now->copy()->addDays(7),
                'created_at'           => $now->copy()->addDays(4),
                'updated_at'           => $now,
            ];
        }

        $accountIds = [5, 6, 7];
        foreach ($accountIds as $accountId) {
            $records[] = [
                'account_id'          => $accountId,
                'follow_up_mode'      => 'Field Visit',
                'reason_for_default'  => 'CALAMITY / ACTS OF NATURE RELATED',
                'action_taken'        => 'ATTEND BRGY HEARING',
                'next_action_plan'    => 'for repo',
                //'assigned_support_cc' => '',
                'remarks'             => 'Follow-up completed successfully.',
                'geotagging'          => 'my address',
                'recorded_by'         => 1,
                'contact_date'        => $now,
                'next_action_date'    => $now->copy()->addDays(7),
                'created_at'           => $now->copy()->addDays(4),
                'updated_at'           => $now,
            ];
        }

        ContactRecording::insert($records);


        ContactRecording::create([
            'account_id'          => 14,
            'follow_up_mode'      => 'Field Visit',
            'reason_for_default'  => 'CALAMITY / ACTS OF NATURE RELATED',
            'action_taken'        => 'ATTEND BRGY HEARING',
            'next_action_plan'    => 'For skiptrace',
            //'assigned_support_cc' => '',
            'remarks'             => 'Follow-up completed successfully.',
            'geotagging'          => 'my address',
            'recorded_by'         => 1,
            'contact_date'        => $now,
            'next_action_date'    => $prev,
            'created_at'           => $now->copy()->addDays(4),
            'updated_at'           => $now,
        ]);


        ContactRecording::create([
            'account_id'          => 14,
            'follow_up_mode'      => 'Field Visit',
            'reason_for_default'  => 'CALAMITY / ACTS OF NATURE RELATED',
            'action_taken'        => 'ATTEND BRGY HEARING',
            'next_action_plan'    => 'Promise to Pay',
            //'assigned_support_cc' => '',
            'remarks'             => 'Follow-up completed successfully.',
            'geotagging'          => 'my address',
            'recorded_by'         => 1,
            'contact_date'        => $now,
            'next_action_date'    => $now->copy()->addDays(7),
            'created_at'           => $now->copy()->addDays(4),
            'updated_at'           => $now,
        ]);

        ContactRecording::create([
            'account_id'          => 14,
            'follow_up_mode'      => 'Field Visit',
            'reason_for_default'  => 'CALAMITY / ACTS OF NATURE RELATED',
            'action_taken'        => 'ATTEND BRGY HEARING',
            'next_action_plan'    => 'For Repo',
            //'assigned_support_cc' => '',
            'remarks'             => 'Follow-up completed successfully.',
            'geotagging'          => 'my address',
            'recorded_by'         => 1,
            'contact_date'        => $now,
            'next_action_date'    => $now->copy()->addDays(7),
            'created_at'           => $now->copy()->addDays(4),
            'updated_at'           => $now,
        ]);

        ContactRecording::create([
            'account_id'          => 13,
            'follow_up_mode'      => 'Field Visit',
            'reason_for_default'  => 'CALAMITY / ACTS OF NATURE RELATED',
            'action_taken'        => 'ATTEND BRGY HEARING',
            'next_action_plan'    => 'For Legal / Small Claims',
            //'assigned_support_cc' => '',
            'remarks'             => 'Follow-up completed successfully.',
            'geotagging'          => 'my address',
            'recorded_by'         => 1,
            'contact_date'        => $now,
            'next_action_date'    => $now,
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);

        ContactRecording::create([
            'account_id'          => 9,
            'follow_up_mode'      => 'Field Visit',
            'reason_for_default'  => 'CALAMITY / ACTS OF NATURE RELATED',
            'action_taken'        => 'ATTEND BRGY HEARING',
            'next_action_plan'    => 'For skiptrace',
            //'assigned_support_cc' => '',
            'remarks'             => 'Follow-up completed successfully.',
            'geotagging'          => 'my address',
            'recorded_by'         => 1,
            'contact_date'        => $now,
            'next_action_date'    => $now->copy()->subDays(),
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);

        ContactRecording::create([
            'account_id'          => 9,
            'follow_up_mode'      => 'Field Visit',
            'reason_for_default'  => 'CALAMITY / ACTS OF NATURE RELATED',
            'action_taken'        => 'ATTEND BRGY HEARING',
            'next_action_plan'    => 'Promise To Pay',
            //'assigned_support_cc' => '',
            'remarks'             => 'Follow-up completed successfully.',
            'geotagging'          => 'my address',
            'recorded_by'         => 1,
            'contact_date'        => $now,
            'next_action_date'    => $now,
            'created_at'           => $prev,
            'updated_at'           => $now,
        ]);

        ContactRecording::create([
            'account_id'          => 8,
            'follow_up_mode'      => 'Field Visit',
            'reason_for_default'  => 'CALAMITY / ACTS OF NATURE RELATED',
            'action_taken'        => 'ATTEND BRGY HEARING',
            'next_action_plan'    => 'For skiptrace',
            //'assigned_support_cc' => '',
            'remarks'             => 'Follow-up completed successfully.',
            'geotagging'          => 'my address',
            'recorded_by'         => 1,
            'contact_date'        => $now,
            'next_action_date'    => $now->copy()->subDays(),
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);

        ContactRecording::create([
            'account_id'          => 8,
            'follow_up_mode'      => 'Field Visit',
            'reason_for_default'  => 'CALAMITY / ACTS OF NATURE RELATED',
            'action_taken'        => 'ATTEND BRGY HEARING',
            'next_action_plan'    => 'Promise To Pay',
            //'assigned_support_cc' => '',
            'remarks'             => 'Follow-up completed successfully.',
            'geotagging'          => 'my address',
            'recorded_by'         => 1,
            'contact_date'        => $now,
            'next_action_date'    => $now,
            'created_at'           => $prev,
            'updated_at'           => $now,
        ]);
    }
}
